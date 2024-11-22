<?php
require 'session_handler.php';

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

$s3 = new S3Client([
    'version' => 'latest',
    'region'  => 'us-east-1',
]);

$bucketName = 'almacenamiento-blog-personal';

$author_id = isset($_GET['author_id']) ? (int)$_GET['author_id'] : 0;

$author_query = $conn->query("SELECT name, description FROM users WHERE id = $author_id");
$author = $author_query->fetch_assoc();

if (!$author) {
    echo "Autor no encontrado.";
    exit;
}

$blogs = $conn->query("SELECT * FROM blogs WHERE user_id = $author_id ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($author['name']) ?> - Blogs</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto py-8">
        <!-- Barra de navegación -->
        <nav class="bg-white shadow-md p-4 rounded mb-8">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-700"><?= htmlspecialchars($author['name']) ?></h1>
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

        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <h2 class="text-3xl font-bold text-gray-700 mb-4">Sobre <?= htmlspecialchars($author['name']) ?></h2>
            <p class="text-gray-600"><?= htmlspecialchars($author['description']) ?></p>
            
            <!-- Botón de suscripción -->
            <form action="subscribe_to_author.php" method="POST" class="mt-4">
                <input type="hidden" name="author_id" value="<?= $author_id ?>">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg">
                    Suscribirse a este autor
                </button>
            </form>
        </div>

        <h2 class="text-3xl font-bold text-gray-700 mb-6">Entradas de <?= htmlspecialchars($author['name']) ?></h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($blog = $blogs->fetch_assoc()): 
                try {
                    $cmd = $s3->getCommand('GetObject', [
                        'Bucket' => $bucketName,
                        'Key'    => 'original/' . htmlspecialchars($blog['image_url']),
                    ]);
                    $request = $s3->createPresignedRequest($cmd, '+1 hour');
                    $image_url = (string)$request->getUri();
                } catch (Exception $e) {
                    $image_url = '';
                }
            ?>
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <img src="<?= $image_url ?>" alt="<?= htmlspecialchars($blog['title']) ?>" class="w-full h-48 object-cover">
                    <div class="p-4">
                        <h3 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($blog['title']) ?></h3>
                        <p class="text-gray-600 mt-2"><?= substr(htmlspecialchars($blog['content']), 0, 100) ?>...</p>
                        <a href="blog_details.php?id=<?= $blog['id'] ?>" class="text-blue-500 hover:underline mt-4 block">
                            Leer más
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>
