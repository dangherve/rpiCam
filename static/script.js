function setLED(id, value) {
    fetch(`/led/${id}`, {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: `duty=${value}`
    });
}

function setResolution() {
    let w = document.getElementById("w").value;
    let h = document.getElementById("h").value;

    fetch("/set_resolution", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: `width=${w}&height=${h}`
    });
}
