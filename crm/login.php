<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'conexao.php'; // Confirme que esse arquivo está correto

$login_error = '';

// Se já estiver logado, redireciona para o dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $login_error = 'Por favor, preencha ambos os campos.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Login bem-sucedido
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            header('Location: dashboard.php');
            exit;
        } else {
            $login_error = 'Email ou senha incorretos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login - Sistema CRM</title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a, #1d4ed8);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background: rgba(31, 41, 55, 0.9);
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 12px 24px rgba(30, 64, 175, 0.5);
            width: 320px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        input[type="email"],
        input[type="password"] {
            padding: 12px;
            border-radius: 8px;
            border: none;
            font-size: 16px;
        }
        button {
            background: #3b82f6;
            border: none;
            padding: 12px;
            font-size: 16px;
            font-weight: 700;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            color: white;
        }
        button:hover {
            background: #2563eb;
        }
        .error {
            background: #dc2626;
            padding: 8px;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            color: white;
        }
        .register-link {
            text-align: center;
            margin-top: 16px;
        }
        .register-link a {
            color: #3b82f6;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <form method="POST" class="login-container" novalidate>
        <h2>Login</h2>
        <?php if (!empty($login_error)): ?>
            <div class="error"><?php echo htmlspecialchars($login_error); ?></div>
        <?php endif; ?>
        <input type="email" name="email" placeholder="Email" required autocomplete="email" />
        <input type="password" name="password" placeholder="Senha" required autocomplete="current-password" />
        <button type="submit">Entrar</button>
        <div class="register-link">
            <p>Ainda não tem uma conta? <a href="cadastro.php">Cadastre-se aqui</a></p>
        </div>
    </form>
</body>
</html>
