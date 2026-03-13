<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Bootstrap;
use App\Core\Database;
use App\Models\UserModel;

require_once dirname(__DIR__) . '/app/Core/Bootstrap.php';

$config = Bootstrap::init();

$db = Database::connection($config);
$auth = new Auth(new UserModel($db), $config);

if ($auth->check()) {
    header('Location: /dashboard.php');
    exit;
}

header('Location: /login.php');
exit;
