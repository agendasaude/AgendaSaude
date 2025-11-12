<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
} if (!defined('ADMIN_EMAIL')) {
    @include_once 'conexao.php'; 
}
// Verifica o tipo de usuário logado
$user_logged_in = isset($_SESSION['user_id']);
$user_tipo = $_SESSION['tipo'] ?? 'visitante';
$is_admin = $user_tipo === 'admin';

// Função para gerar os links de navegação
function render_nav_links($is_admin, $user_logged_in) {
    $links = [];
    // Links para todos os usuários logados
    if ($user_logged_in) {
        $links[] = ['href' => 'sistema.php', 'icon' => 'fas fa-user-circle', 'text' => 'Meu Painel'];
    }
    // Todos (exceto Admin) podem Agendar e Buscar
    if (!$is_admin) {
        $links[] = ['href' => 'calendario.php', 'icon' => 'fas fa-calendar', 'text' => 'Agendar']; 
        $links[] = ['href' => 'buscar.html', 'icon' => 'fas fa-search', 'text' => 'Busca']; // Mantido .html conforme solicit
    }
    // Links específicos do Admin
    if ($is_admin) {
        $links[] = ['href' => 'sistema.php', 'icon' => 'fas fa-lock', 'text' => 'Admin'];
    }
    // Logout ou Login
    if ($user_logged_in) {
        $links[] = ['href' => 'logout.php', 'icon' => 'fas fa-sign-out-alt', 'text' => 'Sair'];
    } else {
        // Redireciona para a página de escolha de cadastro
        $links[] = ['href' => 'tipoconta.php', 'icon' => 'fas fa-user-plus', 'text' => 'Cadastro']; 
        $links[] = ['href' => 'login.html', 'icon' => 'fas fa-sign-in-alt', 'text' => 'Login']; 
    } return $links;
} $nav_links = render_nav_links($is_admin, $user_logged_in);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title><?php echo $page_title ?? 'AgendaSaúde'; ?></title>
</head>
<body>
    <header>
        <a href="inicio.php" class="logo">AgendaSaúde</a>
        <nav>
            <?php foreach ($nav_links as $link): ?>
                <a href="<?php echo htmlspecialchars($link['href']); ?>">
                    <i class="<?php echo htmlspecialchars($link['icon']); ?>"></i> 
                    <?php echo htmlspecialchars($link['text']); ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </header>