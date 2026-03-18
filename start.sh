#!/bin/bash
php /var/www/html/backend/Infrastructure/database/migrate.php
apache2-foreground
