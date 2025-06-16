<?php
// cadastro.php - versão sem seleção de perfil pelo usuário
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'conexao.php'; // Ajuste o caminho conforme necessário

// Inicializa variáveis
$errors = [];
$success = '';
$username = '';
$email = '';

// Perfil padrão para cadastro: Usuário comum
$default_role = 'User';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitização simples
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validações
    if (empty($username)) {
        $errors[] = 'O nome de usuário é obrigatório.';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = 'O nome de usuário deve ter entre 3 e 50 caracteres.';
    }

    if (empty($email)) {
        $errors[] = 'O email é obrigatório.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'O email informado não é válido.';
    }

    if (empty($password)) {
        $errors[] = 'A senha é obrigatória.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'A senha deve ter no mínimo 8 caracteres.';
    }

    if ($password !== $password_confirm) {
        $errors[] = 'As senhas não coincidem.';
    }

    // Verifica se email já existe
    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Este email já está cadastrado.';
        }
    }

    // Se sem erros, insere usuário com papel padrão
    if (empty($errors)) {
        $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)');
        if ($stmt->execute([$username, $email, $hashed_pass, $default_role])) {
            $success = "Usuário cadastrado com sucesso!";
            // Limpar inputs
            $username = '';
            $email = '';
        } else {
            $errors[] = 'Erro ao cadastrar o usuário. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Cadastro de Usuário - Sistema CRM</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter&display=swap');
  body {
    background: linear-gradient(135deg, #1e3a8a, #2563eb);
    font-family: 'Inter', sans-serif;
    margin: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    color: #f9fafb;
  }
  .container {
    background: rgba(31, 41, 55, 0.95);
    padding: 32px;
    border-radius: 16px;
    width: 360px;
    box-shadow: 0 12px 24px rgba(37, 99, 235, 0.6);
  }
  h1 {
    text-align: center;
    margin-bottom: 24px;
    font-weight: 700;
    font-size: 1.8rem;
    background: linear-gradient(135deg, #60a5fa, #2563eb);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }
  form {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }
  label {
    font-weight: 600;
    font-size: 0.9rem;
  }
  input[type="text"], input[type="email"], input[type="password"] {
    padding: 12px;
    border-radius: 8px;
    border: none;
    font-size: 1rem;
  }
  input:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
  }
  button {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 14px;
    font-weight: 700;
    font-size: 1rem;
    border-radius: 10px;
    cursor: pointer;
    transition: background-color 0.25s ease;
  }
  button:hover {
    background: #2563eb;
  }
  .messages {
    margin-bottom: 16px;
  }
  .error {
    background: #dc2626;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 8px;
  }
  .success {
    background: #16a34a;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 8px;
  }
  @media (max-width: 400px) {
    .container {
      width: 90vw;
      padding: 24px;
    }
  }
</style>
</head>
<body>
  <div class="container" role="main">
    <h1>Cadastro de Usuário</h1>

    <div class="messages" aria-live="polite" aria-atomic="true">
      <?php if ($errors): ?>
        <?php foreach ($errors as $error): ?>
          <div class="error" role="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endforeach; ?>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="success" role="alert"><?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>
    </div>

    <form action="" method="POST" novalidate>
      <label for="username">Nome de Usuário</label>
      <input
        type="text"
        id="username"
        name="username"
        placeholder="Digite seu nome"
        value="<?php echo htmlspecialchars($username); ?>"
        required
        minlength="3"
        maxlength="50"
        autocomplete="username"
      />

      <label for="email">Email</label>
      <input
        type="email"
        id="email"
        name="email"
        placeholder="seu@email.com"
        value="<?php echo htmlspecialchars($email); ?>"
        required
        autocomplete="email"
      />

      <label for="password">Senha</label>
      <input
        type="password"
        id="password"
        name="password"
        placeholder="Mínimo 8 caracteres"
        required
        minlength="8"
        autocomplete="new-password"
      />

      <label for="password_confirm">Confirme a Senha</label>
      <input
        type="password"
        id="password_confirm"
        name="password_confirm"
        placeholder="Repita a senha"
        required
        minlength="8"
        autocomplete="new-password"
      />

      <button type="submit">Cadastrar</button>
    </form>
  </div>
</body>
</html>

