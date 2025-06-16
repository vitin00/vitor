<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redireciona para a página de login
    exit;
}

// Verifica se o usuário tem permissão para acessar a página
function hasPermission($required_roles) {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], $required_roles);
}
?>
