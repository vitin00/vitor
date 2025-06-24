<?php
session_start();
include 'conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$errors = [];
$success = '';
$cliente_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$cliente_id) {
    header('Location: listar_clientes.php');
    exit;
}

// Função para registrar logs
function registrarLog($pdo, $cliente_id, $user_id, $acao, $dados_anteriores = null, $dados_novos = null) {
    $stmt = $pdo->prepare("INSERT INTO cliente_logs (cliente_id, user_id, acao, dados_anteriores, dados_novos, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $cliente_id,
        $user_id,
        $acao,
        $dados_anteriores ? json_encode($dados_anteriores) : null,
        $dados_novos ? json_encode($dados_novos) : null,
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}

// Busca o cliente
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$cliente_id]);
$cliente = $stmt->fetch();

if (!$cliente) {
    header('Location: listar_clientes.php');
    exit;
}

// Verifica permissão: ADM pode editar qualquer cliente, usuário comum só o seu
if ($_SESSION['role'] !== 'ADM' && $cliente['user_id'] != $_SESSION['user_id']) {
    echo "Você não tem permissão para editar este cliente.";
    exit;
}

// Dados anteriores para o log
$dados_anteriores = [
    'nome' => $cliente['nome'],
    'email' => $cliente['email'],
    'telefone' => $cliente['telefone'],
    'status' => $cliente['status']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitização dos dados
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $cpf_cnpj = trim($_POST['cpf_cnpj'] ?? '');
    $tipo_pessoa = $_POST['tipo_pessoa'] ?? 'PF';
    $cep = trim($_POST['cep'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $numero = trim($_POST['numero'] ?? '');
    $complemento = trim($_POST['complemento'] ?? '');
    $bairro = trim($_POST['bairro'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $estado = trim($_POST['estado'] ?? '');
    $data_nascimento = $_POST['data_nascimento'] ?? '';
    $observacoes = trim($_POST['observacoes'] ?? '');
    $status = $_POST['status'] ?? 'ativo';

    // Validações
    if (empty($nome)) {
        $errors[] = 'Nome é obrigatório.';
    }

    if (empty($email)) {
        $errors[] = 'Email é obrigatório.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email inválido.';
    }

    if (empty($telefone)) {
        $errors[] = 'Telefone é obrigatório.';
    }

    if (empty($cpf_cnpj)) {
        $errors[] = 'CPF/CNPJ é obrigatório.';
    }

    // Verifica se email já existe (exceto o atual)
    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM clientes WHERE email = ? AND id != ?');
        $stmt->execute([$email, $cliente_id]);
        if ($stmt->fetch()) {
            $errors[] = 'Este email já está cadastrado em outro cliente.';
        }
    }

    // Se não há erros, atualiza o cliente
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('UPDATE clientes SET nome = ?, email = ?, telefone = ?, cpf_cnpj = ?, tipo_pessoa = ?, cep = ?, endereco = ?, numero = ?, complemento = ?, bairro = ?, cidade = ?, estado = ?, data_nascimento = ?, observacoes = ?, status = ? WHERE id = ?');
            
            if ($stmt->execute([$nome, $email, $telefone, $cpf_cnpj, $tipo_pessoa, $cep, $endereco, $numero, $complemento, $bairro, $cidade, $estado, $data_nascimento ?: null, $observacoes, $status, $cliente_id])) {
                
                // Registra log da alteração
                $dados_novos = [
                    'nome' => $nome,
                    'email' => $email,
                    'telefone' => $telefone,
                    'status' => $status
                ];
                
                registrarLog($pdo, $cliente_id, $_SESSION['user_id'], 'update', $dados_anteriores, $dados_novos);
                
                $success = 'Cliente atualizado com sucesso!';
                
                // Atualiza os dados do cliente para exibir na tela
                $cliente['nome'] = $nome;
                $cliente['email'] = $email;
                $cliente['telefone'] = $telefone;
                $cliente['cpf_cnpj'] = $cpf_cnpj;
                $cliente['tipo_pessoa'] = $tipo_pessoa;
                $cliente['cep'] = $cep;
                $cliente['endereco'] = $endereco;
                $cliente['numero'] = $numero;
                $cliente['complemento'] = $complemento;
                $cliente['bairro'] = $bairro;
                $cliente['cidade'] = $cidade;
                $cliente['estado'] = $estado;
                $cliente['data_nascimento'] = $data_nascimento;
                $cliente['observacoes'] = $observacoes;
                $cliente['status'] = $status;
                
            } else {
                $errors[] = 'Erro ao atualizar cliente.';
            }
        } catch (Exception $e) {
            $errors[] = 'Erro ao atualizar cliente: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cliente - CRM</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .header h1 {
            color: #2d3748;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #718096;
            font-size: 1.1rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        
        input, select, textarea {
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        input:focus, select:focus, textarea:focus {
            border-color: #667eea;
            outline: none;
        }

        .error {
            color: #e53e3e;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .success {
            color: #38a169;
            font-size: 1rem;
            margin-bottom: 20px;
        }

        .btn {
            background-color: #667eea;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #5a67d8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Editar Cliente</h1>
            <p>Atualize as informações do cliente abaixo.</p>
        </div>

        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label for="nome">Nome</label>
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($cliente['nome']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($cliente['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="telefone">Telefone</label>
                    <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($cliente['telefone']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="cpf_cnpj">CPF/CNPJ</label>
                    <input type="text" id="cpf_cnpj" name="cpf_cnpj" value="<?= htmlspecialchars($cliente['cpf_cnpj']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="tipo_pessoa">Tipo de Pessoa</label>
                    <select id="tipo_pessoa" name="tipo_pessoa">
                        <option value="PF" <?= $cliente['tipo_pessoa'] === 'PF' ? 'selected' : '' ?>>Pessoa Física</option>
                        <option value="PJ" <?= $cliente['tipo_pessoa'] === 'PJ' ? 'selected' : '' ?>>Pessoa Jurídica</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="cep">CEP</label>
                    <input type="text" id="cep" name="cep" value="<?= htmlspecialchars($cliente['cep']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="endereco">Endereço</label>
                    <input type="text" id="endereco" name="endereco" value="<?= htmlspecialchars($cliente['endereco']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="numero">Número</label>
                    <input type="text" id="numero" name="numero" value="<?= htmlspecialchars($cliente['numero']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="complemento">Complemento</label>
                    <input type="text" id="complemento" name="complemento" value="<?= htmlspecialchars($cliente['complemento']) ?>">
                </div>
                <div class="form-group">
                    <label for="bairro">Bairro</label>
                    <input type="text" id="bairro" name="bairro" value="<?= htmlspecialchars($cliente['bairro']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="cidade">Cidade</label>
                    <input type="text" id="cidade" name="cidade" value="<?= htmlspecialchars($cliente['cidade']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="estado">Estado</label>
                    <input type="text" id="estado" name="estado" value="<?= htmlspecialchars($cliente['estado']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="data_nascimento">Data de Nascimento</label>
                    <input type="date" id="data_nascimento" name="data_nascimento" value="<?= htmlspecialchars($cliente['data_nascimento']) ?>">
                </div>
                <div class="form-group">
                    <label for="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" rows="4"><?= htmlspecialchars($cliente['observacoes']) ?></textarea>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="ativo" <?= $cliente['status'] === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                        <option value="inativo" <?= $cliente['status'] === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn">Atualizar Cliente</button>
        </form>
    </div>
</body>
</html>
