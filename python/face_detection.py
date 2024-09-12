import sys
import json
import face_recognition

def load_image(image_path):
    return face_recognition.load_image_file(image_path)

def compare_faces(image1_path, image2_path):
    image1 = load_image(image1_path)
    image2 = load_image(image2_path)

    encodings1 = face_recognition.face_encodings(image1)
    encodings2 = face_recognition.face_encodings(image2)

    if not encodings1 or not encodings2:
        return {'status': 'error', 'message': 'No faces found in one or both images'}

    match_results = face_recognition.compare_faces([encodings1[0]], encodings2[0])
    if match_results[0]:
        return {'status': 'success', 'message': 'Faces match!'}
    else:
        return {'status': 'error', 'message': 'Faces do not match'}

if __name__ == "__main__":
    if len(sys.argv) != 3:
        print(json.dumps({'status': 'error', 'message': 'Invalid arguments'}))
        sys.exit(1)

    image1_path = sys.argv[1]
    image2_path = sys.argv[2]

    result = compare_faces(image1_path, image2_path)
    print(json.dumps(result))
