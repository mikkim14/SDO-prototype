import time
import random
import requests

class Meter:
    def __init__(self, meter_id, campus, baseline=25.0, api_adress = None):
        self.meter_id = meter_id # Unique identifier for the meter
        self.campus = campus # Store campus for grouping
        self.api_adress = api_adress # Store API address
        self.previous = 0.0 # Store the previous reading
        self.data = [] # List to store captured readings

        self.last_seen = None # Timestamp of the last captured reading
        self.is_online = False # Status of the meter (online/offline)

        # For simulated readings, we can have a baseline that the value tends to return to
        self.baseline = baseline
    
    # Capture the API readings of the physical meter
    def capture_api_reading(self):
        try:
            address = self.api_adress
            resp = requests.get(address, timeout=5)

            payload = resp.json()
            value = payload.get(payload["reading"], 0.0) 

            # Set the previous value, hmm find a better way to do store the previous data of the physical meter without touching the actual reading 
            # Oh right, the reading is stored in the data list, so we can just get the last reading from there with the index of [-2] and the latest reading is stored in the index of [-1]
            self.previous = value

            self.last_seen = time.time()
            self.is_online = True

            reading = {
                "value": value,
                "captured_at": time.time()  # UNIX timestamp
            }
            
            self.data.append(reading)
            print(f"Captured API data: {reading} | {self.meter_id} | {self.campus}")
            return reading

        except Exception as e:
            print (f"Failed to capture API reading for meter {self.meter_id}: {e}")
            return None
    
    # Capture a simulated reading (for testing without real hardware)
    def capture_simulated_reading(self):
        if self.previous == 0.0:
            random_value = random.uniform(22.0, 28.0)
        else:
            noise = random.uniform(-0.6, 0.6)       # small random noise
            pull = (self.baseline - self.previous) * 0.15
            random_value = self.previous + noise + pull

        random_value = round(random_value, 2)
        self.previous = random_value

        reading = {
            "value": random_value,
            "captured_at": time.time()  # UNIX timestamp
        }

        self.last_seen = time.time()
        self.is_online = True

        self.data.append(reading)
        print(f"Captured simulated data: {reading} | {self.meter_id} | {self.campus}")
        return reading

    def check_health(self, timeout_seconds=120):
        if self.last_seen is None:
            self.is_online = False
            return False

        if time.time() - self.last_seen > timeout_seconds:
            self.is_online = False
            return False

        self.is_online = True
        return True