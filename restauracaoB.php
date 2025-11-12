<?php
session_start();
require 'conexao.php'; 

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: login.html?error=admin_required');
    exit();
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['sql_file']) && $_FILES['sql_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['sql_file']['tmp_name'];
        $file_name = $_FILES['sql_file']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if ($file_ext !== 'sql') {
            $message = "Erro: Apenas arquivos .sql são permitidos.";
            $message_type = 'error';
        } else {
            $sql_content = file_get_contents($file_tmp_path);
            if (empty($sql_content) || strlen($sql_content) > 10 * 1024 * 1024) { 
                $message = "Erro: Arquivo vazio ou muito grande.";
                $message_type = 'error';
            } else {
                try {
                    $pdo->exec($sql_content);
                    $message = "Banco de dados restaurado com sucesso a partir de '{$file_name}'!";
                    $message_type = 'success';
                } catch (PDOException $e) {
                    $message = "Erro de execução SQL: " . $e->getMessage();
                    $message_type = 'error';
                } } }
    } else {
        $message = "Erro no upload do arquivo";
        $message_type = 'error';
    } }
?>
    
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restauração de Backup - AgendaSaúde</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        <?php include 'style_base.css';  ?>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --text-color: #2b2d42;
            --light-text: #f8f9fa;
            --success-color: #28a745;
            --error-color: #dc3545;
        } .container {
            flex-grow: 1;
            padding: 2rem;
            max-width: 600px;
            margin: 2rem auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
            text-align: center;
        } .container h1 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
        } .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: bold;
        } .message.success {
            background-color: #d4edda;
            color: var(--success-color);
            border: 1px solid var(--success-color);
        } .message.error {
            background-color: #f8d7da;
            color: var(--error-color);
            border: 1px solid var(--error-color);
        } .upload-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            align-items: center;
        } input[type="file"] {
            border: 2px dashed #ccc;
            padding: 20px;
            border-radius: 8px;
            width: 100%;
            max-width: 400px;
            cursor: pointer;
        } .btn-restore {
            background-color: var(--error-color);
            color: var(--light-text);
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-weight: bold;
        } .btn-restore:hover {
            background-color: #c82333;
        }
    </style>
</head>
    
<body>
<?php include 'header.php'; ?>
<div class="container">
    <h1><i class="fas fa-undo-alt"></i> Restauração de Backup</h1>

    <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <p>Selecione um arquivo `.sql` gerado pelo sistema para restaurar o estado do banco de dados</p>
    <form action="restaurar.php" method="POST" enctype="multipart/form-data" class="upload-form">
        <input type="file" name="sql_file" id="sql_file" accept=".sql" required>
        <button type="submit" class="btn-restore"><i class="fas fa-database"></i> Restaurar Banco</button>
    </form>
</div>
</body>
</html>
