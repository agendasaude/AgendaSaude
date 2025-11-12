<?php
$host = "localhost";
$dbname = "bancoAS";
$user = "root";
$pass = "";

const ADMIN_EMAIL = 'admin@agendasaude.com';
const ADMIN_PASSWORD_HASH = '$2y$10$fy..1YxteR90RxpmhX2w/u8nuvd0KoMPUWIWporbbW3lUPR4jf5EW'; 
const SITE_URL = 'http://localhost/inicio.php'; 

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8", 
        $user, 
        $pass,
        [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC ] );
} catch (PDOException $e) {
    die("Erro de conexão com o banco de dados. Verifique a configuração em conexao.php"); 
}
?>
