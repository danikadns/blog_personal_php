<?php
require 'session_handler.php';

$handler = new MySQLSessionHandler();
session_set_save_handler($handler, true);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';

// Consultar blogs
$sql = "SELECT blogs.id, blogs.title, blogs.content, blogs.image_url, users.name AS author 
        FROM blogs 
        JOIN users ON blogs.user_id = users.id";
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
<body class="bg-gray-100 p-8">
    <div class="container mx-auto">
        <h1 class="text-3xl font-bold mb-6">Publicaciones</h1>
        <a href="create_blog.php" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">Crear Nueva Publicación</a>

        <div class="mt-6">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($blog = $result->fetch_assoc()): ?>
                    <div class="bg-white shadow-md rounded-lg p-6 mb-4">
                        <h2 class="text-xl font-bold mb-2"><?= htmlspecialchars($blog['title']) ?></h2>
                        <p class="text-gray-700 mb-4"><?= htmlspecialchars($blog['content']) ?></p>
                        <?php if (filter_var($blog['image_url'], FILTER_VALIDATE_URL)): ?>
                            <img src="<?= htmlspecialchars($blog['image_url']) ?>" alt="Imagen del blog" class="mb-4">
                        <?php endif; ?>
                        <p class="text-sm text-gray-500">Por: <?= htmlspecialchars($blog['author']) ?></p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-gray-700">No hay publicaciones disponibles.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
