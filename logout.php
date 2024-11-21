<?php
require 'session_handler.php'; // Asegúrate de incluir tu handler personalizado si lo estás usando.

$handler = new MySQLSessionHandler();
session_set_save_handler($handler, true);

session_start(); // Inicia la sesión para poder destruirla

// Destruye todas las variables de sesión
session_unset();

// Destruye la sesión actual
session_destroy();

// Redirige al usuario al login o página principal
header('Location: login.php');
exit;
