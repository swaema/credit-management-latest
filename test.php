<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credit Score Predictor</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .form-container {
            max-width: 600px;
            margin: auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
            background-color: #f9f9f9;
        }
        .form-container h2 {
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        .form-group button {
            width: 100%;
            padding: 10px;
            background-color: #007BFF;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .form-group button:hover {
            background-color: #0056b3;
        }
        .result {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #28a745;
            background-color: #dff0d8;
            color: #155724;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Credit Score Predictor</h2>
        <form method="post" action="">
            <div class="form-group">
                <label for="annual_income">Annual Income:</label>
                <input type="number" step="0.01" id="annual_income" name="annual_income" required>
            </div>
            <div class="form-group">
                <label for="monthly_inhand_salary">Monthly Inhand Salary:</label>
                <input type="number" step="0.01" id="monthly_inhand_salary" name="monthly_inhand_salary" required>
            </div>
            <div class="form-group">
                <label for="num_bank_accounts">Number of Bank Accounts:</label>
                <input type="number" id="num_bank_accounts" name="num_bank_accounts" required>
            </div>
            <div class="form-group">
                <label for="num_credit_card">Number of Credit Cards:</label>
                <input type="number" id="num_credit_card" name="num_credit_card" required>
            </div>
            <div class="form-group">
                <label for="interest_rate">Interest Rate:</label>
                <input type="number" step="0.01" id="interest_rate" name="interest_rate" required>
            </div>
            <div class="form-group">
                <label for="num_of_loan">Number of Loans:</label>
                <input type="number" id="num_of_loan" name="num_of_loan" required>
            </div>
            <div class="form-group">
                <label for="delay_from_due_date">Days of Payment Delay from Due Date:</label>
                <input type="number" id="delay_from_due_date" name="delay_from_due_date" required>
            </div>
            <div class="form-group">
                <label for="num_of_delayed_payment">Number of Delayed Payments:</label>
                <input type="number" id="num_of_delayed_payment" name="num_of_delayed_payment" required>
            </div>
            <div class="form-group">
                <label for="credit_mix">Credit Mix (2: Good, 1: Standard, 0: Bad):</label>
                <input type="number" min="0" max="2" id="credit_mix" name="credit_mix" required>
            </div>
            <div class="form-group">
                <label for="outstanding_debt">Outstanding Debt:</label>
                <input type="number" step="0.01" id="outstanding_debt" name="outstanding_debt" required>
            </div>
            <div class="form-group">
                <label for="credit_history_age">Credit History Age (in months):</label>
                <input type="number" id="credit_history_age" name="credit_history_age" required>
            </div>
            <div class="form-group">
                <label for="monthly_balance">Monthly Balance:</label>
                <input type="number" step="0.01" id="monthly_balance" name="monthly_balance" required>
            </div>
            <div class="form-group">
                <button type="submit" name="predict">Predict Credit Score</button>
            </div>
        </form>
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['predict'])) {
            // Collect form data
            $data = array(
                "features" => array(
                    (float)$_POST['annual_income'],
                    (float)$_POST['monthly_inhand_salary'],
                    (int)$_POST['num_bank_accounts'],
                    (int)$_POST['num_credit_card'],
                    (float)$_POST['interest_rate'],
                    (int)$_POST['num_of_loan'],
                    (int)$_POST['delay_from_due_date'],
                    (int)$_POST['num_of_delayed_payment'],
                    (int)$_POST['credit_mix'],
                    (float)$_POST['outstanding_debt'],
                    (int)$_POST['credit_history_age'],
                    (float)$_POST['monthly_balance']
                )
            );

            // Send data to Python API
            $api_url = 'http://127.0.0.1:5001/predict'; // Python API URL
            $ch = curl_init($api_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            curl_close($ch);

            if ($response) {
                $result = json_decode($response, true);
                if (isset($result['credit_score'])) {
                    echo "<div class='result'>Predicted Credit Score: " . $result['credit_score'] . "</div>";
                } else {
                    echo "<div class='result'>Error: Unable to fetch prediction. Check your API.</div>";
                }
            } else {
                echo "<div class='result'>Error: API did not respond. Ensure the Python API is running.</div>";
            }
        }
        ?>
    </div>
</body>
</html>
