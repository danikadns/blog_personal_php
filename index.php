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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Blog Personal</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="container mx-auto">
        <h1 class="text-4xl font-bold mb-6">Bienvenido a tu Blog Personal</h1>
        
        <nav class="mb-6">
            <ul class="flex space-x-4">
                <li><a href="blogs.php" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">Ver Blogs</a></li>
                <li><a href="users.php" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">Gestión de Usuarios</a></li>
                <li><a href="account.php" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded">Mi Cuenta</a></li>
                <li><a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded">Cerrar Sesión</a></li>
            </ul>
        </nav>

        <section>
            <h2 class="text-2xl font-bold mb-4">Últimos Blogs</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php
                $blogs = $conn->query("SELECT * FROM blogs ORDER BY created_at DESC LIMIT 6");
                while ($blog = $blogs->fetch_assoc()): ?>
                    <div class="bg-white shadow-md rounded-lg p-4">
                        <h3 class="text-xl font-bold"><?= htmlspecialchars($blog['title']) ?></h3>
                        <p class="text-gray-700"><?= substr(htmlspecialchars($blog['content']), 0, 100) ?>...</p>
                        <a href="blog_details.php?id=<?= $blog['id'] ?>" class="text-blue-500 hover:text-blue-700">Leer más</a>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>
    </div>
</body>
</html>
