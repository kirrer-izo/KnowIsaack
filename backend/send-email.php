<?php 
use Resend;
// Autoload Composer dependencies
require_once __DIR__ . '/../vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize Inputs
    $name = strip_tags(trim($_POST["name"]));
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $message = strip_tags(trim($_POST["message"]));

    // Initialize Resend with API key from environment variable
    $resend = Resend::client(getenv('RESEND_API_KEY'));

    try {
        $resend->emails->send([
        'from' => 'Portfolio <onboarding@resend.dev> ',
        'to' => 'isaackmuchoki55@gmail.com',
        'subject' => 'New Portfolio Message from '. $name,
        'reply_to' => $email,
        'html' => '<strong>Name: </strong>' . $name . '<br>' . 
                      '<strong>Email: </strong>' . $email . '<br><br>' . 
                      '<strong>Message:</strong><br>' . $message,
        ]);

        http_response_code(200);
        echo "Thank you for your message! I'll get back to you soon.";

    } catch (Exception $e) {
        http_response_code(500);
        echo "There was an error sending your message: " . $e->getMessage();
    }



}  else {
        http_response_code(403);
        echo "There was a problem with your submission";
    }
?>