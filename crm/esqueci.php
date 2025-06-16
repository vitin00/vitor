<?php
include 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Gerar token e enviar e-mail (implementação simplificada)
        $token = bin2hex(random_bytes(50));
        // Aqui você deve salvar o token em um banco de dados ou enviar por e-mail
        echo "Um e-mail foi enviado para redefinir sua senha.";
    } else {
        echo "Email não encontrado.";
    }
}
?>
