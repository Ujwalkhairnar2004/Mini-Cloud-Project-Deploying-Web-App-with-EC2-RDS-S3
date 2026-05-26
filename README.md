EC2 Project Deployment (NGINX + PHP + S3 + RDS)
📌 Overview
This project demonstrates how to deploy a cloud-based web application using:

Amazon Web Services EC2 for application hosting
NGINX as the web server
PHP for backend logic
Amazon S3 for image storage
Amazon RDS for database management

The application allows users to:

Upload an image
Store the image in S3
Save image metadata into RDS
Display the uploaded image URL

🏗️ Architecture
User
   ↓
EC2 (NGINX + PHP)
   ↓
 ┌───────────────┐
 ↓               ↓
S3 Bucket      RDS MySQL
(Image Store)  (Metadata DB)

🚀 Step 1: Launch EC2 Instance

Create an Amazon Linux EC2 instance.

Configure Security Group

Allow:

SSH (22)
HTTP (80)

EC2 acts as the application server.

🔐 Step 2: Connect to EC2
ssh -i key.pem ec2-user@PUBLIC-IP
📦 Step 3: Install Required Software
sudo yum update -y
sudo yum install nginx php php-fpm php-mysqlnd git -y
Installed Components
Software	Purpose
NGINX	Web Server
PHP	Backend Logic
PHP-FPM	Executes PHP with NGINX
php-mysqlnd	MySQL connectivity
▶️ Step 4: Start and Enable Services
sudo systemctl start nginx php-fpm
sudo systemctl enable nginx php-fpm
📁 Step 5: Setup Project Directory
cd /usr/share/nginx/html
mkdir uploads
chmod 777 uploads

The uploads folder is used for temporary file storage.

🖥️ Step 6: Create Frontend File

Create:

nano index.html

Paste:

<!DOCTYPE html>
<html>
<head>
    <title>Image Upload</title>
</head>
<body>

<form action="upload.php" method="post" enctype="multipart/form-data">
    <h2>Upload Image</h2>

    <input type="text" name="title" placeholder="Enter Title" required><br><br>
    <input type="text" name="description" placeholder="Enter Description" required><br><br>
    <input type="file" name="image" required><br><br>

    <input type="submit" value="Upload">
</form>

</body>
</html>
⚙️ Step 7: Create Backend File

Create:

nano upload.php

Paste:

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Dotenv\Dotenv;

// Load env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// DB config
$host = $_ENV['DB_HOST'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];
$db   = $_ENV['DB_NAME'];

// Connect DB
$conn = new mysqli($host, $user, $pass, $db, 3306);

if ($conn->connect_error) {
    die("DB Connection Failed: " . $conn->connect_error);
}

// S3 config
$s3 = new S3Client([
    'version' => 'latest',
    'region'  => $_ENV['AWS_REGION'],
    'credentials' => [
        'key'    => $_ENV['AWS_KEY'],
        'secret' => $_ENV['AWS_SECRET'],
    ],
]);

$bucket = $_ENV['AWS_BUCKET'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['image'])) {

    $title = $_POST['title'];
    $description = $_POST['description'];

    $file = $_FILES['image'];
    $filename = time() . "_" . basename($file['name']);
    $tmpPath = $file['tmp_name'];

    try {

        // Upload to S3
        $s3->putObject([
            'Bucket' => $bucket,
            'Key'    => $filename,
            'SourceFile' => $tmpPath,
        ]);

        // Generate temporary URL
        $cmd = $s3->getCommand('GetObject', [
            'Bucket' => $bucket,
            'Key' => $filename
        ]);

        $request = $s3->createPresignedRequest($cmd, '+20 minutes');
        $imageUrl = (string)$request->getUri();

        // Save to database
        $stmt = $conn->prepare(
            "INSERT INTO images (title, description, image_url)
             VALUES (?, ?, ?)"
        );

        $stmt->bind_param("sss", $title, $description, $imageUrl);
        $stmt->execute();

        echo "Upload Successful<br>";
        echo "Image URL: " . $imageUrl;

    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
🔒 Step 8: Create .env File

Create:

nano .env

Paste:

DB_HOST=your-rds-endpoint
DB_USER=root
DB_PASS=your-password
DB_NAME=image_upload_db

AWS_REGION=us-east-1
AWS_KEY=your-key
AWS_SECRET=your-secret
AWS_BUCKET=your-bucket

This file stores sensitive credentials securely.

📚 Step 9: Install Composer & Libraries

Install Composer:

php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
sudo mv composer.phar /usr/local/bin/composer

Install required packages:

composer init -n
composer require aws/aws-sdk-php vlucas/phpdotenv
Libraries Used
Library	Purpose
aws/aws-sdk-php	Upload files to S3
vlucas/phpdotenv	Load environment variables
🗄️ Step 10: Setup RDS Database

Connect:

mysql -h RDS-ENDPOINT -u root -p

Create database:

CREATE DATABASE image_upload_db;

USE image_upload_db;

CREATE TABLE images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255),
  description TEXT,
  image_url VARCHAR(500),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
🔐 Step 11: Configure RDS Security Group

Allow:

Port: 3306
Source: EC2 Security Group

This allows EC2 to communicate with RDS securely.

🪣 Step 12: Create S3 Bucket

Create an S3 bucket.

Configuration
Disable Block Public Access (testing only)
Add Read Policy

Purpose:

Store uploaded images
👤 Step 13: Fix Permissions
sudo chown -R nginx:nginx /usr/share/nginx/html
🔄 Step 14: Restart Services
sudo systemctl restart nginx
sudo systemctl restart php-fpm
🌐 Step 15: Test Application

Open in browser:

http://EC2-PUBLIC-IP
Expected Output

✅ Image uploaded to S3
✅ Metadata stored in RDS
✅ Image URL displayed in browser

🎯 Final Result

The project successfully demonstrates:

Cloud-based deployment
Separation of compute and storage
Secure configuration using .env
Integration of EC2, S3, and RDS
Real-world scalable architecture

💡 Real-World Learning
Key Concepts Learned
Web server deployment
Backend integration
Cloud storage architecture
Database connectivity
Security group configuration
Environment variable management
Scalable cloud design

🛠️ Technologies Used
Service	Purpose
EC2	Hosting
NGINX	Web Server
PHP	Backend
S3	Image Storage
RDS	Database
Composer	Dependency Manager

🚀 Conclusion

This mini project is a production-style cloud deployment project using AWS services and PHP.

It provides hands-on experience with:

Cloud infrastructure
Storage management
Database integration
Secure application deployment

✅ Full Working Cloud-Based Image Upload Application Successfully Deployed!

Output:
https://github.com/user-attachments/assets/a119e453-e7e7-47fc-a093-3a3607aa2807


