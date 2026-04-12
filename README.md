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

## Usage

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