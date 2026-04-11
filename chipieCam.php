<?php

    $cmd="";


    $resolutionFile="/tmp/resolution";
    $ledFile="/tmp/led";

    $pidFile="/tmp/pid";
    $logFile="/tmp/chipieCam.log";

    $LED_PINS = [22, 27, 17];

    $RED=0;
    $YELLOW=1;
    $GREEN=2;

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

    function setled($file, $ledStatus) {
        file_put_contents($file, ((int)$ledStatus[0]).','.((int)$ledStatus[1]).','.((int)$ledStatus[2]));
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

    function setResolution($file, $w, $h) {
        file_put_contents($file, ((int)$w).'x'.((int)$h));
    }

    /**
     * Check if process is running
     */

    function isRunning($pid) {
        return $pid > 0 && file_exists("/proc/$pid");
    }

    $pid = (int) @file_get_contents($pidFile);

    $ledStatus=getLed($ledFile);

    /* ===================== */
    /* RESOLUTION MANAGEMENT */
    /* ===================== */

    if (isset($_POST["width"], $_POST["height"])) {
        $killCmd="sudo killall -15 rpicam-vid ffmpeg";
        $width  = (int)$_POST["width"];
        $height = (int)$_POST["height"];
        setResolution($resolutionFile, $width, $height);
        shell_exec($killCmd);
        sleep(3);
    } else {
        list($width, $height) = getResolution($resolutionFile);
    }


    /* ===================== */
    /* LED MANAGEMENT        */
    /* ===================== */
    $led=-1;
    $duty=-1;

    if (isset($_POST["led"], $_POST["duty"])) {

        $led=(int)$_POST["led"];
        $duty=255-(int)$_POST["duty"];

        $cmd="pigs p ".$LED_PINS[$led]." ".$duty;

        shell_exec($cmd);

        $ledStatus[$led]=$_POST["duty"];

        setled($ledFile,$ledStatus);

    }else{
        $cmd="NOT CALL";
    }

    /* ===================== */
    /* CAMERA STATUS         */
    /* ===================== */

    if (!isRunning($pid)) {
        $cmd = "sudo rpicam-vid -t 0 --inline --width $width --height $height --framerate 25 -o -| ffmpeg -i - -c:v copy -f flv rtmp://localhost/live/stream >$logFile 2>&1 & sleep 3; pgrep rpicam-vid";
        $pid = (int) shell_exec($cmd);
        file_put_contents($pidFile, $pid);
        $cameraStatus = $RED;
    } else {
        if (file_exists("/var/www/hls/stream.m3u8")) {
            $cameraStatus = $GREEN;
        } else {
            $cameraStatus = $YELLOW;
        }
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        "cameraStatus" => $cameraStatus,
        "cmd" => $cmd,
        "led" => $led,
        "duty" => $duty,
        "ledStatus" => $ledStatus,
        "resolution" => $width . "x" . $height
    ]);


?>
