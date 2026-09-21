<?php

    $resolutionFile = "/tmp/resolution";
    $ledFile        = "/tmp/led";

    $serviceName = "camera.service";

    $LED_PINS = [22, 27, 17];

    $RED    = 0;
    $YELLOW = 1;
    $GREEN  = 2;


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

    /**
     * Load resolution
     */

    function getResolution($file) {
        $content = @file_get_contents($file);

        if ($content === false || strpos($content, 'x') === false) {

            $w = 640;
            $h = 480;
            file_put_contents($file, "$w"."x"."$h");
            return [$w, $h];
        }

        list($w, $h) = explode('x', $content);
        return [(int)$w, (int)$h];
    }

    /**
     * Save resolution
     */
    function setResolution($file, $w, $h)
    {
        file_put_contents(
            $file,
            ((int)$w) . 'x' . ((int)$h)
        );
    }


    /*
     * Get systemd service state.
     */
    function cameraRunning($serviceName)
    {
        $result = shell_exec(
            "sudo systemctl is-active " .
            escapeshellarg($serviceName) .
            " 2>/dev/null"
        );

        return trim($result) === "active";
    }


    /*
     * Restart camera.
     */
    function restartCamera($serviceName)
    {
        shell_exec(
            "sudo systemctl restart " .
            escapeshellarg($serviceName) .
            " 2>&1"
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
    /* RESOLUTION                                                  */
    /* ========================================================= */

    [$width, $height] = getResolution($resolutionFile);

    if (isset($_POST["width"], $_POST["height"])) {

        $newWidth  = (int)$_POST["width"];
        $newHeight = (int)$_POST["height"];

        if ($newWidth > 0 && $newHeight > 0) {

            /*
             * Only restart if resolution actually changed.
             */
            if (
                $newWidth != $width ||
                $newHeight != $height
            ) {

                setResolution(
                    $resolutionFile,
                    $newWidth,
                    $newHeight
                );

                $width  = $newWidth;
                $height = $newHeight;

                restartCamera($serviceName);
            }
        }
    }


    /* ========================================================= */
    /* CAMERA STATUS                                               */
    /* ========================================================= */

    $running = cameraRunning($serviceName);


    /*
     * Give systemd/rpicam a moment to initialize the stream.
     */
    if ($running) {

        if (file_exists("/var/www/hls/stream.m3u8")) {
            $cameraStatus = $GREEN;
        } else {
            $cameraStatus = $YELLOW;
        }

    } else {

        $cameraStatus = $RED;
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

        "resolution" => $width . "x" . $height
    ]);

?>

