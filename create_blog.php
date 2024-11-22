<?php
require 'session_handler.php';
require 'vendor/autoload.php';
require 'dynamo_activity.php';
require 'generateAwsCredentials.php';

use Aws\S3\S3Client;
use Aws\Lambda\LambdaClient;
use Aws\Exception\AwsException;

$handler = new MySQLSessionHandler();
session_set_save_handler($handler, true);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'db.php';

$success_message = $error_message = '';

try {
    $awsCredentials = generateAwsCredentials($_SESSION['user_id']);
} catch (Exception $e) {
    die("Error al generar credenciales de AWS: " . htmlspecialchars($e->getMessage()));
}

// Configura los clientes S3 y Lambda con credenciales renovadas
$s3 = new S3Client([
    'version' => 'latest',
    'region'  => 'us-east-1',
    'credentials' => [
        'key'    => $awsCredentials['AccessKeyId'],
        'secret' => $awsCredentials['SecretAccessKey'],
        'token'  => $awsCredentials['SessionToken'],
    ],
]);

$lambda = new LambdaClient([
    'version' => 'latest',
    'region'  => 'us-east-1',
    'credentials' => [
        'key'    => $awsCredentials['AccessKeyId'],
        'secret' => $awsCredentials['SecretAccessKey'],
        'token'  => $awsCredentials['SessionToken'],
    ],
]);

$bucketName = 'almacenamiento-blog-personal';
$originalFolder = "original/";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $user_id = $_SESSION['user_id'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($fileExtension, $allowedExtensions)) {
            $error_message = "El formato de la imagen no es válido. Solo se permiten JPG, JPEG, PNG y GIF.";
        } else {
            // Generar un nombre único para la imagen
            $uniqueFileName = uniqid('', true) . '.' . $fileExtension;
            $s3Key = $originalFolder . $uniqueFileName;

            try {
                // Subir la imagen al bucket en la carpeta "original/"
                $result = $s3->putObject([
                    'Bucket' => $bucketName,
                    'Key'    => $s3Key,
                    'SourceFile' => $fileTmpPath,
                    'ACL'    => 'private',
                ]);

                // Guarda la entrada del blog en la base de datos
                $stmt = $conn->prepare("INSERT INTO blogs (user_id, title, content, image_url) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('isss', $user_id, $title, $content, $uniqueFileName);

                if ($stmt->execute()) {
                    $blog_id = $conn->insert_id;
                    logUserActivity($user_id, 'create_blog', [
                        'blog_id' => $blog_id,
                        'blog_title' => $title
                    ]);

                    // URL del blog recién creado
                    $blog_url = "http://3.81.169.233/blog_details.php?id=$blog_id";

                    // Invoca la función Lambda para notificar a los suscriptores
                    try {
                        $result = $lambda->invoke([
                            'FunctionName' => 'arn:aws:lambda:us-east-1:010526258440:function:pruebaSES',
                            'InvocationType' => 'Event',
                            'Payload' => json_encode([
                                'author_id' => $user_id,
                                'blog_title' => $title,
                                'blog_url' => $blog_url,
                            ]),
                        ]);
                    } catch (AwsException $e) {
                        error_log("Error al llamar a Lambda: " . $e->getMessage());
                    }

                    $success_message = "¡Publicación creada con éxito!";
                } else {
                    $error_message = "Error al guardar en la base de datos: " . $conn->error;
                }
            } catch (AwsException $e) {
                $error_message = "Error subiendo la imagen a S3: " . htmlspecialchars($e->getMessage());
            }
        }
    } else {
        $error_message = "Por favor, selecciona una imagen válida.";
    }
}
?>

