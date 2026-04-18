from fastapi import FastAPI
import pandas as pd
import joblib

app = FastAPI()

# 🔥 Load your saved files
model = joblib.load("model.pkl")
scaler = joblib.load("scaler.pkl")
le = joblib.load("encoder.pkl")
columns = joblib.load("columns.pkl")

@app.get("/")
def home():
    return {"message": "AI Crop Yield API is running 🚀"}

@app.post("/predict")
def predict(data: dict):
    
    # Convert to DataFrame
    df = pd.DataFrame([data])
    
    # 🔧 Feature engineering (SAME as your notebook)
    df["temp_diff"] = df["air_temperature"] - df["soil_temperature"]
    df["humidity_temp_ratio"] = df["air_humidity"] / (df["air_temperature"] + 1)
    df["moisture_light_interaction"] = df["soil_moisture"] * df["light_intensity"]
    df["temp_avg"] = (df["air_temperature"] + df["soil_temperature"]) / 2
    df["stress_index"] = df["air_temperature"] * (100 - df["air_humidity"])
    df["water_balance"] = df["soil_moisture"] / (df["air_temperature"] + 1)
    
    # ⚠️ Match training columns
    df = df[columns]
    
    # Scale
    scaled = scaler.transform(df)
    
    # Predict
    pred = model.predict(scaled)
    label = le.inverse_transform(pred)[0]
    
    # Probabilities
    probs = model.predict_proba(scaled)[0]
    
    result = {
        "prediction": label,
        "probabilities": {
            le.classes_[i]: float(probs[i])
            for i in range(len(probs))
        }
    }
    
    return result
