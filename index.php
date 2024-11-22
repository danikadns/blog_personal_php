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

if (!isset($_SESSION['aws_access_key'], $_SESSION['aws_secret_key'], $_SESSION['aws_session_token'])) {
    die("Credenciales de AWS no disponibles. Por favor, inicia sesión nuevamente.");
}

include 'db.php';
require 'vendor/autoload.php';

use Aws\S3\S3Client;

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

$blogs = $conn->query("SELECT blogs.*, users.name AS author_name FROM blogs JOIN users ON blogs.user_id = users.id ORDER BY created_at DESC LIMIT 6");
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
    <?php include 'navbar.php'; ?>

    <div class="container mx-auto py-8">
        <h2 class="text-3xl font-bold text-gray-700 mb-6">Últimos Blogs</h2>
        <?php if ($blogs->num_rows > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while ($blog = $blogs->fetch_assoc()): ?>
                    <div class="bg-white rounded-lg shadow-md">
                        <h3><?= htmlspecialchars($blog['title']) ?></h3>
                        <p>Por <?= htmlspecialchars($blog['author_name']) ?></p>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>No hay blogs disponibles.</p>
        <?php endif; ?>
    </div>
</body>
</html>
