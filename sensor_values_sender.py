import random
import time
import requests
from datetime import datetime

# =============================================
#  CONFIG  
# =============================================
API_URL = "http://127.0.0.1:8000/predict"
DASHBOARD_API = "http://127.0.0.1/agrismart-dashboard/api_save_sensors.php"
SEND_INTERVAL_SECONDS = 15  # 900=15mis
# =============================================

def is_daytime(hour):
    return 8 <= hour <= 18

def generate_sensor_values():
    hour = datetime.now().hour
    day = is_daytime(hour)

    if day:
        air_temperature  = round(random.uniform(24, 34), 2)
        air_humidity     = round(random.uniform(50, 80), 2)
        soil_temperature = round(random.uniform(20, 28), 2)
        soil_moisture    = round(random.uniform(40, 75), 2)

        case = random.choice(["low_light", "normal_light", "strong_light"])
        if case == "low_light":
            light_intensity = round(random.uniform(200, 350), 2)
        elif case == "normal_light":
            light_intensity = round(random.uniform(400, 700), 2)
        else:
            light_intensity = round(random.uniform(700, 1000), 2)
    else:
        air_temperature  = round(random.uniform(15, 22), 2)
        air_humidity     = round(random.uniform(60, 90), 2)
        soil_temperature = round(random.uniform(17, 23), 2)
        soil_moisture    = round(random.uniform(50, 80), 2)
        light_intensity  = round(random.uniform(200, 300), 2)

    return {
        "light_intensity":  light_intensity,
        "soil_moisture":    soil_moisture,
        "soil_temperature": soil_temperature,
        "air_humidity":     air_humidity,
        "air_temperature":  air_temperature
    }

def send_to_api(sensor_data):
    try:
        response = requests.post(API_URL, json=sensor_data, timeout=5)
        response.raise_for_status()
        return response.json()
    except requests.exceptions.ConnectionError:
        return {}  # Aucune prédiction disponible, le dashboard affichera "En attente"
    except requests.exceptions.Timeout:
        return {"error": " API timeout"}
    except Exception as e:
        return {"error": str(e)}

def save_to_dashboard(sensor_data, result):
    # Prepare payload with prediction included
    payload = sensor_data.copy()
    if "error" not in result:
        payload["prediction"] = result.get("prediction", "N/A")
    else:
        payload["prediction"] = "Error"
        
    try:
        response = requests.post(DASHBOARD_API, json=payload, timeout=5)
        response.raise_for_status()
        res_json = response.json()
        print(f"  Dashboard Integration: {res_json.get('message', 'OK')}")
    except Exception as e:
        print(f"  Dashboard Integration Error: {str(e)}")

def print_result(sensor_data, result):
    now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    print("-" * 55)
    print(f"  Timestamp         : {now}")
    print(f"  Light Intensity   : {sensor_data['light_intensity']}")
    print(f"  Soil Moisture     : {sensor_data['soil_moisture']}")
    print(f"  Soil Temperature  : {sensor_data['soil_temperature']}")
    print(f"  Air Humidity      : {sensor_data['air_humidity']}")
    print(f"  Air Temperature   : {sensor_data['air_temperature']}")

    if "error" in result:
        print(f"  {result['error']}")
    else:
        prediction = result.get("prediction", "N/A")
        probabilities = result.get("probabilities", {})

        print(f"  Prediction        : {prediction.upper()}")
        print(f"  Probabilities     :")
        for label, prob in sorted(probabilities.items()):
            bar = "#" * int(prob * 20)
            print(f"      {label:6} : {prob*100:5.1f}%  {bar}")

if __name__ == "__main__":
    print(" Live Sensor Sender started — sending to:", API_URL)
    print(" Dashboard integration — sending to:", DASHBOARD_API)
    print(f"   Interval: every {SEND_INTERVAL_SECONDS}s  |  Ctrl+C to stop\n")

    while True:
        sensor_data = generate_sensor_values()
        result = send_to_api(sensor_data)
        print_result(sensor_data, result)
        save_to_dashboard(sensor_data, result)
        time.sleep(SEND_INTERVAL_SECONDS)
