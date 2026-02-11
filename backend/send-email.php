<?php 
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize Inputs
    $name = strip_tags(trim($_POST["name"]));
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $message = strip_tags(trim($_POST["message"]));

    //SetUp Recipients
    $recepient = "isaackmuchoki55@gmail.com";
    $subject = "New Portfolio Message from $name";

    // Build Email Content

    $email_content = "Name: $name\n";
    $email_content .= "Email: $email\n\n";
    $email_content .= "Message:\n$message\n";

    // Build Headers
    $headers =  "From: $name <$email>";

    // Send Email
    if (mail($recepient, $subject, $email_content, $headers)) {
        http_response_code(200);
        echo "Thank you! Your message has been sent.";
    } else {
        http_response_code(500);
        echo "Oops! Something went wrong.";
    }
}  else {
        http_response_code(403);
        echo "There was a problem with your submission";
    }
?>