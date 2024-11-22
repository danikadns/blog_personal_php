<?php
require 'session_handler.php';
require 'vendor/autoload.php';
require 'db.php';
require 'generateAwsCredentials.php';

use Aws\S3\S3Client;

$handler = new MySQLSessionHandler();
session_set_save_handler($handler, true);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

try {
    $awsCredentials = generateAwsCredentials($_SESSION['user_id']);
} catch (Exception $e) {
    die("Error al generar credenciales de AWS: " . $e->getMessage());
}
// Configuración del cliente S3 con credenciales renovadas
$s3 = new S3Client([
    'version' => 'latest',
    'region' => 'us-east-1',
    'credentials' => [
        'key'    => $awsCredentials['AccessKeyId'],
        'secret' => $awsCredentials['SecretAccessKey'],
        'token'  => $awsCredentials['SessionToken'],
    ],
]);

$bucketName = 'almacenamiento-blog-personal';

$author_id = isset($_GET['author_id']) ? (int)$_GET['author_id'] : 0;

$author_query = $conn->query("SELECT name, description FROM users WHERE id = $author_id");
$author = $author_query->fetch_assoc();

if (!$author) {
    header('Location: error.php?message=Autor no encontrado');
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
    <?php include 'navbar.php'; ?>

    <div class="container mx-auto py-8">
        <!-- Información del autor -->
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-3xl font-bold text-gray-800 mb-4">Sobre <?= htmlspecialchars($author['name']) ?></h2>
            <p class="text-gray-600"><?= htmlspecialchars($author['description']) ?></p>

            <!-- Botón de suscripción -->
            <form action="subscribe_to_author.php" method="POST" class="mt-4">
                <input type="hidden" name="author_id" value="<?= $author_id ?>">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                    Suscribirse a este autor
                </button>
            </form>
        </div>

        <!-- Entradas del autor -->
        <h2 class="text-3xl font-bold text-gray-800 mb-6">Entradas de <?= htmlspecialchars($author['name']) ?></h2>

        <?php if ($blogs->num_rows > 0): ?>
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
                        $image_url = 'default-thumbnail.jpg'; // Imagen predeterminada
                    }
                ?>
                    <div class="bg-white shadow-md rounded-lg overflow-hidden hover:shadow-lg transition-shadow duration-300">
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
        <?php else: ?>
            <div class="bg-white shadow-md rounded-lg p-6 text-center">
                <p class="text-gray-600">Este autor aún no tiene entradas publicadas.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
