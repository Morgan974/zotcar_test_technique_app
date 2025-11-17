FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    bash \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    postgresql-dev \
    oniguruma-dev

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock* ./

# Install PHP dependencies
# Update lock file if composer.json has changed, then install
RUN if [ -f composer.lock ]; then \
        composer install --no-scripts --no-autoloader || composer update --no-scripts --no-autoloader; \
    else \
        composer update --no-scripts --no-autoloader; \
    fi

# Copy application files
COPY . .

# Create .env file if it doesn't exist (required by Symfony)
# This file will be used as fallback, but environment variables from Render will override it
RUN if [ ! -f .env ]; then \
        echo "APP_ENV=prod" > .env && \
        echo "APP_SECRET=change-me-in-production" >> .env && \
        echo "DATABASE_URL=postgresql://user:password@localhost:5432/database" >> .env && \
        echo "CORS_ALLOW_ORIGIN=^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$" >> .env && \
        echo "DEFAULT_URI=http://localhost" >> .env && \
        echo ".env file created in Dockerfile"; \
    else \
        echo ".env file already exists"; \
    fi

# Install/update dependencies, run scripts and generate autoloader
# Skip scripts during build to avoid requiring .env file
# Ensure dev dependencies are installed for development
RUN composer update --no-interaction --no-scripts --prefer-dist && \
    composer dump-autoload --no-scripts

# Create var directory if it doesn't exist and set permissions
RUN mkdir -p /var/www/html/var && \
    chown -R www-data:www-data /var/www/html/var

# Make render-start.sh executable
RUN chmod +x /var/www/html/render-start.sh

EXPOSE 8000

# Use render-start.sh to start the application (handles .env and PORT)
# The router.php file ensures Symfony routes work correctly with PHP built-in server
CMD ["/var/www/html/render-start.sh"]

