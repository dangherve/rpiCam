<html>
    <head>
        <link rel="stylesheet" href="chipieCam.css">
        <meta charset="UTF-8">
        <script src="chipieCam.js"></script>
    </head>

    <body>
        <h1>
            <span class="icon">✨</span>
            ChipieCam
            <span class="icon">✨</span>
        </h1>
        <h2>
            Status <span id="status" ></span><span id="time" ></span>
        </h2>
        <video id="video" controls autoplay></video>
        <div id="controls">
            <h2>
                <span class="icon">💡</span>
                LED Controls
                <span class="icon">💡</span>
            </h2>
<?php

$leds = [
    ["id" => 0, "name" => "Cool White"],
    ["id" => 1, "name" => "Warm White"],
    ["id" => 2, "name" => "InfraRed"]
];
?>

<?php foreach ($leds as $led): ?>
<div class="row">
    <span class="led"><?= $led['name'] ?>:</span>
    <input id="led<?= $led['id'] ?>" type="range" min="0" max="255" value="0">
    <script >
const range<?= $led['id'] ?> = document.getElementById("led<?= $led['id'] ?>");
range<?= $led['id'] ?>.addEventListener("change", function () {
  updateLED(<?= $led['id'] ?>,this.value);
});
    </script>

    <input id="led<?= $led['id'] ?>num" type="number" min="0" max="255" value="0">
    <button class="btn btn-set" onclick="sendLED(<?= $led['id'] ?>)" >Set</button>
    <button id="led<?= $led['id'] ?>btn" class="btn btn-off" onclick="onLED(<?= $led['id'] ?>)">OFF</button>
</div>
<?php endforeach; ?>
            <h2>
                <span class="icon">🎥</span>
                Video Resolution
                <span class="icon">🎥</span>
            </h2>
            <select id="resolutionSelect" onchange="changeResolution(this.value)">
                <option value="320x240">320x240</option>
                <option value="640x480">640x480</option>
                <option value="1280x720">1280x720</option>
            </select>
        </div>
        <script src="player.js"></script>

        <script >
now = new Date()
var video = document.getElementById('video');
var hls = new Hls();
hls.loadSource('hls/stream.m3u8');
hls.attachMedia(video);

document.getElementById('status').innerHTML = "&#11044;";
updateChipieCameraStatus()

        </script>


    </body>
</html>
