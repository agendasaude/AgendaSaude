<?php
$senha_desejada = 'minhasenhasecreta123';
$novo_hash = password_hash($senha_desejada, PASSWORD_DEFAULT);
echo "Senha: {$senha_desejada}<br>";
echo "NOVO HASH: <strong>{$novo_hash}</strong>";
?>