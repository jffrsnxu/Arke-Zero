<?php
/**
 * Arke Zero — Contact form handler
 *
 * Receives the "Draft a request" form via fetch()/FormData and returns JSON.
 * On XAMPP locally, PHP's mail() usually won't actually deliver mail unless
 * you've configured sendmail/SMTP (see the note at the bottom of this file).
 * The form still validates and responds correctly either way, so you can
 * wire up real delivery (e.g. PHPMailer + SMTP) later without touching main.js.
 */

header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

function field($key) {
    return isset($_POST[$key]) ? trim($_POST[$key]) : '';
}

$name    = field('name');
$email   = field('email');
$company = field('company');
$service = field('service');
$message = field('message');

// ---- Validation ----
$errors = [];

if ($name === '' || mb_strlen($name) > 120) {
    $errors[] = 'Please provide a valid name.';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please provide a valid email address.';
}
if ($message === '' || mb_strlen($message) > 3000) {
    $errors[] = 'Please describe what you need help with.';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// ---- Basic header-injection protection ----
$name    = str_replace(["\r", "\n"], '', $name);
$email   = str_replace(["\r", "\n"], '', $email);
$company = str_replace(["\r", "\n"], '', $company);
$service = str_replace(["\r", "\n"], '', $service);

// ---- Build the notification email ----
$to      = 'hello@arkezero.com'; // TODO: replace with your real inbox
$subject = 'New request from ' . $name . ' — Arke Zero site';

$body  = "New contact form submission\n";
$body .= "----------------------------\n";
$body .= "Name: {$name}\n";
$body .= "Email: {$email}\n";
$body .= "Company: " . ($company !== '' ? $company : '—') . "\n";
$body .= "Service interested in: " . ($service !== '' ? $service : '—') . "\n\n";
$body .= "Message:\n{$message}\n";

$headers   = [];
$headers[] = 'From: Arke Zero Website <no-reply@arkezero.com>';
$headers[] = 'Reply-To: ' . $email;
$headers[] = 'Content-Type: text/plain; charset=UTF-8';

// ---- Attempt to send. mail() returns false quietly if unconfigured on XAMPP. ----
$sent = @mail($to, $subject, $body, implode("\r\n", $headers));

// Always log submissions locally too, so nothing is lost during local dev/testing.
$logLine = sprintf(
    "[%s] %s <%s> | %s | %s\n",
    date('Y-m-d H:i:s'),
    $name,
    $email,
    $service !== '' ? $service : 'unspecified',
    str_replace(["\r", "\n"], ' ', $message)
);
@file_put_contents(__DIR__ . '/submissions.log', $logLine, FILE_APPEND);

echo json_encode([
    'success' => true,
    'message' => 'Request received.',
    // mail_sent tells you (in devtools) whether mail() actually fired —
    // useful while testing on XAMPP where mail is often not configured.
    'mail_sent' => $sent
]);

/**
 * NOTE on local XAMPP mail delivery:
 * PHP's mail() needs a configured mail transport to actually deliver anything.
 * For local testing you have two common options:
 *   1. Point php.ini's [mail function] SMTP settings at a real SMTP server
 *      (e.g. Gmail SMTP, Mailtrap for a safe testing sandbox), or
 *   2. Swap this out for PHPMailer with explicit SMTP credentials.
 * Either way, this handler's JSON contract (success/message) stays the same,
 * so the front-end doesn't need to change.
 */
