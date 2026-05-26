#!/bin/bash

# Load configuration from PHP config file
eval $(php -r "
require_once 'config.php';
echo 'FTP_USER=\"' . FTP_USER . '\" ';
echo 'FTP_PASS=\"' . FTP_PASS . '\" ';
echo 'FTP_HOST=\"' . FTP_HOST . '\" ';
echo 'REMOTE_PATH=\"' . FTP_REMOTE_PATH . '\" ';
")

FILES=(
    "index.html"
    "patients.html"
    "intake.html"
    "patient-details.html"
    "reminders.html"
    "specialist.html"
    "style.css"
    "app.js"
    "api.php"
    "setup_db.php"
)

# Upload main files
for file in "${FILES[@]}"; do
    echo "Uploading $file..."
    curl -u "$FTP_USER:$FTP_PASS" -T "$file" "ftp://$FTP_HOST/$REMOTE_PATH/$file"
done

# Create subdirectories and upload
echo "Creating subdirectories and uploading nested files..."
curl -u "$FTP_USER:$FTP_PASS" -X "MKD $REMOTE_PATH/includes" "ftp://$FTP_HOST/"
curl -u "$FTP_USER:$FTP_PASS" -T "includes/db_connect.php" "ftp://$FTP_HOST/$REMOTE_PATH/includes/db_connect.php"

curl -u "$FTP_USER:$FTP_PASS" -X "MKD $REMOTE_PATH/database" "ftp://$FTP_HOST/"
curl -u "$FTP_USER:$FTP_PASS" -T "database/schema.sql" "ftp://$FTP_HOST/$REMOTE_PATH/database/schema.sql"

echo "Upload Complete!"
