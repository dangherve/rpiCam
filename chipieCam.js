function changeResolution(value) {
    let parts = value.split("x");
    fetch('/set_resolution', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'width=' + parts[0] + '&height=' + parts[1]
    });
}

function updateLED(id, duty) {
    document.getElementById("led" + id + "num").value = duty;
    fetch('/led/' + id, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'duty=' + duty
    });
}

function sendLED(id) {
    let duty = document.getElementById("led" + id + "num").value;
    document.getElementById("led" + id).value = duty;
    fetch('/led/' + id, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'duty=' + duty
    });
}

function offLED(id) {
    fetch('/led/' + id, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'duty=0'
    });
}

function onLED(id) {
    fetch('/led/' + id, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'duty=255'
    });
}

async function updateWebsocketStatus(){
    let object = await fetch('/websocketStatus');
    let res = await object.text();
    document.getElementById("websocketStatus").innerHTML = res

    object = await fetch('/cameraStatus');
    res = await object.text();
    document.getElementById("cameraStatus").innerHTML = res

    now = new Date()
    document.getElementById('status').innerText = now.toLocaleTimeString("fr-FR");

}

async function startChipieCam(){
    fetch('/start_websocket')

    do{
        object = await fetch('/websocketStatus');
        res = await object.text();
        console.log("resultat:"+res+" temoin:"+"&#128308;")
        document.getElementById("websocketStatus").innerHTML = res
    }while (res === "&#128308;")

    fetch('/start_camera')

    do{
        object = await fetch('/cameraStatus');
        res = await object.text();
        document.getElementById("cameraStatus").innerHTML = res
    }while (res === "&#128308;")
    setInterval(updateWebsocketStatus, 60*1000);
    startPlayer()
}


function startPlayer(){
    if (mpegts.getFeatureList().mseLivePlayback) {
        const player = mpegts.createPlayer({
            type: 'mpegts',
            url: 'ws://' + location.hostname + ':8082/'
        });
        player.attachMediaElement(document.getElementById('video'));
        player.load();
        player.play();
    }
}

