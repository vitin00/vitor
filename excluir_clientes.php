<?php
session_start();
include 'conexao.php';
verificaLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = (int)$_POST['cliente_id'];
    
    // Verifica se o cliente existe
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ? AND user_id = ?");
    $stmt->execute([$cliente_id, $_SESSION['user_id']]);
    $cliente = $stmt->fetch();

    if ($cliente) {
        $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
        if ($stmt->execute([$cliente_id])) {
            header('Location: clientes_listar.php');
            exit;
        } else {
            echo "Erro ao excluir cliente.";
        }
    } else {
        echo "Cliente não encontrado.";
    }
}
?>
