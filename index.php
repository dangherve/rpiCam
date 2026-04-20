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
            Status <span id="status0" ></span><span id="time0" ></span>
            Status <span id="status1" ></span><span id="time1" ></span>
        </h2>

        <div class="video-container">
            <div class="video-block">
                <video id="video" controls autoplay></video>
            </div>
            <div class="video-block">
                <video id="video2" controls autoplay></video>
            </div>
        </div>

        <div id="controls">
            <h2>
                <span class="icon">💡</span>
                LED Controls
                <span class="icon">💡</span>
            </h2>
<?php

$leds = [
    ["camera"=> 0, "id" => 0, "name" => "Cool White"],
    ["camera"=> 0, "id" => 1, "name" => "Warm White"],
    ["camera"=> 0, "id" => 2, "name" => "InfraRed"],

    ["camera"=> 1, "id" => 0, "name" => "Cool White"],
    ["camera"=> 1, "id" => 1, "name" => "Warm White"],
    ["camera"=> 1, "id" => 2, "name" => "RED"],
    ["camera"=> 1, "id" => 3, "name" => "GREEN"],
    ["camera"=> 1, "id" => 4, "name" => "BLUE"],
];
?>

<?php foreach ($leds as $led): ?>
<div class="row">
    <span class="led"><?= $led['name'] ?>:</span>
    <input id="led<?= $led['camera'] ?><?= $led['id'] ?>" type="range" min="0" max="255" value="0">
    <script >
const range<?= $led['camera'] ?><?= $led['id'] ?> = document.getElementById("led<?= $led['camera'] ?><?= $led['id'] ?>");
range<?= $led['camera'] ?><?= $led['id'] ?>.addEventListener("change", function () {
  updateLED(<?= $led['camera'] ?>,<?= $led['id'] ?>,this.value);
});
    </script>

    <input id="led<?= $led['camera'] ?><?= $led['id'] ?>num" type="number" min="0" max="255" value="0">
    <button class="btn btn-set" onclick="sendLED(<?= $led['camera'] ?><?= $led['id'] ?>)" >Set</button>
    <button id="led<?= $led['camera'] ?><?= $led['id'] ?>btn" class="btn btn-off" onclick="onLED(<?= $led['camera'] ?><?= $led['id'] ?>)">OFF</button>
</div>
<?php endforeach; ?>

            <h2>
                <span class="icon">🎥</span>
                Video Resolution
                <span class="icon">🎥</span>
            </h2>
            <select id="resolutionSelect0" onchange="changeResolution(0,this.value)">
                <option value="320x240">320x240</option>
                <option value="640x480">640x480</option>
                <option value="1280x720">1280x720</option>
            </select>
            <select id="resolutionSelect1" onchange="changeResolution(1,this.value)">
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
var cameraUrl=cameraSelect(0)

hls.loadSource(cameraUrl+'hls/stream.m3u8');
hls.attachMedia(video);

document.getElementById('status0').innerHTML = "&#11044;";
updateChipieCameraStatus(0)


var video2 = document.getElementById('video2');
var hls2 = new Hls();
var cameraUrl2=cameraSelect(1)

hls2.loadSource(cameraUrl2+'hls/stream.m3u8');
hls2.attachMedia(video2);
document.getElementById('status1').innerHTML = "&#11044;";
updateChipieCameraStatus(1)



        </script>


    </body>
</html>
