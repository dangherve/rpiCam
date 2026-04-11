

function changeResolution(value) {
    let parts = value.split("x");
    fetch( 'chipieCam.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'width=' + parts[0] + '&height=' + parts[1]
    });
}

async function updateLED(id, duty) {
    document.getElementById("led" + id + "num").value = duty;
    let object = await fetch( 'chipieCam.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'led=' + id + '&duty=' + duty
    });
    let res = await object.text();
    let obj = JSON.parse(res);
    console.log(obj)

    updateChipieCameraStatus()

}

async function sendLED(id) {
    let duty = document.getElementById("led" + id + "num").value;
    document.getElementById("led" + id).value = duty;
    let object = await fetch( 'chipieCam.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'led=' + id + '&duty=' + duty
    });
    let res =  await object.text();
    let obj = JSON.parse(res);
    console.log(obj)

    updateChipieCameraStatus()

}

async function offLED(id) {

    let object = await fetch( 'chipieCam.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'led=' + id + '&duty=0'
    });
    let res =  await object.text();
    let obj = JSON.parse(res);
    console.log(obj)

    updateChipieCameraStatus()
}

async function onLED(id) {

    let object = await fetch( 'chipieCam.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'led=' + id + '&duty=255'
    });


    let res =  await object.text();
    let obj = JSON.parse(res);
    console.log(obj)

    updateChipieCameraStatus()
}

function updateLed(item,index){
    led=index
    duty=Number(item)

    range = document.getElementById(`led${led}`)
    btn   = document.getElementById(`led${led}btn`)
    num   = document.getElementById(`led${led}num`)

    range.value=duty
    num.value=duty

    if(duty != 0){
        btn.textContent = "OFF";
        btn.classList.remove("btn-on");
        btn.classList.add("btn-off");
        btn.setAttribute("onclick", `offLED(${led})`);

    }else{
        btn.textContent = "ON";
        btn.classList.remove("btn-off");
        btn.classList.add("btn-on");
        btn.setAttribute("onclick", `onLED(${led})`);
    }

}

async function updateChipieCameraStatus(){
    let object = await fetch('chipieCam.php');
    let res =  await object.text();
    let obj = JSON.parse(res);

    let status = "&#128308;"
    let timeout = 1
    switch(obj.cameraStatus){
        case 0:
            status="&#128308;"
            tiemout=1
            break
        case 1:
            status="&#128993;"
            tiemout=1
            break
        case 2:
            status="&#128994;"
            tiemout=60
            break
    }

    now = new Date()
    document.getElementById('status').innerHTML = status ;
    document.getElementById('time').innerHTML =  now.toLocaleTimeString("fr-FR");

    leds=obj.ledStatus

    leds.forEach(updateLed)

    setTimeout(updateChipieCameraStatus, timeout*1000)
}
