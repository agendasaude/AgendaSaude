<?php
session_start();
require 'conexao.php'; 

// Apenas Admin pode acessar
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: login.html?error=admin_required');
    exit();
}

// Configurações do banco (obtidas de conexao.php)
$host = "localhost";  
$dbname = "bancoAS";  
$user = "root";       
$pass = "";          
$filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';

// para forçar o download do arquivo
header('Content-Type: application/octet-stream');
header('Content-Transfer-Encoding: Binary');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Comando mysqldump: exporta o esquema e os dados
// NOTA: Para funcionar, 'mysqldump' precisa estar no PATH do sistema.
$command = "mysqldump --opt --host={$host} --user={$user} --password={$pass} {$dbname}";
// Executa o comando e envia a saída diretamente para o navegador
passthru($command, $return_var);

if ($return_var !== 0) {
    error_log("Erro no mysqldump: return code {$return_var}");
    header("Location: sistema.php?error=backup_failed");
    exit();
} exit();
?>