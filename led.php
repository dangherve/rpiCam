<?php
/** LED API. Read PWM duty cycle from pigpio rather than persisting a status file. */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$ledPins = [22, 27, 17];

if (isset($_POST['led'], $_POST['duty'])) {
    $led = filter_var($_POST['led'], FILTER_VALIDATE_INT);
    $duty = filter_var($_POST['duty'], FILTER_VALIDATE_INT);
    if ($led === false || !isset($ledPins[$led]) || $duty === false || $duty < 0 || $duty > 255) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid LED or brightness']);
        exit;
    }
    // The hardware has inverted PWM: 255 represents off in the UI.
    exec(sprintf('pigs p %d %d', $ledPins[$led], 255 - $duty));
}

$levels = [];
foreach ($ledPins as $pin) {
    $raw = trim((string)shell_exec('pigs gdc ' . $pin . ' 2>/dev/null'));
    $levels[] = is_numeric($raw) ? max(0, min(255, 255 - (int)$raw)) : null;
}
echo json_encode(['ledStatus' => $levels]);
