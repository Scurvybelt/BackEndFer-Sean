
<?php
// Cabeceras CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once 'InvitadoModel.php';
$model = new InvitadoModel($pdo);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

$method = $_SERVER['REQUEST_METHOD'];

// Responder a preflight OPTIONS
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($method === 'GET') {
    // Obtener todos los invitados
    $invitados = $model->getAll();
    echo json_encode($invitados);
    exit;
}

if ($method === 'POST') {
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['attendance'], $input['lastName'], $input['name'], $input['phone'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Faltan campos requeridos']);
        exit;
    }
    // Si attendance es "no", arrivalDay y songSuggestion deben ser vacíos
    if ($input['attendance'] === 'no') {
        $input['arrivalDay'] = '';
        $input['songSuggestion'] = '';
    }
    $id = $model->add([
        'arrivalDay' => $input['arrivalDay'] ?? '',
        'attendance' => $input['attendance'],
        'lastName' => $input['lastName'],
        'name' => $input['name'] ?? '',
        'phone' => $input['phone'] ?? '',
        'songSuggestion' => $input['songSuggestion'] ?? ''
    // PHPMailer 'use' statements moved to the top of the file.
    ]);

    
    $mail = new PHPMailer(true);
    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com'; // ferandsean.com Cambia esto por tu servidor SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'hello@ferandsean.com'; // Cambia esto por tu usuario SMTP
        $mail->Password = 'F3R&S34N@wedding'; // Cambia esto por tu contraseña SMTP
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; //'ssl'
        $mail->Port = 465;

        // Remitente y destinatario
        $mail->setFrom('hello@ferandsean.com', 'Notificaciones');
        $mail->addAddress('hello@ferandsean.com'); // Cambia esto por el correo de destino

        // Contenido del correo
        $mail->isHTML(false);
        $mail->Subject = 'Nuevo invitado registrado';
        $mail->Body = "Nuevo invitado registrado:\n\n" .
            "Nombre: " . ($input['name'] ?? '') . "\n" .
            "Apellido: " . ($input['lastName'] ?? '') . "\n" .
            "Teléfono: " . ($input['phone'] ?? '') . "\n" .
            "Asistencia: " . ($input['attendance'] ?? '') . "\n" .
            "Día de llegada: " . ($input['arrivalDay'] ?? '') . "\n" .
            "Sugerencia de canción: " . ($input['songSuggestion'] ?? '');

        $mail->send();
    } catch (Exception $e) {
        // Puedes loguear el error si lo deseas
        http_response_code(500);
        echo json_encode(['error' => 'Error al enviar el correo: ' . $e->getMessage()]);
    }

    echo json_encode(['success' => true, 'id' => $id]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
