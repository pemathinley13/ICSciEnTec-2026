<?php

require __DIR__ . '/../../src/bootstrap.php';

App\Auth\Session::logout();
header('Location: /auth/login.php');
exit;
