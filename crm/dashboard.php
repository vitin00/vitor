<?php
session_start();

// Protege a página: se não estiver logado, volta para login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Se quiser, pegue o nome do usuário aqui (exemplo usando role)
$user_role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f4f8;
            text-align: center;
            padding-top: 80px;
        }
        h1 {
            color: #2563eb;
        }
        a {
            color: #ef4444;
            font-weight: bold;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <h1>Bem-vindo ao Dashboard!</h1>
    <p>Você está logado como: <strong><?php echo htmlspecialchars($user_role); ?></strong></p>
    <p><a href="logout.php">Sair</a></p>
</body>
</html>
