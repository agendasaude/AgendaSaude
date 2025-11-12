<?php
$page_title = 'Agendamento - AgendaSaúde';
include 'header.php'; 
?>

<?php 
if (!isset($_SESSION['user_id'])): 
?>
    <div class="form-container">
        <h2><i class="fas fa-exclamation-triangle"></i> Acesso Negado</h2>
        <p>Você precisa estar logado para acessar a página de agendamentos.</p>
        <p>Se não tem uma conta, você pode se cadastrar como paciente, médico ou clínica.</p>
        <a href="login.html" class="btn">Fazer Login</a>
        <a href="tipoconta.php" class="btn btn-secondary" style="margin-top: 10px; background-color: var(--success-color);">Criar Conta</a>
    </div>

<?php elseif ($_SESSION['tipo'] !== 'paciente'): ?>
    <div class="form-container">
        <h2><i class="fas fa-exclamation-triangle"></i> Acesso Negado</h2>
        <p>Apenas **pacientes** podem agendar novas consultas.</p>
        <p>Você está logado como (<?php echo htmlspecialchars($_SESSION['tipo']); ?>). Use o seu painel para gerenciar suas atividades.</p>
        <a href="sistema.php" class="btn">Ir para o Painel</a>
    </div>

<?php else: ?>
    <div class="container calendar-layout">
        <div class="calendar-wrapper">
            <h1><i class="fas fa-calendar-alt"></i> Selecione a Data</h1>
            <div id="feedback-message" class="feedback-message" style="display: none;"></div>
            <div class="calendar-header">
                <button id="prev-month" class="btn"><i class="fas fa-chevron-left"></i></button>
                <h2 id="current-month-year"></h2>
                <button id="next-month" class="btn"><i class="fas fa-chevron-right"></i></button>
            </div>
            <div class="calendar-grid" id="calendar-grid">
            </div>
        </div>
        <div class="form-agendamento-wrapper">
            <h1><i class="fas fa-hand-holding-medical"></i> Agendar Consulta</h1>
            <form action="agendamentos.php" method="POST" id="agendamento-form">
                <div class="form-group">
                    <label for="data_agendamento">Data Selecionada:</label>
                    <input type="text" id="data_agendamento_display" class="form-control" disabled value="Selecione uma data no calendário">
                    <input type="hidden" id="data_agendamento" name="data_agendamento" required>
                </div>
                <div class="form-group">
                    <label for="clinica_id">Clínica:</label>
                    <select id="clinica_id" name="clinica_id" required>
                        <option value="">Selecione a Clínica</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="medico_id">Médico:</label>
                    <select id="medico_id" name="medico_id" required disabled>
                        <option value="">Selecione o Médico</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="horario">Horários Disponíveis:</label>
                    <div id="horarios-disponiveis">
                        <p class="message info" id="info-horarios" style="display:block;">Selecione uma clínica, médico e data.</p>
                    </div>
                    <input type="hidden" id="horario" name="horario" required>
                </div>
                <div class="form-group">
                    <label for="descricao">Motivo da Consulta (Opcional):</label>
                    <textarea id="descricao" name="descricao" rows="3" placeholder="Breve descrição do motivo..."></textarea>
                </div>
                <button type="submit" class="btn" id="submit-btn" disabled>Confirmar Agendamento</button>
            </form>
        </div>
    </div>

<?php endif; ?>
<footer>
    &copy; 2025 ©AgendaSaúde. Todos os direitos reservados.
