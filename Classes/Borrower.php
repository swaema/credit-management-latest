<?php
    class Borrower extends User {
        public $documents = [];
        public $loanRequests = [];

        public function uploadDocument($document) {
            $filePath = DocumentUploader::upload($document, "uploads/documents/{$this->id}");
            $this->documents[] = $filePath;
        }
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
        public function viewLoanStatus($loanId) {
            return LoanRequest::find($loanId)->status;
        }
        public function deleteDocumentsByType($docType) {
            $db = Database::getConnection();
            $query = "DELETE FROM documents WHERE user_id = ? AND type = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("is", $this->id, $docType);
            $stmt->execute();
            $stmt->close();
        }
    }
