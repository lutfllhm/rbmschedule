#!/bin/bash

# Script untuk push database ke VPS
# Usage: ./scripts/push_database.sh

VPS_HOST="148.230.100.44"
VPS_USER="root"
DB_FILE="database.sql"
REMOTE_PATH="/tmp/rbm_database.sql"
DB_NAME="rbm_schedule"

echo "=== Push Database ke VPS ==="
echo "VPS: $VPS_USER@$VPS_HOST"
echo ""

# 1. Upload file database.sql ke VPS
echo "📤 Uploading database.sql ke VPS..."
scp $DB_FILE $VPS_USER@$VPS_HOST:$REMOTE_PATH

if [ $? -ne 0 ]; then
    echo "❌ Gagal upload database.sql"
    exit 1
fi

echo "✅ Database file uploaded"
echo ""

# 2. Import database di VPS
echo "📥 Importing database ke MySQL..."
ssh $VPS_USER@$VPS_HOST << 'ENDSSH'
    # Cek apakah database sudah ada
    DB_EXISTS=$(mysql -u root -e "SHOW DATABASES LIKE 'rbm_schedule';" | grep rbm_schedule)
    
    if [ -z "$DB_EXISTS" ]; then
        echo "📦 Database belum ada, membuat database baru..."
        mysql -u root -e "CREATE DATABASE rbm_schedule;"
    else
        echo "📦 Database sudah ada, akan di-update..."
    fi
    
    # Import database
    echo "⚙️  Importing database.sql..."
    mysql -u root rbm_schedule < /tmp/rbm_database.sql
    
    if [ $? -eq 0 ]; then
        echo "✅ Database berhasil di-import"
        
        # Tampilkan info database
        echo ""
        echo "📊 Database Info:"
        mysql -u root -e "USE rbm_schedule; SHOW TABLES;"
        
        echo ""
        echo "👥 Users:"
        mysql -u root -e "USE rbm_schedule; SELECT id, username, role, created_at FROM users;"
        
        echo ""
        echo "📋 Schedules count:"
        mysql -u root -e "USE rbm_schedule; SELECT COUNT(*) as total_schedules FROM schedules;"
    else
        echo "❌ Gagal import database"
        exit 1
    fi
    
    # Cleanup
    rm /tmp/rbm_database.sql
    echo "🧹 Temporary file cleaned"
ENDSSH

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Database berhasil di-push ke VPS!"
    echo ""
    echo "📝 Catatan:"
    echo "   - Database: rbm_schedule"
    echo "   - Default users sudah dibuat (admin/operator)"
    echo "   - Jangan lupa update .env di VPS dengan kredensial database"
else
    echo ""
    echo "❌ Terjadi kesalahan saat push database"
    exit 1
fi
