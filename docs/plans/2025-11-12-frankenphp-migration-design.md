# FrankenPHP Migration Design

**Datum:** 2025-11-12
**Autor:** Claude Code
**Status:** Approved for Implementation

## Übersicht

Migration des TYPO3 Docker-Setups von PHP-FPM + Nginx + Supervisor zu FrankenPHP (Caddy + PHP in einem Binary). Optimiert für Coolify Production Deployment.

## Anforderungen

- **Zielumgebung:** Coolify Production (Performance & Security optimiert)
- **PHP-Modus:** Klassischer Request/Response (kein Worker-Mode)
- **Build-Struktur:** Multi-Stage Build beibehalten (minimale Image-Größe)
- **Caching:** Redis-basiertes TYPO3 Caching (kein zusätzliches Caddy HTTP-Caching)
- **Base Image:** `dunglas/frankenphp:1-php8.4-alpine`

## Architektur-Entscheidungen

### Warum FrankenPHP?

1. **Vereinfachung:** Kein separater Nginx + PHP-FPM + Supervisor Stack
2. **Performance:** Direkter CGI-Aufruf ohne Unix-Socket Overhead
3. **Modern:** HTTP/2, HTTP/3, Early Hints Support
4. **Wartbarkeit:** Ein Prozess statt drei separate Services
5. **Sicherheit:** Weniger Angriffsfläche, moderne TLS-Standards

### Warum Alpine Base?

- Kleinstes Production Image (~150-200MB final)
- Gut maintained durch FrankenPHP Team
- Security Updates über Alpine Security Team
- Geringere Build-Zeiten als Debian-basierte Images

### Warum klassischer Modus (kein Worker)?

- Geringerer RAM-Verbrauch (~80-150MB pro Container vs 300-500MB)
- Einfachere Konfiguration und Debugging
- Weniger Risiko für Memory Leaks bei lange laufenden Prozessen
- Ausreichend Performance für mens-circle.de Traffic

## Design Details

### 1. Multi-Stage Build Struktur

#### Stage 1: Composer Dependencies
```dockerfile
FROM composer:2 AS composer-builder
# Installiert PHP Dependencies mit --no-dev
# Optimierter Autoloader mit --classmap-authoritative
```

**Bleibt unverändert.** Bewährte Struktur wird beibehalten.

#### Stage 2: Frontend Build (Bun/Vite)
```dockerfile
FROM oven/bun:1-alpine AS frontend-builder
# Nutzt Composer-Output für vite-plugin-typo3
# Baut TypeScript/Vite Assets
```

**Bleibt unverändert.** Frontend-Build-Prozess ist unabhängig vom PHP-Server.

#### Stage 3: FrankenPHP Production Image
```dockerfile
FROM dunglas/frankenphp:1-php8.4-alpine

# PHP Extensions Installation
# Caddyfile Setup
# Application Files Copy
# Permissions Setup
```

**Hauptänderungen:**
- Base Image: `php:8.4-fpm-alpine` → `dunglas/frankenphp:1-php8.4-alpine`
- Keine Nginx Installation
- Keine Supervisor Installation
- User: `www-data` → `caddy` (UID 1000)

### 2. PHP Extensions

**Bereits im FrankenPHP Alpine Image enthalten:**
- gd (mit freetype, jpeg, webp)
- intl
- pdo_mysql
- zip
- opcache

**Müssen nachinstalliert werden:**
- redis (via PECL)
- imagick (via PECL, für TYPO3 Image-Processing)
- sodium (via docker-php-ext-install)

**Build-Dependencies:**
- imagemagick-dev (für imagick Compilation)
- libzip-dev, libpng-dev, libjpeg-turbo-dev (bereits vorhanden)

### 3. Caddy Konfiguration

**Datei:** `.docker/frankenphp/Caddyfile`

```caddyfile
{
    # Global Options
    auto_https off  # Coolify handhabt HTTPS via Traefik
    admin off       # Keine Admin-API in Production
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

    # TYPO3 Frontend (alle anderen Requests → index.php)
    handle {
        try_files {path} /index.php?{query}
    }

    # File Server Fallback
    file_server
}
```

**Wichtige Caddy-Features:**
- `php_server`: Integrierte PHP-Ausführung via FrankenPHP
- `encode gzip zstd`: Automatische Compression (ersetzt nginx gzip)
- `try_files`: TYPO3-Routing (alle Requests durch index.php)
- Security Headers: Moderne Browser-Security

### 4. PHP Konfiguration

**Datei:** `.docker/php/typo3.ini`

**Beibehalten:**
```ini
memory_limit = 256M
upload_max_filesize = 20M
post_max_size = 20M
max_execution_time = 240
max_input_vars = 1500

# Opcache
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 2
opcache.fast_shutdown = 1
```

