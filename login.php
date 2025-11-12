<?php
session_start();
require 'conexao.php'; 

function login_fail(string $error_code) {
    header("Location: login.html?error=" . $error_code);
    exit();
}
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$senha = $_POST['senha'] ?? '';

if (empty($email) || empty($senha)) {
    login_fail('empty_fields');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    login_fail('invalid_email_format');
}
$user = null;
$tipo = null;

if ($email === ADMIN_EMAIL) {
    if (password_verify($senha, ADMIN_PASSWORD_HASH)) {
        $user = ['id' => 0]; 
        $tipo = 'admin';
    } else {
        login_fail('invalid_credentials');
    } } 

if (!$user) { 
    $tabelas = [
        'pacientes' => 'paciente',
        'medicos' => 'medico',
        'clinicas' => 'clinica'; ];
    foreach ($tabelas as $tabela => $t) {
        $stmt = $pdo->prepare("SELECT id, senha FROM $tabela WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($fetchedUser = $stmt->fetch()) { 
            if (password_verify($senha, $fetchedUser['senha'])) {
                $user = $fetchedUser;
                $tipo = $t;
                break; 
    } } } }

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
