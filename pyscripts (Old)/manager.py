import time
from apscheduler.schedulers.background import BackgroundScheduler
from datetime import datetime

from hub import capture_data_once, load_meters
from post_data_campus import upload_campus_data

def hub_task():
    print(f"[{datetime.now()}] Running Hub Refresh (6-hour interval)...")
    load_meters()
    # Capture data immediately after refreshing meters, so we have fresh data for the upload task
    capture_data_once()

def upload_task():
    print(f"[{datetime.now()}] Running Campus Upload...")
    upload_campus_data()

scheduler = BackgroundScheduler()

# ** Scheduled tasks **
# # Schedule Manager task, every 6 hours at 06:00, 12:00, 18:00, 00:00
# scheduler.add_job(hub_task, 'cron', hour='0,6,12,18')
# # Schedule upload task, "odd" intervals (9am, 3pm, 9pm)
# scheduler.add_job(upload_task, 'cron', hour='9,15,21')

# For testing, use shorter intervals like this:
scheduler.add_job(hub_task, 'interval', seconds=10)
scheduler.add_job(upload_task, 'interval', seconds=15)

# Start the scheduler
scheduler.start()

print("Scheduler started!")

try:
    while True:
        time.sleep(1)
except (KeyboardInterrupt, SystemExit):
    scheduler.shutdown()