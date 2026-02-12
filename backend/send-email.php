<?php 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// Autoload Composer dependencies
require_once __DIR__ . '/../vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize Inputs
    $name = strip_tags(trim($_POST["name"]));
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $message = strip_tags(trim($_POST["message"]));

    $mail = new PHPMailer(true);

    try {
        // Server settings
       $mail->isSMTP();
       $mail->Host = 'smtp.gmail.com';
       $mail->SMTPAuth = true;
       $mail->Username = getenv('SMTP_USER');
       $mail->Password = getenv('SMTP_PASS');
       $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
       $mail->Port = 587;

    //    Recipients
    $mail->setFrom(getenv('SMTP_USER'), 'Portfolio Contact Form');
    $mail->addAddress(getenv('SMTP_USER')); // Deliver to yourself
    $mail->addReplyTo($email, $name);

    // Content
    $mail->isHTML(true);
    $mail->Subject = "New Portfolio Message from $name";
    $mail->Body    = "<strong>Name:</strong> $name<br>
                      <strong>Email:</strong> $email<br><br>
                      <strong>Message:</strong><br>$message";
    $mail->SMTPDebug = 3;
    $mail->send();
    http_response_code(200);
    echo "Thank you! Your message has been sent.";
        
    } catch (Exception $e) {
        http_response_code(500);
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }

}  else {
        http_response_code(403);
        echo "There was a problem with your submission";
    }
?>