**Entfernen:**
- Alle `php-fpm.*` Direktiven
- `request_terminate_timeout` (FPM-spezifisch)

**FrankenPHP Environment-Variablen:**
```dockerfile
ENV FRANKENPHP_CONFIG="worker ./public/index.php 0"  # 0 = klassischer Modus
ENV SERVER_NAME=":80"
ENV CADDY_GLOBAL_OPTIONS=""
```

### 5. Docker Compose Anpassungen

**Änderungen in `docker-compose.yml`:**

```yaml
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "8080:80"
      # Optional für lokales HTTPS-Testing: - "8443:443"
    environment:
      # Bestehende TYPO3 Vars bleiben identisch
      TYPO3_CONTEXT: Production
      JWT_SECRET: ${JWT_SECRET:-dev-secret-change-in-production}
      TYPO3_DB_DRIVER: mysqli
      TYPO3_DB_HOST: db
      TYPO3_DB_PORT: 3306
      TYPO3_DB_NAME: typo3
      TYPO3_DB_USERNAME: typo3
      TYPO3_DB_PASSWORD: typo3
      REDIS_HOST: redis
      REDIS_PORT: 6379
      # Neu: FrankenPHP Config
      FRANKENPHP_CONFIG: "worker ./public/index.php 0"
      SERVER_NAME: ":80"
```

**Keine Änderungen:**
- Database Service (mysql:8.0)
- Redis Service (redis:7-alpine)
- Volume Mounts (typo3_var, typo3_fileadmin, db_data, redis_data)

### 6. File Structure Änderungen

**Neue Dateien:**
- `.docker/frankenphp/Caddyfile`

**Angepasste Dateien:**
- `Dockerfile` (komplett neu strukturiert)
- `.docker/php/typo3.ini` (FPM-Direktiven entfernt)
- `docker-compose.yml` (FrankenPHP ENV vars)

**Gelöschte Dateien:**
- `.docker/nginx/default.conf` (ersetzt durch Caddyfile)
- `.docker/php/www.conf` (FPM Config nicht mehr nötig)
- `.docker/supervisor/services.conf` (kein Supervisor mehr)

### 7. Permissions & Security

**User-Wechsel:**
- Alt: `www-data:www-data` (UID/GID 82)
- Neu: `caddy:caddy` (UID/GID 1000)

**File Permissions bleiben:**
- Directories: 755
- Files: 644
- Binaries (vendor/bin): +x

**Chown Commands im Dockerfile:**
```dockerfile
RUN chown -R caddy:caddy /var/www/html/var /var/www/html/public
```

**Security Improvements:**
- Ein Prozess statt drei = weniger Angriffsfläche
- Kein PHP-FPM Unix-Socket = keine Socket-Permission Probleme
- Moderne TLS via Caddy (wenn aktiviert)
- Security Headers standardmäßig aktiviert

### 8. Health Check

**Alt (PHP-FPM):**
```dockerfile
HEALTHCHECK CMD php-fpm-healthcheck || exit 1
```

**Neu (FrankenPHP):**
```dockerfile
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD wget --no-verbose --tries=1 --spider http://localhost/typo3/login || exit 1
```

**Rationale:**
- FrankenPHP hat keinen `php-fpm-healthcheck` Befehl
- Stattdessen: HTTP-Request auf TYPO3 Backend Login-Seite
- Status 200/302 = Healthy (Application läuft)
- `wget` ist in Alpine vorhanden

**Alternative:** Custom Health-Script `/health.php`:
```php
<?php
// Test DB Connection
try {
    new PDO("mysql:host={$_ENV['TYPO3_DB_HOST']};dbname={$_ENV['TYPO3_DB_NAME']}",
            $_ENV['TYPO3_DB_USERNAME'], $_ENV['TYPO3_DB_PASSWORD']);
} catch (PDOException $e) {
    http_response_code(503);
    exit(1);
}

// Test Redis Connection
$redis = new Redis();
if (!$redis->connect($_ENV['REDIS_HOST'], $_ENV['REDIS_PORT'])) {
    http_response_code(503);
    exit(1);
}

http_response_code(200);
echo 'OK';
```

## Build & Deployment

### Lokaler Build

```bash
# Build mit BuildKit
DOCKER_BUILDKIT=1 docker build -t typo3-frankenphp .

# Multi-Platform Build (optional für ARM Mac)
docker buildx build --platform linux/amd64,linux/arm64 -t typo3-frankenphp .
```

### Coolify Deployment

**Voraussetzungen:**
- Coolify 4.x mit Docker Support
- Traefik Reverse Proxy (für HTTPS)
- PostgreSQL für Coolify Datenbank

