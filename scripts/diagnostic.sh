#!/bin/bash

echo "=========================================="
echo "RBM Schedule - Diagnostic Script"
echo "=========================================="
echo ""

# 1. Cek IP Publik
echo "1. IP Publik VPS:"
curl -s ifconfig.me
echo ""
echo ""

# 2. Cek port listening
echo "2. Port yang listening:"
sudo netstat -tlnp | grep -E ':80|:443' || sudo ss -tlnp | grep -E ':80|:443'
echo ""

# 3. Cek firewall UFW
echo "3. Status Firewall UFW:"
sudo ufw status
echo ""

# 4. Cek iptables
echo "4. Iptables rules:"
sudo iptables -L -n | grep -E 'ACCEPT|DROP|REJECT' | head -20
echo ""

# 5. Test koneksi localhost
echo "5. Test koneksi dari dalam VPS:"
curl -I http://localhost/ 2>&1 | head -10
echo ""

# 6. Test koneksi ke container
echo "6. Test koneksi ke nginx container:"
curl -I http://localhost:80/ 2>&1 | head -10
echo ""

# 7. Cek DNS
echo "7. DNS Resolution:"
nslookup labelrbm.iwareid.com
echo ""

# 8. Cek log nginx
echo "8. Log Nginx (10 baris terakhir):"
docker compose logs --tail=10 nginx
echo ""

# 9. Cek docker network
echo "9. Docker Network:"
docker network ls
echo ""

# 10. Cek container inspect
echo "10. Nginx Container Ports:"
docker inspect rbmschedule-nginx | grep -A 10 "Ports"
echo ""

echo "=========================================="
echo "Diagnostic selesai!"
echo "=========================================="
