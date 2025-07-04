<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'conexao.php';

$errors = [];
$success = '';
$username = '';
$email = '';
$cep = '';

$default_role = 'User';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $cep = trim($_POST['cep'] ?? '');

    if (empty($username)) {
        $errors[] = 'O nome de usuário é obrigatório.';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = 'O nome de usuário deve ter entre 3 e 50 caracteres.';
    }

    if (empty($email)) {
        $errors[] = 'O email é obrigatório.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'O email informado não é válido.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Este email já está cadastrado.';
        }
    }

    if (empty($password)) {
        $errors[] = 'A senha é obrigatória.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'A senha deve ter no mínimo 8 caracteres.';
    }

    if ($password !== $password_confirm) {
        $errors[] = 'As senhas não coincidem.';
    }

    if (empty($cep)) {
        $errors[] = 'O CEP é obrigatório.';
    } elseif (!preg_match('/^[0-9]{5}-?[0-9]{3}$/', $cep)) {
        $errors[] = 'CEP inválido.';
    }

    if (empty($errors)) {
        $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (username, email, password, role, cep) VALUES (?, ?, ?, ?, ?)');
        if ($stmt->execute([$username, $email, $hashed_pass, $default_role, $cep])) {
            $success = "Usuário cadastrado com sucesso!";
            $username = '';
            $email = '';
            $cep = '';
        } else {
            $errors[] = 'Erro ao cadastrar o usuário. Tente novamente.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cadastro de Usuário</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: 'Inter', sans-serif;
        background: linear-gradient(135deg, #111827, #1f2937);
        color: #f9fafb;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 20px;
    }

    .container {
        background: #1f2937;
        padding: 32px;
        border-radius: 16px;
        width: 100%;
        max-width: 420px;
        box-shadow: 0 0 30px rgba(0, 0, 0, 0.5);
    }

    h1 {
        text-align: center;
        margin-bottom: 24px;
        font-weight: 700;
        font-size: 1.8rem;
        color: #3b82f6;
    }

    .error, .success {
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 12px;
        font-size: 0.95rem;
        font-weight: 600;
    }

    .error {
        background: #dc2626;
        color: #fff;
    }

    .success {
        background: #16a34a;
        color: #fff;
    }

    label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"] {
        width: 100%;
        padding: 12px;
        margin-bottom: 18px;
        border-radius: 8px;
        border: 1px solid #374151;
        background: #111827;
        color: #f9fafb;
        font-size: 1rem;
        transition: border 0.2s;
    }

    input:focus {
        border-color: #3b82f6;
        outline: none;
    }

    button {
        width: 100%;
        background: #3b82f6;
        color: white;
        padding: 14px;
        border: none;
        border-radius: 10px;
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        transition: background 0.3s;
    }

    button:hover {
        background: #2563eb;
    }

    .address-info {
        background: #374151;
        padding: 12px;
        border-radius: 10px;
        margin-bottom: 18px;
        display: none;
    }

    .address-info p {
        margin: 6px 0;
        font-size: 0.95rem;
    }

    @media (max-width: 480px) {
        .container {
            padding: 24px;
        }
    }
</style>

<script>
function buscarCep() {
    const cepInput = document.getElementById('cep');
    const cep = cepInput.value.replace(/\D/g, '');

    if (cep.length === 8) {
        fetch(`https://viacep.com.br/ws/${cep}/json/`)
            .then(response => response.json())
            .then(data => {
                if (!data.erro) {
                    document.getElementById('logradouro').value = data.logradouro;
                    document.getElementById('bairro').value = data.bairro;
                    document.getElementById('cidade').value = data.localidade;
                    document.getElementById('uf').value = data.uf;

                    document.getElementById('logradouro_view').innerText = data.logradouro;
                    document.getElementById('bairro_view').innerText = data.bairro;
                    document.getElementById('cidade_view').innerText = data.localidade;
                    document.getElementById('uf_view').innerText = data.uf;

                    document.getElementById('address-info').style.display = 'block';
                } else {
                    alert('CEP não encontrado.');
                    document.getElementById('address-info').style.display = 'none';
                }
            })
            .catch(error => {
                alert('Erro ao buscar o CEP.');
                console.error(error);
            });
    } else {
        alert('CEP inválido.');
        document.getElementById('address-info').style.display = 'none';
    }
}
</script>
</head>
<body>
<div class="container">
    <h1>Cadastro de Usuário</h1>

    <div class="messages">
        <?php if ($errors): ?>
            <?php foreach ($errors as $error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
    </div>

    <form method="POST" novalidate>
        <label for="username">Nome de Usuário</label>
        <input type="text" id="username" name="username" placeholder="Digite seu nome" value="<?php echo htmlspecialchars($username); ?>" required minlength="3" maxlength="50">

        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="seu@email.com" value="<?php echo htmlspecialchars($email); ?>" required>

        <label for="cep">CEP</label>
        <input type="text" id="cep" name="cep" placeholder="Digite seu CEP" maxlength="9" onblur="buscarCep()" required value="<?php echo htmlspecialchars($cep); ?>">

        <!-- Campos ocultos -->
        <input type="hidden" id="logradouro" name="logradouro">
        <input type="hidden" id="bairro" name="bairro">
        <input type="hidden" id="cidade" name="cidade">
        <input type="hidden" id="uf" name="uf">

        <div id="address-info" class="address-info">
            <p><strong>Rua:</strong> <span id="logradouro_view"></span></p>
            <p><strong>Bairro:</strong> <span id="bairro_view"></span></p>
            <p><strong>Cidade:</strong> <span id="cidade_view"></span></p>
            <p><strong>Estado (UF):</strong> <span id="uf_view"></span></p>
        </div>

        <label for="password">Senha</label>
        <input type="password" id="password" name="password" placeholder="Mínimo 8 caracteres" required minlength="8">

        <label for="password_confirm">Confirme a Senha</label>
        <input type="password" id="password_confirm" name="password_confirm" placeholder="Repita a senha" required minlength="8">

        <button type="submit">Cadastrar</button>
    </form>
</div>
</body>
</html>
