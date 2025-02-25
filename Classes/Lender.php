<?php
    class Lender extends User {
        public $documents = [];
        public $investments = [];
        public function save() {
    
            // Save associated documents
            $db = Database::getConnection();
            foreach ($this->documents as $type => $filePaths) {
                foreach ($filePaths as $filePath) {
                    $query = "INSERT INTO documents (user_id, type, path) VALUES (?, ?, ?)";
                    $stmt = $db->prepare($query);
                    $stmt->bind_param("iss", $this->id, $type, $filePath);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
        public function reviewApplication($loanId) {
            $loan = LoanRequest::find($loanId);
            return $loan;
        }

        public function allocateFunds($loanId, $amount) {
            $loan = LoanRequest::find($loanId);
            if ($loan) {
                $loan->allocateFunds($amount, $this->id);
            }
        }
    }
