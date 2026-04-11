from flask import Flask, Response, request, render_template
from camera import Camera
from led import LEDController

app = Flask(__name__)

camera = Camera()
leds = LEDController()

camera.start()

@app.route('/')
def index():
    return render_template("index.html")

@app.route('/video')
def video():
    return Response(camera.frames(),
                    mimetype='multipart/x-mixed-replace; boundary=frame')

@app.route('/led/<int:led_id>', methods=['POST'])
def control_led(led_id):
    try:
        duty = int(request.form.get("duty", 0))
        leds.set(led_id, duty)
        return f"LED {led_id} set to {duty}", 200
    except Exception as e:
        return str(e), 500

@app.route('/set_resolution', methods=['POST'])
def set_resolution():
    try:
        width = int(request.form.get("width", 320))
        height = int(request.form.get("height", 240))
        camera.set_resolution(width, height)
        return f"Resolution changed to {width}x{height}", 200
    except Exception as e:
        return str(e), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=80, threaded=True)
