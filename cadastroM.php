<?php
// 1. INICIAR SESSÃO E CONEXÃO
// (session_start() é necessário se houver redirects para 'sistema.php' após o login)
session_start(); 
require 'conexao.php'; 

$clinicas = [];
$error_message = '';
$error_type = '';

// 2. LÓGICA DE PROCESSAMENTO DO FORMULÁRIO (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Coleta, sanitiza e valida em um passo conciso
    $input = filter_input_array(INPUT_POST, [
        'nome' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'cbo' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'rqe' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'especialidade' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'clinica_id' => FILTER_VALIDATE_INT,
        'dias_semana' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'horarios' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'email' => FILTER_VALIDATE_EMAIL,
        'senha' => FILTER_DEFAULT
    ]);

    extract($input);
    $senha = $_POST['senha'] ?? ''; // Pega a senha bruta antes de validar o email
    $clinica_id = (int) $clinica_id; // Garante que é um int

    // Validação de campos obrigatórios
    if (empty($nome) || empty($especialidade) || $clinica_id <= 0 || empty($dias_semana) || empty($horarios) || empty($senha) || $email === false || empty($email)) {
        // Define mensagem de erro para exibir no HTML
        $error_type = 'empty_or_invalid_fields';
        $error_message = 'Por favor, preencha todos os campos obrigatórios corretamente.';
    } else {
        
        // 3. Verificação de Duplicidade
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM medicos WHERE email = :email");
        $stmt->execute(['email' => $email]);

        if ($stmt->fetchColumn() > 0) {
            $error_type = 'email_already_registered';
            $error_message = 'Este e-mail já está cadastrado como médico.';
        } else {
        
            // 4. Inserção no Banco de Dados
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO medicos (nome, cbo, rqe, especialidade, clinica_id, dias_semana, horarios, email, senha) 
                                   VALUES (:nome, :cbo, :rqe, :especialidade, :clinica_id, :dias_semana, :horarios, :email, :senha_hash)");

            try {
                $stmt->execute([
                    'nome' => $nome,
                    'cbo' => $cbo,
                    'rqe' => $rqe,
                    'especialidade' => $especialidade,
                    'clinica_id' => $clinica_id,
                    'dias_semana' => $dias_semana,
                    'horarios' => $horarios,
                    'email' => $email,
                    'senha_hash' => $senhaHash
                ]);

                // Inicia a sessão para o médico recém-cadastrado (simula o login)
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['tipo'] = 'medico';
                $_SESSION['email'] = $email;
                
                header("Location: sistema.php?success=cadastro_medico_success");
                exit();

            } catch (PDOException $e) {
                // error_log("Erro de DB: " . $e->getMessage());
                $error_type = 'database_insertion_failed';
                $error_message = 'Erro interno ao tentar salvar o cadastro.';
            }
        }
    }
}

// 5. LÓGICA DE GET (Buscar clínicas para o formulário)
// Esta parte sempre executa, mesmo se o POST falhar, para preencher o <select>
try {
    $stmt = $pdo->query("SELECT id, nome FROM clinicas ORDER BY nome");
    $clinicas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // error_log("Erro ao buscar clínicas: " . $e->getMessage());
    $error_message = 'Erro ao carregar a lista de clínicas.';
    $error_type = 'db_fetch_error';
}

// 6. INCLUIR O HEADER (Inicia o HTML)
$page_title = 'Cadastro de Médico';
include 'header.php'; 
?>

