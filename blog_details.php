<?php
require 'session_handler.php';
require 'dynamo_activity.php';
require 'generateAwsCredentials.php';

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

// Generar credenciales AWS
try {
    $awsCredentials = generateAwsCredentials($_SESSION['user_id']);
} catch (Exception $e) {
    die("Error al generar credenciales de AWS: " . $e->getMessage());
}

// Configurar cliente S3 con las credenciales generadas
$s3 = new S3Client([
    'version' => 'latest',
    'region'  => 'us-east-1',
    'credentials' => [
        'key'    => $awsCredentials['AccessKeyId'],
        'secret' => $awsCredentials['SecretAccessKey'],
        'token'  => $awsCredentials['SessionToken'],
    ],
]);

$bucketName = 'almacenamiento-blog-personal';

// Obtener ID del blog desde la URL
$blog_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($blog_id <= 0) {
    die("ID del blog no válido.");
}

// Consultar el blog directamente
$blog_query = $conn->query("SELECT blogs.*, users.name AS author_name FROM blogs JOIN users ON blogs.user_id = users.id WHERE blogs.id = $blog_id");
if (!$blog_query || $blog_query->num_rows === 0) {
    die("El blog solicitado no existe o fue eliminado.");
}

$blog = $blog_query->fetch_assoc();

// Generar URL firmada para la imagen
$image_url = 'default-thumbnail.jpg'; // Imagen predeterminada
if (!empty($blog['image_url'])) {
    try {
        $cmd = $s3->getCommand('GetObject', [
            'Bucket' => $bucketName,
            'Key'    => 'original/' . htmlspecialchars($blog['image_url']),
        ]);
        $request = $s3->createPresignedRequest($cmd, '+1 hour');
        $image_url = (string)$request->getUri();
    } catch (Exception $e) {
        error_log("Error al generar URL firmada: " . $e->getMessage());
    }
}

// Consultar entradas recientes
$recent_blogs = $conn->query("SELECT id, title FROM blogs ORDER BY created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($blog['title']) ?> - Blog Personal</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <div class="container mx-auto py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Detalles del blog -->
            <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
                <img src="<?= $image_url ?>" alt="<?= htmlspecialchars($blog['title']) ?>" class="w-full h-64 object-cover rounded-lg shadow-sm">
                <h1 class="text-3xl font-bold text-gray-800 mt-4"><?= htmlspecialchars($blog['title']) ?></h1>
                <p class="text-gray-600 mt-2">Publicado el <?= date('d M Y', strtotime($blog['created_at'])) ?></p>
                <p class="text-gray-700 mt-6 leading-relaxed"><?= nl2br(htmlspecialchars($blog['content'])) ?></p>
                <p class="text-gray-800 mt-4">
                    Autor: 
                    <a href="author_blogs.php?author_id=<?= $blog['user_id'] ?>" class="text-blue-500 hover:underline">
                        <?= htmlspecialchars($blog['author_name']) ?>
                    </a>
                </p>
            </div>

            <!-- Entradas recientes -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-bold text-gray-800">Entradas recientes</h2>
                <ul class="mt-4 space-y-2">
                    <?php while ($recent_blog = $recent_blogs->fetch_assoc()): ?>
                        <li class="border-b border-gray-200 pb-2">
                            <a href="blog_details.php?id=<?= $recent_blog['id'] ?>" class="text-blue-500 hover:underline">
                                <?= htmlspecialchars($recent_blog['title']) ?>
                            </a>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
