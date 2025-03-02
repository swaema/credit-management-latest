<?php
class TermandInterst
{
    public $id;
    public $term;
    public $interst;


    public function __construct($id = null, $term = null, $interst = null)
    {
        $this->id = $id;
        $this->term = $term;
        $this->interst = $interst;
    }

    public function save()
    {
        try {
            // Ensure the database connection is available
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }
            // var_dump($this->term);
            // exit;

            // Validate required fields
            if (empty($this->term) || empty($this->interst)) {
                throw new Exception("Fill All the fields");
                // var_dump($this->term);
                // exit;
            }
           


            // Check if it's an insert or update operation

            // Prepare the insert query when we don't have an existing user (new user)
            $query = "INSERT INTO termandinterst (term, interstRate) VALUES (?, ?)";
    
            // Prepare the statement
            $stmt = $db->prepare($query);
        
            // Bind parameters (term is string, interstRate is float)
            $stmt->bind_param("sd", $this->term, $this->interst);
        
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute query: " . $stmt->error);
            }

            // Capture inserted ID for a new user
            if (!$this->id) {
                $this->id = $stmt->insert_id;
            }
            $stmt->close();

            // Return success message
            return "Term saved successfully.";

        } catch (Exception $e) {
            
            // Log the error (can be saved to a file or a logging system)
            error_log("Error saving user: " . $e->getMessage());

            // Return error message
            return "Error: " . $e->getMessage();
        }
    }
    // public function update() {
    //     try {
    //         // Ensure the database connection is available
    //         $db = Database::getConnection();
    //         if ($db === null) {
    //             throw new Exception("Database connection failed");
    //         }

    //         // Validate required fields
    //         if (empty($this->id)||empty($this->name) || empty($this->email) || empty($this->password)) {
    //             throw new Exception("Name, email, and password are required.");
    //         }

    //         // Check if it's an insert or update operation

    //         $query = "UPDATE users SET name = ?, email = ?, password = ?, role = ?, mobile = ?, address = ?, image = ? WHERE id = ?";
    //         $stmt = $db->prepare($query);

    //         // Bind parameters (strings for name, email, password, etc., integer for id)
    //         $stmt->bind_param("sssssssi", $this->name, $this->email, $this->password, $this->role, $this->mobile, $this->address, $this->image, $this->id);


    //         // Execute the query
    //         if (!$stmt->execute()) {
    //             throw new Exception("Failed to execute query: " . $stmt->error);
    //         }

    //         $stmt->close();

    //         // Return success message
    //         return "User Updated successfully.";

    //     } catch (Exception $e) {
    //         // Log the error (can be saved to a file or a logging system)
    //         error_log("Error saving user: " . $e->getMessage());

    //         // Return error message
    //         return "Error: " . $e->getMessage();
    //     }
    // }

    public static function find($id)
    {
        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }

            // Include 'status' field in SELECT query
            $query = "SELECT * FROM ermandinterst WHERE termId = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();

            return $data ? new self($data['id'], $data['term'], $data['interstRate']):null;
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

            $query = "SELECT * FROM `termandinterst`";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $result = $stmt->get_result();

            $users = [];
            while ($data = $result->fetch_assoc()) {
                $users[] = new self(
                    $data['termId'],
                    $data['term'],
                    $data['interstRate'],
                );
            }

            $stmt->close();
            return $users;

        } catch (Exception $e) {
            error_log("Error fetching users: " . $e->getMessage());
            return [];
        }
    }



    public function delete()
    {
        try {
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }

            $query = "DELETE FROM termandinterst WHERE termId = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $this->id);
            $stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            error_log("Error deleting user: " . $e->getMessage());
            echo "Error: " . $e->getMessage();
        }
    }
}
?>