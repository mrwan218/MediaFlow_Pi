# MediaFlow

A self-hosted media streaming solution built with PHP, MySQL, and Node.js.

## Prerequisites

- Docker and Docker Compose
- TMDB API Key (get one from https://www.themoviedb.org/settings/api)

## Quick Start

1. Clone or extract the project files
2. Update the TMDB API key in `.env` file
3. Run the application:

```bash
docker-compose up -d
```

4. Access the application at http://localhost:8080

## Services

- **PHP App**: Main web interface (port 8080)
- **MySQL**: Database server (port 3306)
- **Node.js Scanner**: Background media scanner

## Configuration

### Database
The database is automatically initialized with the schema from `schema.sql`.

### Media Libraries
Edit `backend/config.json` to configure your media library paths:

```json
{
    "libraries": [
        {
            "name": "Movies",
            "path": "/path/to/movies",
            "public": true
        }
    ]
}
```

### Environment Variables
Configure database and API settings in `.env`:

```
DB_SERVER=mysql
DB_USERNAME=mediaflow_user
DB_PASSWORD=change_this_password
DB_NAME=mediaflow_db
TMDB_API_KEY=your_tmdb_api_key_here
```

## Deployment and Environment Handling

### Required Environment Variables
The application requires the following environment variables to be set:

- `DB_SERVER`: Database host (default: mysql)
- `DB_USERNAME`: Database user (default: mediaflow_user)
- `DB_PASSWORD`: Database password (required, no default)
- `DB_NAME`: Database name (default: mediaflow_db)
- `TMDB_API_KEY`: TMDB API key (required, no default)

### Setup Steps
1. Copy `.env.example` to `.env`:
   ```bash
   cp .env.example .env
   ```

2. Edit `.env` with your actual values:
   - Get a TMDB API key from https://www.themoviedb.org/settings/api
   - Set a secure database password
   - Adjust database settings if needed

3. Ensure all required variables are set. The app will fail to start with clear error messages if any are missing.

### Troubleshooting Environment Issues
- **Database connection fails**: Check `DB_*` variables in `.env`
- **TMDB API errors**: Verify `TMDB_API_KEY` is valid
- **Scanner can't find files**: Ensure library paths in `backend/config.json` are accessible in the container
- **Permission errors**: Check Docker volume mounts and file permissions

### Production Deployment
For production:
- Use strong, unique passwords
- Set `DB_SERVER` to your production database host
- Ensure `.env` is not committed to version control (it's in `.gitignore`)
- Use Docker secrets or external secret management for sensitive data

1. Register a new account or login
2. Configure media libraries (admin only)
3. Run the scanner to index media files
4. Browse and stream your media

## Development

To run in development mode:

```bash
docker-compose up
```

This will show logs from all services.

## Admin Features

- User management
- Library permissions
- System configuration

## API

The application uses The Movie Database (TMDB) API for media metadata.