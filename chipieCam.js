
function cameraSelect(camera) {
    if (camera == 0){
        return 'http://192.168.64.16/'
    }else{
        return 'http://192.168.64.17/'
    }

}

function changeResolution(camera,value) {
    let parts = value.split("x");
    let cameraUrl=cameraSelect(camera)
    fetch( cameraUrl+'chipieCam.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'width=' + parts[0] + '&height=' + parts[1]
    });
}

async function updateLED(camera,id, duty) {
    document.getElementById("led" + camera + id + "num").value = duty;
    let cameraUrl=cameraSelect(camera)
    let object = await fetch( cameraUrl+'chipieCam.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'led=' + id + '&duty=' + duty
    });
    let res = await object.text();
    let obj = JSON.parse(res);
    console.log(obj)

    updateChipieCameraStatus(camera)

}

async function sendLED(camera,id) {
    let duty = document.getElementById("led" + camera + id + "num").value;
    document.getElementById("led" + id).value = duty;
    let cameraUrl=cameraSelect(camera)
    let object = await fetch( cameraUrl+'chipieCam.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'led=' + id + '&duty=' + duty
    });
    let res =  await object.text();
    let obj = JSON.parse(res);
    console.log(obj)

    updateChipieCameraStatus(camera)

}

async function offLED(camera,id) {
    let cameraUrl=cameraSelect(camera)
    let object = await fetch( cameraUrl+'chipieCam.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'led=' + id + '&duty=0'
    });
    let res =  await object.text();
    let obj = JSON.parse(res);
    console.log(obj)

    updateChipieCameraStatus(camera)
}

async function onLED(camera,id) {
    let cameraUrl=cameraSelect(camera)
    let object = await fetch( cameraUrl+'chipieCam.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'led=' + id + '&duty=255'
    });


    let res =  await object.text();
    let obj = JSON.parse(res);
    console.log(obj)

    updateChipieCameraStatus(camera)
}

function updateLed(camera,item,index){
    led=index
    duty=Number(item)

    range = document.getElementById(`led${camera}${led}`)
    btn   = document.getElementById(`led${camera}${led}btn`)
    num   = document.getElementById(`led${camera}${led}num`)

    range.value=duty
    num.value=duty

    if(duty != 0){
        btn.textContent = "OFF";
        btn.classList.remove("btn-on");
        btn.classList.add("btn-off");
        btn.setAttribute("onclick", `offLED(${camera},${led})`);

    }else{
        btn.textContent = "ON";
        btn.classList.remove("btn-off");
        btn.classList.add("btn-on");
        btn.setAttribute("onclick", `onLED(${camera},${led})`);
    }

}

async function updateChipieCameraStatus(camera){
    let cameraUrl=cameraSelect(camera)
    let object = await fetch(cameraUrl+'chipieCam.php');
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
    document.getElementById('status'+camera).innerHTML = status ;
    document.getElementById('time'+camera).innerHTML =  now.toLocaleTimeString("fr-FR");

    leds=obj.ledStatus

    leds.forEach(updateLed.bind(null,camera))

    setTimeout(updateChipieCameraStatus.bind(camera), timeout*1000)
}
