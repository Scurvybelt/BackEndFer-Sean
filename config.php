<?php
// Configuración de la base de datos
$host = 'localhost';
$db = 'u889496471_confirmaciones';
$user = 'u889496471_admin';
$pass = 'Pepapig3838';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Error de conexión: ' . $e->getMessage());
}
