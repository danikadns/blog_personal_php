<?php
require 'db.php';
require 'vendor/autoload.php';

use Aws\Sts\StsClient;

function generateAwsCredentials($userId) {
    global $conn;

    // Consultar el ARN del rol del usuario
    $stmt = $conn->prepare('SELECT roles.arn FROM users JOIN roles ON users.role_id = roles.id WHERE users.id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $role = $result->fetch_assoc();

    if (!$role || !isset($role['arn'])) {
        throw new Exception("No se encontró un ARN de rol para este usuario.");
    }

    $roleArn = $role['arn'];

    // Asumir el rol con AWS STS
    $stsClient = new StsClient([
        'version' => 'latest',
        'region' => 'us-east-1',
    ]);

    try {
        $stsResult = $stsClient->assumeRole([
            'RoleArn' => $roleArn,
            'RoleSessionName' => 'SessionForUser_' . $userId,
        ]);

        $credentials = $stsResult->get('Credentials');
        if (!$credentials) {
            throw new Exception("Error al obtener las credenciales del rol.");
        }

        // Devolver las credenciales
        return [
            'AccessKeyId' => $credentials['AccessKeyId'],
            'SecretAccessKey' => $credentials['SecretAccessKey'],
            'SessionToken' => $credentials['SessionToken'],
            'Expiration' => $credentials['Expiration'],
        ];
    } catch (Exception $e) {
        throw new Exception("Error al asumir el rol: " . $e->getMessage());
    }
}
?>
