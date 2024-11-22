<?php
require 'session_handler.php';
require 'vendor/autoload.php';
require 'dynamo_activity.php'; // Incluye la función logUserActivity actualizada
require 'generateAwsCredentials.php';

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

$success_message = $error_message = '';

try {
    $awsCredentials = generateAwsCredentials($_SESSION['user_id']);
} catch (Exception $e) {
    die("Error al generar credenciales de AWS: " . htmlspecialchars($e->getMessage()));
}

// Configurar cliente S3
$s3 = new S3Client([
    'version' => 'latest',
    'region'  => 'us-east-1',
    'credentials' => [
        'key'    => $awsCredentials['AccessKeyId'],
        'secret' => $awsCredentials['SecretAccessKey'],
        'token'  => $awsCredentials['SessionToken'],
    ],
]);

// Configurar cliente Lambda
$lambda = new LambdaClient([
    'version' => 'latest',
    'region'  => 'us-east-1',
    'credentials' => [
        'key'    => $awsCredentials['AccessKeyId'],
        'secret' => $awsCredentials['SecretAccessKey'],
        'token'  => $awsCredentials['SessionToken'],
    ],
]);

$bucketName = 'almacenamiento-blog-personal';
$originalFolder = "original/";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $user_id = $_SESSION['user_id'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($fileExtension, $allowedExtensions)) {
            $error_message = "El formato de la imagen no es válido. Solo se permiten JPG, JPEG, PNG y GIF.";
        } else {
            // Generar un nombre único para la imagen
            $uniqueFileName = uniqid('', true) . '.' . $fileExtension;
            $s3Key = $originalFolder . $uniqueFileName;

            try {
                // Subir la imagen al bucket S3
                $result = $s3->putObject([
                    'Bucket' => $bucketName,
                    'Key'    => $s3Key,
                    'SourceFile' => $fileTmpPath,
                    'ACL'    => 'private',
                ]);

                // Guardar la entrada del blog en la base de datos
                $stmt = $conn->prepare("INSERT INTO blogs (user_id, title, content, image_url) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('isss', $user_id, $title, $content, $uniqueFileName);

                if ($stmt->execute()) {
                    $blog_id = $conn->insert_id;

                    // Llamar a logUserActivity
                    logUserActivity($user_id, 'create_blog', [
                        'blog_id' => $blog_id,
                        'blog_title' => $title,
                    ]);

                    // URL del blog recién creado
                    $blog_url = "http://danikadonis.me/blog_details.php?id=$blog_id";

                    // Invocar función Lambda
                    try {
                        $result = $lambda->invoke([
                            'FunctionName' => 'arn:aws:lambda:us-east-1:010526258440:function:pruebaSES',
                            'InvocationType' => 'Event',
                            'Payload' => json_encode([
                                'author_id' => $user_id,
                                'blog_title' => $title,
                                'blog_url' => $blog_url,
                            ]),
                        ]);
                    } catch (AwsException $e) {
                        error_log("Error al llamar a Lambda: " . $e->getMessage());
                    }

                    header("Location: blog_details.php?id=$blog_id&success=1");
                    exit();
                } else {
                    $error_message = "Error al guardar en la base de datos: " . $conn->error;
                }
            } catch (AwsException $e) {
                $error_message = "Error subiendo la imagen a S3: " . htmlspecialchars($e->getMessage());
            }
        }
    } else {
        $error_message = "Por favor, selecciona una imagen válida.";
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
<body class="bg-gray-100">
    <?php include 'navbar.php'; ?>

    <div class="container mx-auto py-8">
        <h1 class="text-3xl font-bold mb-6">Crear Publicación</h1>

        <?php if ($success_message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php elseif ($error_message): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <form action="create_blog.php" method="POST" enctype="multipart/form-data" class="bg-white shadow-md rounded-lg p-6 space-y-6">
            <div>
                <label for="title" class="block text-sm font-bold text-gray-700 mb-2">Título:</label>
                <input type="text" name="title" id="title" required class="w-full border rounded-lg py-2 px-3 focus:outline-none focus:ring focus:ring-blue-300">
            </div>
            <div>
                <label for="content" class="block text-sm font-bold text-gray-700 mb-2">Contenido:</label>
                <textarea name="content" id="content" required class="w-full border rounded-lg py-2 px-3 focus:outline-none focus:ring focus:ring-blue-300"></textarea>
            </div>
            <div>
                <label for="image" class="block text-sm font-bold text-gray-700 mb-2">Imagen:</label>
                <input type="file" name="image" id="image" accept="image/*" required onchange="previewImage()" class="w-full border rounded-lg py-2 px-3 focus:outline-none focus:ring focus:ring-blue-300">
                <img id="imagePreview" style="display:none; margin-top: 20px; max-width: 100%; height: auto; border-radius: 8px;" alt="Vista previa de la imagen seleccionada">
            </div>
            <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-200">
                Publicar
            </button>
        </form>
    </div>
</body>
</html>
