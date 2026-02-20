from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
import requests
import meters
import threading
import time

METER_REGISTRY_API = "http://localhost/sdo/iot/iot_api_meters.php"
METERS = {}

session = requests.Session()

# Load meters from the IOT Database API and create Meter instances
def load_meters():
    global METERS
    try:
        # resp = requests.get(METER_REGISTRY_API, timeout=5)
        retPayload = {
            "action": "electric",
            "campus": "Alangilan"
        }
        response = session.get(METER_REGISTRY_API, params=retPayload, timeout=5)
        meter_list = response.json()

        updated = {}
        for m in meter_list:
            meter_id = m["meter_id"]
            campus = m["campus"]
            # api_adress = m.get("api_address") 

            if meter_id in METERS:
                updated[meter_id] = METERS[meter_id]
            else:
                updated[meter_id] = meters.Meter(meter_id, campus,)
                # updated[meter_id] = meters.Meter(meter_id, campus, api_adress=api_adress)

        METERS = updated
        print(f"Loaded {len(METERS)} meters")

    except Exception as e:
        print("Failed to load meters:", e)

def capture_data_once():
    for meter in METERS.values():
        meter.capture_simulated_reading()
        meter.check_health()

# /* This function will be deprecated in favor of the scheduled task in manager.py, but can still use for testing

# def refresh_meters_loop():
#     while True:
#         load_meters()
#         time.sleep(60)  # refresh every minute

# def start_data_capture():
#     while True:
#         for meter in METERS.values():
#             meter.capture_simulated_reading()
#             meter.check_health()
#         time.sleep(10)

# */

app = FastAPI()

# Allow JS frontend to access API (CORS)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

# /* Soon to be deprecated in favor of the scheduled tasks in manager.py, but can still be use for testing

# Use the same data storage from before
# from data_capture import data_storage, start_data_capture

# Start background data capture in a thread
# threading.Thread(target=refresh_meters_loop, daemon=True).start()
# threading.Thread(target=start_data_capture, daemon=True).start()

# */

# // API Endpoints

# @app.get("/data")
# def get_data():
#     """
#     Return the latest captured data
#     """
#     return data_storage[-10:]  # return last 10 entries

@app.get("/latest-reading/{meter_id}")
def get_latest_readings(meter_id: str):
    meter = METERS.get(meter_id)
    if not meter or len(meter.data) == 0:
        return {"error": "Meter not found or empty data"}
    
    meter_id = meter.meter_id
    campus = meter.campus
    is_online = meter.is_online
    last_seen = meter.last_seen
    latest = meter.data[-1]
    previous = meter.data[-2] if len(meter.data) > 1 else None

    return {
        "meter_id": meter_id,
        "campus": campus,
        "is_online": is_online,
        "last_seen": last_seen,
        "current": latest,
        "previous": previous
    }

@app.get("/meters-status")
def meters_status():
    return [
        {
            "meter_id": m.meter_id,
            "campus": m.campus,
            "online": m.is_online,
            "last_seen": m.last_seen
        }
        for m in METERS.values()
    ]

@app.get("/campus/{campus}")
def area_readings(campus: str):
    readings = []
    for meter in METERS.values():
        if meter.campus == campus and meter.data:
            readings.append({
                "meter_id": meter.meter_id,
                "is_online": meter.is_online,
                "last_seen": meter.last_seen,
                "current": meter.data[-1],
                "previous": meter.data[-2] if len(meter.data) > 1 else None
            })
    return readings

@app.get("/campus-total/{campus}")
def area_total(campus: str):
    total = 0.0
    for meter in METERS.values():
        if meter.campus == campus and meter.data:
            total += meter.data[-1]["value"]
    return {"campus": campus, "total": round(total, 2)}