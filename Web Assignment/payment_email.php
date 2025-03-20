<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// require_once PHPMailer library
require_once 'vendor/autoload.php'; // Ensure PHPMailer is installed via Composer

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitise user inputs
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $address = htmlspecialchars($_POST['address']);
    $city = htmlspecialchars($_POST['city']);
    $state = htmlspecialchars($_POST['State']);
    $zip = htmlspecialchars($_POST['zip']);
    $cardName = htmlspecialchars($_POST['cardName']);
    $cardNum = htmlspecialchars($_POST['cardNum']);
    $expMonth = htmlspecialchars($_POST['expMonth']);
    $expYear = htmlspecialchars($_POST['expYear']);
    $cvv = htmlspecialchars($_POST['cvv']);

    // Email message content
    $message = "Billing Details:\n";
    $message .= "Name: $name\n";
    $message .= "Email: $email\n";
    $message .= "Address: $address\n";
    $message .= "City: $city\n";
    $message .= "State: $state\n";
    $message .= "Zip Code: $zip\n\n";

    $message .= "Payment Details:\n";
    $message .= "Card Name: $cardName\n";
    $message .= "Card Number: $cardNum\n";
    $message .= "Exp Month: $expMonth\n";
    $message .= "Exp Year: $expYear\n";
    $message .= "CVV: $cvv\n";

    // PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.office365.com'; // SMTP server Gmail: smtp.gmail.com
        $mail->SMTPAuth = true;
        //I'm not willing to try it this time
        $mail->Username = 's2451059@student.hkct.edu.hk'; // Replace with your email
        $mail->Password = 'your_app_password'; // Replace with your app password (not password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Sender and recipient
        $mail->setFrom('s2451059@student.hkct.edu.hk', 'Baldwin');
        $mail->addAddress($email, $name); // Replace with recipient email

        // Email content
        $mail->Subject = 'Payment Form Submission';
        $mail->Body = $message;

        // Send email
        $mail->send();
        echo "Thank you! Your payment details have been sent.";
    } catch (Exception $e) {
        // Handle errors
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
} else {
    // Invalid request method
    echo "Invalid request.";
}
?>
