#!/bin/bash
echo "=== Checking file exists ==="
ls /var/www/html/backend/Infrastructure/database/
echo "=== Running migrations ==="
php /var/www/html/backend/Infrastructure/database/migrate.php
echo "=== Starting Apache ==="
apache2-foreground