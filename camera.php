<?php
/**
 * Tiny camera API. The web server is NOT elevated with sudo.
 * The bash script elevates only its rpicam commands and camera signals.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$allowedActions = ['status', 'photo', 'capture', 'video', 'stop-video'];
$action = (string) ($_POST['action'] ?? 'status');

if (!in_array($action, $allowedActions, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid camera action']);
    exit;
}

$arguments = [];
if ($action === 'video') {
    $seconds = filter_var($_POST['seconds'] ?? null, FILTER_VALIDATE_INT);

    if ($seconds === false || $seconds === null || $seconds < 10 || $seconds > 3600) {
        http_response_code(400);
        echo json_encode(['error' => 'Video duration must be 10–3600 seconds']);
        exit;
    }

    $arguments[] = (string) $seconds;
}

// Requests from visible browsers refresh one shared inactivity timestamp.
$stateDir = '/tmp/rpicam-web-' . posix_geteuid();
if (!is_dir($stateDir) && !mkdir($stateDir, 0700, true) && !is_dir($stateDir)) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to create camera state directory']);
    exit;
}
touch($stateDir . '/activity');

$command = 'bash ' . escapeshellarg(__DIR__ . '/camera.sh');
$command .= ' ' . escapeshellarg($action);
foreach ($arguments as $argument) {
    $command .= ' ' . escapeshellarg($argument);
}

// The controller must only print JSON to stdout on successful requests.
$lines = [];
$exitCode = 0;

exec($command . ' 2>&1', $lines, $exitCode);
$output = implode("\n", $lines);


if ($exitCode !== 0) {
    http_response_code(500);
    echo json_encode(['error' => $output ?: 'Camera command failed']);
    exit;
}

$data = json_decode($output, true);
if (!is_array($data) || !isset($data['mode'])) {
    http_response_code(500);
    echo json_encode(['error' => 'Unexpected controller output', 'details' => $output]);
    exit;
}

echo json_encode($data);
