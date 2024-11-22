<?php
require 'session_handler.php';
require 'db.php';
require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

$handler = new MySQLSessionHandler();
session_set_save_handler($handler, true);
session_start();

// Redirigir si no está en sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Configuración del cliente S3
$s3 = new S3Client([
    'version' => 'latest',
    'region'  => 'us-east-1',
]);

$bucketName = 'almacenamiento-blog-personal';

// Obtener información del usuario
$user_id = $_SESSION['user_id'];
$user_query = $conn->query("SELECT * FROM users WHERE id = $user_id");
$user = $user_query->fetch_assoc();

// Obtener publicaciones del usuario
$blogs_query = $conn->query("SELECT * FROM blogs WHERE user_id = $user_id");

// Manejar eliminación de publicaciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_blog_id'])) {
    $blog_id = $_POST['delete_blog_id'];

    // Obtener la información de la publicación para eliminar las imágenes
    $blog_query = $conn->query("SELECT image_url FROM blogs WHERE id = $blog_id AND user_id = $user_id");
    $blog = $blog_query->fetch_assoc();

    if ($blog) {
        $image_url = $blog['image_url'];

        try {
            // Eliminar imágenes de S3
            $s3->deleteObject(['Bucket' => $bucketName, 'Key' => "original/$image_url"]);
            $s3->deleteObject(['Bucket' => $bucketName, 'Key' => "thumbnails/$image_url"]);

            // Eliminar publicación de la base de datos
            $conn->query("DELETE FROM blogs WHERE id = $blog_id AND user_id = $user_id");

            header('Location: profile.php?success=1');
            exit();
        } catch (AwsException $e) {
            $error = "Error al eliminar la publicación o las imágenes: " . $e->getMessage();
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
    <div class="container mx-auto py-8">
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-4">Mi Perfil</h1>
            <p><strong>Nombre:</strong> <?= htmlspecialchars($user['name']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
            <p><strong>Descripción:</strong> <?= htmlspecialchars($user['description']) ?></p>
        </div>

        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Mis Publicaciones</h2>
            <?php if ($blogs_query->num_rows > 0): ?>
                <table class="table-auto w-full border-collapse border border-gray-200">
                    <thead>
                        <tr>
                            <th class="border border-gray-300 px-4 py-2">Título</th>
                            <th class="border border-gray-300 px-4 py-2">Fecha</th>
                            <th class="border border-gray-300 px-4 py-2">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($blog = $blogs_query->fetch_assoc()): ?>
                            <tr>
                                <td class="border border-gray-300 px-4 py-2"><?= htmlspecialchars($blog['title']) ?></td>
                                <td class="border border-gray-300 px-4 py-2"><?= date('d-m-Y', strtotime($blog['created_at'])) ?></td>
                                <td class="border border-gray-300 px-4 py-2">
                                    <a href="blog_details.php?id=<?= $blog['id'] ?>" class="text-blue-500 hover:underline">Ver</a>
                                    <form action="profile.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="delete_blog_id" value="<?= $blog['id'] ?>">
                                        <button type="submit" class="text-red-500 hover:underline ml-2">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-gray-700">No tienes publicaciones.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
