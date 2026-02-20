# from fastapi import FastAPI
# from fastapi.middleware.cors import CORSMiddleware
# import threading

# app = FastAPI()

# # Allow JS frontend to access API (CORS)
# app.add_middleware(
#     CORSMiddleware,
#     allow_origins=["*"],  # Change to your frontend URL in production
#     allow_methods=["*"],
#     allow_headers=["*"],
# )

# # Use the same data storage from before
# from data_capture import data_storage, start_data_capture  # import your capture code

# # Start background data capture in a thread
# threading.Thread(target=start_data_capture, daemon=True).start()

# @app.get("/data")
# def get_data():
#     """
#     Return the latest captured data
#     """
#     return data_storage[-10:]  # return last 10 entries

# @app.get("/latest-reading")
# def get_latest_readings():
#     if len(data_storage) == 0:
#         return None

#     latest = data_storage[-1]
#     previous = data_storage[-2] if len(data_storage) > 1 else None

#     return {
#         "current": latest,
#         "previous": previous
#     }
