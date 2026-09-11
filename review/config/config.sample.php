<?php
/**
 * Copy this file to config.php and fill in real values. config.php is
 * gitignored — never commit real credentials.
 */

return [
    'app' => [
        // Base URL the app is served from, no trailing slash. Used for building
        // links in emails. e.g. 'https://review.cst.edu.bt'
        'base_url' => 'http://localhost:8000',
        // true only once served over HTTPS — flips the session cookie's Secure flag.
        'https' => false,
        // Blank = public/setup_admin.php is disabled (the default, safe state).
        // Set to a long random string ONLY temporarily, to create the first
        // admin account on a host with no SSH/terminal access — then blank it
        // out again (or delete setup_admin.php) immediately after use.
        'setup_token' => '',
    ],

    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'icscientec_review',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    'mail' => [
        // 'smtp' once configured (Phase B), 'log' just records to email_log without
        // actually sending — safe default until real SMTP credentials exist.
        'driver'      => 'log',
        'smtp_host'   => '',
        'smtp_port'   => 587,
        'smtp_user'   => '',
        'smtp_pass'   => '',
        'smtp_secure' => 'tls', // 'tls' or 'ssl'
        'from_email'  => 'secretary_icscientec.cst@rub.edu.bt',
        'from_name'   => 'ICSciEnTec Organizing Committee',
    ],

    'uploads' => [
        // Absolute path to storage/, OUTSIDE the public/ webroot.
        'storage_path'    => dirname(__DIR__) . '/storage',
        'max_size_bytes'  => 20 * 1024 * 1024, // 20 MB
        'allowed_ext'     => ['docx', 'tex', 'pdf', 'zip'],
    ],
];
