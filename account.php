<?php
require 'session_handler.php';
require 'db.php';
require 'vendor/autoload.php';
require 'generateAwsCredentials.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

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

// Crear cliente S3 con credenciales renovadas
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

$user_id = $_SESSION['user_id'];
$user_query = $conn->query("SELECT * FROM users WHERE id = $user_id");
$user = $user_query->fetch_assoc();

$blogs_query = $conn->query("SELECT * FROM blogs WHERE user_id = $user_id");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_blog_id'])) {
    $blog_id = $_POST['delete_blog_id'];

    $blog_query = $conn->query("SELECT image_url FROM blogs WHERE id = $blog_id AND user_id = $user_id");
    $blog = $blog_query->fetch_assoc();

    if ($blog) {
        $image_url = $blog['image_url'];

        try {
            $s3->deleteObject(['Bucket' => $bucketName, 'Key' => "original/$image_url"]);
            $s3->deleteObject(['Bucket' => $bucketName, 'Key' => "thumbnails/$image_url"]);
            $conn->query("DELETE FROM blogs WHERE id = $blog_id AND user_id = $user_id");

            header('Location: account.php?success=1');
            exit();
        } catch (AwsException $e) {
            $error = "Error al eliminar la publicación: " . $e->getMessage();
        }
    } else {
        $error = "Publicación no encontrada.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include 'navbar.php'; ?>

    <div class="container mx-auto py-8">
        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                Publicación eliminada correctamente.
            </div>
        <?php elseif (isset($error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-4">Mi Perfil</h1>
            <p><strong>Nombre:</strong> <?= htmlspecialchars($user['name']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
            <p><strong>Descripción:</strong> <?= htmlspecialchars($user['description']) ?></p>
        </div>

        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Mis Publicaciones</h2>
            <?php if ($blogs_query->num_rows > 0): ?>
                <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border border-gray-300 px-4 py-2 text-left font-semibold text-gray-600">Título</th>
                            <th class="border border-gray-300 px-4 py-2 text-left font-semibold text-gray-600">Fecha</th>
                            <th class="border border-gray-300 px-4 py-2 text-left font-semibold text-gray-600">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($blog = $blogs_query->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-100">
                                <td class="border border-gray-300 px-4 py-2"><?= htmlspecialchars($blog['title']) ?></td>
                                <td class="border border-gray-300 px-4 py-2"><?= date('d-m-Y', strtotime($blog['created_at'])) ?></td>
                                <td class="border border-gray-300 px-4 py-2">
                                    <a href="blog_details.php?id=<?= $blog['id'] ?>" class="text-blue-500 hover:underline">Ver</a>
                                    <form action="account.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="delete_blog_id" value="<?= $blog['id'] ?>">
                                        <button type="submit" class="text-red-500 hover:underline ml-2">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="flex items-center justify-center h-40 text-gray-600 bg-gray-50 rounded-lg">
                    <p>No tienes publicaciones. <a href="create_blog.php" class="text-blue-500 hover:underline">Crea una nueva publicación</a>.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
