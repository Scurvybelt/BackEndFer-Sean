
<?php
// Cabeceras CORS
header('Access-Control-Allow-Origin: http://localhost:3036');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

try {
    // Incluir configuración de base de datos
    require_once 'config.php';
    require_once 'InvitadoModel.php';
    $model = new InvitadoModel($pdo);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de configuración: ' . $e->getMessage()]);
    exit;
}

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
    
    // Verificar si el JSON se decodificó correctamente
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'JSON inválido: ' . json_last_error_msg()]);
        exit;
    }
    
    if (!isset($input['attendance'], $input['lastName'], $input['name'], $input['phone'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Faltan campos requeridos']);
        exit;
    }
    
    // Validar que attendance sea válido
    if (!in_array($input['attendance'], ['yes', 'no'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Valor de attendance inválido']);
        exit;
    }
    
    // Si attendance es "no", fridayAttendance y dessertChoice deben ser vacíos
    if ($input['attendance'] === 'no') {
        $input['fridayAttendance'] = '';
        $input['dessertChoice'] = '';
    } else {
        // Si attendance es "yes", fridayAttendance es requerido
        if (!isset($input['fridayAttendance'])) {
            http_response_code(400);
            echo json_encode(['error' => 'fridayAttendance es requerido cuando attendance es yes']);
            exit;
        }
    }
    
    try {
        $id = $model->add([
            'attendance' => $input['attendance'],
            'fridayAttendance' => $input['fridayAttendance'] ?? '',
            'lastName' => $input['lastName'],
            'name' => $input['name'] ?? '',
            'phone' => $input['phone'] ?? '',
            'dessertChoice' => $input['dessertChoice'] ?? ''
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al guardar en base de datos: ' . $e->getMessage()]);
        exit;
    }

    
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
        $mail->isHTML(true);
        $mail->Subject = 'New Guest Confirmed - ' . ($input['name'] ?? '') . ' ' . ($input['lastName'] ?? '');
        
        $htmlBody = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; }
                .header { background: #d4c4b0; color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .header h1 { margin: 0; font-size: 28px; font-weight: 300; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                .section { background: white; margin: 20px 0; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .section h2 { color: #796f61; margin-top: 0; font-size: 20px; border-bottom: 2px solid #796f61; padding-bottom: 10px; }
                .detail-row { display: flex; justify-content: space-between; margin: 10px 0; padding: 8px 0; border-bottom: 1px solid #eee; }
                .detail-label { font-weight: bold; color: #555; }
                .detail-value { color: #333; }
                .attendance-yes { color: #28a745; font-weight: bold; }
                .attendance-no { color: #dc3545; font-weight: bold; }
                .footer { text-align: center; margin-top: 30px; padding: 20px; color: #666; font-size: 14px; border-top: 1px solid #ddd; }
                .song-suggestion { background:rgba(230, 230, 230, 0.94); padding: 15px; border-radius: 5px; border-left: 4px solid #796f61; }
                .icon { font-size: 18px; margin-right: 8px; color: #796f61; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>New Guest Confirmation</h1>
                <p>A new guest has confirmed their attendance to the wedding!</p>
            </div>
            
            <div class="content">
                <div class="section">
                    <h2>Guest Details</h2>
                    <div class="detail-row">
                        <span class="detail-label">Name:</span>
                        <span class="detail-value">' . ($input['name'] ?? 'N/A') . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Last Name:</span>
                        <span class="detail-value">' . ($input['lastName'] ?? 'N/A') . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Phone:</span>
                        <span class="detail-value">' . ($input['phone'] ?? 'N/A') . '</span>
                    </div>
                </div>
                
                <div class="section">
                    <h2>Attendance Details</h2>
                    <div class="detail-row">
                        <span class="detail-label">General Attendance:</span>
                        <span class="detail-value ' . (($input['attendance'] ?? '') === 'yes' ? 'attendance-yes' : 'attendance-no') . '">' . ($input['attendance'] ?? 'N/A') . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Friday Attendance:</span>
                        <span class="detail-value ' . (($input['fridayAttendance'] ?? '') === 'yes' ? 'attendance-yes' : 'attendance-no') . '">' . ($input['fridayAttendance'] ?? 'N/A') . '</span>
                    </div>

                </div>
                
                <div class="section">
                    <h2>Song Suggestion</h2>
                    <div class="song-suggestion">
                        ' . ($input['dessertChoice'] ? htmlspecialchars($input['dessertChoice']) : 'No song suggestion provided') . '
                    </div>
                </div>
                
                <div class="footer">
                    <p>Sent automatically from the wedding RSVP system</p>
                    <p style="font-size: 12px; color: #999;">Generated on ' . date('F j, Y \a\t g:i A') . '</p>
                </div>
            </div>
        </body>
        </html>';
        
        $mail->Body = $htmlBody;
        
        // Versión de texto plano como respaldo
        $mail->AltBody = "NEW GUEST CONFIRMATION\n\n" .
            "A new guest has confirmed their attendance to the wedding!\n\n" .
            "GUEST DETAILS:\n" .
            "Name: " . ($input['name'] ?? 'N/A') . "\n" .
            "Last Name: " . ($input['lastName'] ?? 'N/A') . "\n" .
            "Phone: " . ($input['phone'] ?? 'N/A') . "\n\n" .
            "ATTENDANCE DETAILS:\n" .
            "General Attendance: " . ($input['attendance'] ?? 'N/A') . "\n" .
            "Friday Attendance: " . ($input['fridayAttendance'] ?? 'N/A') . "\n\n" .
            "SONG SUGGESTION:\n" .
            ($input['dessertChoice'] ? $input['dessertChoice'] : 'No song suggestion provided') . "\n\n" .
            "Sent automatically from the wedding RSVP system.";

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
