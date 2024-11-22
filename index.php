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

// Consultar los blogs más recientes
$blogs = $conn->query("SELECT * FROM blogs ORDER BY created_at DESC LIMIT 6");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Blog Personal</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script>
        // Mostrar/Ocultar el submenú al hacer clic en "Perfil"
        function toggleMenu() {
            const menu = document.getElementById('profileMenu');
            menu.classList.toggle('hidden');
        }
    </script>
</head>
<body class="bg-gray-100">
    <!-- Contenedor principal -->
    <div class="container mx-auto px-4 md:px-8 lg:px-16 py-8">
        <!-- Barra de navegación -->
        <nav class="bg-white shadow-md p-4 rounded mb-8">
            <div class="flex justify-between items-center">
                <!-- Logo o título -->
                <h1 class="text-2xl font-bold text-gray-700">Blog Personal</h1>

                <!-- Menú de navegación -->
                <ul class="flex space-x-4">
                    <li><a href="blogs.php" class="text-gray-700 hover:text-blue-500 font-semibold">Ver Blogs</a></li>
                    <li><a href="create_blog.php" class="text-gray-700 hover:text-blue-500 font-semibold">Crear Blog</a></li>
                    <li><a href="users.php" class="text-gray-700 hover:text-blue-500 font-semibold">Gestión de Usuarios</a></li>
                </ul>

                <!-- Menú desplegable de perfil -->
                <div class="relative">
                    <button onclick="toggleMenu()" class="bg-blue-500 text-white px-4 py-2 rounded focus:outline-none">
                        Ver Perfil
                    </button>
                    <!-- Submenú -->
                    <div id="profileMenu" class="absolute right-0 mt-2 w-48 bg-white shadow-lg rounded hidden">
                        <a href="account.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Mi Cuenta</a>
                        <a href="logout.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Cerrar Sesión</a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Título principal -->
        <h2 class="text-3xl font-bold text-gray-700 mb-6">Últimos Blogs</h2>

        <!-- Grid de blogs -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($blog = $blogs->fetch_assoc()): 
                // Generar la URL de la imagen desde el bucket S3
                $image_url = "https://almacenamiento-blog-personal.s3.amazonaws.com/original/" . htmlspecialchars($blog['image_url']);
            ?>
                <!-- Blog individual -->
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <!-- Imagen -->
                    <img src="<?= $image_url ?>" alt="Blog Image" class="w-full h-48 object-cover">
                    <!-- Contenido -->
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
