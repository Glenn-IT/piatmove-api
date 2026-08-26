<?php
// ==============================================================================
// PiatMove Mail & SMTP Configuration
// ==============================================================================

// Gmail SMTP Server Settings
if (!defined('SMTP_HOST'))       define('SMTP_HOST',       getenv('SMTP_HOST')       ?: 'smtp.gmail.com');
if (!defined('SMTP_PORT'))       define('SMTP_PORT',       (int)(getenv('SMTP_PORT') ?: 587));
if (!defined('SMTP_SECURE'))     define('SMTP_SECURE',     getenv('SMTP_SECURE')     ?: 'tls'); // 'tls' (port 587) or 'ssl' (port 465)

// Gmail Credentials
if (!defined('SMTP_USER'))       define('SMTP_USER',       getenv('SMTP_USER')       ?: 'prototypev1.03@gmail.com');
if (!defined('SMTP_PASS'))       define('SMTP_PASS',       getenv('SMTP_PASS')       ?: 'ffyjpbwqfjnnaldy');
if (!defined('SMTP_FROM_EMAIL')) define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: SMTP_USER);
if (!defined('SMTP_FROM_NAME'))  define('SMTP_FROM_NAME',  getenv('SMTP_FROM_NAME')  ?: 'PiatMove Transport');

// Load optional secrets file if present
$mailSecretsFile = __DIR__ . '/mail_secrets.php';
if (file_exists($mailSecretsFile)) {
    require_once $mailSecretsFile;
}

