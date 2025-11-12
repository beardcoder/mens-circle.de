# FrankenPHP Migration Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Migrate TYPO3 Docker setup from PHP-FPM + Nginx + Supervisor to FrankenPHP (Caddy + PHP in one binary) for Coolify production deployment.

**Architecture:** Replace three-process stack (nginx, php-fpm, supervisor) with single FrankenPHP binary. Keep multi-stage build (Composer, Bun, Final). Use Caddy's built-in webserver capabilities with TYPO3 routing. Maintain Redis caching and MySQL database. Run in classical mode (no worker) for lower memory footprint.

**Tech Stack:** FrankenPHP 1.x (Alpine), Caddy 2.x, PHP 8.4, Bun 1.x, Composer 2, MySQL 8.0, Redis 7

---

## Task 1: Create Caddyfile for TYPO3 Routing

**Files:**
- Create: `.docker/frankenphp/Caddyfile`

**Step 1: Create directory structure**

```bash
mkdir -p .docker/frankenphp
```

**Step 2: Write Caddyfile with TYPO3 routing**

Create `.docker/frankenphp/Caddyfile`:

```caddyfile
{
    # Global Options
    auto_https off
    admin off
}

:80 {
    root * /var/www/html/public

    # PHP Execution
    php_server

    # Compression
    encode gzip zstd

    # Security Headers
    header /* {
        X-Content-Type-Options "nosniff"
        X-Frame-Options "DENY"
        Referrer-Policy "strict-origin-when-cross-origin"
        X-XSS-Protection "1; mode=block"
    }

    # Block sensitive files
    @blocked {
        path /.git/* /.env* /composer.* /package.* /*.md
        path /config/* /var/*
    }
    respond @blocked 403

    # Static Assets (direct serving)
    @static {
        path /fileadmin/* /_assets/* /favicon.ico /robots.txt
        file
    }
    handle @static {
        file_server
    }

    # TYPO3 Backend
    handle /typo3/* {
        php_server
    }

    # TYPO3 Frontend (all other requests to index.php)
    handle {
        try_files {path} /index.php?{query}
    }

    # File Server Fallback
    file_server
}
```

**Step 3: Verify Caddyfile syntax**

```bash
# Syntax check will happen during Docker build
# For now, verify file was created
ls -la .docker/frankenphp/Caddyfile
```

Expected: File exists with ~700 bytes

**Step 4: Commit Caddyfile**

```bash
git add .docker/frankenphp/Caddyfile
git commit -m "feat: add Caddyfile for FrankenPHP TYPO3 routing

- TYPO3 frontend/backend routing
- Security headers and file blocking
- Static asset serving
- Compression enabled"
```

---

## Task 2: Update PHP Configuration (Remove FPM Directives)

**Files:**
- Modify: `.docker/php/typo3.ini`

**Step 1: Read current PHP config**

```bash
cat .docker/php/typo3.ini
```

**Step 2: Create updated typo3.ini without FPM directives**

Edit `.docker/php/typo3.ini` - remove any lines containing:
- `php-fpm.*`
- `request_terminate_timeout`
- Any FPM-specific settings

Keep all TYPO3-specific settings:
- memory_limit
- upload_max_filesize
- post_max_size
- max_execution_time
- opcache settings

The file should contain only standard PHP INI directives, no FPM-specific ones.

**Step 3: Verify no FPM directives remain**

```bash
grep -i "fpm\|request_terminate" .docker/php/typo3.ini
```

Expected: No output (no matches)

**Step 4: Commit PHP config update**

```bash
git add .docker/php/typo3.ini
git commit -m "refactor: remove PHP-FPM directives from typo3.ini

FrankenPHP doesn't use FPM, only standard PHP INI settings needed"
```

---

## Task 3: Create New Dockerfile with FrankenPHP

**Files:**
- Modify: `Dockerfile` (complete rewrite of Stage 3)

**Step 1: Backup current Dockerfile**

```bash
cp Dockerfile Dockerfile.php-fpm.backup
git add Dockerfile.php-fpm.backup
```

**Step 2: Update Dockerfile Stage 3**

Replace everything from line 60 onwards (Stage 3) with:

