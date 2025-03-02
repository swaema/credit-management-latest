<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
class User
{
    public $id;
    public $name;
    public $email;
    public $password;
    public $role;
    public $mobile;
    public $image;
    public $address;
    public $status;
    public $user_verified;


    public function __construct($id = null, $name = null, $email = null, $password = null, $role = null, $mobile = null, $address = null, $image = null, $status = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->role = $role;
        $this->mobile = $mobile;
        $this->address = $address;
        $this->image = $image;
        $this->status = $status;
        $this->user_verified = false;
    }

    public function save()
    {
        try {
            // var_dump("e");
            // exit;
            // Ensure the database connection is available
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }

            // Validate required fields
            if (empty($this->name) || empty($this->email) || empty($this->password)) {
                throw new Exception("Name, email, and password are required.");
            }

            // Check if it's an insert or update operation
            $status = "inactive";
            // Prepare the insert query when we don't have an existing user (new user)
            $query = "INSERT INTO users (name, email, password, role, mobile, address,status) VALUES (?, ?, ?, ?, ?, ?,?)";
            $stmt = $db->prepare($query);

            // Bind parameters (all strings including image)
            $stmt->bind_param("sssssss", $this->name, $this->email, $this->password, $this->role, $this->mobile, $this->address, $status);


            // Execute the query
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute query: " . $stmt->error);
            }


            // Capture inserted ID for a new user
            if (!$this->id) {
                $this->id = $stmt->insert_id;
            }
            if ($this->role == "lender") {
                $query = "INSERT INTO `consoledatedfund`(`user_id`, `Amount`, `Earning`) values (?, ?, ?)";
                $stmt = $db->prepare($query);
                $earning = 0;
                $contriamount=0;
                // Bind parameters (all strings including image)
                $stmt->bind_param("iii", $this->id, $contriamount, $earning);


                // Execute the query
                if (!$stmt->execute()) {
                    throw new Exception("Failed to execute query: " . $stmt->error);
                }

            }


            $stmt->close();

