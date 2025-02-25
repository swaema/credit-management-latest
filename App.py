from flask import Flask, request, jsonify
import joblib
import pandas as pd
import os

# Initialize Flask app
app = Flask(__name__)

# Load the trained model, scaler, and feature columns
# Get the absolute path for each file
model_file = os.path.abspath("random_forest_model_compressed.pkl")
scaler_file = os.path.abspath("scaler.pkl")
feature_columns_file = os.path.abspath("feature_columns.pkl")

try:
    rf_model = joblib.load(model_file)
    scaler = joblib.load(scaler_file)
    feature_columns = joblib.load(feature_columns_file)
    print("Model, scaler, and feature columns loaded successfully.")
except Exception as e:
    print(f"Error loading model or associated files: {e}")
    rf_model = None
    scaler = None
    feature_columns = None

# Define common purposes used during training
common_purposes = ['credit_card', 'debt_consolidation', 'home_improvement', 'major_purchase', 'medical', 'other']

# Function to preprocess input data
def preprocess_input(input_data, feature_columns, common_purposes):
    """
    Preprocess input data for prediction by aligning it with training features.

    Args:
        input_data (dict): Input data with features for prediction.
        feature_columns (list): List of feature columns used in the training model.
        common_purposes (list): List of common purposes used during training.

    Returns:
        numpy.ndarray: Scaled and preprocessed input data.
    """
    input_df = pd.DataFrame([input_data])
    input_df['term'] = input_df['term'].astype(int)
    input_df['emp_length'] = input_df['emp_length'].str.extract('(\\d+)').astype(float).fillna(0)

    # Map unseen 'purpose' values to 'other'
    input_df['purpose'] = input_df['purpose'].apply(lambda x: x if x in common_purposes else 'other')

    # One-hot encode 'purpose'
    input_df = pd.get_dummies(input_df, columns=['purpose'], drop_first=True)

    # Add missing columns and align with training features
    missing_cols = set(feature_columns) - set(input_df.columns)
    for col in missing_cols:
        input_df[col] = 0  # Add missing columns with default value 0

    # Drop extra columns to match the training columns
    input_df = input_df[feature_columns]

    # Transform the data using the scaler
    return scaler.transform(input_df)

# Define API endpoint for predictions
@app.route('/predict', methods=['POST'])
def predict():
    try:
        # Parse JSON request
        data = request.get_json()

        # Ensure required fields are present
        required_fields = ['loan_amnt', 'term', 'int_rate', 'emp_length', 'annual_inc', 'purpose', 'dti']
        missing_fields = [field for field in required_fields if field not in data]
        if missing_fields:
            return jsonify({'error': f"Missing fields in request: {missing_fields}"}), 400

        # Preprocess input data
        input_scaled = preprocess_input(data, feature_columns, common_purposes)

        # Make prediction
        prediction = rf_model.predict(input_scaled)
        reverse_grade_mapping = {0: 'A', 1: 'B', 2: 'C', 3: 'D', 4: 'E', 5: 'F', 6: 'G'}
        predicted_grade = reverse_grade_mapping[prediction[0]]

        # Return the prediction as JSON response
        return jsonify({'credit_grade': predicted_grade})

    except Exception as e:
        return jsonify({'error': str(e)}), 500

# Main entry point for running the API
if __name__ == '__main__':
    if rf_model is None or scaler is None or feature_columns is None:
        print("Model, scaler, or feature columns are not loaded. Exiting.")
    else:
        app.run(host='0.0.0.0', port=5001)
