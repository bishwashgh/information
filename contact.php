<?php
// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $message = $_POST['message'] ?? '';

    // Validate input
    if (empty($name) || empty($email) || empty($message)) {
        die('Please fill in all required fields.');
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die('Please enter a valid email address.');
    }

    // Load configuration
    $configFile = __DIR__ . '/config.local.php';
    if (!file_exists($configFile)) {
        die('Configuration file not found. Please create config.local.php from config.sample.php');
    }
    $config = require $configFile;

    // --- PHPMailer Setup ---
    require __DIR__ . '/PHPMailer/PHPMailer-6.8.1/src/Exception.php';
    require __DIR__ . '/PHPMailer/PHPMailer-6.8.1/src/PHPMailer.php';
    require __DIR__ . '/PHPMailer/PHPMailer-6.8.1/src/SMTP.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    $mail = new PHPMailer(true);

    try {
        // Load settings from configuration
        $apiKey = $config['brevo_api_key'];
        $senderEmail = $config['sender_email'];
        $senderName = $config['sender_name'];
        $recipientEmail = $config['recipient_email'];

        // Prepare HTML email
        $htmlContent = "
        <h2>New Message from Portfolio Contact Form</h2>
        <p><strong>Name:</strong> ".htmlspecialchars($name)."</p>
        <p><strong>Email:</strong> ".htmlspecialchars($email)."</p>
        <p><strong>Message:</strong></p>
        <div style='background:#f5f5f5;padding:15px;border-left:4px solid #26A69A;'>".nl2br(htmlspecialchars($message))."</div>
        <p>IP: ".$_SERVER['REMOTE_ADDR']." • ".date('Y-m-d H:i:s')."</p>
        ";

        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp-relay.brevo.com';
        $mail->SMTPAuth = true;
        $mail->Username = $senderEmail;
        $mail->Password = $apiKey;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom($senderEmail, $senderName);
        $mail->addAddress($recipientEmail);
        $mail->addReplyTo($email, $name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'New Contact Form Submission from ' . $name;
        $mail->Body = $htmlContent;
        $mail->AltBody = strip_tags($htmlContent);

        $mail->send();
        echo 'Message has been sent';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
} else {
    // If not a POST request, redirect to home page or show an error
    header('Location: index.html');
    exit();
}
