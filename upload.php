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

        // Generate URL
        $cmd = $s3->getCommand('GetObject', [
            'Bucket' => $bucket,
            'Key' => $filename
        ]);

        $request = $s3->createPresignedRequest($cmd, '+20 minutes');
        $imageUrl = (string)$request->getUri();

        // Save to DB
        $stmt = $conn->prepare("INSERT INTO images (title, description, image_url) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $title, $description, $imageUrl);
        $stmt->execute();

        echo "Upload Successful<br>";
        echo "Image URL: " . $imageUrl;

    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

