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

try {
    $awsCredentials = generateAwsCredentials($_SESSION['user_id']);
} catch (Exception $e) {
    die("Error al generar credenciales de AWS: " . $e->getMessage());
}

// Configurar el cliente S3 con las credenciales generadas
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

// Consultar los blogs más recientes
$blogs = $conn->query("SELECT blogs.*, users.name AS author_name FROM blogs JOIN users ON blogs.user_id = users.id ORDER BY created_at DESC LIMIT 6");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Blog Personal</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script>
        function toggleMenu() {
            const menu = document.getElementById('profileMenu');
            menu.classList.toggle('hidden');
        }
    </script>
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <div class="container mx-auto py-8">
        <!-- Título -->
        <h2 class="text-3xl font-bold text-gray-700 mb-6">Últimos Blogs</h2>

        <!-- Grid de blogs -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($blog = $blogs->fetch_assoc()): 
                try {
                    // Generar URL firmada para la imagen
                    $cmd = $s3->getCommand('GetObject', [
                        'Bucket' => $bucketName,
                        'Key'    => 'thumbnails/' . htmlspecialchars($blog['image_url']),
                    ]);
                    $request = $s3->createPresignedRequest($cmd, '+10 hours'); // URL válida por 10 horas
                    $image_url = (string) $request->getUri();
                } catch (Exception $e) {
                    $image_url = 'default-thumbnail.jpg'; // Imagen predeterminada en caso de error
                }
            ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                    <!-- Imagen -->
                    <img src="<?= $image_url ?>" alt="<?= htmlspecialchars($blog['title']) ?>" class="w-full h-48 object-cover">
                    <!-- Contenido del blog -->
                    <div class="p-4">
                        <h3 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($blog['title']) ?></h3>
                        <p class="text-gray-600 mt-2"><?= substr(htmlspecialchars($blog['content']), 0, 100) ?>...</p>
                        <p class="text-gray-500 text-sm mt-2">Por <?= htmlspecialchars($blog['author_name']) ?></p>
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
