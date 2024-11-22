<?php
require 'session_handler.php';
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $description = $_POST['description'];

    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, description = ? WHERE id = ?");
    $stmt->bind_param('sssi', $name, $email, $description, $user_id);
    if ($stmt->execute()) {
        $message = "Perfil actualizado correctamente.";
    } else {
        $message = "Error al actualizar el perfil.";
    }
    $stmt->close();
}

$user = $conn->query("SELECT name, email, description FROM users WHERE id = $user_id")->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Editar Perfil</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body>
<div class="container mx-auto py-8">
    <h1 class="text-3xl font-bold mb-4">Editar Perfil</h1>
    <?php if (isset($message)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <form method="POST" class="bg-white shadow-md rounded-lg p-6">
        <div class="mb-4">
            <label for="name" class="block text-gray-700">Nombre:</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required
                   class="border rounded w-full py-2 px-3">
        </div>
        <div class="mb-4">
            <label for="email" class="block text-gray-700">Correo:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required
                   class="border rounded w-full py-2 px-3">
        </div>
        <div class="mb-4">
            <label for="description" class="block text-gray-700">Descripción:</label>
            <textarea id="description" name="description"
                      class="border rounded w-full py-2 px-3"><?= htmlspecialchars($user['description']) ?></textarea>
        </div>
        <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded">Guardar</button>
    </form>
</div>
</body>
</html>
