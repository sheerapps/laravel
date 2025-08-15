#!/bin/bash

# SheerApps 4D - CentOS 7 Deployment Script
# This script sets up a secure Laravel application with Telegram authentication

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
APP_NAME="sheerapps4d"
APP_USER="sheerapps"
APP_DIR="/var/www/$APP_NAME"
DB_NAME="sheerapps_db"
DB_USER="sheerapps_user"
DB_PASS=$(openssl rand -base64 32)
TELEGRAM_BOT_TOKEN=""
TELEGRAM_BOT_USERNAME=""

echo -e "${GREEN}🚀 Starting SheerApps 4D deployment on CentOS 7...${NC}"

# Function to print status
print_status() {
    echo -e "${YELLOW}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   print_error "This script must be run as root"
   exit 1
fi

# Update system
print_status "Updating system packages..."
yum update -y

# Install EPEL repository
print_status "Installing EPEL repository..."
yum install -y epel-release

# Install required packages
print_status "Installing required packages..."
yum install -y \
    nginx \
    mysql-server \
    php \
    php-fpm \
    php-mysql \
    php-mbstring \
    php-xml \
    php-json \
    php-curl \
    php-gd \
    php-zip \
    php-redis \
    redis \
    git \
    unzip \
    fail2ban \
    ufw \
    certbot \
    python3-certbot-nginx \
    supervisor \
    cronie

# Start and enable services
print_status "Starting and enabling services..."
systemctl start nginx
systemctl enable nginx
systemctl start mysqld
systemctl enable mysqld
systemctl start redis
systemctl enable redis
systemctl start fail2ban
systemctl enable fail2ban
systemctl start crond
systemctl enable crond

# Configure MySQL security
print_status "Configuring MySQL security..."
mysql_secure_installation <<EOF

y
0
$DB_PASS
$DB_PASS
y
y
y
y
EOF

# Create database and user
print_status "Creating database and user..."
mysql -u root -p$DB_PASS <<EOF
CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
EOF

# Create application user
print_status "Creating application user..."
useradd -r -s /bin/false $APP_USER

# Create application directory
print_status "Creating application directory..."
mkdir -p $APP_DIR
chown $APP_USER:$APP_USER $APP_DIR

# Configure PHP-FPM
print_status "Configuring PHP-FPM..."
sed -i 's/user = apache/user = nginx/g' /etc/php-fpm.d/www.conf
sed -i 's/group = apache/group = nginx/g' /etc/php-fpm.d/www.conf
sed -i 's/;listen.owner = nobody/listen.owner = nginx/g' /etc/php-fpm.d/www.conf
sed -i 's/;listen.group = nobody/listen.group = nginx/g' /etc/php-fpm.d/www.conf
sed -i 's/;listen.mode = 0660/listen.mode = 0660/g' /etc/php-fpm.d/www.conf

# Configure PHP settings
print_status "Configuring PHP settings..."
cat > /etc/php.d/99-sheerapps.ini <<EOF
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
memory_limit = 256M
date.timezone = UTC
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = "Strict"
EOF

# Configure Nginx
print_status "Configuring Nginx..."
cat > /etc/nginx/conf.d/$APP_NAME.conf <<EOF
server {
    listen 80;
    server_name _;
    root $APP_DIR/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options DENY;
    add_header X-Content-Type-Options nosniff;
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";
    add_header Permissions-Policy "geolocation=(), microphone=(), camera=()";

    # Rate limiting
    limit_req_zone \$binary_remote_addr zone=api:10m rate=10r/s;
    limit_req_zone \$binary_remote_addr zone=telegram:10m rate=5r/s;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        limit_req zone=api burst=20 nodelay;
        fastcgi_pass unix:/var/run/php-fpm/www.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location /api/telegram-login {
        limit_req zone=telegram burst=10 nodelay;
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }

    location ~ \.(env|log|sql|md|txt)$ {
        deny all;
    }

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;
}
EOF

# Configure Redis
print_status "Configuring Redis..."
sed -i 's/# requirepass foobared/requirepass '$DB_PASS'/g' /etc/redis.conf
sed -i 's/bind 127.0.0.1/bind 127.0.0.1/g' /etc/redis.conf

# Configure Fail2ban
print_status "Configuring Fail2ban..."
cat > /etc/fail2ban/jail.local <<EOF
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3

[nginx-http-auth]
enabled = true
filter = nginx-http-auth
logpath = /var/log/nginx/error.log
maxretry = 3

[nginx-botsearch]
enabled = true
filter = nginx-botsearch
logpath = /var/log/nginx/access.log
maxretry = 2

[nginx-limit-req]
enabled = true
filter = nginx-limit-req
logpath = /var/log/nginx/error.log
maxretry = 3

[mysql]
enabled = true
filter = mysql
logpath = /var/log/mysqld.log
maxretry = 3
EOF

# Configure firewall
print_status "Configuring firewall..."
systemctl start ufw
systemctl enable ufw
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow 80
ufw allow 443
ufw --force enable

# Create environment file
print_status "Creating environment file..."
cat > $APP_DIR/.env <<EOF
APP_NAME="SheerApps 4D"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_KEY=

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=$DB_NAME
DB_USERNAME=$DB_USER
DB_PASSWORD=$DB_PASS

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=$DB_PASS
REDIS_PORT=6379

TELEGRAM_BOT_TOKEN=$TELEGRAM_BOT_TOKEN
TELEGRAM_BOT_USERNAME=$TELEGRAM_BOT_USERNAME

SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict
EOF

# Set proper permissions
print_status "Setting permissions..."
chown -R $APP_USER:$APP_USER $APP_DIR
chmod -R 755 $APP_DIR
chmod 640 $APP_DIR/.env

# Create supervisor configuration for queue workers
print_status "Configuring supervisor..."
cat > /etc/supervisord.d/$APP_NAME.ini <<EOF
[program:$APP_NAME-worker]
process_name=%(program_name)s_%(process_num)02d
command=php $APP_DIR/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=$APP_USER
numprocs=2
redirect_stderr=true
stdout_logfile=$APP_DIR/storage/logs/worker.log
stopwaitsecs=3600
EOF

# Create cron job for Laravel scheduler
print_status "Setting up cron jobs..."
(crontab -u $APP_USER -l 2>/dev/null; echo "* * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1") | crontab -u $APP_USER -

# Restart services
print_status "Restarting services..."
systemctl restart php-fpm
systemctl restart nginx
systemctl restart redis
systemctl restart fail2ban
systemctl restart supervisord

# Create deployment completion script
print_status "Creating deployment completion script..."
cat > $APP_DIR/complete-deployment.sh <<EOF
#!/bin/bash
cd $APP_DIR

# Install Composer dependencies
composer install --no-dev --optimize-autoloader

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Clear and cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set final permissions
chown -R $APP_USER:$APP_USER $APP_DIR
chmod -R 755 $APP_DIR
chmod 640 $APP_DIR/.env

echo "Deployment completed successfully!"
echo "Please update your Telegram bot token in .env file"
echo "Database password: $DB_PASS"
EOF

chmod +x $APP_DIR/complete-deployment.sh
chown $APP_USER:$APP_USER $APP_DIR/complete-deployment.sh

# Print completion message
print_success "Deployment script completed!"
echo ""
echo -e "${GREEN}📋 Next Steps:${NC}"
echo "1. Copy your Laravel application files to: $APP_DIR"
echo "2. Update Telegram bot token in: $APP_DIR/.env"
echo "3. Run: $APP_DIR/complete-deployment.sh"
echo "4. Configure SSL certificate with: certbot --nginx"
echo ""
echo -e "${GREEN}🔐 Database Credentials:${NC}"
echo "Database: $DB_NAME"
echo "Username: $DB_USER"
echo "Password: $DB_PASS"
echo ""
echo -e "${GREEN}🛡️ Security Features Enabled:${NC}"
echo "✓ Firewall (UFW)"
echo "✓ Fail2ban intrusion prevention"
echo "✓ Rate limiting"
echo "✓ Security headers"
echo "✓ SSL/TLS ready"
echo "✓ Redis caching"
echo "✓ Supervisor process management"
echo ""
echo -e "${YELLOW}⚠️  Important:${NC}"
echo "- Update your domain in .env file"
echo "- Configure SSL certificate"
echo "- Set up monitoring and backups"
echo "- Keep system updated regularly"
