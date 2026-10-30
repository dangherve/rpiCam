/** UI-facing LED functions. Duty is 0..255 and inverted on the hardware. */
async function ledRequest(data = {}) {
    const response = await fetch('led.php', { method: 'POST', body: new URLSearchParams(data), cache: 'no-store' });
    if (!response.ok) throw new Error('LED request failed');
    return response.json();
}
function renderLed(id, duty) {
    document.getElementById(`led${id}`).value = duty;
    document.getElementById(`led${id}num`).value = duty;
    const button = document.getElementById(`led${id}btn`);

    if(duty > 0){
        button.textContent = "OFF";
        button.classList.remove("btn-on");
        button.classList.add("btn-off");
        button.setAttribute("onclick", `offLED(${id})`);

    }else{
        button.textContent = "ON";
        button.classList.remove("btn-off");
        button.classList.add("btn-on");
        button.setAttribute("onclick", `onLED(${id})`);
    }



    button.onclick = duty > 0 ? () => offLED(id) : () => onLED(id);
}
async function updateLED(id, duty) {
    const state = await ledRequest({ led: id, duty });
    state.ledStatus.forEach((value, index) => { if (value !== null) renderLed(index, value); });
}
function sendLED(id) { return updateLED(id, document.getElementById(`led${id}num`).value); }
function onLED(id) { return updateLED(id, 255); }
function offLED(id) { return updateLED(id, 0); }
async function refreshLEDs() {
    try { (await ledRequest()).ledStatus.forEach((value, index) => { if (value !== null) renderLed(index, value); }); }
    catch (error) { console.error(error); }
}

/*  setInterval(refreshLEDs, 10000); */
document.addEventListener('DOMContentLoaded', () => { refreshLEDs(); });
