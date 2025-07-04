<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'conexao.php';

$login_error = '';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

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
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            if ($user['first_login']) {
                header('Location: change_password.php');
                exit;
            }

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a, #1e3a8a);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-container {
            background: #1f2937;
            padding: 32px;
            border-radius: 16px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 360px;
        }

        h2 {
            text-align: center;
            margin-bottom: 24px;
            color: #3b82f6;
            font-size: 1.6rem;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid #374151;
            background-color: #111827;
            color: #f9fafb;
            font-size: 1rem;
            margin-bottom: 18px;
            transition: border-color 0.2s;
        }

        input:focus {
            border-color: #3b82f6;
            outline: none;
        }

        button {
            width: 100%;
            background: #3b82f6;
            color: white;
            border: none;
            padding: 14px;
            font-size: 1rem;
            font-weight: 700;
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background: #2563eb;
        }

        .error {
            background: #dc2626;
            color: white;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 16px;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .extra-links {
            text-align: center;
            font-size: 0.9rem;
            margin-top: 16px;
            display: flex;
            justify-content: center;
            gap: 12px;
            color: #cbd5e1;
            flex-wrap: wrap;
        }

        .extra-links a {
            color: #93c5fd;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .extra-links a:hover {
            color: #bfdbfe;
            text-decoration: underline;
        }

        .extra-links span {
            color: #64748b;
        }

        @media (max-width: 420px) {
            .login-container {
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <form class="login-container" method="POST" novalidate>
        <h2>Login</h2>

        <?php if ($login_error): ?>
            <div class="error"><?= htmlspecialchars($login_error) ?></div>
        <?php endif; ?>

        <input type="email" name="email" placeholder="E-mail" required />
        <input type="password" name="password" placeholder="Senha" required />
        <button type="submit">Entrar</button>

        <div class="extra-links">
            <a href="cadastro.php">Criar nova conta</a>
            <span>•</span>
            <a href="forgot_password.php">Recuperar senha</a>
        </div>
    </form>
</body>
</html>
