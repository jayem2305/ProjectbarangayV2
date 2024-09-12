# python/check_id_face.py

import sys
import cv2
import json

def detect_face(image_path):
    # Load the pre-trained face detector model from OpenCV
    face_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')

    # Load the image
    img = cv2.imread(image_path)
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)

    # Detect faces
    faces = face_cascade.detectMultiScale(gray, 1.3, 5)

    # Check if any faces were detected
    if len(faces) > 0:
        return True
    else:
        return False

if __name__ == "__main__":
    image_path = sys.argv[1]

    # Detect face in the image
    face_detected = detect_face(image_path)

    # Return the result as a JSON response
    result = {
        "status": "success" if face_detected else "error",
        "message": "Face detected!" if face_detected else "No face detected."
    }
    print(json.dumps(result))
