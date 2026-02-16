<?php
/**
 * Email Utility
 * Handles sending email notifications
 */

// Prevent direct access
if (!defined('SECURE_ACCESS')) {
    die('Direct access not permitted');
}

/**
 * Send email notification
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email message (HTML)
 * @param string $from_email Optional from email
 * @param string $from_name Optional from name
 * @return bool True if sent, false otherwise
 */
function send_email($to, $subject, $message, $from_email = MAIL_FROM, $from_name = MAIL_FROM_NAME) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: " . $from_name . " <" . $from_email . ">" . "\r\n";
    $headers .= "Reply-To: " . $from_email . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // In production, use a proper email service (SMTP, SendGrid, etc.)
    // For now, we'll use PHP mail() function
    // Note: mail() requires a mail server to be configured
    
    // Log the email instead of actually sending it (for development)
    error_log("Email would be sent to: $to\nSubject: $subject\nMessage: $message");
    
    // Uncomment the following line in production with proper mail server
    // return mail($to, $subject, $message, $headers);
    
    // Return true for development purposes
    return true;
}

/**
 * Send asset request notification to admin and HR
 * @param mysqli $conn Database connection
 * @param int $request_id Request ID
 * @param string $employee_name Employee name
 * @param string $employee_email Employee email
 */
function send_request_notification($conn, $request_id, $employee_name, $employee_email) {
    // Get admin and HR emails
    $query = "SELECT email FROM users WHERE role IN ('admin', 'hr') AND status = 'active'";
    $result = db_query($conn, $query);
    
    if ($result) {
        $subject = "New Asset Request Submitted - Request #$request_id";
        $message = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>New Asset Request</h2>
            <p>A new asset request has been submitted by an employee.</p>
            <table style='border-collapse: collapse; width: 100%; max-width: 600px;'>
                <tr>
                    <td style='padding: 10px; border: 1px solid #ddd;'><strong>Request ID:</strong></td>
                    <td style='padding: 10px; border: 1px solid #ddd;'>#$request_id</td>
                </tr>
                <tr>
                    <td style='padding: 10px; border: 1px solid #ddd;'><strong>Employee Name:</strong></td>
                    <td style='padding: 10px; border: 1px solid #ddd;'>$employee_name</td>
                </tr>
                <tr>
                    <td style='padding: 10px; border: 1px solid #ddd;'><strong>Employee Email:</strong></td>
                    <td style='padding: 10px; border: 1px solid #ddd;'>$employee_email</td>
                </tr>
                <tr>
                    <td style='padding: 10px; border: 1px solid #ddd;'><strong>Status:</strong></td>
                    <td style='padding: 10px; border: 1px solid #ddd;'>Pending Review</td>
                </tr>
            </table>
            <p style='margin-top: 20px;'>Please log in to the Asset Management Portal to review and process this request.</p>
            <p style='color: #666; font-size: 12px; margin-top: 30px;'>This is an automated notification from the Asset Management System.</p>
        </body>
        </html>
        ";
        
        while ($row = mysqli_fetch_assoc($result)) {
            send_email($row['email'], $subject, $message);
        }
    }
}

/**
 * Send asset assignment notification to employee
 * @param string $employee_email Employee email
 * @param string $employee_name Employee name
 * @param string $asset_name Asset name
 * @param string $serial_number Serial number
 */
function send_assignment_notification($employee_email, $employee_name, $asset_name, $serial_number) {
    $subject = "Asset Assigned to You";
    $message = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <h2>Asset Assignment Notification</h2>
        <p>Dear $employee_name,</p>
        <p>An asset has been assigned to you. Please find the details below:</p>
        <table style='border-collapse: collapse; width: 100%; max-width: 600px;'>
            <tr>
                <td style='padding: 10px; border: 1px solid #ddd;'><strong>Asset Name:</strong></td>
                <td style='padding: 10px; border: 1px solid #ddd;'>$asset_name</td>
            </tr>
            <tr>
                <td style='padding: 10px; border: 1px solid #ddd;'><strong>Serial Number:</strong></td>
                <td style='padding: 10px; border: 1px solid #ddd;'>$serial_number</td>
            </tr>
            <tr>
                <td style='padding: 10px; border: 1px solid #ddd;'><strong>Assigned Date:</strong></td>
                <td style='padding: 10px; border: 1px solid #ddd;'>" . date('Y-m-d') . "</td>
            </tr>
        </table>
        <p style='margin-top: 20px;'>Please take care of the assigned asset and report any issues immediately.</p>
        <p style='color: #666; font-size: 12px; margin-top: 30px;'>This is an automated notification from the Asset Management System.</p>
    </body>
    </html>
    ";
    
    send_email($employee_email, $subject, $message);
}

/**
 * Send request status notification to employee
 * @param string $employee_email Employee email
 * @param string $employee_name Employee name
 * @param int $request_id Request ID
 * @param string $status Status (approved/rejected)
 */
function send_request_status_notification($employee_email, $employee_name, $request_id, $status) {
    $status_text = ucfirst($status);
    $subject = "Asset Request $status_text - Request #$request_id";
    
    $message = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <h2>Asset Request $status_text</h2>
        <p>Dear $employee_name,</p>
        <p>Your asset request (#$request_id) has been $status.</p>
        ";
    
    if ($status == 'approved') {
        $message .= "<p style='color: green;'>Your request has been approved. The assets will be assigned to you soon.</p>";
    } else {
        $message .= "<p style='color: red;'>Your request has been rejected. Please contact HR or IT for more information.</p>";
    }
    
    $message .= "
        <p style='color: #666; font-size: 12px; margin-top: 30px;'>This is an automated notification from the Asset Management System.</p>
    </body>
    </html>
    ";
    
    send_email($employee_email, $subject, $message);
}
?>
