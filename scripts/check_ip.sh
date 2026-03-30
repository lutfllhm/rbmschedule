#!/bin/bash

echo "=========================================="
echo "Cek IP Address VPS"
echo "=========================================="
echo ""

echo "IPv4 Address:"
curl -4 -s ifconfig.me || echo "Tidak ada IPv4"
echo ""

echo "IPv6 Address:"
curl -6 -s ifconfig.me || echo "Tidak ada IPv6"
echo ""

echo "Semua IP di interface:"
ip addr show | grep -E 'inet |inet6' | grep -v '127.0.0.1' | grep -v '::1'
echo ""

echo "=========================================="