            // Return success message
            return "User saved successfully.";

        } catch (Exception $e) {
            // Log the error (can be saved to a file or a logging system)
            error_log("Error saving user: " . $e->getMessage());

            // Return error message
            return "Error: " . $e->getMessage();
        }
    }
    public function update()
    {
        try {
            // Ensure the database connection is available
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }

            // Validate required fields
            if (empty($this->id) || empty($this->name) || empty($this->email) || empty($this->password)) {
                throw new Exception("Name, email, and password are required.");
            }

            // Check if it's an insert or update operation

            $query = "UPDATE users SET name = ?, email = ?, password = ?, role = ?, mobile = ?, address = ?, image = ? WHERE id = ?";
            $stmt = $db->prepare($query);

            // Bind parameters (strings for name, email, password, etc., integer for id)
            $stmt->bind_param("sssssssi", $this->name, $this->email, $this->password, $this->role, $this->mobile, $this->address, $this->image, $this->id);


            // Execute the query
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute query: " . $stmt->error);
            }

            $stmt->close();

            // Return success message
            return "User Updated successfully.";

        } catch (Exception $e) {
            // Log the error (can be saved to a file or a logging system)
            error_log("Error saving user: " . $e->getMessage());

            // Return error message
            return "Error: " . $e->getMessage();
        }
    }

    public static function updateUser($userId, $data)
{
    try {
        $db = Database::getConnection();
        if ($db === null) {
            throw new Exception("Database connection failed");
        }

        // Get current timestamp for updated_at
        date_default_timezone_set('Indian/Mauritius');
        $currentTime = date('Y-m-d H:i:s');

        // Create base query that includes updated_at
        $query = "UPDATE users SET name = ?, email = ?, mobile = ?, address = ?, updated_at = ? WHERE id = ?";
        $stmt = $db->prepare($query);

        // Bind parameters including the timestamp and user ID
        $stmt->bind_param("sssssi", 
            $data['name'], 
            $data['email'], 
            $data['mobile'], 
            $data['address'],
            $currentTime,
            $userId
        );

        // Execute the query
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute query: " . $stmt->error);
        }

        $affected = $stmt->affected_rows;
        $stmt->close();

        return $affected > 0;

    } catch (Exception $e) {
        error_log("Error updating user: " . $e->getMessage());
        throw $e;
    }
}

    public static function find($id)
    {
        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }

            // Include 'status' field in SELECT query
            $query = "SELECT * FROM users WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();

            return $data ? new self($data['id'], $data['name'], $data['email'], $data['password'], $data['role'], $data['mobile'], $data['address'], $data['image'], $data['status']) : null;
        } catch (Exception $e) {
            error_log("Error finding user: " . $e->getMessage());
            echo "Error: " . $e->getMessage();
        }
    }

    public static function all()
    {
        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }

            $query = "SELECT * FROM `users` WHERE status IS NULL";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $result = $stmt->get_result();

            $users = [];
            while ($data = $result->fetch_assoc()) {
                $users[] = $data;
            }

            $stmt->close();
            return $users;

        } catch (Exception $e) {
            error_log("Error fetching users: " . $e->getMessage());
            return [];
        }
    }
    public static function UpdateProfile($name, $email, $mobile, $address, $target_file = null)
    {
        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }

            // Create base query
            $query = "UPDATE users SET name = ?, email = ?, mobile = ?, address = ?";

            // Append image update if a new image is uploaded
            if ($target_file !== null) {
                $query .= ", image = ?";
            }

            // Complete the query with WHERE clause
            $query .= " WHERE id = ?";

            // Prepare the statement
            $stmt = $db->prepare($query);

            // Bind parameters in the correct order
            $params = [$name, $email, $mobile, $address];

            // Add image to parameters if it exists
            if ($target_file !== null) {
                $params[] = $target_file;
            }

            // Add user ID to parameters
            $params[] = $_SESSION['user_id'];

            // Execute the query with all parameters
            if ($stmt->execute($params)) {
                return true; // Success
            } else {
                throw new Exception("Error updating profile.");
            }
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public static function getDocument($user_id)
    {
        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }
            $query = "SELECT * FROM `documents` WHERE user_id =?";
            $stmt = $db->prepare($query);
            $veri = "verified";
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $documents = [];
            while ($data = $result->fetch_assoc()) {
                $documents[] = $data;
            }
            $stmt->close();
            return $documents;

        } catch (Exception $e) {
            error_log("Error fetching users: " . $e->getMessage());
            return [];
        }
    }

    public static function allByRole($role)
    {
        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }

            $query = "SELECT * FROM `users` u 
            WHERE u.role =? and u.status =?";
            $stmt = $db->prepare($query);
            $status = "active";
            $stmt->bind_param("ss", $role, $status);
            $stmt->execute();
            $result = $stmt->get_result();

            $users = [];
            while ($data = $result->fetch_assoc()) {
                $users[] = $data;
            }

            $stmt->close();
            return $users;

        } catch (Exception $e) {
            error_log("Error fetching users: " . $e->getMessage());
            return [];
        }
    }
    public static function allByStatus($status)
    {
        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }
            $query = "SELECT * FROM `users` WHERE status =? and user_verfied =?";
            $stmt = $db->prepare($query);
            $veri = "verified";
            $stmt->bind_param("ss", $status, $veri);
            $stmt->execute();
            $result = $stmt->get_result();

            $users = [];
            while ($data = $result->fetch_assoc()) {
                $users[] = $data;
            }

            $stmt->close();
            return $users;

        } catch (Exception $e) {
            error_log("Error fetching users: " . $e->getMessage());
            return [];
        }
    }
    public static function findByEmail($email)
    {
        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }
            // Include 'status' field in SELECT query
            $query = "SELECT * FROM users WHERE email = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();

            return $data ? new self($data['id'], $data['name'], $data['email'], $data['password'], $data['role'], $data['mobile'], $data['address'], $data['image'], $data['status']) : null;
        } catch (Exception $e) {
            error_log("Error finding user by email: " . $e->getMessage());
            echo "Error: " . $e->getMessage();
        }
    }

    public static function findByEmail2($email)
    {
        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }
            // Include 'status' field in SELECT query
            $query = "SELECT * FROM users WHERE email = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();

            return $data;
        } catch (Exception $e) {
            error_log("Error finding user by email: " . $e->getMessage());
            echo "Error: " . $e->getMessage();
        }
    }


    public function delete()
    {
        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }

            $query = "DELETE FROM users WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $this->id);
            $stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            error_log("Error deleting user: " . $e->getMessage());
            echo "Error: " . $e->getMessage();
        }
    }
    public static function activeUser($id)
    {
        require_once '../Classes/Mail.php';
        $db = Database::getConnection();
        if ($db === null) {
            throw new Exception("Database connection failed");
        }
        $selectQuery = "SELECT email FROM users WHERE id=?";
        $selectStmt = $db->prepare($selectQuery);
        if ($selectStmt === false) {
            throw new Exception("Failed to prepare SELECT statement: " . $db->error);
        }

        // Bind the id parameter
        $selectStmt->bind_param("i", $id);

        // Execute the query
        if (!$selectStmt->execute()) {
            throw new Exception("Failed to execute SELECT statement: " . $selectStmt->error);
        }

        // Fetch the result
        $result = $selectStmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception("No user found with id: " . $id);
        }

        $row = $result->fetch_assoc();
        $email = $row['email'];
        // var_dump($email);
        // exit;
        $updateQuery = "UPDATE users SET `status`=? WHERE id=?";

        // Prepare the statement for updating the status
        $updateStmt = $db->prepare($updateQuery);
        if ($updateStmt === false) {
            throw new Exception("Failed to prepare statement: " . $db->error);
        }

        // Set the new status to 'Paid'
        $newStatus = "active";

        // Bind parameters for the update: status, loanInstallmentsId
        $updateStmt->bind_param("si", $newStatus, $id);

        // Execute the statement
        if (!$updateStmt->execute()) {
            throw new Exception("Failed to execute statement: " . $updateStmt->error);
        }

        // Close the statement after executing
        $updateStmt->close();
        $selectStmt->close();
        $subject = "Account Activation";

        $message = "
            Dear user,
        
            Congratulations! Your account has been successfully activated. You can now log in to your account and enjoy all the features and benefits we offer.
        
            If you have any questions or need assistance, feel free to reach out to our support team at sbw.hosenbocus@gmail.com.
        
            Thank you for choosing [SafeFund]!
        
            Best regards,
            [SafeFund] Team
        ";


        // var_dump($check) ;
        // exit;
        $mail = Mail::sendMail($subject, $message, $email);
    }
    public static function changeUserStatus($id, $status)
    {
        require_once __DIR__ . '/Mail.php';

        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }

            // First get user's email and name
            $selectQuery = "SELECT email, name, role FROM users WHERE id = ?";
            $selectStmt = $db->prepare($selectQuery);
            $selectStmt->bind_param("i", $id);
            $selectStmt->execute();
            $result = $selectStmt->get_result();
            $userData = $result->fetch_assoc();
            $selectStmt->close();

            $updateQuery = "UPDATE users SET `status`=? WHERE id=?";
            $updateStmt = $db->prepare($updateQuery);
            if ($updateStmt === false) {
                throw new Exception("Failed to prepare statement: " . $db->error);
            }

            // Bind parameters: "s" for string (status) and "i" for integer (id)
            $updateStmt->bind_param("si", $status, $id);

            if (!$updateStmt->execute()) {
                throw new Exception("Failed to execute statement: " . $updateStmt->error);
            }

            $affectedRows = $updateStmt->affected_rows;
            $updateStmt->close();

            // Send email notification based on status change
            if ($affectedRows > 0 && $userData) {
                $subject = '';
                $message = '';

                switch($status) {
                    case 'suspend':
                        $subject = "Account Suspension Notice - SafeFund";
                        $message = "Dear " . $userData['name'] . ",\n\n"
                            . "We regret to inform you that your SafeFund account has been suspended due to suspicious activity or pending payment verification.\n\n"
                            . "If you believe this is an error or would like to appeal this decision, please contact our support team at sbw.hosenbocus@gmail.com.\n\n"
                            . "Best regards,\nSafeFund Team";
                        break;
                    case 'inactive':
                        $subject = "Account Status Update - SafeFund";
                        $message = "Dear " . $userData['name'] . ",\n\n"
                            . "Your SafeFund account status has been set to inactive. This may affect your ability to use certain features of our platform.\n\n"
                            . "If you need to reactivate your account or have any questions, please contact our support team.\n\n"
                            . "Best regards,\nSafeFund Team";
                        break;
                    case 'blocked':
                        $subject = "Account Blocked - SafeFund";
                        $message = "Dear " . $userData['name'] . ",\n\n"
                            . "We regret to inform you that your SafeFund " . $userData['role'] . " account has been blocked. "
                            . "This action has been taken due to violation of our terms of service or suspicious activities detected.\n\n"
                            . "If you believe this is an error or would like to appeal this decision, please contact our support team immediately at sbw.hosenbocus@gmail.com.\n\n"
                            . "For the security of your account and our platform, all account activities have been suspended until this matter is resolved.\n\n"
                            . "Best regards,\nSafeFund Team";
                        break;
                    case 'active':
                        $subject = "Account Activated - SafeFund";
                        $message = "Dear " . $userData['name'] . ",\n\n"
                            . "Good news! Your SafeFund " . $userData['role'] . " account has been activated. "
                            . "You now have full access to all platform features.\n\n"
                            . "If you have any questions about using our platform, please don't hesitate to contact our support team at sbw.hosenbocus@gmail.com.\n\n"
                            . "Thank you for being part of SafeFund!\n\n"
                            . "Best regards,\nSafeFund Team";
                        break;
                }

                if ($subject && $message) {
                    Mail::sendMail($subject, $message, $userData['email']);
                }
            }

            // Log the update result for debugging
            error_log("changeUserStatus: id={$id}, status={$status}, affectedRows={$affectedRows}");

            return ($affectedRows > 0);

        } catch (Exception $e) {
            error_log("Error in changeUserStatus: " . $e->getMessage());
            throw $e;
        }
    }
    public static function forgetPassword($email)
    {

        require_once 'Mail.php';
        $db = Database::getConnection();
        if ($db === null) {
            throw new Exception("Database connection failed");
        }
        try {

            $query = "SELECT * FROM users WHERE email = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();
            $token = bin2hex(random_bytes(50));

            date_default_timezone_set('Indian/Mauritius');
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            if ($data) {

                $stmt = $db->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?");
                $stmt->bind_param("sss", $token, $expiry, $email);
                $check = $stmt->execute();
                $reset_link = "http://localhost/resetPassword.php?token=" . $token;
                $subject = "Password Reset Request";
                $message = "Click on the following link to reset your password: $reset_link";
                $headers = "From: no-reply@yourwebsite.com";
                // var_dump($check) ;
                // exit;
                $mail = Mail::sendMail($subject, $message, $data['email']);
                // var_dump($mail);
                // exit;
                if ($mail = 1) {
                    header("Location: forgetPassword.php?e=Reset Link has been Sent");
                    exit;
                } else {
                    header("Location: forgetPassword.php?error=Something went wrong");
                    exit;
                }
            } else {
                var_dump("e");
                exit;
            }
        } catch (Exception $e) {
            // var_dump($e->getMessage()) ;
            return $e->getMessage();
        }


    }

    public static function changeStatus($email)
    {
        $db = Database::getConnection();
        if ($db === null) {
            throw new Exception("Database connection failed");
        }
        $updateQuery = "UPDATE users SET `user_verfied`=? WHERE email=?";

        // Prepare the statement for updating the status
        $updateStmt = $db->prepare($updateQuery);
        if ($updateStmt === false) {
            throw new Exception("Failed to prepare statement: " . $db->error);
        }

        // Set the new status to 'Paid'
        $newStatus = "verified";

        // Bind parameters for the update: status, loanInstallmentsId
        $updateStmt->bind_param("ss", $newStatus, $email);

        // Execute the statement
        if (!$updateStmt->execute()) {
            throw new Exception("Failed to execute statement: " . $updateStmt->error);
        }

        // Close the statement after executing
        $updateStmt->close();
        try {
            Mail::ActiveStatusMail($email);
        } catch (Exception $e) {
            // Log error or handle as needed
            var_dump("error");
            exit;
        }
    }
    public static function checkToken($token)
    {
        $db = Database::getConnection();
        if ($db === null) {
            throw new Exception("Database connection failed");
        }
        $stmt = $db->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return $result->num_rows;
        } else {
            return -1;
        }
    }
    public static function changePassword($new, $confirm, $token)
    {
        $db = Database::getConnection();
        if ($db === null) {
            throw new Exception("Database connection failed");
        }
        if ($new !== $confirm) {
            return "Passwords do not match!";
        } else if (strlen($new) < 8) {
            return "Password must be at least 8 characters long!";

        }
        $hashedPassword = password_hash($new, PASSWORD_DEFAULT);
        $updateQuery = "UPDATE users SET `password`=? WHERE reset_token=?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bind_param("ss", $hashedPassword, $token);
        if (!$updateStmt->execute()) {
            return "Passowrd not updated";
        } else {
            return 1;
        }

    }
}
?>