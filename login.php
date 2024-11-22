<?php
require 'db.php';
require 'session_handler.php';
require 'dynamo_activity.php';

$handler = new MySQLSessionHandler();
session_set_save_handler($handler, true);
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['name'];
        $_SESSION['role_id'] = $user['role_id'];
        logUserActivity($user['id'], 'login');
        header('Location: index.php');
        exit;
    } else {
        $error = 'Correo o contraseña incorrectos.';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex justify-center items-center h-screen">
    <div class="w-full max-w-md bg-white shadow-md rounded-lg p-6">
        <h2 class="text-2xl font-bold mb-4">Iniciar Sesión</h2>
        <?php if ($error): ?>
            <p class="text-red-500 mb-4"><?= $error ?></p>
        <?php endif; ?>
        <form action="login.php" method="POST" class="space-y-4">
            <div>
                <label for="email" class="block text-gray-700 font-bold mb-2">Correo Electrónico:</label>
                <input type="email" name="email" id="email" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
            </div>
            <div>
                <label for="password" class="block text-gray-700 font-bold mb-2">Contraseña:</label>
                <input type="password" name="password" id="password" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
            </div>
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded w-full">Iniciar Sesión</button>
        </form>
    </div>
</body>
</html>