<!-- 7. HTML DO FORMULÁRIO (Completo) -->
<div class="form-container" style="max-width: 600px;">
    <h2><i class="fas fa-user-md"></i> Cadastro de Médico</h2>

    <!-- Div para exibir mensagens de erro do PHP -->
    <div id="feedback-message" class="feedback-message" style="display: none;"></div>

    <form action="cadastroM.php" method="POST">
        <div class="form-group">
            <label for="nome">Nome Completo:</label>
            <input type="text" id="nome" name="nome" required>
        </div>
        <div class="form-group">
            <label for="cbo">CBO (Opcional):</label>
            <input type="text" id="cbo" name="cbo" placeholder="Ex: 225125">
        </div>
        <div class="form-group">
            <label for="rqe">RQE (Opcional):</label>
            <input type="text" id="rqe" name="rqe" placeholder="Ex: 123456">
        </div>
        
        <!-- Lista de Especialidades Completa -->
        <div class="form-group">
            <label for="especialidade">Especialidade:</label>
            <select id="especialidade" name="especialidade" required>
                <option value="">Selecione</option>
                <option value="Acupuntura">Acupuntura</option>
                <option value="Alergiaeimunologia">Alergia e imunologia</option>
                <option value="Anestesiologia">Anestesiologia</option>
                <option value="Angiologia">Angiologia</option>
                <option value="Cardiologia">Cardiologia</option>
                <option value="Cirurgia cardiovascula">Cirurgia cardiovascular</option>
                <option value="Cirurgiadamão">Cirurgia da mão</option>
                <option value="Cirurgiadecabeçaepescoço">Cirurgia de cabeça e pescoço</option>
                <option value="Cirurgiadoaparelh digestivo">Cirurgia do aparelho digestivo</option>
                <option value="Cirurgiageral">Cirurgia geral</option>
                <option value="Cirurgiaoncológica">Cirurgia oncológica</option>
                <option value="Cirurgiapediátrica">Cirurgia pediátrica</option>
                <option value="Cirurgiaplástica">Cirurgia plástica</option>
                <option value="Cirurgiatorácica">Cirurgia torácica</option>
                <option value="Cirurgiavascular">Cirurgia vascular</option>
                <option value="Clínicamédica">Clínica médica</option>
                <option value="Coloproctologia">Coloproctologia</option>
                <option value="Dermatologia">Dermatologia</option>
                <option value="Endocrinologiaemetabologia">Endocrinologia e metabologia</option>
                <option value="Endoscopia">Endoscopia</option>
                <option value="Gastroenterologia">Gastroenterologia</option>
                <option value="Genéticamédica">Genética médica</option>
                <option value="Geriatria">Geriatria</option>
                <option value="Ginecologiaeobstetrícia">Ginecologia e obstetrícia</option>
                <option value="Hematologiaehemoterapia">Hematologia e hemoterapia</option>
                <option value="Homeopatia">Homeopatia</option>
                <option value="Infectologia">Infectologia</option>
                <option value="Mastologia">Mastologia</option>
                <option value="Medicinadeemergência">Medicina de emergência</option>
                <option value="Medicinadefamíliaecomunidade">Medicina de família e comunidade</option>
                <option value="Medicinadotrabalho">Medicina do trabalho</option>
                <option value="Medicinadotráfego">Medicina do tráfego</option>
                <option value="Medicinaesportiva">Medicina esportiva</option>
                <option value="Medicinaintensiva">Medicina intensiva</option>
                <option value="Medicinalegaleperíciamédica">Medicina legal e perícia médica</option>
                <option value="Medicinanuclear">Medicina nuclear</option>
                <option value="Medicinapreventivaesocial">Medicina preventiva e social</option>
                <option value="Nefrologia">Nefrologia</option>
                <option value="Neurocirurgia">Neurocirurgia</option>
                <option value="Neurologia">Neurologia</option>
                <option value="Nutrologia">Nutrologia</option>
                <option value="Oftalmologia">Oftalmologia</option>
                <option value="Oncologiaclínica">Oncologia clínica</option>
                <option value="Ortopediaetraumatologia">Ortopedia e traumatologia</option>
                <option value="Otorrinolaringologia">Otorrinolaringologia</option>
                <option value="Patologia">Patologia</option>
                <option value="Patologiaclínica">Patologia clínica/medicina laboratorial</option>
                <option value="Pediatria">Pediatria</option>
                <option value="Pneumologia">Pneumologia</option>
                <option value="Psiquiatria">Psiquiatria</option>
                <option value="Radiologiaediagnosticoporimagem">Radiologia e diagnóstico por imagem</option>
                <option value="Radioterapia">Radioterapia</option>
                <option value="Reumatologia">Reumatologia</option>
                <option value="Urologia">Urologia</option>
            </select>
        </div>

        <div class="form-group">
            <label for="clinica_id">Clínica Associada:</label>
            <select id="clinica_id" name="clinica_id" required>
                <option value="">Selecione a clínica</option>
                <?php
                // Preenche as opções de clínicas
                foreach ($clinicas as $clinica) {
                    $nome_clinica_safe = htmlspecialchars($clinica['nome'], ENT_QUOTES, 'UTF-8');
                    echo "<option value=\"{$clinica['id']}\">{$nome_clinica_safe}</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="dias_semana">Dias da Semana:</label>
            <input type="text" id="dias_semana" name="dias_semana" placeholder="Segunda,Terca,Quarta" required>
            <small>Separe os dias por vírgula (ex: Segunda,Terca,Quarta).</small>
        </div>
        <div class="form-group">
            <label for="horarios">Horários:</label>
            <input type="text" id="horarios" name="horarios" placeholder="08:00-12:00,14:00-18:00" required>
            <small>Separe os blocos de horário por vírgula (ex: 08:00-12:00,14:00-18:00).</small>
        </div>
        <div class="form-group">
            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="senha">Senha:</label>
            <input type="password" id="senha" name="senha" required>
        </div>
        <button type="submit" class="btn">Cadastrar Médico</button>
    </form>
</div>

<footer>
    &copy; 2025 ©AgendaSaúde. Todos os direitos reservados.
</footer>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Verifica se há mensagens de erro vindas do PHP 
        const errorMessage = "<?php echo $error_message; ?>";
        const errorType = "<?php echo $error_type; ?>";
        if (errorMessage) {
            const messageDiv = document.getElementById('feedback-message');
            messageDiv.textContent = errorMessage;
            messageDiv.className = `feedback-message ${errorType === 'db_fetch_error' ? 'error-message' : 'warning-message'}`;
            messageDiv.style.display = 'block';
        } });
</script>
</body>
</html>