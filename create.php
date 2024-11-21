<?php
include 'db.php';
require 'vendor/autoload.php'; // Asegúrate de tener la SDK de AWS instalada

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

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
                $result = $s3->putObject([
                    'Bucket' => $bucketName,
                    'Key'    => $folder,
                    'Body'   => '', // Crea un objeto vacío para simular una carpeta
                    'ACL'    => 'private', // Ajusta según sea necesario
                ]);
                // Registro para verificar resultados
                error_log("Carpeta creada: " . $result['ObjectURL']);
            }

            // Redirigir a la lista de usuarios
            echo "Usuario creado exitosamente y carpetas en S3 configuradas.";
            header('Location: users.php');
        } catch (AwsException $e) {
            // Manejo de excepciones y registro del error
            error_log("Error creando carpetas en S3: " . $e->getMessage());
            echo "Ocurrió un error al crear las carpetas en S3. Por favor, revisa los logs.";
        }
    } else {
        error_log("Error creando usuario en la base de datos: " . $conn->error);
        echo "Error: " . $conn->error;
    }
}

// Obtener roles para el formulario
$roles_result = $conn->query("SELECT id, role FROM roles");
?>
