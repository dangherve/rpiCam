import io
import RPi.GPIO as GPIO
import subprocess
from flask import Flask, Response, request, abort, send_file


import time
app = Flask(__name__)

# Define LED GPIO pins
LED_PINS = [22, 27, 17]  # adjust to your wiring

width = 640
height = 480

CAMERA_PROCESS = None
WEBSOCKET_PROCESS = None


GPIO.setmode(GPIO.BCM)

for pin in LED_PINS:
    GPIO.setup(pin, GPIO.OUT)

@app.route('/led/<int:led_id>', methods=['POST'])
def control_led(led_id):
    if led_id < 0 or led_id >= len(LED_PINS):
        return "Invalid LED ID", 400
    try:
        duty = int(request.form.get("duty", 0))  # 0–255

        if duty == 0:
            print("off True")
            GPIO.output(LED_PINS[led_id],True)
        else:
            print("on False")
            GPIO.output(LED_PINS[led_id],False)


        return "OK", 200
    except Exception as e:
        print(str(e))
        return str(e), 500

@app.route('/websocketStatus')
def websocketStatus():
    if WEBSOCKET_PROCESS:

        with open('websocket.log', 'r') as file:
            content = file.read()
            if 'Awaiting' in content:
                return "&#128994;", 200

        return "&#128993;", 200
    return "&#128308;", 200

@app.route('/cameraStatus')
def cameraStatus():
    if not CAMERA_PROCESS:
        return "&#128308;", 200  # red

    if CAMERA_PROCESS.poll() is not None:
        stop_camera()
        start_camera()
        return "&#11044;", 200  # crashed

    if CAMERA_PROCESS:
        with open('camera.log', 'r') as file:
            content = file.read()
            if 'frame' in content:
                return "&#128994;", 200

        return "&#128993;", 200
    return "&#128308;", 200

@app.route('/')
def index():
    with open("index.html", "r") as file:
        content = file.read()
    return content, 200

@app.route('/<path:filename>')
def file(filename):
    return send_file(filename), 200


@app.route('/set_resolution', methods=['POST'])
def set_resolution():
    width = int(request.form.get("width", 320))
    height = int(request.form.get("height", 240))
    try:
        return f"Resolution changed to {width}x{height}", 200
    except Exception as e:
        return str(e), 500

#rpicam-vid -t 0 --inline --width 640 --height 480 --framerate 25 -o - | ffmpeg -fflags nobuffer -flags low_delay -i - -c copy -f mpegts -reconnect 1 -reconnect_streamed 1 -reconnect_delay_max 2 http://127.0.0.1:8080/aze

@app.route('/start_camera')
def start_camera():
    global CAMERA_PROCESS

    if CAMERA_PROCESS:
        return "", 200

    cmd = """
rpicam-vid -t 0 --inline --width 320 --height 240 --framerate 25 -o - | ffmpeg -thread_queue_size 512 -fflags nobuffer -flags low_delay -i - -c copy -f mpegts http://127.0.0.1:8080/aze > camera.log 2>&1
"""

    with open("camera.log", "wb") as log:
        CAMERA_PROCESS = subprocess.Popen(
            cmd,
            shell=True,
            executable="/bin/bash",
            stdout=log,
            stderr=log
        )


    print(f"The CAMERA_PROCESS ID is: {CAMERA_PROCESS.pid}")
    return "", 200


@app.route('/start_websocket')
def start_websocket():
    global WEBSOCKET_PROCESS

    if WEBSOCKET_PROCESS:
        return "", 200

    cmd = """node websocket-relay.js aze 8080 8082 > websocket.log 2>&1"""

    WEBSOCKET_PROCESS = subprocess.Popen(cmd, shell=True, executable="/bin/bash")

    print(f"The WEBSOCKET_PROCESS ID is: {WEBSOCKET_PROCESS.pid}")
    return "", 200

@app.route('/stop_websocket')
def stop_websocket():
    global WEBSOCKET_PROCESS
    if WEBSOCKET_PROCESS:
        WEBSOCKET_PROCESS.kill()
        WEBSOCKET_PROCESS = None
    return "", 200

@app.route('/stop_camera')
def stop_camera():
    global CAMERA_PROCESS
    if CAMERA_PROCESS:
        CAMERA_PROCESS.kill()
        CAMERA_PROCESS = None
    return "", 200



if __name__ == '__main__':
    app.run(host='0.0.0.0', port=800, threaded=True)

