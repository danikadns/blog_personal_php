<?php

require_once 'vendor/autoload.php';
use Aws\Sts\StsClient;

if (!function_exists('renewAwsCredentials')) {
    function renewAwsCredentials() {
        if (!isset($_SESSION['aws_expiration'], $_SESSION['role_arn']) || time() >= $_SESSION['aws_expiration']) {
            if (!isset($_SESSION['role_arn'])) {
                throw new Exception('No se puede renovar credenciales porque no se encuentra el ARN del rol en la sesión.');
            }

            $stsClient = new StsClient([
                'version' => 'latest',
                'region' => 'us-east-1',
            ]);

            try {
                $stsResult = $stsClient->assumeRole([
                    'RoleArn' => $_SESSION['role_arn'],
                    'RoleSessionName' => 'SessionForUser_' . $_SESSION['user_id'],
                ]);

                $credentials = $stsResult->get('Credentials');
                $_SESSION['aws_access_key'] = $credentials['AccessKeyId'];
                $_SESSION['aws_secret_key'] = $credentials['SecretAccessKey'];
                $_SESSION['aws_session_token'] = $credentials['SessionToken'];
                $_SESSION['aws_expiration'] = strtotime($credentials['Expiration']);
            } catch (Exception $e) {
                error_log("Error al renovar las credenciales de AWS: " . $e->getMessage());
                throw new Exception("No se pudieron renovar las credenciales de AWS. Intenta iniciar sesión nuevamente.");
            }
        }
    }
}
