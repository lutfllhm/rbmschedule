#!/bin/bash

# Script untuk memperbaiki masalah connection timeout
# Jalankan dengan: bash scripts/fix_connection.sh

set -e

echo "=========================================="
echo "RBM Schedule - Connection Fix Script"
echo "=========================================="
echo ""

# Warna untuk output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Fungsi untuk print dengan warna
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ $1${NC}"
}

# 1. Cek apakah di folder yang benar
if [ ! -f "docker-compose.yml" ]; then
    print_error "File docker-compose.yml tidak ditemukan!"
    print_info "Pastikan Anda menjalankan script ini dari folder /opt/rbmschedule"
    exit 1
fi
print_success "Folder project ditemukan"

# 2. Cek apakah Docker terinstall
if ! command -v docker &> /dev/null; then
    print_error "Docker tidak terinstall!"
    print_info "Install Docker terlebih dahulu: https://docs.docker.com/engine/install/"
    exit 1
fi
print_success "Docker terinstall"

# 3. Cek apakah file .env ada
if [ ! -f ".env" ]; then
    print_info "File .env tidak ditemukan, membuat dari .env.example..."
    if [ -f ".env.docker.example" ]; then
        cp .env.docker.example .env
        print_success "File .env dibuat dari .env.docker.example"
    else
        print_error "File .env.docker.example tidak ditemukan!"
        exit 1
    fi
else
    print_success "File .env ditemukan"
fi

# 4. Stop container yang sedang berjalan
print_info "Menghentikan container yang sedang berjalan..."
docker compose down
print_success "Container dihentikan"

# 5. Rebuild dan start container
print_info "Membangun ulang dan menjalankan container..."
docker compose up -d --build
print_success "Container dijalankan"

# 6. Tunggu container siap
print_info "Menunggu container siap (30 detik)..."
sleep 30

# 7. Cek status container
print_info "Mengecek status container..."
docker compose ps

# 8. Cek apakah semua container running
NGINX_STATUS=$(docker compose ps nginx | grep -c "Up" || echo "0")
APP_STATUS=$(docker compose ps app | grep -c "Up" || echo "0")
MYSQL_STATUS=$(docker compose ps mysql | grep -c "Up" || echo "0")

if [ "$NGINX_STATUS" -eq "0" ]; then
    print_error "Container nginx tidak berjalan!"
    docker compose logs --tail=50 nginx
    exit 1
fi

if [ "$APP_STATUS" -eq "0" ]; then
    print_error "Container app tidak berjalan!"
    docker compose logs --tail=50 app
    exit 1
fi

if [ "$MYSQL_STATUS" -eq "0" ]; then
    print_error "Container mysql tidak berjalan!"
    docker compose logs --tail=50 mysql
    exit 1
fi

print_success "Semua container berjalan dengan baik"

# 9. Cek firewall UFW (jika terinstall)
if command -v ufw &> /dev/null; then
    print_info "Mengecek firewall UFW..."
    
    # Cek apakah UFW aktif
    UFW_STATUS=$(sudo ufw status | grep -c "Status: active" || echo "0")
    
    if [ "$UFW_STATUS" -gt "0" ]; then
        print_info "UFW aktif, membuka port 80 dan 443..."
        sudo ufw allow 80/tcp
        sudo ufw allow 443/tcp
        print_success "Port 80 dan 443 dibuka"
    else
        print_info "UFW tidak aktif"
    fi
else
    print_info "UFW tidak terinstall, skip firewall check"
fi

# 10. Test koneksi dari dalam VPS
print_info "Testing koneksi dari dalam VPS..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/ || echo "000")

if [ "$HTTP_CODE" -eq "200" ] || [ "$HTTP_CODE" -eq "301" ] || [ "$HTTP_CODE" -eq "302" ]; then
    print_success "Koneksi dari dalam VPS berhasil (HTTP $HTTP_CODE)"
else
    print_error "Koneksi dari dalam VPS gagal (HTTP $HTTP_CODE)"
    print_info "Cek log nginx:"
    docker compose logs --tail=30 nginx
fi

# 11. Informasi akhir
echo ""
echo "=========================================="
echo "Status Akhir"
echo "=========================================="
echo ""

# Dapatkan IP publik VPS
PUBLIC_IP=$(curl -s ifconfig.me || echo "tidak_terdeteksi")

print_info "IP Publik VPS: $PUBLIC_IP"
print_info "URL Akses: http://$PUBLIC_IP/"
print_info "URL Domain: http://labelrbm.iwareid.com/"

echo ""
print_info "Jika masih tidak bisa diakses dari luar:"
echo "  1. Cek Security Group di cloud provider (AWS, DigitalOcean, dll)"
echo "  2. Pastikan port 80 dan 443 terbuka untuk 0.0.0.0/0"
echo "  3. Cek DNS domain sudah mengarah ke IP: $PUBLIC_IP"
echo ""

print_info "Untuk melihat log real-time:"
echo "  docker compose logs -f nginx"
echo "  docker compose logs -f app"
echo ""

print_success "Script selesai!"
