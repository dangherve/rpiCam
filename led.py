import pigpio

LED_PINS = [22, 27, 17]

class LEDController:
    def __init__(self):
        self.pi = pigpio.pi()
        for pin in LED_PINS:
            self.pi.set_mode(pin, pigpio.OUTPUT)

    def set(self, led_id, duty):
        if led_id < 0 or led_id >= len(LED_PINS):
            raise ValueError("Invalid LED ID")

        self.pi.set_PWM_dutycycle(LED_PINS[led_id], duty)
