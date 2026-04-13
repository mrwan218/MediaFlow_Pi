# MediaFlow: Raspberry Pi Deployment Guide

This guide details how to deploy MediaFlow on a Raspberry Pi (running Raspberry Pi OS / Debian) for up to 100 local users.

## 1. System Requirements
- Raspberry Pi 4 (4GB+) or Pi 5 recommended for best performance.
- Raspberry Pi OS (64-bit recommended).
- External storage (USB 3.0 HDD/SSD) for media files.

## 2. Install Dependencies
Run the following commands on your Pi:

```bash
sudo apt update
sudo apt install docker.io docker-compose git
sudo systemctl enable docker
sudo systemctl start docker
```

## 3. Environment Configuration
Before deploying, configure your environment variables:

1. Copy the example environment file:
   ```bash
   cp .env.example .env
   ```

2. Edit `.env` with your settings:
   ```bash
   nano .env
   ```

   Required variables:
   - `DB_PASSWORD`: Set a strong password for the database
   - `TMDB_API_KEY`: Get from https://www.themoviedb.org/settings/api

3. For Raspberry Pi deployment, ensure paths in `backend/config.json` point to mounted external storage.

## 4. Deploy the Application
```bash
git clone https://github.com/mrwan218/MediaFlow_Pi.git
cd MediaFlow_Pi
docker-compose up -d
```

## 5. Access the Application
- Web interface: http://your-pi-ip:8080
- Database: localhost:3306 (from host)

## 6. Troubleshooting Environment Issues
- **Database won't start**: Check if port 3306 is available
- **Scanner fails**: Verify media paths are accessible in Docker
- **API errors**: Confirm TMDB key is valid and network is available
- **Performance issues**: Ensure adequate RAM and CPU resources

## 7. Maintenance
- Monitor logs: `docker-compose logs -f`
- Update: `git pull && docker-compose up -d`
- Backup database: Use MySQL dump tools
