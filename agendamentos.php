<?php
session_start();
require 'conexao.php';
// Verificação de Autorização: Apenas pacientes podem agendar.
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'paciente') {
    header("Location: login.html?error=unauthorized");
    exit();
} $paciente_id = $_SESSION['user_id']; 

// Coleta, sanitização e validação em um passo conciso
$input = filter_input_array(INPUT_POST, [
    'data_agendamento' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'clinica_id' => FILTER_VALIDATE_INT,
    'medico_id' => FILTER_VALIDATE_INT,
    'horario' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'descricao' => FILTER_SANITIZE_FULL_SPECIAL_CHARS
]);
// Extrai as variáveis
extract($input); 
// Validação dos dados críticos (incluindo a conversão para int para os IDs, se a validação falhar, o valor será null)
$clinica_id = (int) $clinica_id;
$medico_id = (int) $medico_id;
// Validação dos dados críticos 
if (empty($data_agendamento) || $clinica_id <= 0 || $medico_id <= 0 || empty($horario) || empty($descricao)) {
    header("Location: calendario.html?error=empty_or_invalid_fields");
    exit();
}

// VERIFICAÇÃO DE CONFLITO DE HORÁRIO
// Garante que o horário não está ocupado (status 'pendente' ou 'confirmado')
$stmt_check = $pdo->prepare("
    SELECT COUNT(*) 
    FROM agendamentos 
    WHERE medico_id = :medico_id 
    AND clinica_id = :clinica_id 
    AND data_agendamento = :data_agendamento 
    AND horario = :horario 
    AND status IN ('pendente', 'confirmado')
");

// compact() cria o array de parâmetros
$stmt_check->execute(compact('medico_id', 'clinica_id', 'data_agendamento', 'horario'));
if ($stmt_check->fetchColumn() > 0) {
    header("Location: calendario.html?error=time_already_booked");
    exit();
}

// INSERÇÃO DO AGENDAMENTO
$stmt = $pdo->prepare("INSERT INTO agendamentos (paciente_id, medico_id, clinica_id, data_agendamento, horario, descricao)
                       VALUES (:paciente_id, :medico_id, :clinica_id, :data_agendamento, :horario, :descricao)");
try {
    $stmt->execute([
        'paciente_id' => $paciente_id,
        'medico_id' => $medico_id,
        'clinica_id' => $clinica_id,
        'data_agendamento' => $data_agendamento,
        'horario' => $horario,
        'descricao' => $descricao
    ]);
    header("Location: calendario.html?success=true");
    exit();

} catch (PDOException $e) {
    header("Location: calendario.html?error=database_insertion_failed");
    exit();
}
?>