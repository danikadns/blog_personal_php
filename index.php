<?php
require 'session_handler.php';
require 'dynamo_activity.php';
require 'renewAwsCredentials.php'; 

$handler = new MySQLSessionHandler();
session_set_save_handler($handler, true);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'db.php';
require 'vendor/autoload.php';

use Aws\S3\S3Client;

// Renueva credenciales AWS si es necesario
try {
    renewAwsCredentials();
} catch (Exception $e) {
    die("Error al renovar las credenciales de AWS: " . $e->getMessage());
}

// Configurar el cliente S3 con las credenciales renovadas
$s3 = new S3Client([
    'version' => 'latest',
    'region'  => 'us-east-1',
    'credentials' => [
        'key'    => $_SESSION['aws_access_key'],
        'secret' => $_SESSION['aws_secret_key'],
        'token'  => $_SESSION['aws_session_token'],
    ],
]);

$bucketName = 'almacenamiento-blog-personal';

// Consultar los blogs más recientes
$blogs = $conn->query("SELECT blogs.*, users.name AS author_name 
                       FROM blogs 
                       JOIN users ON blogs.user_id = users.id 
                       ORDER BY created_at DESC LIMIT 6");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Blog Personal</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Barra de navegación -->
    <?php include 'navbar.php'; ?>

    <!-- Contenedor principal -->
    <div class="container mx-auto py-8">
        <h2 class="text-3xl font-bold text-gray-700 mb-6">Últimos Blogs</h2>

        <?php if ($blogs->num_rows > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while ($blog = $blogs->fetch_assoc()): 
                    try {
                        $cmd = $s3->getCommand('GetObject', [
                            'Bucket' => $bucketName,
                            'Key'    => 'thumbnails/' . htmlspecialchars($blog['image_url']),
                        ]);
                        $request = $s3->createPresignedRequest($cmd, '+10 hours');
                        $image_url = (string)$request->getUri();
                    } catch (Exception $e) {
                        $image_url = 'default-thumbnail.jpg';
                    }
                ?>
                    <div class="bg-white shadow-md rounded-lg overflow-hidden hover:shadow-lg transition-shadow duration-300">
                        <img src="<?= $image_url ?>" alt="Blog Image" class="w-full h-48 object-cover">
                        <div class="p-4">
                            <h3 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($blog['title']) ?></h3>
                            <p class="text-gray-600 mt-2"><?= substr(htmlspecialchars($blog['content']), 0, 100) ?>...</p>
                            <p class="text-gray-500 text-sm mt-2">Por: <?= htmlspecialchars($blog['author_name']) ?></p>
                            <a href="blog_details.php?id=<?= $blog['id'] ?>" class="text-blue-500 hover:underline mt-4 block">
                                Leer más
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-600 text-center">No hay blogs disponibles actualmente. Vuelve más tarde.</p>
        <?php endif; ?>
    </div>
</body>
</html>
