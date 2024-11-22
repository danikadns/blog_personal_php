<?php
require 'vendor/autoload.php';
require 'renewAwsCredentials.php'; 

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;

function logUserActivity($user_id, $action, $details = []) {
    try {
        renewAwsCredentials();
    } catch (Exception $e) {
        die("Error al renovar credenciales: " . $e->getMessage());
    }

    // Configurar el cliente DynamoDB con credenciales temporales
    $dynamodb = new DynamoDbClient([
        'region' => 'us-east-1',
        'version' => 'latest',
        'credentials' => [
            'key' => $_SESSION['aws_access_key'],
            'secret' => $_SESSION['aws_secret_key'],
            'token' => $_SESSION['aws_session_token'],
        ],
    ]);

    $tableName = 'data_activity';
    $timestamp = gmdate("Y-m-d\TH:i:s\Z"); // Fecha/hora en UTC
    $uuid = uniqid('', true); // Genera un ID único

    // Construir los datos para insertar
    $item = [
        'id_data' => ['S' => $uuid],
        'user_id' => ['N' => (string) $user_id],
        'action' => ['S' => $action],
        'timestamp' => ['S' => $timestamp],
    ];

    // Agregar detalles adicionales (opcional)
    foreach ($details as $key => $value) {
        $item[$key] = ['S' => (string) $value];
    }

    try {
        $result = $dynamodb->putItem([
            'TableName' => $tableName,
            'Item' => $item,
        ]);
        error_log("Actividad registrada: " . json_encode($result));
    } catch (DynamoDbException $e) {
        error_log("Error al registrar actividad: " . $e->getMessage());
    }
}
?>
