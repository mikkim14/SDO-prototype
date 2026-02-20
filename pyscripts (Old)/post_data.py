import requests
import time
from datetime import datetime

FASTAPI_BASE_URL = "http://localhost:8000"
PHP_API_URL = "http://localhost/sdo/iot/iot_api_electricity.php"
METER_REGISTRY_API = "http://localhost/sdo/iot/iot_api_meters.php"

INTERVAL = 5  # seconds

# Track last sent timestamp PER meter
last_sent_ts = {}

def fetch_meters():
    resp = requests.get(METER_REGISTRY_API, timeout=5)
    return [m["meter_id"] for m in resp.json()]

while True:
    meter_ids = fetch_meters()

    for meter_id in meter_ids:
        try:
            url = f"{FASTAPI_BASE_URL}/latest-reading/{meter_id}"
            resp = requests.get(url, timeout=5)
            data = resp.json()

            if "error" in data:
                print(f"[{meter_id}] No data yet")
                continue

            current = data["current"]
            previous = data["previous"]

            captured_at = current["captured_at"]

            # Duplicate protection (per meter)
            if last_sent_ts.get(meter_id) == captured_at:
                print(f"[{meter_id}] Duplicate — skipping")
                continue

            current_value = current["value"]
            prev_value = (
                previous["value"]
                if previous
                else current_value * 0.95
            )

            payload = {
                "action": "add_record",
                "meter_id": meter_id,
                "current_reading": current_value,
                "prev_reading": prev_value,
            }

            response = requests.post(PHP_API_URL, data=payload, timeout=5)
            result = response.json()

            if result.get("success"):
                last_sent_ts[meter_id] = captured_at
                print(f"[{meter_id}] Uploaded successfully")
            else:
                print(f"[{meter_id}] Upload failed:", result)

        except Exception as e:
            print(f"[{meter_id}] [{payload}] Error:", e)

    time.sleep(INTERVAL)
