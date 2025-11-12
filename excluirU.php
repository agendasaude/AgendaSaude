<?php
session_start();
require 'conexao.php'; 

// Apenas Admin pode acessar
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: login.html?error=admin_required');
    exit();
}
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$tipo_usuario = filter_input(INPUT_POST, 'tipo_usuario', FILTER_SANITIZE_STRING);

// Validação dos dados
$valid_types = ['medico', 'clinica'];
if ($id === false || $id <= 0 || !in_array($tipo_usuario, $valid_types)) {
    header('Location: sistema.php?error=invalid_data');
    exit();
}

// Mapeamento para o nome da tabela
$tabela = match($tipo_usuario) {
    'medico' => 'medicos',
    'clinica' => 'clinicas',
    default => null
};
if (is_null($tabela)) {
    header('Location: sistema.php?error=internal_error');
    exit();
}

try {
    // Exclusão no Banco de Dados dos relacioandos
    $stmt = $pdo->prepare("DELETE FROM {$tabela} WHERE id = :id");
    $stmt->execute(['id' => $id]);
    header("Location: sistema.php?success=user_{$tipo_usuario}_deleted");
    exit();

} catch (PDOException $e) {
    error_log("Erro ao deletar {$tipo_usuario}: " . $e->getMessage());
    header('Location: sistema.php?error=db_delete_failed');
    exit();
}
?>