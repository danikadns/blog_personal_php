<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $user_id = $_SESSION['user_id'];
    $image_url = $_POST['image_url']; // Reemplazar con lógica para cargar imágenes a S3.

    $stmt = $conn->prepare("INSERT INTO blogs (user_id, title, content, image_url) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('isss', $user_id, $title, $content, $image_url);

    if ($stmt->execute()) {
        header('Location: blogs.php');
        exit;
    } else {
        echo "Error al guardar la publicación: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Blog</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="container mx-auto">
        <h1 class="text-3xl font-bold mb-6">Crear Publicación</h1>
        <form method="POST" class="bg-white shadow-md rounded-lg p-6">
            <div class="mb-4">
                <label for="title" class="block text-gray-700 text-sm font-bold mb-2">Título:</label>
                <input type="text" name="title" id="title" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
            </div>

            <div class="mb-4">
                <label for="content" class="block text-gray-700 text-sm font-bold mb-2">Contenido:</label>
                <textarea name="content" id="content" rows="5" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"></textarea>
            </div>

            <div class="mb-4">
                <label for="image_url" class="block text-gray-700 text-sm font-bold mb-2">URL de la Imagen:</label>
                <input type="text" name="image_url" id="image_url" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
            </div>

            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">Publicar</button>
        </form>
    </div>
</body>
</html>
