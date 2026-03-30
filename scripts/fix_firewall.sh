#!/bin/bash

echo "=========================================="
echo "Fix Firewall & Network - RBM Schedule"
echo "=========================================="
echo ""

# Warna
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

print_success() { echo -e "${GREEN}✓ $1${NC}"; }
print_error() { echo -e "${RED}✗ $1${NC}"; }
print_info() { echo -e "${YELLOW}ℹ $1${NC}"; }

# 1. Flush iptables rules yang mungkin blocking
print_info "Membersihkan iptables rules..."
sudo iptables -F
sudo iptables -X
sudo iptables -t nat -F
sudo iptables -t nat -X
sudo iptables -t mangle -F
sudo iptables -t mangle -X
sudo iptables -P INPUT ACCEPT
sudo iptables -P FORWARD ACCEPT
sudo iptables -P OUTPUT ACCEPT
print_success "Iptables rules dibersihkan"

# 2. Setup UFW dengan benar
print_info "Konfigurasi UFW..."
sudo ufw --force reset
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 22/tcp comment 'SSH'
sudo ufw allow 80/tcp comment 'HTTP'
sudo ufw allow 443/tcp comment 'HTTPS'
sudo ufw allow 3306/tcp comment 'MySQL'
sudo ufw --force enable
print_success "UFW dikonfigurasi"

# 3. Restart Docker untuk refresh network
print_info "Restart Docker containers..."
cd /var/www/rbmschedule
docker compose down
sleep 3
docker compose up -d
sleep 10
print_success "Docker containers restarted"

# 4. Test koneksi
print_info "Testing koneksi..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/ || echo "000")
if [ "$HTTP_CODE" -eq "302" ] || [ "$HTTP_CODE" -eq "200" ]; then
    print_success "Koneksi dari dalam VPS: OK (HTTP $HTTP_CODE)"
else
    print_error "Koneksi dari dalam VPS: GAGAL (HTTP $HTTP_CODE)"
fi

# 5. Informasi akses
echo ""
echo "=========================================="
echo "Informasi Akses"
echo "=========================================="
IP_V4=$(curl -4 -s ifconfig.me)
print_info "IPv4 Address: $IP_V4"
print_info "URL Akses: http://$IP_V4/"
print_info "Domain: http://labelrbm.iwareid.com/"
echo ""

# 6. Test dari luar (jika curl bisa akses external)
print_info "Testing akses dari luar..."
EXTERNAL_TEST=$(curl -s -o /dev/null -w "%{http_code}" http://$IP_V4/ --max-time 5 || echo "000")
if [ "$EXTERNAL_TEST" -eq "302" ] || [ "$EXTERNAL_TEST" -eq "200" ]; then
    print_success "Akses dari luar: OK (HTTP $EXTERNAL_TEST)"
    echo ""
    print_success "WEBSITE SUDAH BISA DIAKSES!"
    print_info "Buka browser: http://$IP_V4/"
else
    print_error "Akses dari luar: MASIH GAGAL (HTTP $EXTERNAL_TEST)"
    echo ""
    print_info "Kemungkinan penyebab:"
    echo "  1. Security Group di cloud provider masih block"
    echo "  2. Network firewall di level datacenter"
    echo ""
    print_info "Solusi:"
    echo "  1. Login ke dashboard cloud provider Anda"
    echo "  2. Cari menu Security Group / Firewall"
    echo "  3. Pastikan port 80 dan 443 terbuka untuk 0.0.0.0/0"
    echo ""
    print_info "Provider yang umum:"
    echo "  - AWS: EC2 → Security Groups → Inbound Rules"
    echo "  - DigitalOcean: Networking → Firewalls"
    echo "  - Google Cloud: VPC Network → Firewall Rules"
    echo "  - Vultr: Settings → Firewall"
    echo "  - Contabo: Control Panel → Firewall"
fi

echo ""
echo "=========================================="
print_success "Script selesai!"
echo "=========================================="
