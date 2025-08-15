# SheerApps 4D - Secure Laravel API

A secure Laravel 5.8.38 application with Telegram authentication, referral system, and React Native integration.

## 🚀 Features

- **Secure Telegram Authentication**: HMAC-SHA256 validation with bot token
- **Referral System**: Track user referrals with multi-level support
- **API Security**: Rate limiting, token validation, and IP tracking
- **React Native Integration**: Deep link support for mobile app
- **Database Security**: Proper indexing and foreign key constraints
- **Audit Logging**: Comprehensive login history and security monitoring

## 🛡️ Security Features

- **Rate Limiting**: Prevents brute force attacks
- **Token Validation**: 64-character hex API tokens
- **IP Tracking**: Monitor login attempts and suspicious activity
- **Hash Validation**: Telegram WebApp data validation
- **SQL Injection Protection**: Eloquent ORM with prepared statements
- **XSS Protection**: Input validation and sanitization
- **CSRF Protection**: Built-in Laravel CSRF protection

## 📋 Requirements

- **PHP**: 7.1.33 or higher
- **Laravel**: 5.8.38
- **Database**: MySQL 5.7+ or MariaDB 10.2+
- **OS**: CentOS 7 (recommended)
- **Web Server**: Apache/Nginx
- **Redis**: For caching and rate limiting

## 🗄️ Database Schema

### sheerapps_accounts Table

```sql
CREATE TABLE `sheerapps_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `telegram_id` varchar(191) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `photo_url` varchar(500) DEFAULT NULL,
  `api_token` varchar(64) DEFAULT NULL,
  `referrer_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('active','suspended','banned') DEFAULT 'active',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_ip_address` varchar(45) DEFAULT NULL,
  `login_history` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sheerapps_accounts_telegram_id_unique` (`telegram_id`),
  UNIQUE KEY `sheerapps_accounts_api_token_unique` (`api_token`),
  KEY `sheerapps_accounts_referrer_id_index` (`referrer_id`),
  KEY `sheerapps_accounts_status_index` (`status`),
  KEY `sheerapps_accounts_created_at_index` (`created_at`),
  CONSTRAINT `sheerapps_accounts_referrer_id_foreign` FOREIGN KEY (`referrer_id`) REFERENCES `sheerapps_accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 🔧 Installation

### 1. Clone Repository
```bash
git clone <repository-url>
cd laravel
```

### 2. Install Dependencies
```bash
composer install --no-dev --optimize-autoloader
npm install && npm run production
```

### 3. Environment Configuration
Create `.env` file with the following variables:

```env
APP_NAME="SheerApps 4D"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sheerapps_db
DB_USERNAME=sheerapps_user
DB_PASSWORD=your_secure_password_here

TELEGRAM_BOT_TOKEN=your_telegram_bot_token_here
TELEGRAM_BOT_USERNAME=your_bot_username

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

### 4. Database Setup
```bash
php artisan migrate
php artisan db:seed
```

### 5. Generate Application Key
```bash
php artisan key:generate
```

### 6. Set Permissions
```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## 🔐 Telegram Bot Setup

