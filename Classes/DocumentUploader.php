<?php

class DocumentUploader {
    public static function upload($tmpName, $destinationDir, $fileName) {
        // Ensure the directory exists
        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0777, true);
        }

        // Final upload path
        $uploadPath = $destinationDir . '/' . $fileName;

        // Validate file type (allow only images and PDFs)
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        $fileType = mime_content_type($tmpName);

        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception("Invalid file type. Allowed types are: JPEG, PNG, and PDF.");
        }

        // Validate file size (max 5 MB)
        if (filesize($tmpName) > 5 * 1024 * 1024) {
            throw new Exception("File size exceeds the maximum limit of 5 MB.");
        }

        // Move the uploaded file
        if (!move_uploaded_file($tmpName, $uploadPath)) {
            throw new Exception("Failed to upload file.");
        }

        return $uploadPath;
    }
}
?>
