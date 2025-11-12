<?php
session_start();
require 'conexao.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: cadastroC.html");
    exit();
}

$input = filter_input_array(INPUT_POST, [
    'nome' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'cnpj' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'cep' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'telefone' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'email' => FILTER_VALIDATE_EMAIL,
    'senha' => FILTER_DEFAULT,
    // Filtro especial para 'especialidades' como um array
    'especialidades' => [
        'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'flags'  => FILTER_REQUIRE_ARRAY
    ]
]);
extract($input); // $especialidades agora é um array
$senha = $_POST['senha'] ?? ''; 

// Validação de campos obrigatórios (checa se o array de especialidades não está vazio)
if (empty($nome) || empty($cnpj) || empty($especialidades) || !is_array($especialidades) || empty($cep) || empty($telefone) || empty($senha) || $email === false || empty($email)) {
    header("Location: cadastroC.html?error=empty_or_invalid_fields");
    exit();
} $especialidades_str = implode(', ', $especialidades);

// Verificação de Duplicidade
$stmt = $pdo->prepare("SELECT COUNT(*) FROM clinicas WHERE email = :email OR cnpj = :cnpj");
$stmt->execute(['email' => $email, 'cnpj' => $cnpj]);
if ($stmt->fetchColumn() > 0) {
    header("Location: cadastroC.html?error=email_or_cnpj_already_registered");
    exit();
}

// Inserção no Banco de Dados
$senhaHash = password_hash($senha, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO clinicas (nome, cnpj, especialidades, cep, telefone, email, senha) 
                       VALUES (:nome, :cnpj, :especialidades, :cep, :telefone, :email, :senha_hash)");
try {
    $stmt->execute([
        'nome' => $nome,
        'cnpj' => $cnpj,
        'especialidades' => $especialidades_str,
        'cep' => $cep,
        'telefone' => $telefone,
        'email' => $email,
        'senha_hash' => $senhaHash
    ]);

    // simula o login
    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['tipo'] = 'clinica';
    $_SESSION['email'] = $email;
    header("Location: sistema.php?success=cadastro_clinica_success");
    exit();

} catch (PDOException $e) {
    header("Location: cadastroC.html?error=database_insertion_failed");
    exit();
}