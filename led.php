<?php

$LED_PINS = [22, 27, 17];


$cmd="pigs p ".$LED_PINS[$_GET['led']]." ".$_GET['duty'];


shell_exec($cmd);

?>
