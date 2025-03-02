<?php

class Notification {
    private $userId;
    private $content;
    private $type; // 'email', 'sms'

    public function __construct($userId, $content, $type) {
        $this->userId = $userId;
        $this->content = $content;
        $this->type = $type;
    }

    

    private function sendEmail(): bool {
        $to = User::find($this->userId)->email;
        $subject = "Notification from Credit Management System";
        $headers = "From: noreply@creditmanagement.com";

        if (mail($to, $subject, $this->content, $headers)) {
            return true;
        } else {
            throw new Exception("Failed to send email notification.");
        }
    }

    

   
}
