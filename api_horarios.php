<?php
require 'conexao.php'; 
header('Content-Type: application/json');

function safe_input(string $key, $filter = FILTER_SANITIZE_FULL_SPECIAL_CHARS) {
    $value = filter_input(INPUT_GET, $key, $filter);
    if ($filter !== FILTER_SANITIZE_FULL_FULL_SPECIAL_CHARS && ($value === false || $value === null)) {
        return '';
    } return $value;
}
$action = safe_input('action');
$response = [];

if ($action === 'options') {
    $day = safe_input('day');
    if (!empty($day)) {
        $response['clinicas'] = $pdo->query("SELECT id, nome FROM clinicas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
        $day_safe = htmlspecialchars($day, ENT_QUOTES, 'UTF-8');
        $stmt = $pdo->prepare("SELECT id, nome, clinica_id, horarios, especialidade FROM medicos WHERE dias_semana LIKE ?");
        $stmt->execute(['%' . $day_safe . '%']);
        $response['medicos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } echo json_encode($response);
    exit(); 
}

if ($action === 'booked') {
    $medico_id = safe_input('medico_id', FILTER_VALIDATE_INT);
    $clinica_id = safe_input('clinica_id', FILTER_VALIDATE_INT);
    $data_agendamento = safe_input('data');
    $agendados = [];

    if ($medico_id > 0 && $clinica_id > 0 && !empty($data_agendamento)) {
        $stmt = $pdo->prepare("
            SELECT horario 
            FROM agendamentos 
            WHERE medico_id = :medico_id AND clinica_id = :clinica_id 
            AND data_agendamento = :data_agendamento AND status IN ('pendente', 'confirmado')
        ");
        $stmt->execute(compact('medico_id', 'clinica_id', 'data_agendamento'));
        $agendados = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    } echo json_encode(['booked' => $agendados]);
    exit();
} echo json_encode(['error' => 'Ação não suportada']);
exit();

?>
