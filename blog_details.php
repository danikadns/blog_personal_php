<?php
require 'session_handler.php';
require 'dynamo_activity.php';
require 'generateAwsCredentials.php';
require 'db.php';
require 'vendor/autoload.php';

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
    die("Error al generar credenciales de AWS: " . htmlspecialchars($e->getMessage()));
}

// Configurar el cliente de S3 con credenciales renovadas
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

// Obtener el ID del blog desde la URL
$blog_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

// Consultar los detalles del blog
$stmt = $conn->prepare("SELECT blogs.*, users.name AS author_name, users.description AS author_description, users.id AS author_id 
                        FROM blogs 
                        JOIN users ON blogs.user_id = users.id 
                        WHERE blogs.id = ?");
$stmt->bind_param('i', $blog_id);
$stmt->execute();
$blog = $stmt->get_result()->fetch_assoc();

// Si no se encuentra el blog
if (!$blog) {
    $error_message = "El blog solicitado no se encuentra disponible.";
} else {
    // Registro de actividad solo si el blog existe
    logUserActivity($_SESSION['user_id'], 'view_blog', [
        'blog_id' => $blog_id,
        'blog_title' => $blog['title']
    ]);

    // Generar URL firmada para la imagen del blog
    try {
        $cmd = $s3->getCommand('GetObject', [
            'Bucket' => $bucketName,
            'Key'    => 'original/' . htmlspecialchars($blog['image_url']),
        ]);
        $request = $s3->createPresignedRequest($cmd, '+1 hour');
        $image_url = (string)$request->getUri();
    } catch (Exception $e) {
        $image_url = 'path/to/default-thumbnail.jpg'; // Imagen predeterminada
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
    <title><?= $blog ? htmlspecialchars($blog['title']) : 'Blog no encontrado' ?> - Blog Personal</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include 'navbar.php'; ?>

    <div class="container mx-auto py-8">
        <?php if (isset($error_message)): ?>
            <!-- Mensaje de error -->
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg shadow-md">
                <h2 class="text-2xl font-bold mb-2">Error</h2>
                <p><?= htmlspecialchars($error_message) ?></p>
                <a href="index.php" class="text-blue-500 hover:underline mt-4 block">Volver al inicio</a>
            </div>
        <?php else: ?>
            <!-- Contenido del blog -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Detalles del blog -->
                <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
                    <div>
                        <img src="<?= $image_url ?>" alt="<?= htmlspecialchars($blog['title']) ?>" class="w-full h-64 object-cover rounded-lg shadow-sm">
                    </div>
                    <h1 class="text-3xl font-bold text-gray-800 mt-4"><?= htmlspecialchars($blog['title']) ?></h1>
                    <p class="text-gray-600 mt-2">Publicado el <?= date('d M Y', strtotime($blog['created_at'])) ?></p>
                    <p class="text-gray-700 mt-6 leading-relaxed"><?= nl2br(htmlspecialchars($blog['content'])) ?></p>
                    <p class="text-gray-800 mt-4">
                        Autor: 
                        <a href="author_blogs.php?author_id=<?= $blog['author_id'] ?>" class="text-blue-500 hover:underline">
                            <?= htmlspecialchars($blog['author_name']) ?>
                        </a>
                    </p>
                </div>

                <!-- Entradas recientes -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="mb-6">
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
        <?php endif; ?>
    </div>
</body>
</html>
