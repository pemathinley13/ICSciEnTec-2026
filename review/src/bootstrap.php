<?php

declare(strict_types=1);

date_default_timezone_set('Asia/Thimphu');

// Minimal PSR-4-ish autoloader for the App\ namespace — no Composer required,
// so this deploys to plain shared hosting with nothing more than a file
// upload. App\Foo\Bar maps to src/Foo/Bar.php.
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

$configPath = __DIR__ . '/../config/config.php';
if (!is_file($configPath)) {
    http_response_code(500);
    die('Missing config/config.php — copy config/config.sample.php to config/config.php and fill in your settings.');
}

App\Auth\Session::start();

/** Small helper so pages/templates can do config('mail.from_email') etc. */
function config(string $dotKey, $default = null)
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../config/config.php';
    }
    $value = $config;
    foreach (explode('.', $dotKey) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function old(string $field, $default = '')
{
    return $_SESSION['old_input'][$field] ?? $default;
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . App\Auth\Session::csrfToken() . '">';
}
