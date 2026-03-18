#!/bin/bash
echo "=== Root contents ==="
ls /var/www/html/
echo "=== Backend contents ==="
ls /var/www/html/backend/
echo "=== Infrastructure contents ==="
ls /var/www/html/backend/Infrastructure/
echo "=== Checking file exists ==="
ls /var/www/html/backend/Infrastructure/database/
echo "=== Permissions ==="
ls -la /var/www/html/backend/Infrastructure/database/
echo "=== Running migrations ==="
php /var/www/html/backend/Infrastructure/database/migrate.php
echo "=== Starting Apache ==="
apache2-foreground