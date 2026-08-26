<?php
require_once __DIR__ . '/../config/mail.php';

/**
 * Sends an email using pure PHP SMTP sockets (works seamlessly with Gmail SMTP).
 *
 * @param string $toEmail Recipient email address
 * @param string $toName Recipient display name
 * @param string $subject Email subject line
 * @param string $htmlBody HTML content
 * @param string $plainTextBody Optional plain text fallback
 * @return array ['success' => bool, 'message' => string]
 */
function send_smtp_email(string $toEmail, string $toName, string $subject, string $htmlBody, string $plainTextBody = ''): array {
    $host = SMTP_HOST;
    $port = SMTP_PORT;
    $user = SMTP_USER;
    $pass = str_replace(' ', '', SMTP_PASS); // Strip spaces from Gmail app passwords
    $secure = strtolower(SMTP_SECURE);
    $fromEmail = SMTP_FROM_EMAIL;
    $fromName = SMTP_FROM_NAME;

    // Graceful exit if SMTP password or user is not yet set
    if (empty($pass) || empty($user) || $user === 'piatmove@gmail.com' && empty($pass)) {
        error_log("[SMTP Mailer] Email to {$toEmail} skipped: SMTP_PASS is not configured yet.");
        return ['success' => false, 'message' => 'SMTP credentials not configured'];
    }

    $socketHost = ($secure === 'ssl') ? "ssl://{$host}" : "tcp://{$host}";
    $timeout = 15;
    $errno = 0;
    $errstr = '';

    $socket = @stream_socket_client("{$socketHost}:{$port}", $errno, $errstr, $timeout);
    if (!$socket) {
        error_log("[SMTP Mailer] Could not connect to {$host}:{$port} - {$errstr} ({$errno})");
        return ['success' => false, 'message' => "Could not connect to mail server: {$errstr}"];
    }

    stream_set_timeout($socket, $timeout);

    $readResponse = function() use ($socket) {
        $data = '';
        while ($str = fgets($socket, 515)) {
            $data .= $str;
            if (substr($str, 3, 1) === ' ') {
                break;
            }
        }
        return $data;
    };

    $sendCommand = function(string $cmd, array $expectedCodes) use ($socket, $readResponse) {
        fputs($socket, $cmd . "\r\n");
        $res = $readResponse();
        $code = (int)substr($res, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new Exception("SMTP command failed: [{$cmd}] -> {$res}");
        }
        return $res;
    };

    try {
        $greeting = $readResponse();
        if ((int)substr($greeting, 0, 3) !== 220) {
            throw new Exception("Bad server greeting: {$greeting}");
        }

        $sendCommand("EHLO " . gethostname(), [250]);

        if ($secure === 'tls' || $port === 587) {
            $sendCommand("STARTTLS", [220]);
            $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            }
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
            }

            if (!stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                throw new Exception("Failed to start TLS encryption");
            }
            $sendCommand("EHLO " . gethostname(), [250]);
        }

        // Authenticate with Gmail
        $sendCommand("AUTH LOGIN", [334]);
        $sendCommand(base64_encode($user), [334]);
        $sendCommand(base64_encode($pass), [235]);

        // Envelope
        $sendCommand("MAIL FROM: <{$fromEmail}>", [250]);
        $sendCommand("RCPT TO: <{$toEmail}>", [250, 251]);
        $sendCommand("DATA", [354]);

        $boundary = '=_piatmove_' . md5(uniqid((string)microtime(true), true));
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
        $encodedToName = !empty($toName) ? '=?UTF-8?B?' . base64_encode($toName) . '?=' : '';

        $toHeader = $encodedToName ? "{$encodedToName} <{$toEmail}>" : "<{$toEmail}>";

        $headers = [];
        $headers[] = "From: {$encodedFromName} <{$fromEmail}>";
        $headers[] = "To: {$toHeader}";
        $headers[] = "Subject: {$encodedSubject}";
        $headers[] = "Date: " . date('r');
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";
        $headers[] = "X-Mailer: PiatMove-Mailer";

        $plain = !empty($plainTextBody) ? $plainTextBody : strip_tags($htmlBody);

        $bodyParts = [];
        $bodyParts[] = "--{$boundary}";
        $bodyParts[] = "Content-Type: text/plain; charset=UTF-8";
        $bodyParts[] = "Content-Transfer-Encoding: base64\r\n";
        $bodyParts[] = chunk_split(base64_encode($plain));

        $bodyParts[] = "--{$boundary}";
        $bodyParts[] = "Content-Type: text/html; charset=UTF-8";
        $bodyParts[] = "Content-Transfer-Encoding: base64\r\n";
        $bodyParts[] = chunk_split(base64_encode($htmlBody));

        $bodyParts[] = "--{$boundary}--";

        $messagePayload = implode("\r\n", $headers) . "\r\n\r\n" . implode("\r\n", $bodyParts) . "\r\n.";

        $sendCommand($messagePayload, [250]);
        $sendCommand("QUIT", [221, 250]);

        fclose($socket);
        return ['success' => true, 'message' => 'Email sent successfully'];
    } catch (Exception $e) {
        if (is_resource($socket)) {
            @fclose($socket);
        }
        error_log("[SMTP Mailer Error] " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Sends a password reset OTP email with PiatMove branding.
 */
function send_password_reset_email(string $toEmail, string $userName, string $otp): array {
    $subject = "PiatMove - Password Reset Verification Code: {$otp}";
    $safeName = htmlspecialchars($userName ?: 'User', ENT_QUOTES, 'UTF-8');
    $safeOtp = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Password Reset</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 40px 15px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" style="max-width: 520px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05);">
          <!-- Header -->
          <tr>
            <td style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); padding: 32px 24px; text-align: center;">
              <h1 style="margin: 0; font-size: 26px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px;">🛺 PiatMove</h1>
              <p style="margin: 6px 0 0 0; color: #d1fae5; font-size: 14px; font-weight: 500;">Piat Tricycle Booking Service</p>
            </td>
          </tr>
          <!-- Body -->
          <tr>
            <td style="padding: 36px 28px;">
              <h2 style="margin: 0 0 12px 0; font-size: 20px; font-weight: 700; color: #0f172a;">Password Reset Request</h2>
              <p style="margin: 0 0 20px 0; font-size: 15px; line-height: 1.6; color: #475569;">
                Hello <strong>{$safeName}</strong>,<br>
                We received a request to reset your PiatMove account password. Use the 6-digit verification code below to complete your reset:
              </p>

              <!-- OTP Box -->
              <div style="background-color: #f8fafc; border: 2px dashed #10b981; border-radius: 12px; padding: 20px; text-align: center; margin: 24px 0;">
                <span style="font-size: 34px; font-weight: 800; letter-spacing: 8px; color: #047857; display: inline-block;">{$safeOtp}</span>
              </div>

              <p style="margin: 0 0 12px 0; font-size: 13px; line-height: 1.5; color: #64748b;">
                ⏱️ This verification code is valid for <strong>15 minutes</strong>. If you did not request a password reset, please ignore this email or keep your account secure.
              </p>
            </td>
          </tr>
          <!-- Footer -->
          <tr>
            <td style="background-color: #f8fafc; padding: 20px 24px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8;">
              &copy; 2026 PiatMove Transport. All rights reserved.<br>
              Municipality of Piat, Cagayan, Philippines
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

    return send_smtp_email($toEmail, $userName, $subject, $html);
}