### 1. Create Bot
1. Message [@BotFather](https://t.me/botfather) on Telegram
2. Use `/newbot` command
3. Follow instructions to create bot
4. Save the bot token

### 2. Configure WebApp
1. Use `/setmenubutton` command
2. Set button text (e.g., "Login to SheerApps")
3. Set WebApp URL: `https://yourdomain.com/telegram-login`

### 3. Environment Variables
Add bot token to `.env`:
```env
TELEGRAM_BOT_TOKEN=1234567890:ABCdefGHIjklMNOpqrsTUVwxyz
TELEGRAM_BOT_USERNAME=your_bot_username
```

## 🌐 API Endpoints

### Public Endpoints

#### POST /api/telegram-login
Telegram authentication endpoint.

**Request Body:**
```json
{
  "id": 123456789,
  "first_name": "John",
  "username": "john_doe",
  "photo_url": "https://t.me/i/userpic/320/photo.jpg",
  "hash": "telegram_hash_here",
  "referrer_id": 1
}
```

**Response:** Redirects to React Native app with user data.

### Protected Endpoints (Require Authorization Header)

#### GET /api/profile
Get user profile information.

**Headers:**
```
Authorization: Bearer <api_token>
```

#### POST /api/logout
Logout user and revoke token.

#### GET /api/referrals
Get user's referral list.

#### GET /api/referral-stats
Get referral statistics.

## 🔒 Security Best Practices

### 1. Server Security
```bash
# Update system
sudo yum update -y

# Install security tools
sudo yum install -y fail2ban ufw

# Configure firewall
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 80
sudo ufw allow 443
sudo ufw enable
```

### 2. Database Security
```sql
-- Create dedicated user with limited privileges
CREATE USER 'sheerapps_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON sheerapps_db.* TO 'sheerapps_user'@'localhost';
FLUSH PRIVILEGES;

-- Restrict network access
DELETE FROM mysql.user WHERE Host NOT IN ('localhost', '127.0.0.1');
FLUSH PRIVILEGES;
```

### 3. SSL/TLS Configuration
```nginx
server {
    listen 443 ssl http2;
    server_name yourdomain.com;
    
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;
    ssl_prefer_server_ciphers off;
    
    # Security headers
    add_header X-Frame-Options DENY;
    add_header X-Content-Type-Options nosniff;
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";
}
```

## 📱 React Native Integration

### Deep Link Configuration
The API redirects to your React Native app using the scheme `sheerapps4d://`.

**Example redirect URL:**
```
sheerapps4d://telegram-login-success?username=john_doe&avatar=https://t.me/i/userpic/320/photo.jpg&status=active&token=abc123...&user_id=1&referrer_id=&referral_count=0
```

### React Native Deep Link Handler
```javascript
import { Linking } from 'react-native';

const handleDeepLink = (url) => {
  if (url.startsWith('sheerapps4d://telegram-login-success')) {
    const params = new URLSearchParams(url.split('?')[1]);
    const userData = {
      username: decodeURIComponent(params.get('username')),
      avatar: decodeURIComponent(params.get('avatar')),
      status: decodeURIComponent(params.get('status')),
      token: params.get('token'),
      userId: params.get('user_id'),
      referrerId: params.get('referrer_id'),
      referralCount: params.get('referral_count')
    };
    
    // Handle successful login
    handleTelegramLoginSuccess(userData);
  }
};

Linking.addEventListener('url', handleDeepLink);
```

## 🚨 Monitoring & Logging

### 1. Log Files
- **Application logs**: `storage/logs/laravel.log`
- **Access logs**: `/var/log/nginx/access.log`
- **Error logs**: `/var/log/nginx/error.log`

### 2. Security Monitoring
```bash
# Monitor failed login attempts
tail -f storage/logs/laravel.log | grep "Invalid Telegram hash"

# Monitor rate limit violations
tail -f storage/logs/laravel.log | grep "Rate limit exceeded"

# Check suspicious IP addresses
tail -f storage/logs/laravel.log | grep "Invalid API token"
```

### 3. Database Monitoring
```sql
-- Check recent logins
SELECT telegram_id, name, last_login_at, last_ip_address 
FROM sheerapps_accounts 
WHERE last_login_at > DATE_SUB(NOW(), INTERVAL 24 HOUR);

-- Check referral statistics
SELECT 
    COUNT(*) as total_users,
    COUNT(referrer_id) as referred_users,
    COUNT(*) - COUNT(referrer_id) as direct_users
FROM sheerapps_accounts;
```

## 🔧 Troubleshooting

### Common Issues

1. **Telegram hash validation fails**
   - Check bot token in `.env`
   - Verify WebApp data format
   - Check server time synchronization

2. **Database connection errors**
   - Verify database credentials
   - Check MySQL service status
   - Ensure proper user privileges

3. **Rate limiting issues**
   - Check Redis service status
   - Verify cache configuration
   - Monitor server resources

### Performance Optimization

1. **Database Indexing**
   ```sql
   -- Add composite indexes for common queries
   ALTER TABLE sheerapps_accounts ADD INDEX idx_telegram_status (telegram_id, status);
   ALTER TABLE sheerapps_accounts ADD INDEX idx_referrer_status (referrer_id, status);
   ```

2. **Caching Strategy**
   ```php
   // Cache user profiles
   Cache::remember('user_profile_' . $userId, 3600, function() use ($userId) {
       return SheerappsAccount::find($userId);
   });
   ```

3. **Queue Processing**
   ```bash
   # Process background jobs
   php artisan queue:work --daemon
   ```

## 📞 Support

For technical support or security concerns:
- **Email**: security@yourdomain.com
- **Telegram**: @your_support_bot
- **Documentation**: https://docs.yourdomain.com

## 📄 License

This project is proprietary software. All rights reserved.

---

**⚠️ Security Notice**: This application handles sensitive user data. Always follow security best practices and keep dependencies updated.
