from fastapi import FastAPI, UploadFile, File
from fastapi.responses import JSONResponse
import tensorflow as tf
import numpy as np
import tempfile
import os

app = FastAPI(title="Leaf Disease Detection API")


# =====================================
# ALL CLASSES (same order as training)
# =====================================
class_name = [
    'Apple___Apple_scab',
    'Apple___Black_rot',
    'Apple___Cedar_apple_rust',
    'Apple___healthy',
    'Blueberry___healthy',
    'Cherry_(including_sour)___Powdery_mildew',
    'Cherry_(including_sour)___healthy',
    'Corn_(maize)___Cercospora_leaf_spot Gray_leaf_spot',
    'Corn_(maize)___Common_rust_',
    'Corn_(maize)___Northern_Leaf_Blight',
    'Corn_(maize)___healthy',
    'Grape___Black_rot',
    'Grape___Esca_(Black_Measles)',
    'Grape___Leaf_blight_(Isariopsis_Leaf_Spot)',
    'Grape___healthy',
    'Orange___Haunglongbing_(Citrus_greening)',
    'Peach___Bacterial_spot',
    'Peach___healthy',
    'Pepper,_bell___Bacterial_spot',
    'Pepper,_bell___healthy',
    'Potato___Early_blight',
    'Potato___Late_blight',
    'Potato___healthy',
    'Raspberry___healthy',
    'Soybean___healthy',
    'Squash___Powdery_mildew',
    'Strawberry___Leaf_scorch',
    'Strawberry___healthy',
    'Tomato___Bacterial_spot',
    'Tomato___Early_blight',
    'Tomato___Late_blight',
    'Tomato___Leaf_Mold',
    'Tomato___Septoria_leaf_spot',
    'Tomato___Spider_mites Two-spotted_spider_mite',
    'Tomato___Target_Spot',
    'Tomato___Tomato_Yellow_Leaf_Curl_Virus',
    'Tomato___Tomato_mosaic_virus',
    'Tomato___healthy'
]


# =====================================
# MODEL PREDICTION FUNCTION
# =====================================
def model_prediction(test_image):
    model = tf.keras.models.load_model("trained_model.keras")

    image = tf.keras.preprocessing.image.load_img(
        test_image,
        target_size=(128, 128)
    )

    input_arr = tf.keras.preprocessing.image.img_to_array(image)

    input_arr = np.array([input_arr])

    prediction = model.predict(input_arr)

    result_index = np.argmax(prediction)

    confidence = float(np.max(prediction)) * 100

    return int(result_index), round(confidence, 2)


# =====================================
# CLEAN LABEL
# =====================================
def clean_result(label):
    parts = label.split("___")

    plant = parts[0]
    disease = parts[1]

    plant = plant.replace("_", " ")
    plant = plant.replace(",", " ")
    plant = plant.replace("(", "")
    plant = plant.replace(")", "")
    plant = " ".join(plant.split())

    disease = disease.replace("_", " ")
    disease = disease.replace("(", "")
    disease = disease.replace(")", "")
    disease = " ".join(disease.split())

    is_healthy = disease.lower() == "healthy"

    return plant, disease, is_healthy


# =====================================
# HOME
# =====================================
@app.get("/")
def home():
    return {"message": "Leaf Disease Detection API Running"}


# =====================================
# PREDICT
# =====================================
@app.post("/predict")
async def predict(file: UploadFile = File(...)):
    try:
        # Save uploaded image
        contents = await file.read()

        with tempfile.NamedTemporaryFile(delete=False, suffix=".jpg") as temp:
            temp.write(contents)
            temp_path = temp.name

        # Predict
        result_index, confidence = model_prediction(temp_path)

        # Delete temp file
        os.remove(temp_path)

        # Get class label
        label = class_name[result_index]

        # Clean result
        plant, disease, is_healthy = clean_result(label)

        return JSONResponse(content={
            "plant": plant,
            "disease": disease,
            "confidence": confidence,
            "is_healthy": is_healthy
        })

    except Exception as e:
        return JSONResponse(
            content={"error": str(e)},
            status_code=500
        )

## to run the API use  command : uvicorn app:app --reload

## hada bach truni API 