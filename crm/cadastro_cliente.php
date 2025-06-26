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

    // Verifica se email já existe
    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM clientes WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Este email já está cadastrado.';
        }
    }

    // Se não há erros, cadastra o cliente
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('INSERT INTO clientes (nome, email, telefone, cpf_cnpj, tipo_pessoa, cep, endereco, numero, complemento, bairro, cidade, estado, data_nascimento, observacoes, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            
            if ($stmt->execute([$nome, $email, $telefone, $cpf_cnpj, $tipo_pessoa, $cep, $endereco, $numero, $complemento, $bairro, $cidade, $estado, $data_nascimento ?: null, $observacoes, $_SESSION['user_id']])) {
                $cliente_id = $pdo->lastInsertId();
                
                // Registra log
                registrarLog($pdo, $cliente_id, $_SESSION['user_id'], 'create', null, [
                    'nome' => $nome,
                    'email' => $email,
                    'telefone' => $telefone
                ]);
                
                $success = 'Cliente cadastrado com sucesso!';
                
                // Limpa os campos após o sucesso
                $nome = $email = $telefone = $cpf_cnpj = $cep = $endereco = $numero = $complemento = $bairro = $cidade = $estado = $data_nascimento = $observacoes = '';
                $tipo_pessoa = 'PF';
            } else {
                $errors[] = 'Erro ao cadastrar cliente.';
            }
        } catch (Exception $e) {
            $errors[] = 'Erro ao cadastrar cliente: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Cliente - CRM</title>
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
            transition: all 0.3s ease;
            background: white;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .cep-group {
            display: flex;
            gap: 10px;
            align-items: end;
        }
        
        .cep-group input {
            flex: 1;
        }
        
        .btn-buscar-cep {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s ease;
        }
        
        .btn-buscar-cep:hover {
            background: #5a67d8;
        }
        
        .btn-buscar-cep:disabled {
            background: #a0aec0;
            cursor: not-allowed;
        }
        
        .endereco-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 15px;
        }
        
        .endereco-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 16px 40px;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .messages {
            margin-bottom: 30px;
        }
        
        .error {
            background: #fed7d7;
            color: #c53030;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 10px;
            border-left: 4px solid #c53030;
        }
        
        .success {
            background: #c6f6d5;
            color: #2f855a;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 10px;
            border-left: 4px solid #2f855a;
        }
        
        .back-link {
            display: inline-block;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            transition: color 0.3s ease;
        }
        
        .back-link:hover {
            color: #5a67d8;
        }
        
        .loading {
            display: none;
            color: #667eea;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 10px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .endereco-grid,
            .endereco-row {
                grid-template-columns: 1fr;
            }
            
            .cep-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="listagem_cliente.php" class="back-link">← Voltar para lista de clientes</a>
        
        <div class="header">
            <h1>Cadastro de Cliente</h1>
            <p>Preencha os dados do cliente</p>
        </div>

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

        <form method="POST">
            <div class="form-grid">
                <div class="form-group full-width">
                    <label for="nome">Nome Completo *</label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($nome ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="telefone">Telefone *</label>
                    <input type="tel" id="telefone" name="telefone" value="<?php echo htmlspecialchars($telefone ?? ''); ?>" placeholder="(11) 99999-9999" required>
                </div>

                <div class="form-group">
                    <label for="tipo_pessoa">Tipo de Pessoa *</label>
                    <select id="tipo_pessoa" name="tipo_pessoa" required>
                        <option value="PF" <?php echo ($tipo_pessoa ?? 'PF') === 'PF' ? 'selected' : ''; ?>>Pessoa Física</option>
                        <option value="PJ" <?php echo ($tipo_pessoa ?? '') === 'PJ' ? 'selected' : ''; ?>>Pessoa Jurídica</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="cpf_cnpj">CPF/CNPJ *</label>
                    <input type="text" id="cpf_cnpj" name="cpf_cnpj" value="<?php echo htmlspecialchars($cpf_cnpj ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="data_nascimento">Data de Nascimento</label>
                    <input type="date" id="data_nascimento" name="data_nascimento" value="<?php echo htmlspecialchars($data_nascimento ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="cep">CEP</label>
                    <div class="cep-group">
                        <input type="text" id="cep" name="cep" value="<?php echo htmlspecialchars($cep ?? ''); ?>" placeholder="00000-000" maxlength="9">
                        <button type="button" class="btn-buscar-cep" onclick="buscarCep()">Buscar</button>
                    </div>
                    <div class="loading" id="loading-cep">Buscando CEP...</div>
                </div>

                <div class="form-group full-width">
                    <div class="endereco-grid">
                        <div class="form-group">
                            <label for="endereco">Endereço</label>
                            <input type="text" id="endereco" name="endereco" value="<?php echo htmlspecialchars($endereco ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="numero">Número</label>
                            <input type="text" id="numero" name="numero" value="<?php echo htmlspecialchars($numero ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="complemento">Complemento</label>
                    <input type="text" id="complemento" name="complemento" value="<?php echo htmlspecialchars($complemento ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="bairro">Bairro</label>
                    <input type="text" id="bairro" name="bairro" value="<?php echo htmlspecialchars($bairro ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="cidade">Cidade</label>
                    <input type="text" id="cidade" name="cidade" value="<?php echo htmlspecialchars($cidade ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado">
                        <option value="">Selecione...</option>
                        <option value="AC" <?php echo ($estado ?? '') === 'AC' ? 'selected' : ''; ?>>Acre</option>
                        <option value="AL" <?php echo ($estado ?? '') === 'AL' ? 'selected' : ''; ?>>Alagoas</option>
                        <option value="AP" <?php echo ($estado ?? '') === 'AP' ? 'selected' : ''; ?>>Amapá</option>
                        <option value="AM" <?php echo ($estado ?? '') === 'AM' ? 'selected' : ''; ?>>Amazonas</option>
                        <option value="BA" <?php echo ($estado ?? '') === 'BA' ? 'selected' : ''; ?>>Bahia</option>
                        <option value="CE" <?php echo ($estado ?? '') === 'CE' ? 'selected' : ''; ?>>Ceará</option>
                        <option value="DF" <?php echo ($estado ?? '') === 'DF' ? 'selected' : ''; ?>>Distrito Federal</option>
                        <option value="ES" <?php echo ($estado ?? '') === 'ES' ? 'selected' : ''; ?>>Espírito Santo</option>
                        <option value="GO" <?php echo ($estado ?? '') === 'GO' ? 'selected' : ''; ?>>Goiás</option>
                        <option value="MA" <?php echo ($estado ?? '') === 'MA' ? 'selected' : ''; ?>>Maranhão</option>
                        <option value="MT" <?php echo ($estado ?? '') === 'MT' ? 'selected' : ''; ?>>Mato Grosso</option>
                        <option value="MS" <?php echo ($estado ?? '') === 'MS' ? 'selected' : ''; ?>>Mato Grosso do Sul</option>
                        <option value="MG" <?php echo ($estado ?? '') === 'MG' ? 'selected' : ''; ?>>Minas Gerais</option>
                        <option value="PA" <?php echo ($estado ?? '') === 'PA' ? 'selected' : ''; ?>>Pará</option>
                        <option value="PB" <?php echo ($estado ?? '') === 'PB' ? 'selected' : ''; ?>>Paraíba</option>
                        <option value="PR" <?php echo ($estado ?? '') === 'PR' ? 'selected' : ''; ?>>Paraná</option>
                        <option value="PE" <?php echo ($estado ?? '') === 'PE' ? 'selected' : ''; ?>>Pernambuco</option>
                        <option value="PI" <?php echo ($estado ?? '') === 'PI' ? 'selected' : ''; ?>>Piauí</option>
                        <option value="RJ" <?php echo ($estado ?? '') === 'RJ' ? 'selected' : ''; ?>>Rio de Janeiro</option>
                        <option value="RN" <?php echo ($estado ?? '') === 'RN' ? 'selected' : ''; ?>>Rio Grande do Norte</option>
                        <option value="RS" <?php echo ($estado ?? '') === 'RS' ? 'selected' : ''; ?>>Rio Grande do Sul</option>
                        <option value="RO" <?php echo ($estado ?? '') === 'RO' ? 'selected' : ''; ?>>Rondônia</option>
                        <option value="RR" <?php echo ($estado ?? '') === 'RR' ? 'selected' : ''; ?>>Roraima</option>
                        <option value="SC" <?php echo ($estado ?? '') === 'SC' ? 'selected' : ''; ?>>Santa Catarina</option>
                        <option value="SP" <?php echo ($estado ?? '') === 'SP' ? 'selected' : ''; ?>>São Paulo</option>
                        <option value="SE" <?php echo ($estado ?? '') === 'SE' ? 'selected' : ''; ?>>Sergipe</option>
                        <option value="TO" <?php echo ($estado ?? '') === 'TO' ? 'selected' : ''; ?>>Tocantins</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" rows="4" placeholder="Observações adicionais sobre o cliente..."><?php echo htmlspecialchars($observacoes ?? ''); ?></textarea>
                </div>
            </div>

            <button type="submit" class="btn-submit">Cadastrar Cliente</button>
        </form>
    </div>

    <script>
        // Função para buscar CEP
        async function buscarCep() {
            const cepInput = document.getElementById('cep');
            const cep = cepInput.value.replace(/\D/g, '');
            const loading = document.getElementById('loading-cep');
            const btnBuscar = document.querySelector('.btn-buscar-cep');
            
            if (cep.length === 8) {
                loading.style.display = 'block';
                btnBuscar.disabled = true;

                try {
                    const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                    const data = await response.json();

                    if (!data.erro) {
                        document.getElementById('endereco').value = data.logradouro;
                        document.getElementById('bairro').value = data.bairro;
                        document.getElementById('cidade').value = data.localidade;
                        document.getElementById('estado').value = data.uf;
                    } else {
                        alert('CEP não encontrado.');
                    }
                } catch (error) {
                    alert('Erro ao buscar CEP.');
                } finally {
                    loading.style.display = 'none';
                    btnBuscar.disabled = false;
                }
            } else {
                alert('Por favor, insira um CEP válido.');
            }
        }
    </script>
</body>
</html>