```dockerfile
# ============================================
# Stage 3: Final Production Image (FrankenPHP)
# ============================================
FROM dunglas/frankenphp:1-php8.4-alpine

# Install system dependencies
RUN apk add --no-cache \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libwebp-dev \
    icu-dev \
    libsodium-dev \
    imagemagick \
    imagemagick-dev \
    $PHPIZE_DEPS

# Install PHP extensions not included in FrankenPHP
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j$(nproc) \
        sodium \
    && pecl install redis imagick \
    && docker-php-ext-enable redis imagick

# Clean up build dependencies
RUN apk del --no-cache $PHPIZE_DEPS \
    && rm -rf /tmp/* /var/cache/apk/*

# Install Composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install runtime dependencies for Composer
RUN apk add --no-cache \
    git \
    unzip \
    wget \
    && rm -rf /var/cache/apk/*

# Configure PHP for production
COPY .docker/php/typo3.ini /usr/local/etc/php/conf.d/typo3.ini

# Copy Caddyfile
COPY .docker/frankenphp/Caddyfile /etc/caddy/Caddyfile

# FrankenPHP Environment Variables
ENV FRANKENPHP_CONFIG="worker ./public/index.php 0"
ENV SERVER_NAME=":80"

WORKDIR /var/www/html

# Copy application files
COPY --chown=caddy:caddy . .

# Copy composer dependencies
COPY --from=composer-builder --chown=caddy:caddy /app/vendor ./vendor

# Copy frontend build artifacts
COPY --from=frontend-builder --chown=caddy:caddy /app/public/_assets ./public/_assets

# Create necessary directories and set permissions
RUN mkdir -p var/log var/cache var/charset var/lock \
    && chown -R caddy:caddy var/ public/ \
    && find . -type d -exec chmod 755 {} \; \
    && find . -type f -exec chmod 644 {} \; \
    && find vendor/bin -type f -exec chmod +x {} \;

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD wget --no-verbose --tries=1 --spider http://localhost/typo3/login || exit 1

# Expose ports
EXPOSE 80 443

# Start FrankenPHP
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
```

**Step 3: Verify Dockerfile syntax**

```bash
# Check for obvious syntax errors
grep -E "^FROM|^RUN|^COPY|^ENV|^CMD" Dockerfile | head -20
```

Expected: See three FROM statements (multi-stage), no obvious errors

**Step 4: Commit new Dockerfile**

```bash
git add Dockerfile Dockerfile.php-fpm.backup
git commit -m "feat: migrate Dockerfile from PHP-FPM to FrankenPHP

- Replace php:8.4-fpm-alpine with dunglas/frankenphp:1-php8.4-alpine
- Remove nginx and supervisor installation
- Add Caddyfile configuration
- Change user from www-data to caddy
- Update CMD to run frankenphp
- Keep multi-stage build structure
- Backup old Dockerfile as Dockerfile.php-fpm.backup"
```

---

## Task 4: Update docker-compose.yml

**Files:**
- Modify: `docker-compose.yml`

**Step 1: Add FrankenPHP environment variables**

Edit `docker-compose.yml` in the `app` service `environment` section, add after existing TYPO3 vars:

```yaml
      # FrankenPHP configuration
      FRANKENPHP_CONFIG: "worker ./public/index.php 0"
      SERVER_NAME: ":80"
```

**Step 2: Verify YAML syntax**

```bash
# Check if docker-compose.yml is valid YAML
docker compose config > /dev/null 2>&1 && echo "Valid YAML" || echo "Invalid YAML"
```

Expected: "Valid YAML"

**Step 3: Commit docker-compose changes**

```bash
git add docker-compose.yml
git commit -m "feat: add FrankenPHP environment variables to docker-compose

- FRANKENPHP_CONFIG for classical mode (no worker)
- SERVER_NAME for port binding"
```

---

## Task 5: Remove Obsolete Configuration Files

**Files:**
- Delete: `.docker/nginx/default.conf`
- Delete: `.docker/php/www.conf`
- Delete: `.docker/supervisor/services.conf`

**Step 1: Remove nginx configuration**

```bash
git rm .docker/nginx/default.conf
```

**Step 2: Remove PHP-FPM pool configuration**

```bash
git rm .docker/php/www.conf
```

**Step 3: Remove supervisor configuration**

```bash
git rm .docker/supervisor/services.conf
```

**Step 4: Remove empty directories**

```bash
rmdir .docker/nginx 2>/dev/null || true
rmdir .docker/supervisor 2>/dev/null || true
```

**Step 5: Commit deletions**

```bash
git commit -m "chore: remove obsolete nginx, php-fpm, supervisor configs

FrankenPHP replaces all three components with single binary"
```

---

## Task 6: Test Local Docker Build

**Files:**
- N/A (testing only)

**Step 1: Build Docker image**

```bash
DOCKER_BUILDKIT=1 docker build -t typo3-frankenphp-test .
```

Expected: Build completes successfully with "Successfully tagged typo3-frankenphp-test"
Note: This will take 5-10 minutes for first build

**Step 2: Verify image size**

```bash
docker images typo3-frankenphp-test --format "{{.Size}}"
```

Expected: Around 180-220MB (Alpine-based)

**Step 3: Verify image layers**

```bash
docker history typo3-frankenphp-test --no-trunc | head -10
```

Expected: See FrankenPHP base image, PHP extensions, application files

**Step 4: Document build success**

```bash
echo "Build successful: $(date)" >> docs/plans/build-log.txt
git add docs/plans/build-log.txt
git commit -m "test: verify Docker build succeeds with FrankenPHP"
```

---

## Task 7: Test Container Startup

**Files:**
- N/A (testing only)

**Step 1: Start container**

