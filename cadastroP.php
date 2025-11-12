<?php
session_start();
require 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: cadastroP.html");
    exit();
}

$input = filter_input_array(INPUT_POST, [
    'nome' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'cpf' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'nascimento' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'genero' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'telefone' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'email' => FILTER_VALIDATE_EMAIL,
    'senha' => FILTER_DEFAULT ]);
$senha = $_POST['senha'] ?? ''; 
extract($input);

if (empty($nome) || empty($cpf) || empty($nascimento) || empty($telefone) || empty($senha) || $email === false || empty($email)) {
    header("Location: cadastroP.html?error=empty_or_invalid_fields");
    exit();
}
$stmt = $pdo->prepare("SELECT COUNT(*) FROM pacientes WHERE email = :email OR cpf = :cpf");
$stmt->execute(['email' => $email, 'cpf' => $cpf]);

if ($stmt->fetchColumn() > 0) {
    header("Location: cadastroP.html?error=email_or_cpf_already_registered");
    exit();
}
$senhaHash = password_hash($senha, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO pacientes (nome, cpf, nascimento, genero, telefone, email, senha) VALUES (:nome, :cpf, :nascimento, :genero, :telefone, :email, :senha)");

try {
    $stmt->execute(compact('nome', 'cpf', 'nascimento', 'genero', 'telefone', 'email') + ['senha' => $senhaHash]);
    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['tipo'] = 'paciente';
    $_SESSION['email'] = $email;
    header("Location: sistema.php?success=cadastro_paciente_success");
    exit();
} catch (PDOException $e) {
    header("Location: cadastroP.html?error=database_insertion_failed");
    exit();
}
