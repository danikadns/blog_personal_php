<?php
require 'vendor/autoload.php';
require 'generateAwsCredentials.php'; 

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;

function logUserActivity($user_id, $action, $details = []) {
    $awsCredentials = null;

    try {
        $awsCredentials = generateAwsCredentials($_SESSION['user_id']);
    } catch (Exception $e) {
        error_log("Error al generar credenciales de AWS: " . $e->getMessage());
        return; // Salir de la función, pero no detener el script.
    }

    if (!$awsCredentials) {
        error_log("No se pudieron generar credenciales de AWS.");
        return;
    }

    $dynamodb = new DynamoDbClient([
        'region' => 'us-east-1',
        'version' => 'latest',
        'credentials' => [
            'key'    => $awsCredentials['AccessKeyId'],
            'secret' => $awsCredentials['SecretAccessKey'],
            'token'  => $awsCredentials['SessionToken'],
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
        error_log("Error al registrar actividad en DynamoDB: " . $e->getMessage());
    }
}
