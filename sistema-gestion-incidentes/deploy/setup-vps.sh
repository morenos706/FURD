#!/usr/bin/env bash
# Instala y configura el Sistema de Gestion de Incidentes (FURD) en un VPS
# Ubuntu limpio (Oracle Cloud Free Tier u otro). Correr como root o con sudo:
#
#   curl -fsSL <URL_DE_ESTE_SCRIPT> -o setup-vps.sh
#   sudo bash setup-vps.sh
#
set -euo pipefail

REPO_URL="https://github.com/morenos706/FURD.git"
REPO_BRANCH="claude/ejecutarlo-ftigis"
APP_DIR="/var/www/furd"
DB_NAME="sistema_incidentes"
DB_USER="sistema_user"
DB_PASS="$(openssl rand -hex 12)"
ADMIN_USER="admin"
ADMIN_EMAIL="admin@furd.local"
ADMIN_PASS="$(openssl rand -hex 8)"
ADMIN_NAME="Administrador del Sistema"

echo "==> Instalando paquetes (Apache, PHP, MariaDB, Composer, Git)..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq apache2 mariadb-server git unzip curl \
  php php-cli php-mysql php-mbstring php-xml php-curl php-gd php-zip composer

echo "==> Habilitando mod_rewrite y arrancando servicios..."
a2enmod rewrite >/dev/null
systemctl enable --now apache2 >/dev/null
systemctl enable --now mariadb >/dev/null

echo "==> Clonando el repositorio..."
rm -rf "$APP_DIR"
git clone --quiet --branch "$REPO_BRANCH" --single-branch "$REPO_URL" /tmp/furd-clone
mkdir -p "$APP_DIR"
cp -r /tmp/furd-clone/sistema-gestion-incidentes/. "$APP_DIR"/
rm -rf /tmp/furd-clone

echo "==> Creando base de datos..."
mysql -u root <<SQL
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$APP_DIR/database/schema.sql"
mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$APP_DIR/database/seed_catalogs.sql"

echo "==> Configurando .env..."
PUBLIC_IP="$(curl -fsSL -4 ifconfig.me || echo 'TU_IP_PUBLICA')"
cat > "$APP_DIR/.env" <<EOF
APP_NAME="Sistema de Gestion de Incidentes"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://${PUBLIC_IP}

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}

SESSION_LIFETIME=120
EOF

echo "==> Instalando dependencias de Composer (PDF/Excel)..."
cd "$APP_DIR"
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --quiet

echo "==> Creando usuario administrador..."
php database/seed_admin.php "$ADMIN_USER" "$ADMIN_EMAIL" "$ADMIN_PASS" "$ADMIN_NAME"

echo "==> Cargando 60 casos de demostracion..."
php database/seed_demo.php 60

echo "==> Configurando Apache VirtualHost..."
cat > /etc/apache2/sites-available/furd.conf <<EOF
<VirtualHost *:80>
    ServerName ${PUBLIC_IP}
    DocumentRoot ${APP_DIR}/public
    <Directory ${APP_DIR}/public>
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog \${APACHE_LOG_DIR}/furd-error.log
    CustomLog \${APACHE_LOG_DIR}/furd-access.log combined
</VirtualHost>
EOF
a2dissite 000-default >/dev/null 2>&1 || true
a2ensite furd >/dev/null
systemctl reload apache2

echo "==> Permisos de storage..."
chown -R www-data:www-data "$APP_DIR/storage"
chmod -R 775 "$APP_DIR/storage"

echo "==> Configurando firewall local (ufw, si esta activo)..."
if command -v ufw >/dev/null && ufw status | grep -q "Status: active"; then
    ufw allow 80/tcp >/dev/null || true
    ufw allow 22/tcp >/dev/null || true
fi

echo ""
echo "=================================================================="
echo " LISTO. Sistema disponible en: http://${PUBLIC_IP}/login"
echo ""
echo " Usuario admin:   ${ADMIN_USER}"
echo " Clave admin:     ${ADMIN_PASS}"
echo ""
echo " Base de datos:   ${DB_NAME}"
echo " Usuario BD:      ${DB_USER}"
echo " Clave BD:        ${DB_PASS}"
echo ""
echo " IMPORTANTE: en Oracle Cloud (u otro proveedor con firewall de red"
echo " propio), agrega una regla de 'Ingress' permitiendo el puerto 80"
echo " (TCP) desde 0.0.0.0/0 en el 'Security List' de tu VCN, o el sitio"
echo " no sera accesible desde internet aunque Apache este corriendo."
echo "=================================================================="
