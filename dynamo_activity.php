<?php
require 'vendor/autoload.php';

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;

function logUserActivity($user_id, $action, $details = []) {
    // Configurar el cliente DynamoDB
    $dynamodb = new DynamoDbClient([
        'region' => 'us-east-1',
        'version' => 'latest'
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
            'Item' => $item
        ]);
        error_log("Actividad registrada: " . json_encode($result));
    } catch (DynamoDbException $e) {
        error_log("Error al registrar actividad: " . $e->getMessage());
    }
}

?>