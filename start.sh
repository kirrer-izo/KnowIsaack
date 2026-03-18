#!/bin/bash
php /var/www/html/backend/Infrastructure/Database/migrate.php
apache2-foreground
