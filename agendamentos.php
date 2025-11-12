<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'paciente') {
    header("Location: login.html?error=unauthorized");
    exit();
} $paciente_id = $_SESSION['user_id']; 

$input = filter_input_array(INPUT_POST, [
    'data_agendamento' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'clinica_id' => FILTER_VALIDATE_INT,
    'medico_id' => FILTER_VALIDATE_INT,
    'horario' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'descricao' => FILTER_SANITIZE_FULL_SPECIAL_CHARS]);
extract($input); 
$clinica_id = (int) $clinica_id;
$medico_id = (int) $medico_id;

if (empty($data_agendamento) || $clinica_id <= 0 || $medico_id <= 0 || empty($horario) || empty($descricao)) {
    header("Location: calendario.html?error=empty_or_invalid_fields");
    exit();
}

$stmt_check = $pdo->prepare("SELECT COUNT(*) FROM agendamentos WHERE medico_id = :medico_id AND clinica_id = :clinica_id AND data_agendamento = :data_agendamento AND horario = :horario AND status IN ('pendente', 'confirmado')");
$stmt_check->execute(compact('medico_id', 'clinica_id', 'data_agendamento', 'horario'));
if ($stmt_check->fetchColumn() > 0) {
    header("Location: calendario.html?error=time_already_booked");
    exit();
}

$stmt = $pdo->prepare("INSERT INTO agendamentos (paciente_id, medico_id, clinica_id, data_agendamento, horario, descricao) VALUES (:paciente_id, :medico_id, :clinica_id, :data_agendamento, :horario, :descricao)");
try {
    $stmt->execute([
        'paciente_id' => $paciente_id,
        'medico_id' => $medico_id,
        'clinica_id' => $clinica_id,
        'data_agendamento' => $data_agendamento,
        'horario' => $horario,
        'descricao' => $descricao]);
    header("Location: calendario.html?success=true");
    exit();
} catch (PDOException $e) {
    header("Location: calendario.html?error=database_insertion_failed");
    exit();
}
?>
