import requests
import time
from datetime import datetime

FASTAPI_BASE_URL = "http://localhost:8000"
PHP_API_URL = "http://localhost/sdo/iot/iot_api_electricity.php"
METER_REGISTRY_API = "http://localhost/sdo/iot/iot_api_meters.php"

INTERVAL = 5  # seconds
session = requests.Session()

# Track last sent timestamp PER meter
last_sent_ts = {}

campus = "Alangilan"

def upload_campus_data():
    try:
        url = f"{FASTAPI_BASE_URL}/campus-total/{campus}"
        resp = session.get(url, timeout=5)
        data = resp.json()

        if "error" in data:
            print(f"[{campus}] No data yet")

        aggregated_consumption = data["total"]
        

        payload = {
            "action": "add_campus_record", # Matches the PHP case
            "campus": campus,
            "current_reading": aggregated_consumption,
            "year": datetime.now().year,
            "month": datetime.now().month
        }

        response = session.post(PHP_API_URL, data=payload, timeout=5)
        result = response.json()

        if result.get("success"):
            print(f"[{campus}] Uploaded successfully")
        else:
            print(f"[{campus}] Upload failed:", result)

    except Exception as e:
        print(f"[{campus}] Error:", e)
