<?php

namespace App\Controllers;

use App\Domain\Interfaces\EmailServiceInterface;

class ContactController {
    private $mailer;

    public function __construct(EmailServiceInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function handleRequest()
    {
        header('Content-Type: application/json');
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Sanitize Inputs
        $name = strip_tags(trim($_POST["name"]));
        $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
        $message = strip_tags(trim($_POST["message"]));  
        
        $isSent = $this->mailer->sendEmail(
                'isaackmuchoki55@gmail.com',
                'New Portfolio Message from ' . $name,
                '<strong>Name: </strong>' . $name . '<br>' . 
                          '<strong>Email: </strong>' . $email . '<br><br>' . 
                          '<strong>Message:</strong><br>' . $message,
                $email
             );

        if ($isSent) {
            http_response_code(200);
            echo json_encode(['message' => 'Thank you for your message! I\'ll get back to you soon.']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'There was a problem with your submission']);
        }
    }   else {
        http_response_code(405);
        echo json_encode(['message' => '405 Method Not Allowed']);
    }
}
}
