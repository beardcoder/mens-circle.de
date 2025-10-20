#!/bin/bash

# ============================================
# Secret Generator for Coolify Deployment
# Generates all required passwords and keys
# ============================================

set -e

echo "🔐 Generating secrets for Coolify deployment..."
echo ""

# ============================================
# Database Passwords
# ============================================

echo "📊 Database Passwords:"
DB_ROOT_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-32)
DB_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-32)

echo "DB_ROOT_PASSWORD=$DB_ROOT_PASSWORD"
echo "DB_PASSWORD=$DB_PASSWORD"
echo ""

# ============================================
# JWT Secret
# ============================================

echo "🔑 JWT Secret:"
JWT_SECRET=$(openssl rand -base64 32 | tr -d "=+/")
echo "JWT_SECRET=$JWT_SECRET"
echo ""

# ============================================
# TYPO3 Encryption Key
# ============================================

echo "🔐 TYPO3 Encryption Key:"
TYPO3_ENCRYPTION_KEY=$(openssl rand -hex 48)
echo "TYPO3_ENCRYPTION_KEY=$TYPO3_ENCRYPTION_KEY"
echo ""

# ============================================
# Install Tool Password (3 Methods)
# ============================================

echo "🛠️  Install Tool Password:"
echo "Enter a password for TYPO3 Install Tool:"
read -s INSTALL_TOOL_PASS
echo ""

# Method 1: Plain-text (simplest for Coolify)
echo "INSTALL_TOOL_PASSWORD=$INSTALL_TOOL_PASS"
echo ""

# Method 2: Base64-encoded hash (if you have PHP)
if command -v php &> /dev/null; then
    INSTALL_TOOL_HASH=$(php -r "echo password_hash('$INSTALL_TOOL_PASS', PASSWORD_ARGON2I);")
    INSTALL_TOOL_BASE64=$(echo -n "$INSTALL_TOOL_HASH" | base64)

    echo "Alternative (if plain-text doesn't work):"
    echo "INSTALL_TOOL_PASSWORD_BASE64=$INSTALL_TOOL_BASE64"
    echo ""
else
    echo "⚠️  PHP not found. Using plain-text method only."
fi

echo "⚠️  IMPORTANT: Save this password: $INSTALL_TOOL_PASS"
echo ""

# ============================================
# Generate .env file
# ============================================

echo "📝 Generating .env.production file..."

cat > .env.production <<EOF
# ============================================
# Coolify Production Environment Variables
# Generated: $(date)
# ============================================

# Database
DB_ROOT_PASSWORD=$DB_ROOT_PASSWORD
DB_DATABASE=typo3
DB_USERNAME=typo3
DB_PASSWORD=$DB_PASSWORD

# Security
JWT_SECRET=$JWT_SECRET
TYPO3_ENCRYPTION_KEY=$TYPO3_ENCRYPTION_KEY

# Install Tool Password (Method 1: Plain-text - RECOMMENDED)
INSTALL_TOOL_PASSWORD=$INSTALL_TOOL_PASS

# Alternative: Base64-encoded hash (if plain-text doesn't work)
# INSTALL_TOOL_PASSWORD_BASE64=$INSTALL_TOOL_BASE64

# TYPO3
TYPO3_CONTEXT=Production

# Mail (UPDATE THESE!)
TYPO3_MAIL_TRANSPORT=smtp
TYPO3_MAIL_SMTP_SERVER=smtp.example.com:587
TYPO3_MAIL_SMTP_ENCRYPT=tls
TYPO3_MAIL_SMTP_USERNAME=noreply@example.com
TYPO3_MAIL_SMTP_PASSWORD=CHANGE_ME
TYPO3_MAIL_DEFAULT_FROM=noreply@example.com
TYPO3_MAIL_DEFAULT_FROM_NAME=Mens Circle

# Optional: Sentry
# SENTRY_DSN=
# SENTRY_ENVIRONMENT=production
EOF

echo "✅ .env.production file created!"
echo ""

# ============================================
# Summary
# ============================================

echo "📋 Summary:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "1. Copy the values above to Coolify Environment Variables"
echo "2. Update mail configuration in .env.production"
echo "3. Keep .env.production secure (don't commit to Git!)"
echo "4. Install Tool Password: $INSTALL_TOOL_PASS"
echo ""
echo "⚠️  IMPORTANT: Save Install Tool password in your password manager!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "🎉 All secrets generated successfully!"
