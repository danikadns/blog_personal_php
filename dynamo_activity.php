<?php
require 'vendor/autoload.php';

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;

function logUserActivity($user_id, $action, $details = []) {
    try {
        // Generar credenciales de AWS
        $awsCredentials = generateAwsCredentials($user_id);
    } catch (Exception $e) {
        error_log("Error al generar credenciales de AWS: " . $e->getMessage());
        return; // No detener el script, solo salir de la función.
    }

    if (!$awsCredentials) {
        error_log("No se pudieron generar credenciales de AWS.");
        return;
    }

    // Configurar cliente DynamoDB
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
    $uuid = uniqid('', true); // Generar un identificador único para el registro

    // Construir los datos para DynamoDB
    $item = [
        'id_data' => ['S' => $uuid],
        'user_id' => ['N' => (string)$user_id],
        'action' => ['S' => $action],
        'timestamp' => ['S' => $timestamp],
    ];

    // Agregar detalles adicionales si están disponibles
    if (!empty($details)) {
        foreach ($details as $key => $value) {
            $item[$key] = ['S' => (string)$value];
        }
    }

    // Intentar registrar la actividad
    try {
        $result = $dynamodb->putItem([
            'TableName' => $tableName,
            'Item' => $item,
        ]);
        error_log("Actividad registrada correctamente en DynamoDB: " . json_encode($result));
    } catch (DynamoDbException $e) {
        error_log("Error al registrar actividad en DynamoDB: " . $e->getMessage());
    }
}
