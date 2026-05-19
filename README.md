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

## Default Admin Account
A default administrator account is seeded during database initialization.

- Username: `admin`
- Email: `admin@example.com`
- Password: `Admin@123`

> For security, change the default admin password immediately after first login.

## Services

- **PHP App**: Main web interface (port 8080)
- **MySQL**: Database server (port 3306)
- **Node.js Scanner**: Background media scanner

## Configuration

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

### Default Database Credentials
Use these defaults for local Docker setup:

- `DB_SERVER=mysql`
- `DB_USERNAME=mediaflow_user`
- `DB_PASSWORD=change_this_password`
- `DB_NAME=mediaflow_db`
- `MYSQL_ROOT_PASSWORD=rootpassword`

> Change these values before deploying to production.

### Environment Variables
Copy `.env.example` to `.env` and update the values as needed:

```bash
cp .env.example .env
```

```dotenv
DB_SERVER=mysql
DB_USERNAME=mediaflow_user
DB_PASSWORD=change_this_password
DB_NAME=mediaflow_db
TMDB_API_KEY=your_tmdb_api_key_here
```

### Deployment Notes
- `TMDB_API_KEY` must be obtained from https://www.themoviedb.org/settings/api
- Do not commit `.env` to version control
- For production, use secure passwords and consider Docker secrets

### Troubleshooting
- **Database connection fails**: Verify `DB_SERVER`, `DB_USERNAME`, `DB_PASSWORD`, and `DB_NAME`
- **TMDB API errors**: Check that `TMDB_API_KEY` is valid
- **Scanner issues**: Ensure library paths in `backend/config.json` are accessible in the container
- **Permission errors**: Check Docker volume mounts and file permissions

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