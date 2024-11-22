<?php
require 'session_handler.php';
require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Lambda\LambdaClient;
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

    // Configura el cliente de S3
    $s3 = new S3Client([
        'version' => 'latest',
        'region'  => 'us-east-1',
    ]);

    $lambda = new LambdaClient([
        'version' => 'latest',
        'region'  => 'us-east-1',
    ]);

    $bucketName = 'almacenamiento-blog-personal';
    $originalFolder = "original/";

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);

        // Generar un nombre único para la imagen
        $uniqueFileName = uniqid('', true) . '.' . $fileExtension;
        $s3Key = $originalFolder . $uniqueFileName;

        try {
            // Subir la imagen al bucket en la carpeta "original/"
            $result = $s3->putObject([
                'Bucket' => $bucketName,
                'Key'    => $s3Key,
                'SourceFile' => $fileTmpPath,
                'ACL'    => 'private',
            ]);

            // Guarda la entrada del blog en la base de datos
            $sql = "INSERT INTO blogs (user_id, title, content, image_url) VALUES ('$user_id', '$title', '$content', '$uniqueFileName')";
            if ($conn->query($sql) === TRUE) {

                logUserActivity($user_id, 'create_blog', [
                    'blog_id' => $conn->insert_id,
                    'blog_title' => $title
                ]);
                $blog_id = $conn->insert_id;

                // URL del blog recién creado
                $blog_url = "http://3.81.169.233/blog_details.php?id=$blog_id";

                // Invoca la función Lambda para notificar a los suscriptores
                try {
                    $result = $lambda->invoke([
                        'FunctionName' => 'arn:aws:lambda:us-east-1:010526258440:function:pruebaSES', // ARN de tu función Lambda
                        'InvocationType' => 'Event', // Ejecución asíncrona
                        'Payload' => json_encode([
                            'author_id' => $user_id,
                            'blog_title' => $title,
                            'blog_url' => $blog_url,
                        ]),
                    ]);
                    error_log("Llamada a Lambda exitosa: " . json_encode($result));
                } catch (AwsException $e) {
                    error_log("Error al llamar a Lambda: " . $e->getMessage());
                }

                header('Location: blogs.php');
                exit();
            } else {
                echo "Error al guardar en la base de datos: " . $conn->error;
            }
        } catch (AwsException $e) {
            echo "Error subiendo la imagen a S3: " . $e->getMessage();
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
    <script>
        function previewImage() {
            const input = document.getElementById('image');
            const preview = document.getElementById('imagePreview');

            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
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
                <input type="file" name="image" id="image" accept="image/*" required onchange="previewImage()" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                <!-- Vista previa de la imagen -->
                <img id="imagePreview" style="display:none; margin-top: 20px; max-width: 100%; height: auto;" alt="Vista previa de la imagen seleccionada">
            </div>
            
            <!-- Botón para publicar -->
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                Publicar
            </button>
        </form>
    </div>
</body>
</html>
