<?php
require 'session_handler.php';
require 'vendor/autoload.php'; // Asegúrate de tener la SDK de AWS instalada

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

$handler = new MySQLSessionHandler();
session_set_save_handler($handler, true);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $user_id = $_SESSION['user_id'];

    // Configura S3
    $s3 = new S3Client([
        'version' => 'latest',
        'region'  => 'us-east-1',
    ]);

    $bucketName = 'almacenamiento-blog-personal';
    $userFolder = "user-{$user_id}/original/";

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $s3Key = $userFolder . $fileName;

        try {
            // Subir la imagen al bucket
            $result = $s3->putObject([
                'Bucket' => $bucketName,
                'Key'    => $s3Key,
                'SourceFile' => $fileTmpPath,
                'ACL'    => 'private',
            ]);

            // Guarda la entrada del blog en la base de datos
            $sql = "INSERT INTO blogs (user_id, title, content, image_path) VALUES ('$user_id', '$title', '$content', '$s3Key')";
            if ($conn->query($sql) === TRUE) {
                header('Location: blogs.php');
                exit();
            } else {
                echo "Error: " . $conn->error;
            }
        } catch (AwsException $e) {
            echo "Error subiendo la imagen: " . $e->getMessage();
        }
    } else {
        echo "Error al cargar la imagen.";
    }
}
?>