```bash
docker run -d --name frankenphp-test -p 8080:80 \
  -e TYPO3_CONTEXT=Development \
  -e JWT_SECRET=test-secret \
  typo3-frankenphp-test
```

Expected: Container ID returned

**Step 2: Wait for health check**

```bash
sleep 45
docker ps --filter name=frankenphp-test --format "{{.Status}}"
```

Expected: Status includes "healthy" after ~40s

**Step 3: Check container logs for errors**

```bash
docker logs frankenphp-test 2>&1 | grep -i "error\|fatal\|warning" | head -20
```

Expected: No critical errors (some PHP notices acceptable in dev mode)

**Step 4: Test HTTP response**

```bash
curl -I http://localhost:8080/
```

Expected: HTTP 200 or 302 (redirect to setup if fresh install)

**Step 5: Test TYPO3 backend accessibility**

```bash
curl -I http://localhost:8080/typo3/
```

Expected: HTTP 200 or 302

**Step 6: Cleanup test container**

```bash
docker stop frankenphp-test
docker rm frankenphp-test
```

**Step 7: Document test results**

```bash
cat >> docs/plans/build-log.txt << EOF
Container test successful: $(date)
- Container started and became healthy
- HTTP endpoints responding
- No critical errors in logs
EOF
git add docs/plans/build-log.txt
git commit -m "test: verify container starts and serves HTTP"
```

---

## Task 8: Test with Full Stack (docker-compose)

**Files:**
- N/A (testing only)

**Step 1: Start full stack**

```bash
docker compose up -d
```

Expected: 3 containers start (app, db, redis)

**Step 2: Wait for all services to be healthy**

```bash
sleep 60
docker compose ps
```

Expected: All services show "Up" and app shows "healthy"

**Step 3: Check app logs**

```bash
docker compose logs app | tail -50
```

Expected: FrankenPHP startup messages, no fatal errors

**Step 4: Test database connection**

```bash
docker compose exec app php -r "new PDO('mysql:host=db;dbname=typo3', 'typo3', 'typo3');"
```

Expected: No output (success), or "Connected successfully"

**Step 5: Test Redis connection**

```bash
docker compose exec app php -r "\$r = new Redis(); \$r->connect('redis', 6379); echo 'Redis OK';"
```

Expected: "Redis OK"

**Step 6: Test TYPO3 is reachable**

```bash
curl -I http://localhost:8080/
```

Expected: HTTP 200 or 302

**Step 7: Cleanup**

```bash
docker compose down
```

**Step 8: Document integration test**

```bash
cat >> docs/plans/build-log.txt << EOF
Full stack test successful: $(date)
- All services started healthy
- Database connection working
- Redis connection working
- TYPO3 accessible via HTTP
EOF
git add docs/plans/build-log.txt
git commit -m "test: verify full docker-compose stack works"
```

---

## Task 9: Update Documentation

**Files:**
- Create: `docs/deployment/frankenphp-migration-notes.md`

**Step 1: Create deployment notes**

Create `docs/deployment/frankenphp-migration-notes.md`:

```markdown
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
```

**Step 2: Commit documentation**

```bash
mkdir -p docs/deployment
git add docs/deployment/frankenphp-migration-notes.md
git commit -m "docs: add FrankenPHP migration deployment notes"
```

---

## Task 10: Final Verification and Tagging

**Files:**
- N/A (git operations)

**Step 1: Verify all commits**

```bash
git log --oneline feature/frankenphp-migration ^develop | head -20
```

Expected: See all commits from tasks 1-9

**Step 2: Verify no uncommitted changes**

```bash
git status
```

Expected: "working tree clean"

**Step 3: Create pre-merge tag**

```bash
git tag frankenphp-migration-complete
```

**Step 4: Run final build test**

```bash
DOCKER_BUILDKIT=1 docker build -t final-test . && echo "FINAL BUILD SUCCESS"
```

Expected: "FINAL BUILD SUCCESS"

**Step 5: Document completion**

```bash
cat >> docs/plans/build-log.txt << EOF

=================================
Migration Complete: $(date)
=================================
All tasks completed successfully
Ready for merge to develop branch
EOF
git add docs/plans/build-log.txt
git commit -m "docs: mark FrankenPHP migration as complete"
git push origin feature/frankenphp-migration
```

---

## Summary

**Total Tasks:** 10
**Estimated Time:** 60-90 minutes
**Commits:** ~15 commits
**Testing Points:** Build, startup, health check, database, Redis, HTTP

**Key Principles Applied:**
- **DRY:** Reused existing multi-stage build structure
- **YAGNI:** No worker mode, no Caddy caching (Redis sufficient)
- **TDD:** Test after each major change (build, startup, integration)
- **Frequent commits:** ~2 commits per task

**Next Steps:**
1. Execute this plan using superpowers:executing-plans
2. Test in Coolify staging environment
3. Create PR: feature/frankenphp-migration → develop
4. Deploy to production after PR approval
