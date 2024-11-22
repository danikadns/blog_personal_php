<?php
require 'session_handler.php';
require 'dynamo_activity.php';

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

// Configura el cliente de S3
$s3 = new S3Client([
    'version' => 'latest',
    'region'  => 'us-east-1',
]);

$bucketName = 'almacenamiento-blog-personal';


    logUserActivity($_SESSION['user_id'], 'view_blog', [
        'blog_id' => $blog_id,
        'blog_title' => $blog['title']
    ]);

// Obtener el ID del blog desde la URL
$blog_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Consultar los detalles del blog
$blog_query = $conn->query("SELECT blogs.*, users.name AS author_name, users.description AS author_description, users.id AS author_id 
                            FROM blogs 
                            JOIN users ON blogs.user_id = users.id 
                            WHERE blogs.id = $blog_id");
$blog = $blog_query->fetch_assoc();

if (!$blog) {
    echo "Blog no encontrado.";
    exit;
}

// Generar URL firmada para la imagen del blog
$image_url = '';
try {
    $cmd = $s3->getCommand('GetObject', [
        'Bucket' => $bucketName,
        'Key'    => 'original/' . htmlspecialchars($blog['image_url']),
    ]);
    $request = $s3->createPresignedRequest($cmd, '+1 hour');
    $image_url = (string)$request->getUri();
} catch (Exception $e) {
    $image_url = ''; // Imagen de reemplazo si ocurre un error
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
    <div class="container mx-auto py-8">
        <!-- Barra de navegación -->
        <nav class="bg-white shadow-md p-4 rounded mb-8">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-700">Blog Personal</h1>
                <ul class="flex space-x-4">
                    <li><a href="index.php" class="text-gray-700 hover:text-blue-500 font-semibold">Inicio</a></li>
                </ul>
                <div class="relative">
                    <button onclick="toggleMenu()" class="bg-blue-500 text-white px-4 py-2 rounded focus:outline-none">
                        Ver Perfil
                    </button>
                    <div id="profileMenu" class="absolute right-0 mt-2 w-48 bg-white shadow-lg rounded hidden">
                        <a href="account.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Mi Cuenta</a>
                        <a href="logout.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Cerrar Sesión</a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
                <div>
                    <img src="<?= $image_url ?>" alt="<?= htmlspecialchars($blog['title']) ?>" class="w-full h-64 object-cover rounded-lg">
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mt-4"><?= htmlspecialchars($blog['title']) ?></h1>
                <p class="text-gray-600 mt-2">Publicado el <?= date('d M Y', strtotime($blog['created_at'])) ?></p>
                <p class="text-gray-700 mt-6"><?= nl2br(htmlspecialchars($blog['content'])) ?></p>
                <p class="text-gray-800 mt-4">
                    Autor: 
                    <a href="author_blogs.php?author_id=<?= $blog['author_id'] ?>" class="text-blue-500 hover:underline">
                        <?= htmlspecialchars($blog['author_name']) ?>
                    </a>
                </p>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-gray-800">Entradas recientes</h2>
                    <ul class="mt-4">
                        <?php while ($recent_blog = $recent_blogs->fetch_assoc()): ?>
                            <li>
                                <a href="blog_details.php?id=<?= $recent_blog['id'] ?>" class="text-blue-500 hover:underline">
                                    <?= htmlspecialchars($recent_blog['title']) ?>
                                </a>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
