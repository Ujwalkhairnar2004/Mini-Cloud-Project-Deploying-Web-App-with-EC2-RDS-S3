# EC2 Project Deployment (NGINX + PHP + S3 + RDS)

## 📌 Project Overview

This project deploys a cloud-based image upload application using:

* Amazon EC2 → Application Hosting
* NGINX → Web Server
* PHP → Backend Logic
* Amazon S3 → Image Storage
* Amazon RDS → Database Storage

---

# 🚀 Deployment Steps

## 1️⃣ Launch EC2 Instance

Create Amazon Linux EC2 instance.

### Security Group

Allow:

* SSH (22)
* HTTP (80)

Connect to EC2:

```bash
ssh -i key.pem ec2-user@PUBLIC-IP
```

---

## 2️⃣ Install Required Software

```bash
sudo yum update -y
sudo yum install nginx php php-fpm php-mysqlnd git -y
```

Start services:

```bash
sudo systemctl start nginx php-fpm
sudo systemctl enable nginx php-fpm
```

---

## 3️⃣ Setup Project Directory

```bash
cd /usr/share/nginx/html
mkdir uploads
chmod 777 uploads
```

---

## 4️⃣ Create Frontend File

Create `index.html`

```html
<!DOCTYPE html>
<html>
<head>
    <title>Image Upload</title>
</head>
<body>

<form action="upload.php" method="post" enctype="multipart/form-data">

    <input type="text" name="title" placeholder="Title" required><br><br>

    <input type="text" name="description" placeholder="Description" required><br><br>

    <input type="file" name="image" required><br><br>

    <input type="submit" value="Upload">

</form>

</body>
</html>
```

---

## 5️⃣ Create Backend File

Create `upload.php`

```php
<?php

require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$conn = new mysqli(
    $_ENV['DB_HOST'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASS'],
    $_ENV['DB_NAME']
);

$s3 = new S3Client([
    'version' => 'latest',
    'region'  => $_ENV['AWS_REGION'],
    'credentials' => [
        'key'    => $_ENV['AWS_KEY'],
        'secret' => $_ENV['AWS_SECRET'],
    ],
]);

$file = $_FILES['image'];

$filename = time() . "_" . basename($file['name']);

$s3->putObject([
    'Bucket' => $_ENV['AWS_BUCKET'],
    'Key' => $filename,
    'SourceFile' => $file['tmp_name']
]);

$imageUrl = "https://" . $_ENV['AWS_BUCKET'] . ".s3.amazonaws.com/" . $filename;

$stmt = $conn->prepare(
"INSERT INTO images(title, description, image_url)
VALUES (?, ?, ?)"
);

$stmt->bind_param(
"sss",
$_POST['title'],
$_POST['description'],
$imageUrl
);

$stmt->execute();

echo "Upload Successful<br>";
echo $imageUrl;

?>
```

---

## 6️⃣ Create `.env` File

```env
DB_HOST=your-rds-endpoint
DB_USER=root
DB_PASS=your-password
DB_NAME=image_upload_db

AWS_REGION=us-east-1
AWS_KEY=your-key
AWS_SECRET=your-secret
AWS_BUCKET=your-bucket
```

⚠️ Important:
Never share `.env` file publicly.

---

## 7️⃣ Install Composer & Packages

Install Composer:

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
sudo mv composer.phar /usr/local/bin/composer
```

Install libraries:

```bash
composer init -n
composer require aws/aws-sdk-php vlucas/phpdotenv
```

---

## 8️⃣ Setup RDS Database

```sql
CREATE DATABASE image_upload_db;

USE image_upload_db;

CREATE TABLE images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255),
  description TEXT,
  image_url VARCHAR(500),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

⚠️ Important:
Allow port `3306` in RDS Security Group from EC2 Security Group.

---

## 9️⃣ Create S3 Bucket

Create S3 bucket for storing images.

### Important Settings

* Disable Block Public Access (testing only)
* Add Read Permission Policy

---

## 🔟 Set Permissions & Restart

```bash
sudo chown -R nginx:nginx /usr/share/nginx/html

sudo systemctl restart nginx
sudo systemctl restart php-fpm
```

---

# 🌐 Test Application

Open in browser:

```text
http://EC2-PUBLIC-IP
```

---

# 🎯 Final Architecture

```text
User
  ↓
EC2 (NGINX + PHP)
  ↓
S3 → Image Storage
RDS → Database
```

---

# ✅ Key Learning

* Cloud Deployment
* EC2 Hosting
* S3 Storage
* RDS Database Connection
* Secure Config using `.env`
* Scalable Architecture

---

# 🚀 Final Output

✔ Image uploaded to S3
✔ Data stored in RDS
✔ URL displayed successfully

🔥 Full Working AWS Cloud Mini Project Completed!

Output:
https://github.com/user-attachments/assets/a119e453-e7e7-47fc-a093-3a3607aa2807


