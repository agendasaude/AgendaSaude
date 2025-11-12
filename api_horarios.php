<?php
require 'conexao.php'; 
header('Content-Type: application/json');

/**
 * Função para sanitizar e validar entradas GET
 * @param string $key Chave do array GET
 * @param int $filter Filtro PHP (ex: FILTER_VALIDATE_INT)
 * @return mixed Valor filtrado ou string sanitizada
 */
function safe_input(string $key, $filter = FILTER_SANITIZE_FULL_SPECIAL_CHARS) {
    $value = filter_input(INPUT_GET, $key, $filter);
    // Se o filtro de validação falhar (ex: FILTER_VALIDATE_INT com string), o valor pode ser false/null.
    // Retorna a string vazia se for false/null para consistência.
    if ($filter !== FILTER_SANITIZE_FULL_FULL_SPECIAL_CHARS && ($value === false || $value === null)) {
        return '';
    } return $value;
}
$action = safe_input('action');
$response = [];

// Ação para buscar CLÍNICAS e MÉDICOS disponíveis por dia ('options')
if ($action === 'options') {
    $day = safe_input('day'); // Ex: 'Segunda'
    if (!empty($day)) {
        // Busca clínicas (todas) - Apenas ID e Nome
        $response['clinicas'] = $pdo->query("SELECT id, nome FROM clinicas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
        // Busca médicos disponíveis no dia 
        $day_safe = htmlspecialchars($day, ENT_QUOTES, 'UTF-8');
        $stmt = $pdo->prepare("SELECT id, nome, clinica_id, horarios, especialidade FROM medicos WHERE dias_semana LIKE ?");
        $stmt->execute(['%' . $day_safe . '%']);
        $response['medicos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } 
    echo json_encode($response);
    exit(); 
}

// Ação para buscar HORÁRIOS JÁ AGENDADOS ('booked')
if ($action === 'booked') {
    // Obtém e valida os IDs como inteiros
    $medico_id = safe_input('medico_id', FILTER_VALIDATE_INT);
    $clinica_id = safe_input('clinica_id', FILTER_VALIDATE_INT);
    $data_agendamento = safe_input('data');
    $agendados = [];

    // Garante que todos os parâmetros necessários estão presentes e válidos
    if ($medico_id > 0 && $clinica_id > 0 && !empty($data_agendamento)) {
        $stmt = $pdo->prepare("
            SELECT horario 
            FROM agendamentos 
            WHERE medico_id = :medico_id 
            AND clinica_id = :clinica_id 
            AND data_agendamento = :data_agendamento 
            AND status IN ('pendente', 'confirmado')
        ");
        // compact() cria o array de parâmetros: ['medico_id' => X, 'clinica_id' => Y, 'data_agendamento' => Z]
        $stmt->execute(compact('medico_id', 'clinica_id', 'data_agendamento'));
        // Mapeia o resultado para uma lista simples de horários agendados
        $agendados = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
    echo json_encode(['booked' => $agendados]);
    exit();
}
echo json_encode(['error' => 'Ação não suportada']);
exit();
?>