<?php
declare(strict_types=1);

// Use the address recipients can open, e.g. https://example.com/taskflow-php.
// localhost links only work on this computer. A LAN IP works on the same network.
const APP_URL = 'http://localhost/taskflow-php';

// Enter your SMTP provider settings here. Keep this file private.
const SMTP_HOST = '';
const SMTP_PORT = 587;
const SMTP_SECURITY = 'tls'; // tls (STARTTLS, usually 587), or ssl (usually 465)
const SMTP_USERNAME = '';
const SMTP_PASSWORD = ''; // Provider SMTP password or app password.
const SMTP_FROM = '';     // A sender address allowed by your SMTP provider.
