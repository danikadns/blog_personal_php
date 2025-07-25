<?php
include 'db.php';
require 'vendor/autoload.php';
require 'session_handler.php';
require 'generateAwsCredentials.php';

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;

$handler = new MySQLSessionHandler();
session_set_save_handler($handler, true);
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != '1') {
    header('Location: login.php');
    exit;
}

try {
    $awsCredentials = generateAwsCredentials($_SESSION['user_id']);
} catch (Exception $e) {
    die("Error al generar credenciales de AWS: " . $e->getMessage());
}

// Configurar el cliente SES con credenciales renovadas
$sesClient = new SesClient([
    'version' => 'latest',
    'region' => 'us-east-1',
    'credentials' => [
        'key'    => $awsCredentials['AccessKeyId'],
        'secret' => $awsCredentials['SecretAccessKey'],
        'token'  => $awsCredentials['SessionToken'],
    ],
]);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $role_id = $_POST['role_id'];
    $description = $_POST['description'];

    // Encripta la contraseña
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insertar usuario en la base de datos
    $sql = "INSERT INTO users (name, username, password, email, phone_number, role_id, description) 
            VALUES ('$name', '$username', '$hashed_password', '$email', '$phone_number', '$role_id', '$description')";

    if ($conn->query($sql) === TRUE) {
        try {
            // Verificar el correo electrónico con SES
            $result = $sesClient->verifyEmailIdentity([
                'EmailAddress' => $email,
            ]);

            // Redirige al usuario a la lista de usuarios
            header('Location: users.php');
            exit();
        } catch (AwsException $e) {
            echo "Error al verificar el correo electrónico con SES: " . $e->getMessage();
        }
    } else {
        // Error al insertar en la base de datos
        echo "Error creando usuario: " . $conn->error;
    }
}

// Obtener roles para el formulario
$roles_result = $conn->query("SELECT id, role FROM roles");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
<?php include 'navbar.php'; ?>
    <div class="container mx-auto">
        <h1 class="text-3xl font-bold mb-6">Crear Usuario</h1>

        <form action="create.php" method="POST" class="bg-white shadow-md rounded-lg p-6">
            <div class="mb-4">
                <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Nombre:</label>
                <input type="text" name="name" id="name" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
            </div>

            <div class="mb-4">
                <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Nombre de Usuario:</label>
                <input type="text" name="username" id="username" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
            </div>

            <div class="mb-4">
                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Contraseña:</label>
                <input type="password" name="password" id="password" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
            </div>

            <div class="mb-4">
                <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email:</label>
                <input type="email" name="email" id="email" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
            </div>

            <div class="mb-4">
                <label for="phone_number" class="block text-gray-700 text-sm font-bold mb-2">Número de Teléfono:</label>
                <input type="text" name="phone_number" id="phone_number" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
            </div>

            <div class="mb-4">
                <label for="role_id" class="block text-gray-700 text-sm font-bold mb-2">Rol:</label>
                <select name="role_id" id="role_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    <?php while ($role = $roles_result->fetch_assoc()): ?>
                        <option value="<?= $role['id'] ?>"><?= $role['role'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="mb-4">
                <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Descripción:</label>
                <textarea name="description" id="description" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"></textarea>
            </div>

            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">Crear</button>
        </form>
    </div>
</body>
</html>