**Deployment Steps:**
1. Git Repository in Coolify verbinden
2. Dockerfile als Source auswählen
3. Environment-Variablen setzen (DB, Redis, JWT)
4. Port 80 für Traefik mapping
5. Volumes für `typo3_var` und `typo3_fileadmin` persistent
6. Health-Check konfigurieren: `/typo3/login` (HTTP 200/302)
7. Deploy starten

**Coolify-spezifische Settings:**
- **Build Pack:** Dockerfile
- **Port:** 80 (intern), Traefik handhabt HTTPS
- **Volumes:**
  - `/var/www/html/var` → `typo3_var`
  - `/var/www/html/public/fileadmin` → `typo3_fileadmin`
- **Health Check:** `/typo3/login` alle 30s

### Rollback-Strategie

**Vor Deployment:**
```bash
# Git Tag erstellen
git tag pre-frankenphp-migration
git push origin pre-frankenphp-migration

# Alte Dockerfile sichern
cp Dockerfile Dockerfile.php-fpm.backup
```

**Bei Problemen:**
1. In Coolify: Previous Deployment auswählen
2. Oder: `git revert` und neues Deployment
3. Datenbank-State ist kompatibel (keine Schema-Änderungen)

**Datenbank-Backup vor Migration:**
```bash
# Über Coolify UI: Backup erstellen
# Oder manuell:
docker exec typo3-db mysqldump -u typo3 -p typo3 > backup-$(date +%Y%m%d).sql
```

## Testing Checklist

**Vor Production Deployment:**

- [ ] Lokaler Build erfolgreich: `docker build -t test .`
- [ ] Container startet: `docker run -p 8080:80 test`
- [ ] Health-Check wird grün (nach 40s start-period)
- [ ] Composer autoload lädt korrekt
- [ ] Frontend Assets sind unter `/_assets/` erreichbar
- [ ] TYPO3 Backend Login erreichbar: `http://localhost:8080/typo3/`
- [ ] Backend Login funktioniert (DB Connection OK)
- [ ] Frontend-Seite rendert korrekt
- [ ] Redis-Cache funktioniert (Cache-Keys in redis-cli sichtbar)
- [ ] File-Upload in fileadmin funktioniert
- [ ] Image-Processing (ImageMagick) funktioniert
- [ ] Logs zeigen keine Errors: `docker logs <container>`

**Nach Production Deployment:**

- [ ] Coolify Build erfolgreich
- [ ] Container ist healthy
- [ ] HTTPS funktioniert (Traefik)
- [ ] Backend erreichbar und performant
- [ ] Frontend lädt in < 2s (First Contentful Paint)
- [ ] Cache-Hit-Rate > 80% nach Warmup
- [ ] Keine PHP Errors in Logs
- [ ] Memory Usage stabil < 200MB
- [ ] Uptime Monitor zeigt 100% Availability

## Performance Erwartungen

**PHP-FPM vs FrankenPHP (klassischer Modus):**
- **Latenz:** ~10-15% schneller (kein Unix-Socket Overhead)
- **Throughput:** Ähnlich bei klassischem Modus
- **Memory:** ~20-30MB weniger pro Container (kein Nginx/Supervisor)
- **Cold Start:** ~5-10s schneller (ein Prozess statt drei)

**Image Größe:**
- Alt: ~250MB (Alpine FPM + Nginx + Deps)
- Neu: ~180MB (Alpine FrankenPHP + Deps)
- Einsparung: ~70MB (28% kleiner)

## Monitoring & Observability

**Logs:**
```bash
# FrankenPHP Logs (STDOUT/STDERR)
docker logs -f typo3-app

# Strukturierte Logs via Coolify UI
```

**Metrics (optional mit Prometheus):**
- FrankenPHP Caddy Metrics: `/metrics` Endpoint (wenn aktiviert)
- PHP-FPM Status entfällt (kein `/status` mehr)

**Wichtige Metriken:**
- Request Duration (p50, p95, p99)
- Memory Usage (Container)
- Cache Hit Rate (Redis)
- Error Rate (5xx Responses)

## Offene Fragen & Entscheidungen

- [ ] **Custom Health-Script:** `/health.php` vs. `/typo3/login` Check?
- [ ] **Metrics-Endpoint:** Caddy `/metrics` aktivieren für Prometheus?
- [ ] **Worker-Mode Testing:** Später migrieren wenn Traffic steigt?
- [ ] **HTTP/3:** In Coolify aktivieren (erfordert UDP Port)?

## Referenzen

- [FrankenPHP Dokumentation](https://frankenphp.dev)
- [Caddy Dokumentation](https://caddyserver.com/docs/)
- [TYPO3 Docker Best Practices](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/SystemRequirements/Index.html)
- [Alpine Linux Security](https://alpinelinux.org/about/)
