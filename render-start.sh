#!/bin/bash
set -e

# Get the directory where the script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

echo "=== Starting Application ==="
echo "Current directory: $(pwd)"

# Verify .env file exists and create if needed
if [ ! -f .env ]; then
    echo "Creating .env file..."
    cat > .env << 'EOF'
APP_ENV=prod
APP_SECRET=change-me-in-production
DATABASE_URL=postgresql://user:password@localhost:5432/database
CORS_ALLOW_ORIGIN=^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$
DEFAULT_URI=http://localhost
EOF
    echo ".env file created"
fi

# Verify .env file exists
if [ -f .env ]; then
    echo ".env file found at: $(pwd)/.env"
else
    echo "ERROR: .env file not found!"
    exit 1
fi

# Create var directory if it doesn't exist
mkdir -p var
chmod -R 777 var

# Start PHP built-in server
echo "Starting PHP server on port ${PORT:-8000}..."
echo "Server will be accessible at: http://0.0.0.0:${PORT:-8000}"
exec php -S 0.0.0.0:${PORT:-8000} -t public

