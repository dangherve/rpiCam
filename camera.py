import subprocess

class Camera:
    def __init__(self):
        self.process = None
        self.width = 320
        self.height = 240

    def start(self):
        self.stop()

        cmd = [
            "rpicam-vid",
            "-t", "0",
            "-w", str(self.width),
            "-h", str(self.height),
            "-fps", "30",
            "-o", "-"
        ]

        self.process = subprocess.Popen(
            cmd,
            stdout=subprocess.PIPE,
            bufsize=0
        )

    def stop(self):
        if self.process:
            self.process.kill()
            self.process = None

    def set_resolution(self, width, height):
        self.width = width
        self.height = height
        self.start()

    def frames(self):
        buffer = b""

        while True:
            if not self.process:
                self.start()

            chunk = self.process.stdout.read(4096)
            if not chunk:
                continue

            buffer += chunk

            start = buffer.find(b'\xff\xd8')
            end = buffer.find(b'\xff\xd9')

            if start != -1 and end != -1:
                jpg = buffer[start:end+2]
                buffer = buffer[end+2:]

                yield (b'--frame\r\n'
                       b'Content-Type: image/jpeg\r\n\r\n' +
                       jpg + b'\r\n')
