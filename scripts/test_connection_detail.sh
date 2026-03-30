#!/bin/bash

echo "=========================================="
echo "Test Koneksi Detail - RBM Schedule"
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

# 1. Test localhost
print_info "1. Test koneksi ke localhost..."
LOCALHOST_TEST=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/ --max-time 5)
if [ "$LOCALHOST_TEST" -eq "302" ] || [ "$LOCALHOST_TEST" -eq "200" ]; then
    print_success "Localhost: OK (HTTP $LOCALHOST_TEST)"
else
    print_error "Localhost: GAGAL (HTTP $LOCALHOST_TEST)"
fi
echo ""

# 2. Test 127.0.0.1
print_info "2. Test koneksi ke 127.0.0.1..."
LOOPBACK_TEST=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1/ --max-time 5)
if [ "$LOOPBACK_TEST" -eq "302" ] || [ "$LOOPBACK_TEST" -eq "200" ]; then
    print_success "127.0.0.1: OK (HTTP $LOOPBACK_TEST)"
else
    print_error "127.0.0.1: GAGAL (HTTP $LOOPBACK_TEST)"
fi
echo ""

# 3. Test IP lokal
print_info "3. Test koneksi ke IP lokal..."
LOCAL_IP=$(ip addr show | grep "inet " | grep -v "127.0.0.1" | head -1 | awk '{print $2}' | cut -d'/' -f1)
print_info "IP Lokal: $LOCAL_IP"
LOCAL_TEST=$(curl -s -o /dev/null -w "%{http_code}" http://$LOCAL_IP/ --max-time 5)
if [ "$LOCAL_TEST" -eq "302" ] || [ "$LOCAL_TEST" -eq "200" ]; then
    print_success "IP Lokal: OK (HTTP $LOCAL_TEST)"
else
    print_error "IP Lokal: GAGAL (HTTP $LOCAL_TEST)"
fi
echo ""

# 4. Test IP publik dari dalam VPS
print_info "4. Test koneksi ke IP publik dari dalam VPS..."
PUBLIC_IP=$(curl -4 -s ifconfig.me)
print_info "IP Publik: $PUBLIC_IP"
PUBLIC_TEST=$(curl -s -o /dev/null -w "%{http_code}" http://$PUBLIC_IP/ --max-time 10)
if [ "$PUBLIC_TEST" -eq "302" ] || [ "$PUBLIC_TEST" -eq "200" ]; then
    print_success "IP Publik dari dalam: OK (HTTP $PUBLIC_TEST)"
    print_success "WEBSITE BISA DIAKSES DARI LUAR!"
else
    print_error "IP Publik dari dalam: GAGAL (HTTP $PUBLIC_TEST)"
    print_error "FIREWALL HOSTINGER BLOCK AKSES DARI LUAR!"
fi
echo ""

# 5. Cek port listening
print_info "5. Cek port yang listening..."
echo "Port 80:"
sudo netstat -tlnp | grep :80 || sudo ss -tlnp | grep :80
echo ""
echo "Port 443:"
sudo netstat -tlnp | grep :443 || sudo ss -tlnp | grep :443
echo ""

# 6. Cek Docker container
print_info "6. Status Docker containers..."
docker compose ps
echo ""

# 7. Cek iptables
print_info "7. Cek iptables rules..."
sudo iptables -L INPUT -n | grep -E "ACCEPT|DROP|REJECT" | head -10
echo ""

# 8. Cek UFW
print_info "8. Status UFW..."
sudo ufw status | grep -E "80|443"
echo ""

# 9. Test DNS
print_info "9. Test DNS resolution..."
nslookup labelrbm.iwareid.com 2>&1 | grep -A 2 "Name:"
echo ""

# 10. Kesimpulan
echo "=========================================="
echo "KESIMPULAN"
echo "=========================================="
echo ""

if [ "$LOCALHOST_TEST" -eq "302" ] && [ "$PUBLIC_TEST" -ne "302" ]; then
    print_error "Aplikasi berjalan normal di VPS"
    print_error "Tapi TIDAK BISA diakses dari luar"
    echo ""
    print_info "PENYEBAB: Firewall Hostinger block port 80/443"
    echo ""
    print_info "SOLUSI:"
    echo "  1. Login ke hPanel Hostinger: https://hpanel.hostinger.com/"
    echo "  2. Pilih VPS → Firewall/Security"
    echo "  3. Buka port 80 dan 443"
    echo ""
    print_info "ATAU hubungi Hostinger Support:"
    echo "  Live Chat: https://www.hostinger.com/contact"
    echo "  Katakan: 'Mohon buka port 80 dan 443 untuk VPS IP $PUBLIC_IP'"
    echo ""
elif [ "$LOCALHOST_TEST" -eq "302" ] && [ "$PUBLIC_TEST" -eq "302" ]; then
    print_success "WEBSITE SUDAH BISA DIAKSES!"
    echo ""
    print_info "URL Akses:"
    echo "  - Via IP: http://$PUBLIC_IP/"
    echo "  - Via Domain: http://labelrbm.iwareid.com/ (setelah setup DNS)"
    echo ""
    print_info "Langkah selanjutnya:"
    echo "  1. Setup DNS domain"
    echo "  2. Setup SSL certificate"
    echo "  3. Ganti password di .env"
    echo ""
else
    print_error "Ada masalah dengan aplikasi di VPS"
    echo ""
    print_info "Cek log Docker:"
    echo "  docker compose logs --tail=50 nginx"
    echo "  docker compose logs --tail=50 app"
fi

echo "=========================================="
