<?php
session_start();
require 'conexao.php'; 

function login_fail(string $error_code) {
    header("Location: login.html?error=" . $error_code);
    exit();
}

// Sanitização dos inputs
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$senha = $_POST['senha'] ?? '';
// Validação básica
if (empty($email) || empty($senha)) {
    login_fail('empty_fields');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    login_fail('invalid_email_format');
}
$user = null;
$tipo = null;

// =========================================================
// CHECAGEM PRIORITÁRIA: ADMINISTRADOR HARDCODED
// =========================================================
if ($email === ADMIN_EMAIL) {
    if (password_verify($senha, ADMIN_PASSWORD_HASH)) {
        // Login Admin SUCESSO. Define Admin e ignora o resto das checagens.
        $user = ['id' => 0]; // ID 0 para Admin
        $tipo = 'admin';
    } else {
        // Falha no login do Admin
        login_fail('invalid_credentials');
    } } 

// =========================================================
// CHECAGEM 2: OUTROS USUÁRIOS (SÓ RODA SE O ADMIN NÃO FOI LOGADO)
// =========================================================
if (!$user) { // Se o Admin não foi logado
    $tabelas = [
        'pacientes' => 'paciente',
        'medicos' => 'medico',
        'clinicas' => 'clinica'
    ];
    foreach ($tabelas as $tabela => $t) {
        $stmt = $pdo->prepare("SELECT id, senha FROM $tabela WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($fetchedUser = $stmt->fetch()) { 
            if (password_verify($senha, $fetchedUser['senha'])) {
                $user = $fetchedUser;
                $tipo = $t;
                break; // Sai do loop após o primeiro sucesso de login
             } } } }

// Verifica se o login foi bem-sucedido (se $user foi definido)
if ($user) {
    session_regenerate_id(true); 
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['tipo'] = $tipo;
    $_SESSION['email'] = $email;
    header('Location: sistema.php');
    exit();
} else {
    login_fail('invalid_credentials');
}
?>