#!/usr/bin/env bash
set -euo pipefail

# Usage:
# chmod +x scripts/deploy_vps.sh
# ./scripts/deploy_vps.sh labelrbm.iwareid.com /var/www/rbmschedule

DOMAIN="${1:-}"
APP_DIR="${2:-/var/www/rbmschedule}"
PHP_VERSION="${PHP_VERSION:-8.2}"

if [[ -z "$DOMAIN" ]]; then
  echo "Usage: $0 <domain> [app_dir]"
  exit 1
fi

echo "[1/8] Installing packages..."
sudo apt update
sudo apt install -y nginx mysql-server php${PHP_VERSION}-fpm php${PHP_VERSION}-mysql php${PHP_VERSION}-mbstring php${PHP_VERSION}-xml php${PHP_VERSION}-curl unzip

echo "[2/8] Creating app directory..."
sudo mkdir -p "$APP_DIR"
sudo chown -R "$USER:www-data" "$APP_DIR"

echo "[3/8] Copying project files..."
rsync -av --exclude='.git' --exclude='node_modules' ./ "$APP_DIR"/

echo "[4/8] Preparing environment file..."
if [[ ! -f "$APP_DIR/.env" ]]; then
  cp "$APP_DIR/.env.example" "$APP_DIR/.env"
  echo "Please edit $APP_DIR/.env with production credentials."
fi

echo "[5/8] Setting permissions..."
sudo chown -R www-data:www-data "$APP_DIR/logs"
sudo chmod -R 775 "$APP_DIR/logs"
sudo find "$APP_DIR" -type f -name "*.php" -exec chmod 644 {} \;
sudo find "$APP_DIR" -type d -exec chmod 755 {} \;

echo "[6/8] Installing Nginx site config..."
TMP_CONF="/tmp/rbmschedule.conf"
sed "s|your-domain.com|$DOMAIN|g; s|/var/www/rbmschedule|$APP_DIR|g" deploy/nginx-rbmschedule.conf > "$TMP_CONF"
sudo cp "$TMP_CONF" "/etc/nginx/sites-available/rbmschedule.conf"
sudo ln -sf "/etc/nginx/sites-available/rbmschedule.conf" "/etc/nginx/sites-enabled/rbmschedule.conf"
sudo rm -f "/etc/nginx/sites-enabled/default"
rm -f "$TMP_CONF"

echo "[7/8] Testing and reloading services..."
sudo nginx -t
sudo systemctl restart php${PHP_VERSION}-fpm
sudo systemctl restart nginx

echo "[8/8] Done."
echo "Next steps:"
echo "- Import database.sql into MySQL"
echo "- Update .env values in $APP_DIR/.env"
echo "- Install SSL certificate (certbot recommended)"
