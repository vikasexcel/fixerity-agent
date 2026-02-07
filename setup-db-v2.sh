#!/bin/bash

# Script to set up db_fixerity_v2 database
# This script creates the database, runs migrations, and seeds it (excluding test data seeders)

echo "=========================================="
echo "Setting up db_fixerity_v2 database"
echo "=========================================="

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check if .env file exists
if [ ! -f .env ]; then
    echo -e "${RED}Error: .env file not found!${NC}"
    echo "Please copy .env.example to .env and configure it first."
    exit 1
fi

# Read database credentials from .env
DB_HOST=$(grep "^DB_HOST=" .env | cut -d '=' -f2 | tr -d ' ')
DB_PORT=$(grep "^DB_PORT=" .env | cut -d '=' -f2 | tr -d ' ')
DB_USERNAME=$(grep "^DB_USERNAME=" .env | cut -d '=' -f2 | tr -d ' ')
DB_PASSWORD=$(grep "^DB_PASSWORD=" .env | cut -d '=' -f2 | tr -d ' ')
DB_DATABASE="db_fixerity_v2"

# Default values if not set
DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-3306}
DB_USERNAME=${DB_USERNAME:-root}
DB_PASSWORD=${DB_PASSWORD:-}

echo -e "${YELLOW}Database Configuration:${NC}"
echo "Host: $DB_HOST"
echo "Port: $DB_PORT"
echo "Username: $DB_USERNAME"
echo "Database: $DB_DATABASE"
echo ""

# Create database
echo -e "${YELLOW}Step 1: Creating database $DB_DATABASE...${NC}"
if [ -z "$DB_PASSWORD" ]; then
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -e "CREATE DATABASE IF NOT EXISTS $DB_DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
else
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS $DB_DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
fi

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Database created successfully${NC}"
else
    echo -e "${RED}✗ Failed to create database. Please check your MySQL credentials.${NC}"
    exit 1
fi

# Set TESTINGDB=true temporarily for migrations and seeding
echo -e "${YELLOW}Step 2: Running migrations...${NC}"
export TESTINGDB=true
php artisan migrate --force

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Migrations completed successfully${NC}"
else
    echo -e "${RED}✗ Migrations failed${NC}"
    exit 1
fi

# Run seeders (DatabaseSeeder already excludes BuyerAgentTestDataSeeder, CompleteProviderSeeder, ProviderAgentDataSeeder)
echo -e "${YELLOW}Step 3: Running seeders (excluding test data seeders)...${NC}"
php artisan db:seed --force

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Seeders completed successfully${NC}"
else
    echo -e "${RED}✗ Seeders failed${NC}"
    exit 1
fi

# Reset TESTINGDB to false
unset TESTINGDB

echo ""
echo -e "${GREEN}=========================================="
echo "Database setup completed successfully!"
echo "==========================================${NC}"
echo ""
echo "To use db_fixerity_v2, set TESTINGDB=true in your .env file"
echo "To use db_fixerity (default), set TESTINGDB=false or remove it from .env"
