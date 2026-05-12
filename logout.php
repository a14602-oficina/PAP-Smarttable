<?php
session_start();

/* Limpar todas as variáveis de sessão */
$_SESSION = [];

/* Destruir sessão */
session_destroy();

/* Redirecionar para login */
header("Location: login.php");
exit;
?>