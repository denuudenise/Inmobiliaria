<?php
declare(strict_types=1);
require dirname(__DIR__) . '/sistema/Nucleo/inicio.php';
$user = require_user(['Agente', 'Administrador']);
redirect(role_destination($user['role']));
