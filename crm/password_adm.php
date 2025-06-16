<?php
include 'conexao.php'; // Inclua o arquivo de conexão

// Crie um hash da senha
$hashed_password = password_hash('senhaSegura123', PASSWORD_DEFAULT);

// Insira o usuário ADM no banco de dados
$stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
$stmt->execute(['admin', 'admin@example.com', $hashed_password, 'ADM']);

echo "Usuário ADM cadastrado com sucesso!";
?>
