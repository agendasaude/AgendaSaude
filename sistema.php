<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['tipo'])) {
    header('Location: login.html');
    exit();
}

require 'conexao.php';

$user_id = $_SESSION['user_id'];
$tipo = $_SESSION['tipo'];
$logado = $_SESSION['email'] ?? 'Usuário';
$user_data = [];
$is_admin = $tipo === 'admin';

function getUserData($pdo, $user_id, $tipo) {
    if ($tipo === 'admin') return ['nome' => 'Administrador'];
    $table = match($tipo) {
        'paciente' => 'pacientes',
        'medico' => 'medicos',
        'clinica' => 'clinicas',
        default => null
    };

    if (is_null($table)) return null;
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function renderAgendamentoItem($ag, $tipo) {
    $status_class = match($ag['status']) {
        'confirmado' => 'status-confirmado',
        'cancelado' => 'status-cancelado',
        'pendente' => 'status-pendente',
        default => ''
    };
    
    $pac_info = ($tipo === 'paciente') ? "" : " | Paciente: " . htmlspecialchars($ag['paciente_nome']);
    $med_info = ($tipo === 'medico') ? "" : " | Médico: " . htmlspecialchars($ag['medico_nome']);
    $cli_info = ($tipo === 'clinica') ? "" : " | Clínica: " . htmlspecialchars($ag['clinica_nome']);

    $html = "<div class='agendamento-item {$status_class}'>";
    $html .= "  <div class='info'>";
    $html .= "      <strong>Data:</strong> " . date('d/m/Y', strtotime($ag['data_agendamento']));
    $html .= "      <strong>Horário:</strong> " . htmlspecialchars($ag['horario']) . $med_info . $cli_info . $pac_info;
    $html .= "      <p><strong>Motivo:</strong> " . htmlspecialchars($ag['descricao']) . "</p>";
    $html .= "  </div>";
    $html .= "  <div class='status-badge'>Status: " . ucfirst($ag['status']) . "</div>";

    if ($tipo === 'medico' || $tipo === 'clinica') {
        $html .= "  <div class='actions'>";
        $html .= "      <form action='gerenciarA.php' method='POST' style='display:inline;'>";
        $html .= "          <input type='hidden' name='agendamento_id' value='{$ag['id']}'>";
        $html .= "          <input type='hidden' name='status' value='confirmado'>";
        $html .= "          <button type='submit' class='btn-action btn-confirm' title='Confirmar'><i class='fas fa-check'></i></button>";
        $html .= "      </form>";
        $html .= "      <form action='gerenciarA.php' method='POST' style='display:inline;'>";
        $html .= "          <input type='hidden' name='agendamento_id' value='{$ag['id']}'>";
        $html .= "          <input type='hidden' name='status' value='cancelado'>";
        $html .= "          <button type='submit' class='btn-action btn-cancel' title='Cancelar'><i class='fas fa-times'></i></button>";
        $html .= "      </form>";
        $html .= "  </div>";
    }
    $html .= "</div>";
    return $html;
}

$user_data = getUserData($pdo, $user_id, $tipo);
$nome_display = htmlspecialchars($user_data['nome'] ?? 'Painel');

if (!$is_admin) {
    $agendamentos = [];
    $historico_agendamentos = [];
    $filter_column = match($tipo) {
        'paciente' => 'paciente_id',
        'medico' => 'medico_id',
        'clinica' => 'clinica_id',
        default => null
    };

    if (!is_null($filter_column)) {
        $sql_base = "
            SELECT a.id, a.data_agendamento, a.horario, a.descricao, a.status, p.nome AS paciente_nome, m.nome AS medico_nome, c.nome AS clinica_nome
            FROM agendamentos a LEFT JOIN pacientes p ON a.paciente_id = p.id
            LEFT JOIN medicos m ON a.medico_id = m.id LEFT JOIN clinicas c ON a.clinica_id = c.id
            WHERE a.{$filter_column} = :user_id ORDER BY a.data_agendamento DESC, a.horario DESC";
        
        $stmt = $pdo->prepare($sql_base);
        $stmt->execute(['user_id' => $user_id]);
        $all_agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $hoje = date('Y-m-d');

        foreach ($all_agendamentos as $ag) {
            $data_ag = $ag['data_agendamento'];
            if (($ag['status'] === 'pendente' || $ag['status'] === 'confirmado') && $data_ag >= $hoje) {
                $agendamentos[] = $ag;
            } else {
                $historico_agendamentos[] = $ag;
            } } }
} else {
    $medicos = $pdo->query("SELECT id, nome, email FROM medicos ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
    $clinicas = $pdo->query("SELECT id, nome, email FROM clinicas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Controle - AgendaSaúde</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        <?php include 'style_base.css'; ?>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --text-color: #2b2d42;
            --light-text: #f8f9fa;
            --border-color: #dee2e6;
            --success-color: #28a745;
            --error-color: #dc3545;
            --warning-color: #ffc107;
        } body {
        } .main-content {
            display: flex;
            flex-grow: 1;
            padding: 2rem;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            gap: 20px;
        } .sidebar {
            width: 250px;
            padding: 1.5rem;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            align-self: flex-start;
        } .sidebar h2 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 0.5rem;
        } .sidebar p {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 1.5rem;
        } .sidebar ul {
            list-style: none;
            padding: 0;
        } .sidebar ul li a {
            display: block;
            padding: 0.75rem 0;
            text-decoration: none;
            color: var(--text-color);
            font-weight: 500;
            transition: color 0.3s;
            border-bottom: 1px dashed #eee;
        } .sidebar ul li a:hover {
            color: var(--secondary-color);
        } .content-area {
            flex-grow: 1;
            background-color: #ffffff;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        } .content-area h1 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        } .agendamento-section {
            margin-top: 2rem;
        } .agendamento-section h2 {
            color: var(--text-color);
            margin-bottom: 0.75rem;
        } hr {
            border: 0;
            height: 1px;
            background: var(--border-color);
            margin-bottom: 1rem;
        } .agendamento-item {
            background-color: #f8f9fa;
            border-left: 5px solid;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        } .agendamento-item .info {
            flex: 1;
        } .agendamento-item strong {
            display: inline-block;
            margin-right: 10px;
        } .status-badge {
            font-weight: bold;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-left: 1rem;
        } .status-pendente { border-color: var(--warning-color); }
        .status-confirmado { border-color: var(--success-color); }
        .status-cancelado { border-color: var(--error-color); }
        .status-pendente .status-badge { background-color: #fff3cd; color: #856404; }
        .status-confirmado .status-badge { background-color: #d4edda; color: #155724; }
        .status-cancelado .status-badge { background-color: #f8d7da; color: #721c24; }
        .no-records {
            padding: 1rem;
            background-color: #e9ecef;
            border-radius: 8px;
            text-align: center;
        } .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: bold;
            text-align: center;
        } .message.success {
            background-color: #d4edda;
            color: var(--success-color);
            border: 1px solid var(--success-color);
        } .message.error {
            background-color: #f8d7da;
            color: var(--error-color);
            border: 1px solid var(--error-color);
        } .actions {
            display: flex;
            gap: 10px;
        } .btn-action {
            padding: 0.5rem 0.75rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s;
            color: white;
            font-size: 0.9rem;
        } .btn-confirm { background-color: var(--success-color); }
        .btn-confirm:hover { background-color: #1e7e34; }
        .btn-cancel { background-color: var(--error-color); }
        .btn-cancel:hover { background-color: #c82333; }
        .admin-list {
            list-style: none;
            padding: 0;
            margin-top: 1rem;
        } .admin-list li {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 4px solid var(--primary-color);
        } .admin-list li span {
            font-weight: bold;
            flex-grow: 1;
        } .btn-delete {
            background-color: var(--error-color);
            color: white;
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        } .btn-delete:hover {
            background-color: #c82333;
        } .admin-actions {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        } .admin-actions a {
            background-color: var(--secondary-color);
            color: var(--light-text);
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s;
        } .admin-actions a:hover {
            background-color: #2c25a1;
        }
        @media (max-width: 768px) {
            .main-content {
                flex-direction: column;
                padding: 1rem;
            } .sidebar {
                width: 100%;
                margin-bottom: 1rem;
            } .content-area {
                padding: 1.5rem;
            } .agendamento-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            } .agendamento-item .info {
                width: 100%;
            } .status-badge {
                margin-left: 0;
            } .actions {
                width: 100%;
                justify-content: flex-end;
            } }
    </style>
</head>

<body>
<?php include 'header.php'; ?>
<div class="main-content">
    <div class="sidebar">
        <h2><i class="fas fa-user-circle"></i> Meu Perfil</h2>
        <p>Logado como: **<?php echo htmlspecialchars(ucfirst($tipo)); ?>**</p>
        <p>E-mail: <?php echo htmlspecialchars($logado); ?></p>
        <?php if (!$is_admin && $user_data): ?>
            <h3>Detalhes:</h3>
            <ul>
                <?php if ($tipo === 'paciente'): ?>
                    <li>Nome: <?php echo $nome_display; ?></li>
                    <li>CPF: <?php echo htmlspecialchars($user_data['cpf']); ?></li>
                <?php elseif ($tipo === 'medico'): ?>
                    <li>Nome: <?php echo $nome_display; ?></li>
                    <li>Especialidade: <?php echo htmlspecialchars($user_data['especialidade']); ?></li>
                    <li>Clínica ID: <?php echo htmlspecialchars($user_data['clinica_id']); ?></li>
                <?php elseif ($tipo === 'clinica'): ?>
                    <li>Nome: <?php echo $nome_display; ?></li>
                    <li>CNPJ: <?php echo htmlspecialchars($user_data['cnpj']); ?></li>
                <?php endif; ?>
            </ul>
        <?php endif; ?>
    </div>
    
    <div class="content-area">
        <h1>Painel de <?php echo $nome_display; ?></h1>
        <?php 
        $success = $_GET['success'] ?? '';
        $error = $_GET['error'] ?? '';
        if ($success === 'user_medico_deleted') {
            echo '<div class="message success">Médico removido</div>';
        } elseif ($success === 'user_clinica_deleted') {
            echo '<div class="message success">Clínica removida</div>';
        } elseif ($error) {
            echo '<div class="message error">Ocorreu um erro: ' . htmlspecialchars($error) . '</div>';
        }
        ?>

        <?php if (!$is_admin): ?>
            <div class="agendamento-section">
                <h2>Próximos Agendamentos (Pendentes/Confirmados)</h2>
                <hr>
                <div class="agendamento-list">
                    <?php if (empty($agendamentos)): ?>
                        <div class="no-records">Nenhum agendamento pendente ou confirmado encontrado</div>
                    <?php else: ?>
                        <?php foreach ($agendamentos as $ag): ?>
                            <?php echo renderAgendamentoItem($ag, $tipo); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="agendamento-section" style="margin-top: 30px;">
                <h2>Histórico de Agendamentos</h2>
                <hr>
                <div class="agendamento-list">
                    <?php if (empty($historico_agendamentos)): ?>
                        <div class="no-records">Nenhum histórico encontrado</div>
                    <?php else: ?>
                        <?php foreach ($historico_agendamentos as $ag): ?>
                            <?php echo renderAgendamentoItem($ag, $tipo); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="admin-actions">
                <a href="backup.php"><i class="fas fa-download"></i> Fazer Backup</a>
                <a href="restaurar.php"><i class="fas fa-upload"></i> Restaurar Backup</a>
            </div>

            <div class="agendamento-section">
                <h2>Gerenciar Médicos (<?php echo count($medicos); ?>)</h2>
                <hr>
                <ul class="admin-list">
                    <?php foreach ($medicos as $med): ?>
                        <li>
                            <span>**ID #<?php echo $med['id']; ?>** - <?php echo htmlspecialchars($med['nome']); ?> (<?php echo htmlspecialchars($med['email']); ?>)</span>
                            <form action="excluir_usuario.php" method="POST" onsubmit="return confirm('Tem certeza que deseja EXCLUIR o Médico <?php echo htmlspecialchars($med['nome']); ?>? Todos os agendamentos serão perdidos.');">
                                <input type="hidden" name="id" value="<?php echo $med['id']; ?>">
                                <input type="hidden" name="tipo_usuario" value="medico">
                                <button type="submit" class="btn-delete" title="Excluir Médico"><i class="fas fa-trash-alt"></i> Excluir</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="agendamento-section">
                <h2>Gerenciar Clínicas (<?php echo count($clinicas); ?>)</h2>
                <hr>
                <ul class="admin-list">
                    <?php foreach ($clinicas as $cli): ?>
                        <li>
                            <span>**ID #<?php echo $cli['id']; ?>** - <?php echo htmlspecialchars($cli['nome']); ?> (<?php echo htmlspecialchars($cli['email']); ?>)</span>
                            <form action="excluir_usuario.php" method="POST" onsubmit="return confirm('Tem certeza que deseja EXCLUIR a Clínica <?php echo htmlspecialchars($cli['nome']); ?>? Todos os médicos e agendamentos relacionados serão perdidos.');">
                                <input type="hidden" name="id" value="<?php echo $cli['id']; ?>">
                                <input type="hidden" name="tipo_usuario" value="clinica">
                                <button type="submit" class="btn-delete" title="Excluir Clínica"><i class="fas fa-trash-alt"></i> Excluir</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
