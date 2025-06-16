<?php
session_start();
session_unset(); // limpa as variáveis da sessão
session_destroy(); // destrói a sessão

header('Location: login.php'); // Redireciona para o login
exit;
