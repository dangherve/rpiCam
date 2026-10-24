<?php

    $LED_PINS = [22, 27, 17];

    /* ========================================================= */
    /* LED MANAGEMENT                                            */
    /* ========================================================= */


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

        }
    }

    /* ========================================================= */
    /* CAMERA MANAGEMENT                                          */
    /* ========================================================= */

    if (isset($_POST["photo"])){

        if( $_POST["photo"]==1) {
            echo "toto";
            #exec("sudo rpicam-still -t 0 -n -o photo.jpeg --signal >/tmp/photo.log");
        }else{
            exec("sudo pkill -USR1 rpicam-still");

        }
    }


    /* ========================================================= */
    /* RESPONSE                                                    */
    /* ========================================================= */

    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        "cmd" => $cmd,
        "led" => $led,
        "duty" => $duty,
        "ledStatus" => $ledStatus
    ]);

?>

