<?php
include 'auth.php'; // Inclui o controle de sessão

// Verifica se o usuário tem permissão de acesso
if (!hasPermission(['ADM'])) {
    echo "Acesso negado.";
    exit;
}

$stmt = $pdo->query("SELECT logs.id, users.username, logs.action, logs.created_at FROM logs JOIN users ON logs.user_id = users.id ORDER BY logs.created_at DESC");
$logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Logs do Sistema</title>
</head>
<body>
    <h1>Logs do Sistema</h1>
    <table>
        <tr>
            <th>ID</th>
            <th>Usuário</th>
            <th>Ação</th>
            <th>Data</th>
        </tr>
        <?php foreach ($logs as $log): ?>
        <tr>
            <td><?php echo htmlspecialchars($log['id']); ?></td>
            <td><?php echo htmlspecialchars($log['username']); ?></td>
            <td><?php echo htmlspecialchars($log['action']); ?></td>
            <td><?php echo htmlspecialchars($log['created_at']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
