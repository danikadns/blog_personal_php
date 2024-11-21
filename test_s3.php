<?php
require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

// Configuración del cliente S3
$s3 = new S3Client([
    'version' => 'latest',
    'region'  => 'us-east-1', // Cambia esto si tu bucket está en otra región
]);

$bucketName = 'almacenamiento-blog-personal'; // Cambia por el nombre de tu bucket
$testFolder = "test-folder/";

try {
    // Crear una carpeta vacía en S3
    $result = $s3->putObject([
        'Bucket' => $bucketName,
        'Key'    => $testFolder, // Las carpetas se simulan creando un objeto con '/' al final
        'Body'   => '', // Crea un objeto vacío
        'ACL'    => 'private', // Cambia si necesitas otro nivel de acceso
    ]);

    echo "Carpeta creada con éxito: " . $result['ObjectURL'];
} catch (AwsException $e) {
    echo "Error creando la carpeta: " . $e->getAwsErrorMessage();
}
