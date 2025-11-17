#!/bin/bash
set -e

# Get the directory where the script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

echo "Current directory: $(pwd)"
echo "Listing files in current directory:"
ls -la | head -20

# Verify .env file exists and show its location
if [ -f .env ]; then
    echo ".env file found at: $(pwd)/.env"
    echo ".env file size: $(wc -c < .env) bytes"
    echo "First few lines of .env:"
    head -5 .env
else
    echo "ERROR: .env file not found at: $(pwd)/.env"
    echo "Creating .env file..."
    cat > .env << 'EOF'
APP_ENV=prod
APP_SECRET=change-me-in-production
DATABASE_URL=postgresql://user:password@localhost:5432/database
CORS_ALLOW_ORIGIN=^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$
EOF
    echo ".env file created at: $(pwd)/.env"
    echo "Verifying .env file exists:"
    ls -la .env
fi

# Start PHP built-in server
echo "Starting PHP server from: $(pwd)"
php -S 0.0.0.0:$PORT -t public

