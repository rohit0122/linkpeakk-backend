# Production Deployment Guide

Follow these steps to install and configure the **LinkPeakK** backend on a production server (Ubuntu/Debian recommended).

## 1. Environment Setup

### Install Required Software

```bash
# Update package list
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2 and extensions
sudo apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-curl php8.2-xml php8.2-mbstring php8.2-zip php8.2-bcmath php8.2-intl

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Nginx and MySQL (if not using managed DB)
sudo apt install -y nginx mysql-server
```

## 2. Project Setup

### Clone Repository

```bash
cd /var/www
git clone <your-repo-url> linkpeakk-backend
cd linkpeakk-backend
```

### Install Dependencies

```bash
composer install --optimize-autoloader --no-dev
```

### Environment Configuration

```bash
cp .env.example .env
nano .env
```

**Critical `.env` settings for Production:**

```ini
APP_NAME=LinkPeakK
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.linkpeakk.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=linkpeakk_prod
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Queue Driver (Using database is easiest for simple setups)
QUEUE_CONNECTION=database

# Mail Configuration (SES, Postmark, etc.)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="hello@linkpeakk.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Generate Application Key

```bash
php artisan key:generate
```

### Link Storage

```bash
php artisan storage:link
```

## 3. Database & Migrations

```bash
# Create database (if needed)
mysql -u root -p -e "CREATE DATABASE linkpeakk_prod;"

# Run Migrations & Seeders
php artisan migrate --force
php artisan db:seed --force
```

## 4. Permissions

```bash
# Set ownership to web server user (usually www-data)
sudo chown -R www-data:www-data /var/www/linkpeakk-backend
sudo chmod -R 775 /var/www/linkpeakk-backend/storage
sudo chmod -R 775 /var/www/linkpeakk-backend/bootstrap/cache
```

## 5. Web Server Configuration (Nginx)

Create a new config file: `/etc/nginx/sites-available/linkpeakk`

```nginx
server {
    listen 80;
    server_name api.linkpeakk.com;
    root /var/www/linkpeakk-backend/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/linkpeakk /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

## 5.1 Alternate: Web Server Configuration (Apache)

If you prefer Apache, ensure `mod_rewrite` is enabled:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

Create config: `/etc/apache2/sites-available/linkpeakk.conf`

```apache
<VirtualHost *:80>
    ServerName api.linkpeakk.com
    DocumentRoot /var/www/linkpeakk-backend/public

    # Redirect HTTP to HTTPS
    RewriteEngine on
    RewriteCond %{SERVER_NAME} =api.linkpeakk.com
    RewriteRule ^ https://%{SERVER_NAME}%{REQUEST_URI} [END,NE,R=permanent]
</VirtualHost>

<VirtualHost *:443>
    ServerName api.linkpeakk.com
    DocumentRoot /var/www/linkpeakk-backend/public

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/api.linkpeakk.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/api.linkpeakk.com/privkey.pem

    <Directory /var/www/linkpeakk-backend/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

Enable the site:

```bash
sudo a2ensite linkpeakk
sudo systemctl reload apache2
```

## 6. Optimization Commands

Run these commands everytime you deploy new code:

```bash
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache
```

## 7. Cron Job (Task Scheduler)

This is **essential** for automated plan expiry emails (`linkpeak:send-expiry-warnings`) and other scheduled tasks.

Open crontab:

```bash
sudo crontab -u www-data -e
```

Add the following line to run the Laravel scheduler every minute:

```cron
* * * * * cd /var/www/linkpeakk-backend && php artisan schedule:run >> /dev/null 2>&1
```

## 8. Queue Worker

To process emails and background jobs efficiently, use Supervisor.

Install Supervisor:

```bash
sudo apt install -y supervisor
```

Create config: `/etc/supervisor/conf.d/linkpeakk-worker.conf`

```ini
[program:linkpeakk-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/linkpeakk-backend/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/linkpeakk-backend/storage/logs/worker.log
stopwaitsecs=3600
```

Start Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start linkpeakk-worker:*
```
