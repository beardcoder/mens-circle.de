# FrankenPHP Migration Notes

**Date:** 2025-11-12
**Status:** Completed

## Changes

- Replaced PHP-FPM + Nginx + Supervisor with FrankenPHP
- Base image: `dunglas/frankenphp:1-php8.4-alpine`
- Webserver: Caddy (integrated in FrankenPHP)
- Mode: Classical (no worker mode)

## Coolify Deployment

1. Update environment variables (already compatible)
2. Port mapping: 80 (Traefik handles HTTPS)
3. Health check: `/typo3/login` (HTTP 200/302)
4. Volumes: No changes needed

## Rollback

If issues occur:
1. Revert to commit: `git tag pre-frankenphp-migration`
2. Rebuild with old Dockerfile: `Dockerfile.php-fpm.backup`

## Performance

- Expected: 10-15% faster response times
- Memory: ~20-30MB less per container
- Image size: ~70MB smaller
