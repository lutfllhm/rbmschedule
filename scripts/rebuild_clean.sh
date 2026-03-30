#!/bin/bash

echo "=========================================="
echo "Rebuild Docker - Clean Install"
echo "=========================================="
echo ""

cd /var/www/rbmschedule

# Stop dan remove semua
echo "1. Stopping and removing containers..."
docker compose down -v
docker system prune -f

# Remove dangling images
echo "2. Cleaning Docker images..."
docker image prune -f

# Remove networks
echo "3. Cleaning Docker networks..."
docker network prune -f

# Restart Docker service
echo "4. Restarting Docker service..."
sudo systemctl restart docker
sleep 5

# Build ulang tanpa cache
echo "5. Building images from scratch..."
docker compose build --no-cache

# Start containers
echo "6. Starting containers..."
docker compose up -d

# Wait
echo "7. Waiting for containers to be ready..."
sleep 20

# Check status
echo ""
echo "=========================================="
echo "Container Status:"
echo "=========================================="
docker compose ps

echo ""
echo "=========================================="
echo "Testing Connection:"
echo "=========================================="
curl -I http://localhost/

echo ""
echo "=========================================="
echo "Done!"
echo "=========================================="
