#!/bin/bash

# Script untuk push database ke VPS (dengan password MySQL)
# Usage: ./scripts/push_database_secure.sh

VPS_HOST="148.230.100.44"
VPS_USER="root"
DB_FILE="database.sql"
REMOTE_PATH="/tmp/rbm_database.sql"
DB_NAME="rbm_schedule"

echo "=== Push Database ke VPS (Secure) ==="
echo "VPS: $VPS_USER@$VPS_HOST"
echo ""

# Tanya password MySQL
read -sp "MySQL root password di VPS (tekan Enter jika tidak ada): " MYSQL_PASS
echo ""
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

if [ -z "$MYSQL_PASS" ]; then
    # Tanpa password
    ssh $VPS_USER@$VPS_HOST << 'ENDSSH'
        # Cek apakah database sudah ada
        DB_EXISTS=$(mysql -u root -e "SHOW DATABASES LIKE 'rbm_schedule';" 2>/dev/null | grep rbm_schedule)
        
        if [ -z "$DB_EXISTS" ]; then
            echo "📦 Database belum ada, membuat database baru..."
            mysql -u root -e "CREATE DATABASE rbm_schedule;" 2>/dev/null
        else
            echo "📦 Database sudah ada, akan di-update..."
        fi
        
        # Import database
        echo "⚙️  Importing database.sql..."
        mysql -u root rbm_schedule < /tmp/rbm_database.sql 2>/dev/null
        
        if [ $? -eq 0 ]; then
            echo "✅ Database berhasil di-import"
            
            # Tampilkan info database
            echo ""
            echo "📊 Database Info:"
            mysql -u root -e "USE rbm_schedule; SHOW TABLES;" 2>/dev/null
            
            echo ""
            echo "👥 Users:"
            mysql -u root -e "USE rbm_schedule; SELECT id, username, role, created_at FROM users;" 2>/dev/null
            
            echo ""
            echo "📋 Schedules count:"
            mysql -u root -e "USE rbm_schedule; SELECT COUNT(*) as total_schedules FROM schedules;" 2>/dev/null
        else
            echo "❌ Gagal import database"
            exit 1
        fi
        
        # Cleanup
        rm /tmp/rbm_database.sql
        echo "🧹 Temporary file cleaned"
ENDSSH
else
    # Dengan password
    ssh $VPS_USER@$VPS_HOST bash -s "$MYSQL_PASS" << 'ENDSSH'
        MYSQL_PASS="$1"
        
        # Cek apakah database sudah ada
        DB_EXISTS=$(mysql -u root -p"$MYSQL_PASS" -e "SHOW DATABASES LIKE 'rbm_schedule';" 2>/dev/null | grep rbm_schedule)
        
        if [ -z "$DB_EXISTS" ]; then
            echo "📦 Database belum ada, membuat database baru..."
            mysql -u root -p"$MYSQL_PASS" -e "CREATE DATABASE rbm_schedule;" 2>/dev/null
        else
            echo "📦 Database sudah ada, akan di-update..."
        fi
        
        # Import database
        echo "⚙️  Importing database.sql..."
        mysql -u root -p"$MYSQL_PASS" rbm_schedule < /tmp/rbm_database.sql 2>/dev/null
        
        if [ $? -eq 0 ]; then
            echo "✅ Database berhasil di-import"
            
            # Tampilkan info database
            echo ""
            echo "📊 Database Info:"
            mysql -u root -p"$MYSQL_PASS" -e "USE rbm_schedule; SHOW TABLES;" 2>/dev/null
            
            echo ""
            echo "👥 Users:"
            mysql -u root -p"$MYSQL_PASS" -e "USE rbm_schedule; SELECT id, username, role, created_at FROM users;" 2>/dev/null
            
            echo ""
            echo "📋 Schedules count:"
            mysql -u root -p"$MYSQL_PASS" -e "USE rbm_schedule; SELECT COUNT(*) as total_schedules FROM schedules;" 2>/dev/null
        else
            echo "❌ Gagal import database"
            exit 1
        fi
        
        # Cleanup
        rm /tmp/rbm_database.sql
        echo "🧹 Temporary file cleaned"
ENDSSH
fi

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Database berhasil di-push ke VPS!"
    echo ""
    echo "📝 Catatan:"
    echo "   - Database: rbm_schedule"
    echo "   - Default users: admin/admin123, operator/operator123"
    echo "   - Jangan lupa update .env di VPS dengan kredensial database"
    echo ""
    echo "🔧 Langkah selanjutnya:"
    echo "   1. SSH ke VPS: ssh root@148.230.100.44"
    echo "   2. Edit .env: nano /var/www/html/rbmschedule/.env"
    echo "   3. Set DB_NAME=rbm_schedule, DB_USER=root, DB_PASS=..."
else
    echo ""
    echo "❌ Terjadi kesalahan saat push database"
    exit 1
fi
