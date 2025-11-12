<?php
session_start();
require 'conexao.php';
$user_id = $_SESSION['user_id'] ?? null;
$user_tipo = $_SESSION['tipo'] ?? null;

// Apenas 'medico' ou 'clinica' podem gerenciar agendamentos.
if (!isset($user_id) || !in_array($user_tipo, ['medico', 'clinica'])) {
    header("Location: sistema.php?error=unauthorized_action");
    exit();
}

// OBTENÇÃO E VALIDAÇÃO DOS DADOS
$agendamento_id = filter_input(INPUT_POST, 'agendamento_id', FILTER_VALIDATE_INT);
$nova_status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
// Validação do ID e do Status
$valid_statuses = ['confirmado', 'cancelado', 'pendente', 'realizado'];
if ($agendamento_id === false || $agendamento_id <= 0 || !in_array($nova_status, $valid_statuses)) {
    header("Location: sistema.php?error=invalid_data");
    exit();
}
// Determina qual coluna do banco de dados usar para checar a posse
$id_column = ($user_tipo === 'medico') ? 'medico_id' : 'clinica_id';

// Verifica se o agendamento existe E pertence ao usuário logado (Médico ou Clínica)
$stmt_check = $pdo->prepare("
    SELECT id 
    FROM agendamentos 
    WHERE id = :agendamento_id 
    AND {$id_column} = :user_id
");
$stmt_check->execute([
    'agendamento_id' => $agendamento_id,
    'user_id' => $user_id
]);
if (!$stmt_check->fetch()) {
    header("Location: sistema.php?error=agendamento_nao_encontrado");
    exit();
}

// ATUALIZAÇÃO DO STATUS
$stmt_update = $pdo->prepare("
    UPDATE agendamentos 
    SET status = :nova_status 
    WHERE id = :agendamento_id
");

try {
    $stmt_update->execute([
        'nova_status' => $nova_status,
        'agendamento_id' => $agendamento_id
    ]);
    $success_message = urlencode("Agendamento #{$agendamento_id} atualizado para '{$nova_status}'.");
    header("Location: sistema.php?success={$success_message}");
    exit();

} catch (PDOException $e) {
    header("Location: sistema.php?error=database_update_failed");
    exit();
}