<?php

require __DIR__ . '/../src/bootstrap.php';

use App\Auth\Session;
use App\Models\User;

if (Session::isLoggedIn()) {
    header('Location: ' . User::defaultDashboardUrl(Session::userId()));
} else {
    header('Location: /auth/login.php');
}
exit;
