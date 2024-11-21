<?php
require 'session_handler.php';
require 'vendor/autoload.php';

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
            $sql = "INSERT INTO blogs (user_id, title, content, image_url) VALUES ('$user_id', '$title', '$content', '$s3Key')";
            if ($conn->query($sql) === TRUE) {
                header('Location: blogs.php');
                exit();
            } else {
                echo "Error al guardar en la base de datos: " . $conn->error;
            }
        } catch (AwsException $e) {
            echo "Error subiendo la imagen: " . $e->getMessage();
        }
    } else {
        echo "Error al cargar la imagen.";
    }
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Blog</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="container mx-auto">
        <h1 class="text-3xl font-bold mb-6">Crear Publicación</h1>
        <form action="create_blog.php" method="POST" enctype="multipart/form-data" class="bg-white shadow-md rounded-lg p-6">
            <!-- Campo para el título -->
            <div class="mb-4">
                <label for="title" class="block text-gray-700 text-sm font-bold mb-2">Título:</label>
                <input type="text" name="title" id="title" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
            </div>
            
            <!-- Campo para el contenido -->
            <div class="mb-4">
                <label for="content" class="block text-gray-700 text-sm font-bold mb-2">Contenido:</label>
                <textarea name="content" id="content" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"></textarea>
            </div>
            
            <!-- Campo para la imagen -->
            <div class="mb-4">
                <label for="image" class="block text-gray-700 text-sm font-bold mb-2">Imagen:</label>
                <input type="file" name="image" id="image" accept="image/*" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
            </div>
            
            <!-- Botón para publicar -->
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                Publicar
            </button>
        </form>
    </div>
</body>
</html>
