import io
import cv2
import RPi.GPIO as GPIO
import pigpio

from flask import Flask, Response, request
from picamera2 import Picamera2

app = Flask(__name__)

# Define LED GPIO pins
LED_PINS = [22, 27, 17]  # adjust to your wiring

pi = pigpio.pi()

for pin in LED_PINS:
    pi.set_mode(pin, pigpio.OUTPUT)

# Camera setup
picam2 = Picamera2()
picam2.configure(picam2.create_video_configuration(main={"size": (320, 240)}))

picam2.start()

def gen_frames():
    """Generator that yields MJPEG frames from Picamera2."""
    while True:
        frame = picam2.capture_array("main")
        ret, jpeg = cv2.imencode('.jpg', frame)
        if not ret:
            continue
        yield (b'--frame\r\n'
               b'Content-Type: image/jpeg\r\n\r\n' + jpeg.tobytes() + b'\r\n')

@app.route('/video')
def video_feed():
    return Response(gen_frames(),
                    mimetype='multipart/x-mixed-replace; boundary=frame')

@app.route('/led/<int:led_id>', methods=['POST'])
def control_led(led_id):
    if led_id < 0 or led_id >= len(LED_PINS):
        return "Invalid LED ID", 400
    try:
        duty = int(request.form.get("duty", 0))  # 0–255
        pi.set_PWM_dutycycle(LED_PINS[led_id], duty)
        print(f"LED {led_id} set to {duty}")
        return f"LED {led_id} set to {duty}", 200
    except Exception as e:
        return str(e), 500

@app.route('/set_resolution', methods=['POST'])
def set_resolution():
    width = int(request.form.get("width", 320))
    height = int(request.form.get("height", 240))
    try:
        picam2.stop()
        picam2.configure(picam2.create_video_configuration(
            main={"size": (width, height), "format": "XRGB8888"}))
        picam2.start()
        return f"Resolution changed to {width}x{height}", 200
    except Exception as e:
        return str(e), 500


 # width="640" height="480"
@app.route('/')
def index():
    with open("index.html", "r") as file:
        content = file.read()
    return content

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=800, threaded=True)

