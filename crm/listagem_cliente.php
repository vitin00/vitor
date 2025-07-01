<?php
session_start();
include 'conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Configurações de paginação
$registros_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $registros_por_pagina;

// Filtros de busca
$filtro_nome = $_GET['nome'] ?? '';
$filtro_email = $_GET['email'] ?? '';
$filtro_status = $_GET['status'] ?? '';

// Construindo a query base
$where_conditions = [];
$params = [];

if (!empty($filtro_nome)) {
    $where_conditions[] = "c.nome LIKE ?";
    $params[] = "%$filtro_nome%";
}

if (!empty($filtro_email)) {
    $where_conditions[] = "c.email LIKE ?";
    $params[] = "%$filtro_email%";
}

if (!empty($filtro_status)) {
    $where_conditions[] = "c.status = ?";
    $params[] = $filtro_status;
}

// Usuários comuns só veem seus próprios clientes
if ($_SESSION['role'] !== 'ADM') {
    $where_conditions[] = "c.user_id = ?";
    $params[] = $_SESSION['user_id'];
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Query para contar total de registros
$count_sql = "SELECT COUNT(*) FROM clientes c $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_registros = $count_stmt->fetchColumn();
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Query principal para buscar os clientes
$sql = "SELECT c.*, u.username as cadastrado_por 
        FROM clientes c 
        LEFT JOIN users u ON c.user_id = u.id 
        $where_clause 
        ORDER BY c.created_at DESC 
        LIMIT $registros_por_pagina OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll();

// Função para excluir cliente
if (isset($_POST['excluir_cliente'])) {
    $cliente_id = (int)$_POST['cliente_id'];
    
    // Verifica se o usuário pode excluir este cliente
    $stmt = $pdo->prepare("SELECT user_id FROM clientes WHERE id = ?");
    $stmt->execute([$cliente_id]);
    $cliente = $stmt->fetch();
    
    if ($cliente && ($_SESSION['role'] === 'ADM' || $cliente['user_id'] == $_SESSION['user_id'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
            if ($stmt->execute([$cliente_id])) {
                $success_message = "Cliente excluído com sucesso!";
                // Recarrega a página para atualizar a lista
                header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
                exit;
            }
        } catch (Exception $e) {
            $error_message = "Erro ao excluir cliente: " . $e->getMessage();
        }
    } else {
        $error_message = "Você não tem permissão para excluir este cliente.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Clientes - CRM</title>
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
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header h1 {
            color: #2d3748;
            font-size: 2.2rem;
            font-weight: 700;
        }
        
        .btn-novo {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: inline-block;
        }
        
        .btn-novo:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .filters {
            background: #f7fafc;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .filters h3 {
            color: #2d3748;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            font-weight: 500;
            color: #4a5568;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        .filter-group input,
        .filter-group select {
            padding: 10px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: border-color 0.3s ease;
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-filtrar,
        .btn-limpar {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-filtrar {
            background: #667eea;
            color: white;
        }
        
        .btn-filtrar:hover {
            background: #5a67d8;
        }
        
        .btn-limpar {
            background: #e2e8f0;
            color: #4a5568;
        }
        
        .btn-limpar:hover {
            background: #cbd5e0;
        }
        
        .stats {
            background: #e6fffa;
            border: 1px solid #81e6d9;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: center;
        }
        
        .stats-text {
            color: #234e52;
            font-weight: 600;
        }
        
        .table-container {
            overflow-x: auto;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 15px 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.9rem;
        }
        
        tr:hover {
            background: #f7fafc;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-ativo {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .status-inativo {
            background: #fed7d7;
            color: #742a2a;
        }
        
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-editar {
            background: #ffd89b;
            color: #744210;
        }
        
        .btn-editar:hover {
            background: #f6ad55;
        }
        
        .btn-excluir {
            background: #fed7d7;
            color: #742a2a;
        }
        
        .btn-excluir:hover {
            background: #fc8181;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .pagination a,
        .pagination span {
            padding: 10px 15px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .pagination a {
            background: #e2e8f0;
            color: #4a5568;
        }
        
        .pagination a:hover {
            background: #667eea;
            color: white;
        }
        
        .pagination .current {
            background: #667eea;
            color: white;
        }
        
        .pagination .disabled {
            background: #f7fafc;
            color: #a0aec0;
            cursor: not-allowed;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            font-size: 1.1rem;
            margin-bottom: 20px;
        }
        
        .messages {
            margin-bottom: 20px;
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
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 10px;
            }
            
            .header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .table-container {
                font-size: 0.8rem;
            }
            
            th, td {
                padding: 10px 8px;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Lista de Clientes</h1>
            <a href="cadastro_cliente.php" class="btn-novo">+ Novo Cliente</a>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="messages">
                <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
            </div>
        <?php endif; ?>

        <?php if (isset($success_message)): ?>
            <div class="messages">
                <div class="success"><?php echo htmlspecialchars($success_message); ?></div>
            </div>
        <?php endif; ?>

        <!-- Filtros -->
<div class="filters">
    <h3>Filtros de Busca</h3>
    <form method="GET">
        <input type="hidden" name="pagina" value="1"> <!-- Para resetar a paginação -->
        <div class="filter-grid">
            <div class="filter-group">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($filtro_nome); ?>" placeholder="Buscar por nome...">
            </div>
            <div class="filter-group">
                <label for="email">Email</label>
                <input type="text" id="email" name="email" value="<?php echo htmlspecialchars($filtro_email); ?>" placeholder="Buscar por email...">
            </div>
            <div class="filter-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Todos</option>
                    <option value="ativo" <?php echo $filtro_status === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                    <option value="inativo" <?php echo $filtro_status === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                </select>
            </div>
        </div>
        <div class="filter-buttons">
            <button type="submit" class="btn-filtrar">Filtrar</button>
            <a href="<?php echo basename($_SERVER['PHP_SELF']); ?>" class="btn-limpar">Limpar Filtros</a>
        </div>
    </form>
</div>


        <!-- Estatísticas -->
        <div class="stats">
            <span class="stats-text">
                Mostrando <?php echo count($clientes); ?> de <?php echo $total_registros; ?> cliente(s) 
                - Página <?php echo $pagina_atual; ?> de <?php echo max(1, $total_paginas); ?>
            </span>
        </div>

        <?php if (empty($clientes)): ?>
            <div class="empty-state">
                <h3>Nenhum cliente encontrado</h3>
                <p>Não há clientes cadastrados com os filtros selecionados.</p>
                <a href="cadastro_cliente.php" class="btn-novo">Cadastrar Primeiro Cliente</a>
            </div>
        <?php else: ?>
            <!-- Tabela de clientes -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Telefone</th>
                            <th>Tipo</th>
                            <th>Cidade</th>
                            <th>Status</th>
                            <?php if ($_SESSION['role'] === 'ADM'): ?>
                                <th>Cadastrado por</th>
                            <?php endif; ?>
                            <th>Cadastrado em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($cliente['nome']); ?></strong></td>
                                <td><?php echo htmlspecialchars($cliente['email']); ?></td>
                                <td><?php echo htmlspecialchars($cliente['telefone'] ?: '-'); ?></td>
                                <td><?php echo $cliente['tipo_pessoa'] === 'PF' ? 'Pessoa Física' : 'Pessoa Jurídica'; ?></td>
                                <td><?php echo htmlspecialchars($cliente['cidade'] ?: '-'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $cliente['status']; ?>">
                                        <?php echo ucfirst($cliente['status']); ?>
                                    </span>
                                </td>
                                <?php if ($_SESSION['role'] === 'ADM'): ?>
                                    <td><?php echo htmlspecialchars($cliente['cadastrado_por']); ?></td>
                                <?php endif; ?>
                                <td><?php echo date('d/m/Y H:i', strtotime($cliente['created_at'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <?php if ($_SESSION['role'] === 'ADM' || $cliente['user_id'] == $_SESSION['user_id']): ?>
                                            <a href="editar_cliente.php?id=<?php echo $cliente['id']; ?>" class="btn-action btn-editar">Editar</a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir este cliente?')">
                                                <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
                                                <button type="submit" name="excluir_cliente" class="btn-action btn-excluir">Excluir</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color: #a0aec0; font-size: 0.8rem;">Sem permissão</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <?php if ($total_paginas > 1): ?>
                <div class="pagination">
                    <?php
                    $query_params = $_GET;
                    
                    // Link para página anterior
                    if ($pagina_atual > 1):
                        $query_params['pagina'] = $pagina_atual - 1;
                    ?>
                        <a href="?<?php echo http_build_query($query_params); ?>">← Anterior</a>
                    <?php else: ?>
                        <span class="disabled">← Anterior</span>
                    <?php endif; ?>

                    <?php
                    // Links das páginas
                    $inicio = max(1, $pagina_atual - 2);
                    $fim = min($total_paginas, $pagina_atual + 2);
                    
                    if ($inicio > 1) {
                        $query_params['pagina'] = 1;
                        echo '<a href="?' . http_build_query($query_params) . '">1</a>';
                        if ($inicio > 2) {
                            echo '<span class="disabled">...</span>';
                        }
                    }
                    
                    for ($i = $inicio; $i <= $fim; $i++) {
                        if ($i == $pagina_atual) {
                            echo '<span class="current">' . $i . '</span>';
                        } else {
                            $query_params['pagina'] = $i;
                            echo '<a href="?' . http_build_query($query_params) . '">' . $i . '</a>';
                        }
                    }
                    
                    if ($fim < $total_paginas) {
                        if ($fim < $total_paginas - 1) {
                            echo '<span class="disabled">...</span>';
                        }
                        $query_params['pagina'] = $total_paginas;
                        echo '<a href="?' . http_build_query($query_params) . '">' . $total_paginas . '</a>';
                    }
                    
                    // Link para próxima página
                    if ($pagina_atual < $total_paginas):
                        $query_params['pagina'] = $pagina_atual + 1;
                    ?>
                        <a href="?<?php echo http_build_query($query_params); ?>">Próxima →</a>
                    <?php else: ?>
                        <span class="disabled">Próxima →</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div style="margin-top: 30px; text-align: center;">
            <a href="dashboard.php" style="color: #667eea; text-decoration: none; font-weight: 600;">← Voltar ao Dashboard</a>
        </div>
    </div>
</body>
</html>


