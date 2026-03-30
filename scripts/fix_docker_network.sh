#!/bin/bash

echo "=========================================="
echo "Fix Docker Network Error"
echo "=========================================="
echo ""

cd /var/www/rbmschedule

# Stop containers
echo "Stopping containers..."
docker compose down

# Remove old networks
echo "Removing old networks..."
docker network prune -f

# Restart Docker service
echo "Restarting Docker service..."
sudo systemctl restart docker
sleep 5

# Start containers with fresh network
echo "Starting containers with fresh network..."
docker compose up -d

# Wait for containers to be ready
echo "Waiting for containers to be ready..."
sleep 15

# Check status
echo ""
echo "Container status:"
docker compose ps

echo ""
echo "Testing connection..."
curl -I http://localhost/

echo ""
echo "=========================================="
echo "Done! Docker network fixed."
echo "=========================================="
