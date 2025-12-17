# Docker Configuration Files

Diese Ordnerstruktur enthält alle Docker-Konfigurationsdateien für die TYPO3-Anwendung.

Das Dockerfile nutzt ein **Multi-Stage Build** mit:
1. **Stage 1:** Composer Dependencies (PHP/TYPO3)
2. **Stage 2:** Frontend Build mit Bun (Vite/TypeScript)
3. **Stage 3:** Production Image (FrankenPHP + Caddy)

## Struktur

```
.docker/
├── frankenphp/     # FrankenPHP + Caddy Web Server Konfiguration
│   └── Caddyfile
├── php/            # PHP Runtime Konfiguration
│   └── typo3.ini
├── supervisor/     # Process Manager Konfiguration
│   └── supervisord.conf
├── typo3/          # TYPO3 System Konfiguration
│   └── additional.php
└── imagemagick-policy.xml
```

## Dateien

### frankenphp/Caddyfile
- Caddy Web Server Konfiguration (integriert in FrankenPHP)
- TYPO3-spezifische Routing-Regeln
- Security Headers (X-Frame-Options, CSP, etc.)
- Gzip Compression
- Static File Caching
- Schutz für sensible Verzeichnisse

### php/typo3.ini
- PHP Production Settings
- Memory Limits (512M)
- Upload Limits (64M)
- OPcache Optimierungen
- Security Settings
- Session Configuration
- PHP 8.5 spezifische Einstellungen

### typo3/additional.php
- **NEU:** TYPO3 System-Konfiguration mit Environment-Variablen
- Ersetzt hardcoded Konfigurationen durch flexible ENV-basierte Settings
- Optimiert für TYPO3 v14 + PHP 8.5 + FrankenPHP
- Performance-Optimierungen für FrankenPHP (persistente Connections)
- Redis Cache Konfiguration
- Database Settings
- Mail Configuration (SMTP)
- Sentry Error Tracking
- Session Handling
- Siehe `.env.production.example` für alle verfügbaren Environment-Variablen

### supervisor/supervisord.conf
- Supervisor Daemon Konfiguration
- FrankenPHP Process Management
- Symfony Messenger Queue Worker
- Logging zu stdout/stderr

## Anpassungen

Wenn du Konfigurationen ändern möchtest:

1. Bearbeite die entsprechende Datei in diesem Ordner
2. Rebuilde das Docker Image: `docker build -t mens-circle .`
3. Teste die Änderungen lokal
4. Deploy zu Coolify v4

## Environment-Variablen

Die Anwendung wird vollständig über Environment-Variablen konfiguriert. Siehe `.env.production.example` für eine vollständige Liste aller verfügbaren Variablen.

### Erforderliche Variablen (Minimum):
- `DB_NAME`, `DB_HOST`, `DB_USER`, `DB_PASSWORD` - Datenbank-Verbindung
- `REDIS_HOST`, `REDIS_PASSWORD` - Redis Cache (optional aber empfohlen)
- `MAIL_SMTP_SERVER`, `MAIL_SMTP_USERNAME`, `MAIL_SMTP_PASSWORD` - Mail-Versand

### Performance-Variablen:
- `REDIS_PERSISTENT=true` - Persistente Redis-Connections für FrankenPHP (Standard: true)
- `DB_PERSISTENT=false` - Persistente DB-Connections (Standard: false)
- `FRANKENPHP_ENABLED=true` - FrankenPHP-spezifische Optimierungen
- `APCU_ENABLED=false` - APCu für Runtime-Caches (wenn installiert)

### Best Practices:
- Verwende starke, zufällig generierte Passwörter
- Setze `REDIS_ENABLED=true` für optimale Performance
- Aktiviere `REDIS_PERSISTENT=true` für FrankenPHP
- Konfiguriere `SENTRY_TRACES_SAMPLE_RATE` für Error Tracking

## Logging

Alle Logs werden an die Docker Console (stdout/stderr) ausgegeben und sind in Coolify sichtbar:

- **FrankenPHP/Caddy access_log** → stdout
- **FrankenPHP/Caddy error_log** → stderr
- **PHP errors** → stderr (via RotatingFileWriter)
- **TYPO3 errors** → stderr + Sentry (wenn konfiguriert)
- **Supervisor** → stdout/stderr
- **Queue Worker** → stdout/stderr

Dies ermöglicht einfaches Monitoring in Coolify ohne in den Container zu müssen.

## Production Deployment

Diese Dateien werden während des Docker Build-Prozesses in das finale Image kopiert:

- `frankenphp/Caddyfile` → `/etc/caddy/Caddyfile`
- `php/typo3.ini` → `/usr/local/etc/php/conf.d/typo3.ini`
- `supervisor/supervisord.conf` → `/etc/supervisord.conf`
- `typo3/additional.php` → `/var/www/html/config/system/additional.php`
- `imagemagick-policy.xml` → `/etc/ImageMagick-7/policy.xml`

## FrankenPHP Performance

FrankenPHP bietet deutlich bessere Performance als traditionelles PHP-FPM:

- **Integrierter Webserver:** Caddy direkt in FrankenPHP integriert
- **HTTP/2 & HTTP/3:** Native Unterstützung ohne zusätzliche Konfiguration
- **Early Hints:** Für optimales Preloading
- **Worker Mode:** Optional für 3-4x bessere Performance (über `FRANKENPHP_CONFIG`)
- **Persistente Connections:** Redis und optional Datenbank

### Worker Mode aktivieren (Optional):
```env
FRANKENPHP_CONFIG=worker ./public/index.php
```

**Hinweis:** Worker Mode erfordert Worker-kompatiblen Code (stateless Services, kein globaler State).
