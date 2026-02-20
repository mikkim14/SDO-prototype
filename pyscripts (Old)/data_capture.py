import time
import random

# Global storage for captured data
data_storage = []

previous = 0.0
BASELINE = 25.0

def capture_data():
    """
    Simulate data capture by generating random numbers
    """
    
    global previous
    if previous == 0.0:
        random_value = random.uniform(22.0, 28.0)
    else:
        noise = random.uniform(-0.6, 0.6)       # small random noise
        pull = (BASELINE - previous) * 0.15
        random_value = previous + noise + pull

    random_value = round(random_value, 2)
    previous = random_value

    new_data = {
        "current_reading": random_value,
        "captured_at": time.time()  # UNIX timestamp
    }

    previous = random_value
    data_storage.append(new_data)
    print(f"Captured data: {new_data}")

# Simulate data capture every 15 seconds
def start_data_capture(interval=15):
    while True:
        capture_data()
        time.sleep(interval)

# Uncomment below to run as standalone simulation
# start_data_capture()
