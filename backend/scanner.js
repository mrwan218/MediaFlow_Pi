const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const mysql = require('mysql2/promise');
const axios = require('axios');

// --- Configuration ---
const CONFIG_PATH = path.join(__dirname, 'config.json');
const DB_CONFIG_PATH = path.join(__dirname, 'db_config.json');
const TMDB_BASE_URL = 'https://api.themoviedb.org/3';
let TMDB_API_KEY = null;

async function scan() {
    console.log("Starting MediaFlow Scanner (Docker Mode)...");
    
    // 1. Load Configs
    if (!fs.existsSync(CONFIG_PATH) || !fs.existsSync(DB_CONFIG_PATH)) {
        console.error("Configuration files missing.");
        return;
    }

    const config = JSON.parse(fs.readFileSync(CONFIG_PATH, 'utf8'));
    const dbConfig = JSON.parse(fs.readFileSync(DB_CONFIG_PATH, 'utf8'));
    TMDB_API_KEY = config.tmdb_api_key;

    // 2. Connect to Database
    let connection;
    try {
        console.log(`Connecting to database at ${dbConfig.host}...`);
        connection = await mysql.createConnection(dbConfig);
    } catch (err) {
        console.error("Database connection failed:", err.message);
        return;
    }

    for (const library of config.libraries) {
        const libraryPath = resolveLibraryPath(library.path);
        console.log(`Scanning library: ${library.name} at ${libraryPath}`);
        if (!libraryPath || !fs.existsSync(libraryPath)) {
            console.warn(`Path does not exist: ${libraryPath || library.path}`);
            continue;
        }

        const files = getAllFiles(libraryPath);
        console.log(`Found ${files.length} files in ${library.name}`);

        for (const filePath of files) {
            if (!isVideoFile(filePath)) continue;

            const fileName = path.basename(filePath);
            const relativePath = path.relative(libraryPath, filePath);
            const fileHash = crypto.createHash('md5').update(filePath).digest('hex');

            // Check if already in DB
            const [rows] = await connection.execute(
                'SELECT id FROM media_items WHERE file_hash = ?',
                [fileHash]
            );

            if (rows.length === 0) {
                console.log(`New file: ${fileName}`);
                const metadata = await fetchMetadata(fileName);
                
                await connection.execute(
                    'INSERT INTO media_items (file_path, relative_path, file_hash, library_name, title, year, overview, poster_path, backdrop_path, rating) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        filePath,
                        relativePath,
                        fileHash,
                        library.name,
                        metadata.title || fileName,
                        metadata.year || null,
                        metadata.overview || 'No overview available.',
                        metadata.poster_path || null,
                        metadata.backdrop_path || null,
                        metadata.rating || 'R'
                    ]
                );
            }
        }
    }

    await connection.end();
    console.log("Scan complete.");
}

function normalizeLibraryPath(libPath) {
    if (/^[a-zA-Z]:[\\/]/.test(libPath)) {
        const drive = libPath[0].toLowerCase();
        const rest = libPath.slice(2).replace(/\\/g, '/');
        return `/${drive}/${rest}`;
    }
    return libPath;
}

function findDirectoryByName(root, name, maxDepth = 4, currentDepth = 0) {
    if (currentDepth > maxDepth) {
        return null;
    }

    let entries;
    try {
        entries = fs.readdirSync(root, { withFileTypes: true });
    } catch (err) {
        return null;
    }

    for (const entry of entries) {
        if (!entry.isDirectory()) {
            continue;
        }

        const fullPath = path.join(root, entry.name);
        if (entry.name.toLowerCase() === name.toLowerCase()) {
            return fullPath;
        }

        const nested = findDirectoryByName(fullPath, name, maxDepth, currentDepth + 1);
        if (nested) {
            return nested;
        }
    }

    return null;
}

function resolveLibraryPath(libPath) {
    if (!libPath || typeof libPath !== 'string') {
        return null;
    }

    if (fs.existsSync(libPath)) {
        return libPath;
    }

    const normalized = normalizeLibraryPath(libPath);
    if (fs.existsSync(normalized)) {
        return normalized;
    }

    const folderName = path.basename(libPath.replace(/\\/g, '/'));
    const searchRoots = ['/c', '/d', '/e', '/f', '/g', '/h'];
    for (const root of searchRoots) {
        if (!fs.existsSync(root)) {
            continue;
        }
        const found = findDirectoryByName(root, folderName);
        if (found) {
            return found;
        }
    }

    return null;
}

function getAllFiles(dirPath, arrayOfFiles = []) {
    const files = fs.readdirSync(dirPath);
    files.forEach(function(file) {
        const fullPath = path.join(dirPath, file);
        if (fs.statSync(fullPath).isDirectory()) {
            arrayOfFiles = getAllFiles(fullPath, arrayOfFiles);
        } else {
            arrayOfFiles.push(fullPath);
        }
    });
    return arrayOfFiles;
}

function isVideoFile(filePath) {
    const videoExtensions = ['.mp4', '.mkv', '.avi', '.mov', '.wmv'];
    return videoExtensions.includes(path.extname(filePath).toLowerCase());
}

async function fetchMetadata(fileName) {
    // Clean file name for search
    let query = fileName.replace(/\.[^/.]+$/, "").replace(/[\.\_\-]/g, " ");
    query = query.replace(/(1080p|720p|webrip|x264|bluray|h264|aac|dts|web-dl|brrip|repack|proper)/gi, "").trim();

    try {
        const response = await axios.get(`${TMDB_BASE_URL}/search/movie`, {
            params: {
                api_key: TMDB_API_KEY,
                query: query
            }
        });

        if (response.data.results && response.data.results.length > 0) {
            const result = response.data.results[0];
            
            // Get detailed info for rating
            const details = await axios.get(`${TMDB_BASE_URL}/movie/${result.id}/release_dates`, {
                params: { api_key: TMDB_API_KEY }
            });

            let rating = 'R'; // Default
            const usRelease = details.data.results.find(r => r.iso_3166_1 === 'US');
            if (usRelease && usRelease.release_dates.length > 0) {
                rating = usRelease.release_dates[0].certification || 'R';
            }

            return {
                title: result.title,
                year: result.release_date ? result.release_date.split('-')[0] : null,
                overview: result.overview,
                poster_path: result.poster_path,
                backdrop_path: result.backdrop_path,
                rating: rating
            };
        }
    } catch (error) {
        console.error(`Error fetching metadata for ${fileName}:`, error.message);
    }

    return {};
}

scan().catch(console.error);
