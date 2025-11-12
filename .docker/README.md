# Docker Configuration Files

Diese Ordnerstruktur enthält alle Docker-Konfigurationsdateien für die TYPO3-Anwendung.

Das Dockerfile nutzt ein **Multi-Stage Build** mit:
1. **Stage 1:** Composer Dependencies (PHP/TYPO3)
2. **Stage 2:** Frontend Build mit Bun (Vite/TypeScript)
3. **Stage 3:** Production Image (nginx + PHP-FPM)

## Struktur

```
.docker/
├── nginx/          # Nginx Web Server Konfiguration
│   └── default.conf
├── php/            # PHP Runtime Konfiguration
│   ├── typo3.ini
│   └── www.conf
└── supervisor/     # Process Manager Konfiguration
    └── services.conf
```

## Dateien

### nginx/default.conf
- TYPO3-spezifische nginx-Konfiguration
- Security Headers (X-Frame-Options, CSP, etc.)
- Gzip Compression
- PHP-FPM Integration
- Static File Caching
- Schutz für sensible Verzeichnisse

### php/typo3.ini
- PHP Production Settings
- Memory Limits (512M)
- Upload Limits (64M)
- OPcache Optimierungen
- Security Settings
- Session Configuration

### php/www.conf
- PHP-FPM Process Manager Konfiguration
- Worker Pool Settings (max_children, start_servers, etc.)
- Performance Tuning
- Request Timeout Settings

### supervisor/services.conf
- Supervisor Daemon Konfiguration
- PHP-FPM Process Management
- Nginx Process Management
- Logging zu stdout/stderr

## Anpassungen

Wenn du Konfigurationen ändern möchtest:

1. Bearbeite die entsprechende Datei in diesem Ordner
2. Rebuilde das Docker Image: `docker build -t mens-circle .`
3. Teste die Änderungen lokal
4. Deploy zu Coolify v4

## Logging

Alle Logs werden an die Docker Console (stdout/stderr) ausgegeben und sind in Coolify sichtbar:

- **nginx access_log** → stdout
- **nginx error_log** → stderr
- **PHP errors** → stderr
- **PHP-FPM errors** → stderr
- **Supervisor** → stdout/stderr

Dies ermöglicht einfaches Monitoring in Coolify ohne in den Container zu müssen.

## Production Deployment

Diese Dateien werden während des Docker Build-Prozesses in das finale Image kopiert:

- `nginx/default.conf` → `/etc/nginx/http.d/default.conf`
- `php/typo3.ini` → `/usr/local/etc/php/conf.d/typo3.ini`
- `php/www.conf` → `/usr/local/etc/php-fpm.d/www.conf`
- `supervisor/services.conf` → `/etc/supervisor/conf.d/services.conf`
