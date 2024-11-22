<?php
include 'db.php';
require 'session_handler.php';


$handler = new MySQLSessionHandler();
session_set_save_handler($handler, true);
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != '1') {
    header('Location: login.php');
    exit;
}


$id = $_GET['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $username = $_POST['username']; // Capturar el nuevo campo
    $password = $_POST['password'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $role_id = $_POST['role_id'];
    $description = $_POST['description'];

    // Si el campo de contraseña está vacío, no actualizamos la contraseña
    if (!empty($password)) {
        // Encriptar la nueva contraseña
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $password_sql = ", password='$hashed_password'";
    } else {
        $password_sql = ""; // No modificar la contraseña
    }

    $sql = "UPDATE users SET 
                name='$name', 
                username='$username',
                email='$email', 
                phone_number='$phone_number', 
                role_id='$role_id', 
                description='$description' 
                $password_sql
            WHERE id=$id";
    if ($conn->query($sql) === TRUE) {
        header('Location: users.php'); // Redirigir a la lista de usuarios
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    $user_result = $conn->query("SELECT * FROM users WHERE id=$id");
    $user = $user_result->fetch_assoc();

    $roles_result = $conn->query("SELECT id, role FROM roles");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="container mx-auto">
        <h1 class="text-3xl font-bold mb-6">Editar Usuario</h1>

        <form action="edit.php?id=<?= $id ?>" method="POST" class="bg-white shadow-md rounded-lg p-6">
            <div class="mb-4">
                <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Nombre:</label>
                <input type="text" name="name" id="name" value="<?= $user['name'] ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
            </div>

            <div class="mb-4">
                <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Nombre de Usuario:</label>
                <input type="text" name="username" id="username" value="<?= $user['username'] ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
            </div>

            <div class="mb-4">
                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Contraseña:</label>
                <input type="password" name="password" id="password" value="" placeholder="Dejar en blanco para mantener la actual" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
            </div>

            <div class="mb-4">
                <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email:</label>
                <input type="email" name="email" id="email" value="<?= $user['email'] ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
            </div>

            <div class="mb-4">
                <label for="phone_number" class="block text-gray-700 text-sm font-bold mb-2">Número de Teléfono:</label>
                <input type="text" name="phone_number" id="phone_number" value="<?= $user['phone_number'] ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
            </div>

            <div class="mb-4">
                <label for="role_id" class="block text-gray-700 text-sm font-bold mb-2">Rol:</label>
                <select name="role_id" id="role_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    <?php while ($role = $roles_result->fetch_assoc()): ?>
                        <option value="<?= $role['id'] ?>" <?= $role['id'] == $user['role_id'] ? 'selected' : '' ?>><?= $role['role'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="mb-4">
                <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Descripción:</label>
                <textarea name="description" id="description" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"><?= $user['description'] ?></textarea>
            </div>

            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">Actualizar</button>
        </form>
    </div>
</body>
</html>
