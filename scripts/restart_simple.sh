#!/bin/bash

echo "=========================================="
echo "Restart Docker - Simple Mode"
echo "=========================================="
echo ""

cd /var/www/rbmschedule

# Stop containers
echo "1. Stopping containers..."
docker compose down

# Remove old networks
echo "2. Removing old networks..."
docker network rm rbmschedule_default 2>/dev/null || true
docker network prune -f

# Start dengan konfigurasi baru
echo "3. Starting containers with new config..."
docker compose up -d

# Wait
echo "4. Waiting 30 seconds for containers to be ready..."
sleep 30

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

# Test localhost
echo "Test 1: localhost"
curl -I http://localhost/ 2>&1 | head -5

echo ""
echo "Test 2: 127.0.0.1"
curl -I http://127.0.0.1/ 2>&1 | head -5

echo ""
echo "Test 3: IP Publik"
PUBLIC_IP=$(curl -4 -s ifconfig.me)
echo "IP: $PUBLIC_IP"
curl -I http://$PUBLIC_IP/ 2>&1 | head -5

echo ""
echo "=========================================="
echo "Logs (jika ada error):"
echo "=========================================="
docker compose logs --tail=20 app

echo ""
echo "=========================================="
echo "Done!"
echo "=========================================="
echo ""
echo "Jika masih error, jalankan:"
echo "  docker compose logs -f app"
echo "  docker compose logs -f nginx"
