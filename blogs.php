<?php
require 'session_handler.php';
require 'vendor/autoload.php';

use Aws\S3\S3Client;

$handler = new MySQLSessionHandler();
session_set_save_handler($handler, true);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';

// Configura el cliente de S3
$s3 = new S3Client([
    'version' => 'latest',
    'region'  => 'us-east-1',
]);

$bucketName = 'almacenamiento-blog-personal';

// Consultar blogs
$sql = "SELECT blogs.id, blogs.title, blogs.content, blogs.image_url, users.name AS author 
        FROM blogs 
        JOIN users ON blogs.user_id = users.id
        ORDER BY blogs.created_at DESC";
$result = $conn->query($sql);

if (!$result) {
    die("Error al ejecutar la consulta: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blogs</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include 'navbar.php'; ?>

    <div class="container mx-auto py-8">
        <h1 class="text-3xl font-bold mb-6">Publicaciones Disponibles</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($blog = $result->fetch_assoc()): 
                    // Obtener la URL firmada para la imagen desde S3
                    try {
                        $cmd = $s3->getCommand('GetObject', [
                            'Bucket' => $bucketName,
                            'Key'    => 'thumbnails/' . htmlspecialchars($blog['image_url']),
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
                            <h2 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($blog['title']) ?></h2>
                            <p class="text-gray-600 mt-2"><?= substr(htmlspecialchars($blog['content']), 0, 100) ?>...</p>
                            <p class="text-sm text-gray-500 mt-4">Por: <?= htmlspecialchars($blog['author']) ?></p>
                            <a href="blog_details.php?id=<?= $blog['id'] ?>" class="text-blue-500 hover:underline mt-4 block">
                                Leer más
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="bg-white shadow-md rounded-lg p-6 text-center">
                    <p class="text-gray-600">No hay publicaciones disponibles en este momento.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
