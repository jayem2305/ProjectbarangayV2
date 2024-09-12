import tensorflow as tf
from tensorflow.keras.preprocessing.image import ImageDataGenerator
from tensorflow.keras.models import Sequential
from tensorflow.keras.layers import Conv2D, MaxPooling2D, Flatten, Dense, Dropout, Input
from tensorflow.keras.optimizers import Adam

# Define parameters
img_height, img_width = 150, 150
batch_size = 32
epochs = 10

# Prepare data generators
train_datagen = ImageDataGenerator(
    rescale=1./255,
    shear_range=0.2,
    zoom_range=0.2,
    horizontal_flip=True
)

train_generator = train_datagen.flow_from_directory(
    'dataset/',
    target_size=(img_height, img_width),
    batch_size=batch_size,
    class_mode='binary'  # Use 'categorical' if you have more than two classes
)

# Define the CNN model
model = Sequential([
    Input(shape=(img_height, img_width, 3)),  # Input layer
    Conv2D(32, (3, 3), activation='relu'),
    MaxPooling2D(pool_size=(2, 2)),
    Conv2D(64, (3, 3), activation='relu'),
    MaxPooling2D(pool_size=(2, 2)),
    Conv2D(128, (3, 3), activation='relu'),
    MaxPooling2D(pool_size=(2, 2)),
    Flatten(),
    Dense(128, activation='relu'),
    Dropout(0.5),
    Dense(1, activation='sigmoid')  # Use 'softmax' for more than two classes
])

# Compile the model with the updated parameter
model.compile(optimizer=Adam(learning_rate=0.001),  # Use learning_rate instead of lr
              loss='binary_crossentropy',  # Use 'categorical_crossentropy' for more than two classes
              metrics=['accuracy'])

# Train the model
history = model.fit(
    train_generator,
    steps_per_epoch=train_generator.samples // batch_size,
    epochs=epochs
)

# Save the model
model.save('face_detection_model.h5')
