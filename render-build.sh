#!/bin/bash
set -e

# Get the directory where the script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

echo "Build script running from: $(pwd)"
echo "Listing files before creating .env:"
ls -la | grep -E "^-|^d" | head -15

# Create .env file if it doesn't exist (required by Symfony)
# This file will be used as fallback, but environment variables from Render will override it
create_env_file() {
    if [ ! -f .env ]; then
        echo "Creating .env file at: $(pwd)/.env"
        cat > .env << 'EOF'
APP_ENV=prod
APP_SECRET=change-me-in-production
DATABASE_URL=postgresql://user:password@localhost:5432/database
CORS_ALLOW_ORIGIN=^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$
EOF
        echo ".env file created successfully"
        ls -la .env
    else
        echo ".env file already exists at: $(pwd)/.env"
        ls -la .env
    fi
}

# Create .env file first
create_env_file

# Install dependencies (production only, no dev dependencies)
composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Generate autoloader
composer dump-autoload --no-scripts --optimize

# Note: We don't clear cache here because it requires .env to be properly configured
# Cache will be cleared automatically on first request if needed

# Verify .env file exists before finishing
if [ -f .env ]; then
    echo ".env file verified at: $(pwd)/.env"
    echo "Content preview:"
    head -3 .env
else
    echo "ERROR: .env file not found after build!"
    exit 1
fi

