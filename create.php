<?php
include 'db.php';
require 'vendor/autoload.php'; // Asegúrate de tener la SDK de AWS instalada

use Aws\S3\S3Client;

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

    $sql = "INSERT INTO users (name, username, password, email, phone_number, role_id, description) 
            VALUES ('$name', '$username', '$hashed_password', '$email', '$phone_number', '$role_id', '$description')";

    if ($conn->query($sql) === TRUE) {
        // Obtén el ID del usuario recién creado
        $user_id = $conn->insert_id;

        // Configura el cliente de S3
        $s3 = new S3Client([
            'version' => 'latest',
            'region'  => 'us-east-1', // Ajusta según tu región
        ]);

        $bucketName = 'almacenamiento-blog-personal'; 
        $userFolder = "user-{$user_id}/";

        try {
            // Crear carpetas en S3
            $folders = [
                $userFolder . "original/",
                $userFolder . "thumbnails/",
            ];

            foreach ($folders as $folder) {
                $s3->putObject([
                    'Bucket' => $bucketName,
                    'Key'    => $folder,
                    'Body'   => '', // Crea un objeto vacío para simular una carpeta
                    'ACL'    => 'private', // Ajusta según sea necesario
                ]);
            }

            header('Location: users.php'); // Redirigir a la lista de usuarios
        } catch (Aws\Exception\S3Exception $e) {
            echo "Error creando carpetas en S3: " . $e->getMessage();
        }
    } else {
        echo "Error: " . $conn->error;
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
