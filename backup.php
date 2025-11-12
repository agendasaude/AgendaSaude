<?php
session_start();
require 'conexao.php'; 

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: login.html?error=admin_required');
    exit();
}

$host = "localhost";  
$dbname = "bancoAS";  
$user = "root";       
$pass = "";          
$filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';

header('Content-Type: application/octet-stream');
header('Content-Transfer-Encoding: Binary');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$command = "mysqldump --opt --host={$host} --user={$user} --password={$pass} {$dbname}";
passthru($command, $return_var);

if ($return_var !== 0) {
    error_log("Erro no mysqldump: return code {$return_var}");
    header("Location: sistema.php?error=backup_failed");
    exit();
} exit();
?>
