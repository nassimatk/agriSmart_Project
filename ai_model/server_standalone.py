# -*- coding: utf-8 -*-
"""
AgriSmart AI Model - Serveur autonome (aucune installation requise)
Reproduit la logique de feature engineering du notebook et simule les prédictions
en attendant que les dépendances (pandas/scikit-learn) soient installées.
"""
from http.server import HTTPServer, BaseHTTPRequestHandler
import json
import math

PORT = 8000


def feature_engineering(data):
    """Reproduit exactement la logique du notebook."""
    air_temp = float(data.get("air_temperature", 25))
    air_hum = float(data.get("air_humidity", 65))
    soil_temp = float(data.get("soil_temperature", 22))
    soil_moisture = float(data.get("soil_moisture", 60))
    light = float(data.get("light_intensity", 500))

    temp_diff = air_temp - soil_temp
    humidity_temp_ratio = air_hum / (air_temp + 1)
    moisture_light_interaction = soil_moisture * light
    temp_avg = (air_temp + soil_temp) / 2
    stress_index = air_temp * (100 - air_hum)
    water_balance = soil_moisture / (air_temp + 1)

    return {
        "air_temperature": air_temp,
        "air_humidity": air_hum,
        "soil_temperature": soil_temp,
        "soil_moisture": soil_moisture,
        "light_intensity": light,
        "temp_diff": temp_diff,
        "humidity_temp_ratio": humidity_temp_ratio,
        "moisture_light_interaction": moisture_light_interaction,
        "temp_avg": temp_avg,
        "stress_index": stress_index,
        "water_balance": water_balance,
    }


def predict_yield(features):
    """
    Classification par règles agronomiques (approximation du modèle ML).
    Basée sur des seuils typiques pour la culture de tomates.
    """
    temp = features["air_temperature"]
    hum = features["air_humidity"]
    soil_m = features["soil_moisture"]
    light = features["light_intensity"]
    stress = features["stress_index"]
    water = features["water_balance"]

    # Score de conditions favorables (0-100)
    score = 0.0

    # Température optimale 22-28°C
    if 22 <= temp <= 28:
        score += 30
    elif 18 <= temp < 22 or 28 < temp <= 32:
        score += 15
    else:
        score += 0

    # Humidité idéale 60-75%
    if 60 <= hum <= 75:
        score += 20
    elif 50 <= hum < 60 or 75 < hum <= 85:
        score += 10
    else:
        score += 0

    # Humidité sol: 50-70%
    if 50 <= soil_m <= 70:
        score += 20
    elif 40 <= soil_m < 50 or 70 < soil_m <= 80:
        score += 10
    else:
        score += 0

    # Luminosité: 400-1000 Lux (capteur)
    if light >= 600:
        score += 20
    elif light >= 400:
        score += 10
    else:
        score += 0

    # Balance eau
    if water >= 2.0:
        score += 10

    # Decision
    if score >= 75:
        label = "excellente"
        probs = {"bonne": 0.10, "excellente": 0.78, "faible": 0.02, "moyenne": 0.10}
    elif score >= 55:
        label = "bonne"
        probs = {"bonne": 0.55, "excellente": 0.20, "faible": 0.05, "moyenne": 0.20}
    elif score >= 35:
        label = "moyenne"
        probs = {"bonne": 0.20, "excellente": 0.05, "faible": 0.20, "moyenne": 0.55}
    else:
        label = "faible"
        probs = {"bonne": 0.10, "excellente": 0.02, "faible": 0.78, "moyenne": 0.10}

    return label, probs


class AIHandler(BaseHTTPRequestHandler):
    def log_message(self, format, *args):
        print(f"[AgriSmart AI] {self.address_string()} - {format % args}")

    def do_GET(self):
        if self.path == "/":
            self._send_json({"message": "AgriSmart AI Yield API is running (standalone mode) [OK]"})
        else:
            self._send_json({"error": "Not found"}, 404)

    def do_POST(self):
        if self.path == "/predict":
            try:
                length = int(self.headers.get("Content-Length", 0))
                body = self.rfile.read(length)
                data = json.loads(body)

                features = feature_engineering(data)
                label, probs = predict_yield(features)

                result = {
                    "prediction": label,
                    "probabilities": probs
                }
                self._send_json(result)
            except Exception as e:
                self._send_json({"error": str(e)}, 500)
        else:
            self._send_json({"error": "Not found"}, 404)

    def do_OPTIONS(self):
        self.send_response(200)
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Content-Type")
        self.end_headers()

    def _send_json(self, data, code=200):
        body = json.dumps(data).encode()
        self.send_response(code)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(body)))
        self.send_header("Access-Control-Allow-Origin", "*")
        self.end_headers()
        self.wfile.write(body)


if __name__ == "__main__":
    print("=" * 50)
    print("  AgriSmart AI Yield Prediction Server")
    print("  Port: {} | Mode: Autonome (sans pip)".format(PORT))
    print("=" * 50)
    server = HTTPServer(("0.0.0.0", PORT), AIHandler)
    print(f"  [OK] Serveur pret sur http://127.0.0.1:{PORT}")
    print("  Ctrl+C pour arrêter\n")
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        print("\n  Serveur arrêté.")
        server.server_close()
