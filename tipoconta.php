<?php
$page_title = 'Selecione o tipo de Cadastro - AgendaSaúde';
include 'header.php'; 
?>

<div class="selector-container">
    <h2>Selecione o tipo de Cadastro:</h2>
    <div class="option-grid">
        <a href="cadastroP.php" class="option-button">
            <i class="fas fa-user"></i>
            Paciente
        </a>
        <a href="cadastroM.php" class="option-button">
            <i class="fas fa-user-md"></i>
            Médico
        </a>
        <a href="cadastroC.php" class="option-button">
            <i class="fas fa-hospital"></i>
            Clínica
        </a>
    </div>
</div>

<footer>
    &copy; 2025 ©AgendaSaúde. Todos os direitos reservados.
</footer>

</body>
</html>