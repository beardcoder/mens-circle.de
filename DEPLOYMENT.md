# TYPO3 Deployment auf Coolify v4

Diese Anleitung beschreibt, wie du die TYPO3-Website auf Coolify v4 deployst.

## Voraussetzungen

- Coolify v4 Installation
- Git Repository mit diesem Code
- MySQL/MariaDB Datenbank (als Coolify Service)
- Redis Cache (als Coolify Service)

## Deployment Steps

### 1. Neues Projekt in Coolify erstellen

1. Gehe zu Coolify Dashboard
2. Erstelle ein neues Projekt
3. Wähle "Docker Image" als Deployment-Typ
4. Verbinde dein Git Repository

### 2. Build Configuration

Stelle sicher, dass Coolify das Dockerfile verwendet:

```yaml
Build Method: Dockerfile
Dockerfile Path: ./Dockerfile
```

### 3. Environment Variables konfigurieren

Füge folgende Environment Variables in Coolify hinzu:

#### TYPO3 Core
```bash
TYPO3_CONTEXT=Production
JWT_SECRET=<generiere-einen-sicheren-secret>
```

#### Database
```bash
TYPO3_DB_DRIVER=mysqli
TYPO3_DB_HOST=<dein-mysql-service-host>
TYPO3_DB_PORT=3306
TYPO3_DB_NAME=typo3
TYPO3_DB_USERNAME=<db-username>
TYPO3_DB_PASSWORD=<db-password>
```

#### Redis (Optional, aber empfohlen)
```bash
REDIS_HOST=<dein-redis-service-host>
REDIS_PORT=6379
```

### 4. Volumes konfigurieren

Erstelle folgende Persistent Volumes in Coolify:

```yaml
/var/www/html/var -> Für TYPO3 Cache, Logs, Sessions
/var/www/html/public/fileadmin -> Für User Uploads
```

### 5. Port Mapping

- Container Port: `80`
- Public Port: Automatisch von Coolify verwaltet

### 6. Health Check

Der Container enthält bereits einen Health Check:
```dockerfile
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3
```

Coolify sollte diesen automatisch erkennen.

### 7. Logging

Alle Logs werden automatisch an die Docker Console ausgegeben und sind in Coolify unter "Logs" sichtbar:

- **nginx Access Logs** → stdout (alle HTTP-Requests)
- **nginx Error Logs** → stderr (Server-Fehler)
- **PHP Errors** → stderr (PHP-Laufzeitfehler)
- **PHP-FPM Errors** → stderr (FastCGI Process Manager Fehler)
- **Supervisor** → stdout/stderr (Process Management)

**In Coolify:**
- Gehe zu deiner Application → "Logs"
- Alle Fehler und Access-Logs werden in Echtzeit angezeigt
- Kein SSH-Zugriff oder Container-Exec notwendig

**TYPO3 Application Logs:**
- TYPO3 schreibt seine eigenen Logs nach `/var/www/html/var/log/`
- Diese sind über das gemountete Volume zugänglich
- Für produktives Monitoring solltest du zusätzlich ein externes Logging-System (z.B. Sentry) verwenden

### 8. Resource Limits (Empfohlen)

```yaml
Memory Limit: 2GB
CPU Limit: 2 Cores
```

## Lokales Testing vor Deployment

Du kannst das Docker-Setup lokal testen:

```bash
# Docker Compose verwenden
docker-compose up -d

# Oder nur das Image bauen (nutzt Bun für Frontend-Build)
docker build -t mens-circle .

# Image starten
docker run -p 8080:80 \
  -e TYPO3_CONTEXT=Production \
  -e JWT_SECRET=test-secret \
  mens-circle
```

**Hinweis:** Das Dockerfile nutzt Bun statt npm für deutlich schnellere Builds.

Dann öffne: http://localhost:8080

## Troubleshooting

### Container startet nicht

1. **Prüfe die Logs in Coolify** (Logs Tab - alle Fehler werden nach stderr ausgegeben)
2. Stelle sicher, dass alle Environment Variables gesetzt sind
3. Prüfe, ob die Datenbank erreichbar ist
4. Checke Health Check Status in Coolify

**Typische Fehler in den Logs:**
```
PHP Fatal error: ... → PHP-Fehler in stderr
nginx: [emerg] ... → nginx Konfigurationsfehler in stderr
FATAL: ... → Supervisor Start-Fehler in stderr
```

### 502 Bad Gateway

- **Prüfe PHP-FPM Status** in Coolify Logs:
  - Suche nach "php-fpm" Einträgen
  - Supervisor sollte "php-fpm entered RUNNING state" melden
- **nginx-Konfiguration fehlerhaft** → Prüfe `.docker/nginx/default.conf`
- **PHP-FPM Port** nicht erreichbar → Prüfe ob Port 9000 gebunden ist

### PHP Fehler debuggen

Alle PHP-Fehler werden automatisch nach **stderr** ausgegeben:
- Öffne Coolify → Logs
- Filtere nach "PHP" oder "Fatal error"
- Fehler werden in Echtzeit angezeigt

### Langsame Performance

- Erhöhe Memory Limit in Coolify
- **Prüfe OPcache** in Logs: `opcache.enable=1` sollte aktiv sein
- Stelle sicher, dass Redis als Cache konfiguriert ist
- Prüfe nginx Access Logs für langsame Requests (Response Times)

## Updates und Rollback

### Neue Version deployen
```bash
git push origin main
```

Coolify deployed automatisch bei Push zu main (wenn Auto-Deploy aktiviert).

### Rollback
Nutze Coolify's "Rollback" Feature im Dashboard.

## Monitoring

Alle wichtigen Logs sind in Echtzeit über Coolify verfügbar:

- **Echtzeit Logs**: Coolify Dashboard → Application → "Logs" Tab
  - nginx Access & Error Logs
  - PHP Errors & Warnings
  - PHP-FPM Process Logs
  - Supervisor Process Management

- **TYPO3 Application Logs**: `/var/www/html/var/log/` (im Volume)
  - Erreichbar über Volume Mount oder Container Exec
  - Für produktives Monitoring: Sentry Integration nutzen (bereits im Projekt vorhanden)

**Live Debugging in Coolify:**
```bash
# Coolify zeigt automatisch alle stdout/stderr Logs
# Kein zusätzlicher Setup notwendig
```

## Performance Tuning

Die Container-Konfiguration ist bereits optimiert für Production:

- ✅ OPcache aktiviert
- ✅ PHP-FPM Worker Pools konfiguriert
- ✅ Gzip Compression aktiviert
- ✅ Static File Caching (1 Jahr)
- ✅ Security Headers gesetzt

Bei Bedarf kannst du die Konfigurationen in `.docker/` anpassen.

## Sicherheit

- 🔒 Alle sensiblen Daten über Environment Variables
- 🔒 Keine .env Dateien im Container
- 🔒 Security Headers konfiguriert
- 🔒 Schutz für sensible Verzeichnisse (vendor, config, var)
- 🔒 PHP-Fehler werden nicht angezeigt (nur geloggt)

## Support

Bei Problemen prüfe:
1. Coolify Logs
2. Container Health Status
3. Environment Variables
4. Volume Mounts
5. Network Connectivity zu DB/Redis
