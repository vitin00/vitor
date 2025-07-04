<?php
session_start();
include 'conexao.php';

// Função para registrar logs de atividades do sistema
function logActivity($action, $details = '') {
    global $pdo;
    $user_id = $_SESSION['user_id'] ?? null;
    $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $user_id,
        $action,
        $details,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);
}

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    logActivity('Tentativa de acesso não autenticada', $_SERVER['REQUEST_URI']);
    header('Location: login.php');
    exit;
}

// Função de verificação de permissão
function hasPermission($required_roles) {
    if (!isset($_SESSION['role'])) {
        return false;
    }
    
    // Administrador tem acesso a tudo
    if ($_SESSION['role'] === 'ADMIN') {
        return true;
    }
    
    return in_array($_SESSION['role'], $required_roles);
}

// Verificação de primeiro login (se aplicável)
function isFirstLogin() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT first_login FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user && $user['first_login'];
}

// Proteção de rotas críticas (conforme solicitado)
function protectCriticalRoute($route_name) {
    $critical_routes = [
        'delete_user' => ['ADMIN'],
        'system_settings' => ['ADMIN'],
        'user_invitations' => ['ADMIN', 'SUPERVISOR']
    ];
    
    if (array_key_exists($route_name, $critical_routes) && !hasPermission($critical_routes[$route_name])) {
        logActivity('Tentativa de acesso não autorizado a rota crítica', $route_name);
        header('HTTP/1.0 403 Forbidden');
        exit('Acesso negado');
    }
}

// Função para enviar e-mail (pode ser integrada com PHPMailer ou similar)
function sendEmail($to, $subject, $message, $from = 'no-reply@seusistema.com') {
    $headers = "From: $from" . "\r\n" .
               "Reply-To: $from" . "\r\n" .
               "X-Mailer: PHP/" . phpversion();
    
    // Em ambiente de produção, considere usar uma biblioteca como PHPMailer
    return mail($to, $subject, $message, $headers);
}

// Geração de tokens seguros
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Sanitização de entrada
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>
