<?php

    $ledFile        = "/tmp/led";

    $serviceName = "camera.service";

    $LED_PINS = [22, 27, 17];

    /* ========================================================= */
    /* HELPERS                                                   */
    /* ========================================================= */

    /**
     * Load led status
     */

    function getLed($file) {
        $content = @file_get_contents($file);

        if ($content === false || strpos($content, ',') === false) {
            $ledStatus = [0,0,0];
            file_put_contents($file, "$ledStatus[0],$ledStatus[1],$ledStatus[2]");
            return $ledStatus;
        }

        $ledStatus = explode(',', $content);
        return $ledStatus;
    }

    /**
     * Save led
     */


    function setLed($file, $ledStatus)
    {
        file_put_contents(
            $file,
            ((int)$ledStatus[0]) . ',' .
            ((int)$ledStatus[1]) . ',' .
            ((int)$ledStatus[2])
        );
    }

    /* ========================================================= */
    /* LED MANAGEMENT                                            */
    /* ========================================================= */

    $ledStatus = getLed($ledFile);

    $led  = -1;
    $duty = -1;
    $cmd  = "NOT CALL";

    if (isset($_POST["led"], $_POST["duty"])) {

        $led  = (int)$_POST["led"];
        $duty = (int)$_POST["duty"];

        if (isset($LED_PINS[$led])) {

            /*
             * Inverted PWM as in your original code.
             */
            $pwm = 255 - $duty;

            $cmd = sprintf(
                "pigs p %d %d",
                $LED_PINS[$led],
                $pwm
            );

            shell_exec($cmd);

            $ledStatus[$led] = $duty;

            setLed($ledFile, $ledStatus);
        }
    }

    /* ========================================================= */
    /* CAMERA MANAGEMENT                                          */
    /* ========================================================= */

    if (isset($_POST["photo"] && $_POST["photo"]==1) {
            shell_exec("sudo rpicam-still -t 0 -n -o photo.jpeg --signal >/tmp/photo.log");

    }


    /* ========================================================= */
    /* RESPONSE                                                    */
    /* ========================================================= */

    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        "cameraStatus" => $cameraStatus,
        "cameraRunning" => $running,
        "cmd" => $cmd,
        "led" => $led,
        "duty" => $duty,
        "ledStatus" => $ledStatus,
        "resolution" => $width . "x" . $height,
        "famerate" => $famerate
    ]);

?>

