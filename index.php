
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
    
    if (!isset($input['attendance'], $input['fridayAttendance'], $input['saturdayAttendance'], $input['lastName'], $input['name'], $input['phone'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Faltan campos requeridos']);
        exit;
    }
    // Si attendance es "no", fridayAttendance, saturdayAttendance y songSuggestion deben ser vacíos
    if ($input['attendance'] === 'no') {
        $input['fridayAttendance'] = '';
        $input['saturdayAttendance'] = '';
        $input['songSuggestion'] = '';
    }
    $id = $model->add([
        'attendance' => $input['attendance'],
        'fridayAttendance' => $input['fridayAttendance'] ?? '',
        'saturdayAttendance' => $input['saturdayAttendance'] ?? '',
        'lastName' => $input['lastName'],
        'name' => $input['name'] ?? '',
        'phone' => $input['phone'] ?? '',
        'songSuggestion' => $input['songSuggestion'] ?? ''
    ]);

    
    $mail = new PHPMailer(true);
    $correoEnviado = false;
    $errorCorreo = '';
    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'hello@ferandsean.com';
        $mail->Password = 'F3R&S34N@wedding';
        $mail->SMTPSecure = 'ssl'; // 'ssl'
        $mail->Port = 465;

        // Remitente y destinatario
        $mail->setFrom('hello@ferandsean.com', 'Notificaciones');
        $mail->addAddress('hello@ferandsean.com');

        // Contenido del correo
        $mail->isHTML(false);
        $mail->Subject = 'Nuevo invitado registrado';
        $mail->Body = "Nuevo invitado registrado:\n\n" .
            "Nombre: " . ($input['name'] ?? '') . "\n" .
            "Apellido: " . ($input['lastName'] ?? '') . "\n" .
            "Teléfono: " . ($input['phone'] ?? '') . "\n" .
            "Asistencia general: " . ($input['attendance'] ?? '') . "\n" .
            "Asistencia viernes: " . ($input['fridayAttendance'] ?? '') . "\n" .
            "Asistencia sábado: " . ($input['saturdayAttendance'] ?? '') . "\n" .
            "Sugerencia de canción: " . ($input['songSuggestion'] ?? '');

        $mail->send();
        $correoEnviado = true;
    } catch (Exception $e) {
        $errorCorreo = $e->getMessage();
    }

    $response = ['success' => true, 'id' => $id];
    if ($correoEnviado) {
        $response['correo'] = 'Correo enviado correctamente';
    } else {
        $response['correo'] = 'No se pudo enviar el correo';
        if ($errorCorreo) {
            $response['errorCorreo'] = $errorCorreo;
        }
    }
    echo json_encode($response);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