/**
 * Sends a booking accepted notification email to the passenger.
 */
function send_booking_accepted_email(
    string $passengerEmail,
    string $passengerName,
    string $driverName,
    string $driverPhone,
    string $vehicleNo,
    string $pickupAddress,
    string $dropoffAddress,
    float $fare
): array {
    $subject = "🛺 Your PiatMove Ride has been Accepted!";
    $safePassName = htmlspecialchars($passengerName ?: 'Passenger', ENT_QUOTES, 'UTF-8');
    $safeDriverName = htmlspecialchars($driverName ?: 'Assigned Driver', ENT_QUOTES, 'UTF-8');
    $safePhone = htmlspecialchars($driverPhone ?: 'N/A', ENT_QUOTES, 'UTF-8');
    $safeVehicle = htmlspecialchars($vehicleNo ?: 'Tricycle', ENT_QUOTES, 'UTF-8');
    $safePickup = htmlspecialchars($pickupAddress, ENT_QUOTES, 'UTF-8');
    $safeDropoff = htmlspecialchars($dropoffAddress, ENT_QUOTES, 'UTF-8');
    $formattedFare = number_format($fare, 2);

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Ride Accepted</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 40px 15px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" style="max-width: 520px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05);">
          <!-- Header -->
          <tr>
            <td style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); padding: 30px 24px; text-align: center;">
              <h1 style="margin: 0; font-size: 24px; font-weight: 800; color: #ffffff;">🛺 Driver On The Way!</h1>
              <p style="margin: 6px 0 0 0; color: #d1fae5; font-size: 14px;">Your PiatMove booking is confirmed</p>
            </td>
          </tr>
          <!-- Content -->
          <tr>
            <td style="padding: 28px 24px;">
              <p style="margin: 0 0 18px 0; font-size: 15px; color: #334155;">
                Hello <strong>{$safePassName}</strong>,<br>
                Great news! A driver has accepted your ride request.
              </p>

              <!-- Driver Card -->
              <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 16px; margin-bottom: 20px;">
                <table width="100%" cellspacing="0" cellpadding="4" style="font-size: 14px; color: #166534;">
                  <tr>
                    <td width="35%" style="font-weight: 600;">Driver:</td>
                    <td style="font-weight: 700; color: #14532d;">{$safeDriverName}</td>
                  </tr>
                  <tr>
                    <td style="font-weight: 600;">Vehicle No:</td>
                    <td style="font-weight: 700; color: #14532d;">{$safeVehicle}</td>
                  </tr>
                  <tr>
                    <td style="font-weight: 600;">Contact:</td>
                    <td style="font-weight: 700; color: #14532d;">{$safePhone}</td>
                  </tr>
                </table>
              </div>

              <!-- Trip Details -->
              <div style="background-color: #f8fafc; border-radius: 12px; padding: 16px; margin-bottom: 20px;">
                <table width="100%" cellspacing="0" cellpadding="6" style="font-size: 14px;">
                  <tr>
                    <td width="30%" style="color: #64748b; font-weight: 600;">📍 Pickup:</td>
                    <td style="color: #0f172a; font-weight: 500;">{$safePickup}</td>
                  </tr>
                  <tr>
                    <td style="color: #64748b; font-weight: 600;">🏁 Drop-off:</td>
                    <td style="color: #0f172a; font-weight: 500;">{$safeDropoff}</td>
                  </tr>
                  <tr>
                    <td style="color: #64748b; font-weight: 600;">💵 Fare:</td>
                    <td style="color: #047857; font-weight: 800; font-size: 16px;">₱{$formattedFare}</td>
                  </tr>
                </table>
              </div>

              <p style="margin: 0; font-size: 13px; color: #64748b; line-height: 1.5;">
                Please be at your pickup point. You can view live tracking and contact your driver directly inside the PiatMove app.
              </p>
            </td>
          </tr>
          <!-- Footer -->
          <tr>
            <td style="background-color: #f8fafc; padding: 18px 24px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8;">
              &copy; 2026 PiatMove Transport. Safe travels!
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

    return send_smtp_email($passengerEmail, $passengerName, $subject, $html);
}
