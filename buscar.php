<?php
session_start();
require 'conexao.php';

$query = $_GET['q'] ?? '';
$resultados = ['medicos' => [], 'clinicas' => []];
header('Content-Type: application/json');

if (!empty($query)) {
    // A remoção de htmlspecialchars aqui é intencional. 
    // O Prepared Statement abaixo já garante a segurança contra SQL Injection.
    $query_like = '%' . $query . '%'; 
    
    // Busca em Médicos (por Nome, Especialidade, ou Nome da Clínica)
    $stmt = $pdo->prepare("
        SELECT m.id, m.nome, m.especialidade, c.nome AS clinica, m.email 
        FROM medicos m
        JOIN clinicas c ON m.clinica_id = c.id
        WHERE m.nome LIKE :query OR m.especialidade LIKE :query OR c.nome LIKE :query
    ");
    $stmt->execute(['query' => $query_like]); 
    $resultados['medicos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //  Busca em Clínicas (por Nome ou Especialidades)
    $stmt = $pdo->prepare("SELECT id, nome, especialidades, cep, telefone, email FROM clinicas WHERE nome LIKE :query OR especialidades LIKE :query");
    $stmt->execute(['query' => $query_like]);
    $resultados['clinicas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Sanitiza os resultados ANTES de enviar para o navegador (Prevenção XSS)
    $sanitize_results = function(&$results) {
        foreach ($results as &$row) {
            foreach ($row as $key => &$value) {
                if (is_string($value)) {
                    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                }
            }
        }
    };

    $sanitize_results($resultados['medicos']);
    $sanitize_results($resultados['clinicas']);
}

echo json_encode($resultados);
?>