</footer>
<script>
    // Código JavaScript do Calendário
    const clinicaSelect = document.getElementById('clinica_id');
    const medicoSelect = document.getElementById('medico_id');
    const dataAgendamentoInput = document.getElementById('data_agendamento');
    const dataAgendamentoDisplay = document.getElementById('data_agendamento_display');
    const horarioInput = document.getElementById('horario');
    const horariosDiv = document.getElementById('horarios-disponiveis');
    const submitBtn = document.getElementById('submit-btn');
    const infoHorarios = document.getElementById('info-horarios');
    const MONTH_NAMES = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
    const DAY_NAMES = ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb"];
    let currentMonth = new Date().getMonth();
    let currentYear = new Date().getFullYear();
    let selectedDate = null;
    let selectedSlot = null;
    let allMedicos = [];

    // Função para mostrar feedback (usada na seção de agendamento)
    function showFeedback(message, type) {
        const messageDiv = document.getElementById('feedback-message');
        if (messageDiv) {
            messageDiv.textContent = message;
            messageDiv.className = 'feedback-message message ' + type; // As classes .message.info, .message.error vêm do style.css
            messageDiv.style.display = 'block';
        } }

    // Busca opções de clínicas e médicos para um dia da semana (ex: Segunda)
    function fetchOptionsForDay(dayName) {
        // Remove acentos para a requisição
        const daySafe = dayName.normalize("NFD").replace(/[\u0300-\u036f]/g, ""); 
        fetch(`api_horarios.php?action=options&day=${daySafe}`)
            .then(response => response.json())
            .then(data => {
                // Preenche as opções de clínicas
                data.clinicas.forEach(c => {
                    const option = document.createElement('option');
                    option.value = c.id;
                    option.textContent = c.nome;
                    clinicaSelect.appendChild(option);
                });
                // Armazena todos os médicos disponíveis para o dia
                allMedicos = data.medicos; 
            }) .catch(error => {
                console.error('Erro ao buscar opções:', error);
                showFeedback('Erro ao carregar clínicas e médicos.', 'error');
            });
    }

    // Atualiza o select de Médicos baseado na Clínica selecionada
    function updateMedicoOptions() {
        medicoSelect.innerHTML = '<option value="">Selecione o Médico</option>';
        medicoSelect.disabled = true;
        const clinicaId = parseInt(clinicaSelect.value);
        if (clinicaId > 0 && selectedDate) {
            const filteredMedicos = allMedicos.filter(m => parseInt(m.clinica_id) === clinicaId);
            if (filteredMedicos.length > 0) {
                filteredMedicos.forEach(m => {
                    const option = document.createElement('option');
                    option.value = m.id;
                    option.textContent = `${m.nome} (${m.especialidade})`;
                    medicoSelect.appendChild(option);
                });
                medicoSelect.disabled = false;
            } else {
                showFeedback('Nenhum médico encontrado para esta clínica no dia selecionado.', 'info');
            } } loadAvailableTimeSlots(); // Recarrega slots sempre que o médico muda
    }
    
    // Converte nome de dia em português para o nome do dia do banco (ex: Segunda)
    function getDayName(date) {
        const days = ["Domingo", "Segunda", "Terça", "Quarta", "Quinta", "Sexta", "Sábado"];
        return days[date.getDay()];
    }

    // Carrega horários disponíveis para o médico/clínica na data selecionada
    function loadAvailableTimeSlots() {
        horariosDiv.innerHTML = '';
        infoHorarios.style.display = 'none';
        submitBtn.disabled = true;
        horarioInput.value = '';
        selectedSlot = null; // Limpa o slot selecionado
        const medicoId = medicoSelect.value;
        const clinicaId = clinicaSelect.value;
        const dateStr = selectedDate; 

        if (!medicoId || !clinicaId || !dateStr) {
            infoHorarios.textContent = 'Selecione uma clínica, médico e data.';
            infoHorarios.style.display = 'block';
            horariosDiv.appendChild(infoHorarios);
            return;
        }

        // Busca os horários JÁ AGENDADOS
        fetch(`api_horarios.php?action=booked&medico_id=${medicoId}&clinica_id=${clinicaId}&data=${dateStr}`)
            .then(response => response.json())
            .then(bookedData => {
                const bookedSlots = bookedData.booked || [];
                
                // Busca o horário de trabalho do médico/clínica (Simplificado - apenas horários)
                const medico = allMedicos.find(m => parseInt(m.id) === parseInt(medicoId));
                if (!medico || !medico.horarios) {
                    infoHorarios.textContent = 'Não foi possível carregar os horários de trabalho do médico.';
                    infoHorarios.style.display = 'block';
                    horariosDiv.appendChild(infoHorarios);
                    return;
                }

                // Lógica para gerar slots de 30 minutos a partir dos blocos de horário do médico (ex: 08:00-12:00)
                const workingBlocks = medico.horarios.split(',').map(b => b.trim());
                let availableSlots = [];
                workingBlocks.forEach(block => {
                    const [start, end] = block.split('-');
                    if (start && end) {
                        let current = new Date(`2000/01/01 ${start}`);
                        const finish = new Date(`2000/01/01 ${end}`);
                        while (current < finish) {
                            // Formata o horário (HH:MM)
                            const slotTime = current.toTimeString().substring(0, 5); 
                            availableSlots.push(slotTime);
                            // Adiciona 30 minutos (ajuste conforme a duração padrão da consulta)
                            current.setMinutes(current.getMinutes() + 30); 
                        } } });
                if (availableSlots.length === 0) {
                    infoHorarios.textContent = 'O médico não tem horário de trabalho definido para este dia.';
                    infoHorarios.style.display = 'block';
                    horariosDiv.appendChild(infoHorarios);
                    return;
                }

                //  Renderiza os slots
                availableSlots.forEach(slot => {
                    const isBooked = bookedSlots.includes(slot);
                    const slotElement = document.createElement('div');
                    slotElement.className = 'time-slot' + (isBooked ? ' booked' : '');
                    slotElement.textContent = slot;
                    slotElement.dataset.time = slot;
                    if (!isBooked) {
                        slotElement.addEventListener('click', () => selectTimeSlot(slotElement));
                    }
                    horariosDiv.appendChild(slotElement);
                });
                if (availableSlots.length > 0) {
                    infoHorarios.style.display = 'none';
                } }) .catch(error => {
                console.error('Erro ao buscar horários:', error);
                showFeedback('Erro ao carregar horários disponíveis.', 'error');
            });
    }

    // Seleciona um horário e atualiza o input oculto
    function selectTimeSlot(element) {
        // Desseleciona o slot anterior (se houver)
        if (selectedSlot) {
            selectedSlot.classList.remove('selected');
        }
        // Seleciona o novo slot
        selectedSlot = element;
        selectedSlot.classList.add('selected');
        horarioInput.value = selectedSlot.dataset.time;
        submitBtn.disabled = false;
    }

    // Lógica do Calendário
    function renderCalendar(month, year) {
        const calendarGrid = document.getElementById('calendar-grid');
        const monthYearText = document.getElementById('current-month-year');
        calendarGrid.innerHTML = ''; // Limpa o calendário anterior
        monthYearText.textContent = `${MONTH_NAMES[month]} ${year}`;

        // Adiciona dias da semana
        DAY_NAMES.forEach(day => {
            const dayName = document.createElement('div');
            dayName.className = 'calendar-day day-name';
            dayName.textContent = day;
            calendarGrid.appendChild(dayName);
        });
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const daysInMonth = lastDay.getDate();

        // Preenche células vazias antes do primeiro dia
        let startDayOfWeek = firstDay.getDay(); 
        for (let i = 0; i < startDayOfWeek; i++) {
            const emptyCell = document.createElement('div');
            emptyCell.className = 'calendar-day inactive';
            calendarGrid.appendChild(emptyCell);
        }
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        // Preenche os dias do mês
        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(year, month, day);
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const dayElement = document.createElement('div');
            dayElement.className = 'calendar-day';
            dayElement.textContent = day;
            dayElement.dataset.date = dateStr; 
            // Define se o dia é hoje
            if (date.getTime() === today.getTime()) {
                dayElement.classList.add('today');
            }
            // Define se o dia já passou
            if (date < today) {
                dayElement.classList.add('inactive');
                dayElement.style.cursor = 'default';
            } else {
                // Event listener para seleção de data
                dayElement.addEventListener('click', () => {
                    // Remove seleção anterior
                    document.querySelectorAll('.calendar-day.selected').forEach(d => d.classList.remove('selected'));
                    // Adiciona nova seleção
                    dayElement.classList.add('selected');
                    selectedDate = dateStr;
                    dataAgendamentoInput.value = selectedDate;
                    // Atualiza display
                    const dayNamePt = getDayName(date);
                    dataAgendamentoDisplay.value = `${day} de ${MONTH_NAMES[month]} de ${year} (${dayNamePt})`;
                    // Limpa e recarrega as opções de clínica/médico (depende do dia da semana)
                    clinicaSelect.innerHTML = '<option value="">Selecione a Clínica</option>';
                    medicoSelect.innerHTML = '<option value="">Selecione o Médico</option>';
                    medicoSelect.disabled = true;
                    horariosDiv.innerHTML = '';
                    submitBtn.disabled = true;
                    horarioInput.value = '';
                    // Carrega as opções (Clínicas/Médicos) para o dia da semana
                    fetchOptionsForDay(dayNamePt); 
                });
            } calendarGrid.appendChild(dayElement);
        } }

    // Event Listeners para navegação
    document.getElementById('prev-month').addEventListener('click', () => {
        if (currentMonth === 0) {
            currentMonth = 11;
            currentYear--;
        } else {
            currentMonth--;
        } renderCalendar(currentMonth, currentYear);
    });
    document.getElementById('next-month').addEventListener('click', () => {
        if (currentMonth === 11) {
            currentMonth = 0;
            currentYear++;
        } else {
            currentMonth++;
        } renderCalendar(currentMonth, currentYear);
    });

    // Inicialização
    document.addEventListener('DOMContentLoaded', () => {
        // Renderiza o calendário inicial
        renderCalendar(currentMonth, currentYear);
        // Manipula mensagens de erro/sucesso da URL (vindo do agendamentos.php)
        const urlParams = new URLSearchParams(window.location.search);
        const error = urlParams.get('error');
        const success = urlParams.get('success');
        if (error) {
            let msg = 'Ocorreu um erro no agendamento.';
            if (error === 'empty_or_invalid_fields') msg = 'Preencha todos os campos obrigatórios.';
            if (error === 'time_already_booked') msg = 'O horário selecionado não está mais disponível. Por favor, escolha outro.';
            if (error === 'database_insertion_failed') msg = 'Erro interno ao salvar o agendamento no banco de dados.';
            if (error === 'unauthorized') msg = 'Você precisa estar logado como paciente para agendar consultas.';
            showFeedback(msg, 'error');
        } else if (success) {
            showFeedback('Agendamento realizado com sucesso! Verifique seu painel.', 'success');
        }
        // Atualização de filtros e carregamento de horários
        clinicaSelect.addEventListener('change', updateMedicoOptions);
        medicoSelect.addEventListener('change', loadAvailableTimeSlots);
        // Garante que o input de data não possa ser alterado manualmente, apenas pelo clique no calendário
        dataAgendamentoInput.addEventListener('input', (e) => e.target.value = selectedDate || '');
    });
</script>
</body>
</html>