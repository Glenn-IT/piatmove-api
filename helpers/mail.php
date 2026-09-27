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

/**
 * Sends a welcome onboarding email to a newly registered passenger.
 */
function send_passenger_welcome_email(string $toEmail, string $userName, string $phone): array {
    $subject = "🛺 Welcome to PiatMove - Your Commuter Account is Ready!";
    $safeName = htmlspecialchars($userName ?: 'Commuter', ENT_QUOTES, 'UTF-8');
    $safeEmail = htmlspecialchars($toEmail, ENT_QUOTES, 'UTF-8');
    $safePhone = htmlspecialchars($phone ?: 'N/A', ENT_QUOTES, 'UTF-8');

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Welcome to PiatMove</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 40px 15px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" style="max-width: 540px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05);">
          <!-- Header -->
          <tr>
            <td style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); padding: 32px 24px; text-align: center;">
              <h1 style="margin: 0; font-size: 26px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px;">🛺 Welcome to PiatMove!</h1>
              <p style="margin: 6px 0 0 0; color: #d1fae5; font-size: 14px; font-weight: 500;">Tricycle Transport Hailing in Piat, Cagayan</p>
            </td>
          </tr>
          <!-- Body -->
          <tr>
            <td style="padding: 32px 26px;">
              <p style="margin: 0 0 16px 0; font-size: 15px; line-height: 1.6; color: #334155;">
                Hello <strong>{$safeName}</strong>,<br>
                Thank you for signing up for <strong>PiatMove</strong>. Your commuter account is officially registered and ready to use!
              </p>

              <!-- Account Summary Box -->
              <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; margin: 20px 0;">
                <h3 style="margin: 0 0 12px 0; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; color: #047857;">Your Account Details</h3>
                <table width="100%" cellspacing="0" cellpadding="5" style="font-size: 14px; color: #334155;">
                  <tr>
                    <td width="35%" style="color: #64748b;">Full Name:</td>
                    <td style="font-weight: 600;">{$safeName}</td>
                  </tr>
                  <tr>
                    <td style="color: #64748b;">Email Address:</td>
                    <td style="font-weight: 600;">{$safeEmail}</td>
                  </tr>
                  <tr>
                    <td style="color: #64748b;">Mobile Number:</td>
                    <td style="font-weight: 600;">{$safePhone}</td>
                  </tr>
                  <tr>
                    <td style="color: #64748b;">Account Status:</td>
                    <td><span style="display: inline-block; background-color: #dcfce7; color: #15803d; font-size: 12px; font-weight: 700; padding: 2px 8px; border-radius: 999px;">✓ Active</span></td>
                  </tr>
                </table>
              </div>

              <!-- Fare Guidelines -->
              <div style="background-color: #f0fdf4; border-left: 4px solid #10b981; border-radius: 0 8px 8px 0; padding: 14px 18px; margin-bottom: 24px;">
                <h4 style="margin: 0 0 6px 0; font-size: 14px; color: #166534;">💡 Municipal Fare & Discount Guidelines</h4>
                <p style="margin: 0 0 6px 0; font-size: 13px; line-height: 1.5; color: #15803d;">
                  • <strong>Standard Base Fare:</strong> ₱20.00 per passenger.<br>
                  • <strong>Statutory 20% Discount (₱16.00):</strong> Students, Senior Citizens, PWDs, and Pregnant commuters.
                </p>
                <p style="margin: 0; font-size: 12px; color: #166534; font-style: italic;">
                  * Please present your valid physical ID or document to the driver upon boarding when availing of discounted fares.
                </p>
              </div>

              <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #475569;">
                You can now log in to the <strong>PiatMove Passenger App</strong>, set your pickup location using the interactive map or municipal landmarks, and hail a ride with guaranteed fare transparency.
              </p>
            </td>
          </tr>
          <!-- Footer -->
          <tr>
            <td style="background-color: #f8fafc; padding: 20px 24px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8;">
              &copy; 2026 PiatMove Transport Service. All rights reserved.<br>
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
 * Sends an application receipt confirmation email to a newly registered driver.
 */
function send_driver_registration_email(
    string $toEmail,
    string $userName,
    string $licenseNo,
    string $vehicleNo,
    string $barangay
): array {
    $subject = "🛺 PiatMove Driver Application Received - Pending Verification";
    $safeName = htmlspecialchars($userName ?: 'Driver Applicant', ENT_QUOTES, 'UTF-8');
    $safeEmail = htmlspecialchars($toEmail, ENT_QUOTES, 'UTF-8');
    $safeLicense = htmlspecialchars($licenseNo ?: 'N/A', ENT_QUOTES, 'UTF-8');
    $safeVehicle = htmlspecialchars($vehicleNo ?: 'N/A', ENT_QUOTES, 'UTF-8');
    $safeBrgy = htmlspecialchars($barangay ?: 'Piat', ENT_QUOTES, 'UTF-8');

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Driver Application Received</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 40px 15px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" style="max-width: 540px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05);">
          <!-- Header -->
          <tr>
            <td style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); padding: 32px 24px; text-align: center;">
              <h1 style="margin: 0; font-size: 24px; font-weight: 800; color: #ffffff;">🛺 Driver Registration Received</h1>
              <p style="margin: 6px 0 0 0; color: #bfdbfe; font-size: 14px;">Piat Municipal Tricycle Transport Network</p>
            </td>
          </tr>
          <!-- Body -->
          <tr>
            <td style="padding: 32px 26px;">
              <p style="margin: 0 0 16px 0; font-size: 15px; line-height: 1.6; color: #334155;">
                Hello <strong>{$safeName}</strong>,<br>
                Thank you for applying to be an official tricycle driver on <strong>PiatMove</strong>. We have successfully received your registration and uploaded compliance documents.
              </p>

              <!-- Application Status Badge -->
              <div style="background-color: #fefce8; border: 1px solid #fef08a; border-radius: 12px; padding: 16px; text-align: center; margin: 20px 0;">
                <span style="font-size: 13px; font-weight: 700; color: #854d0e; text-transform: uppercase; letter-spacing: 0.5px;">Application Status</span>
                <div style="font-size: 20px; font-weight: 800; color: #a16207; margin-top: 4px;">⏳ Pending Admin Approval</div>
              </div>

              <!-- Details Box -->
              <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; margin-bottom: 22px;">
                <h3 style="margin: 0 0 12px 0; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; color: #1e40af;">Submitted Vehicle & Driver Profile</h3>
                <table width="100%" cellspacing="0" cellpadding="5" style="font-size: 14px; color: #334155;">
                  <tr>
                    <td width="38%" style="color: #64748b;">Driver Name:</td>
                    <td style="font-weight: 600;">{$safeName}</td>
                  </tr>
                  <tr>
                    <td style="color: #64748b;">License Number:</td>
                    <td style="font-weight: 600;">{$safeLicense}</td>
                  </tr>
                  <tr>
                    <td style="color: #64748b;">Franchise / Plate No:</td>
                    <td style="font-weight: 600;">{$safeVehicle}</td>
                  </tr>
                  <tr>
                    <td style="color: #64748b;">Home Barangay:</td>
                    <td style="font-weight: 600;">{$safeBrgy}, Piat</td>
                  </tr>
                </table>
              </div>

              <!-- Next Steps Notice -->
              <div style="background-color: #eff6ff; border-left: 4px solid #3b82f6; border-radius: 0 8px 8px 0; padding: 14px 18px; margin-bottom: 22px;">
                <h4 style="margin: 0 0 6px 0; font-size: 14px; color: #1e40af;">📋 What Happens Next?</h4>
                <p style="margin: 0; font-size: 13px; line-height: 1.5; color: #1d4ed8;">
                  Our municipal administrators are verifying your 4 compliance documents (Selfie, Driver's License, Plate/Franchise Proof, and Tricycle Photo). Once verified, you will receive an approval email and you will be able to toggle <strong>"Go Online"</strong> in your Driver App to start receiving passenger bookings.
                </p>
              </div>

              <p style="margin: 0; font-size: 13px; color: #64748b;">
                If you have any questions or need to update your documents, please coordinate with your local TODA or municipal transport regulatory office.
              </p>
            </td>
          </tr>
          <!-- Footer -->
          <tr>
            <td style="background-color: #f8fafc; padding: 20px 24px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8;">
              &copy; 2026 PiatMove Transport Service. All rights reserved.<br>
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
 * Sends a notification email when a driver application is approved or rejected by the admin.
 */
function send_driver_status_update_email(
    string $toEmail,
    string $userName,
    string $status,
    string $vehicleNo = ''
): array {
    $isApproved = ($status === 'approved');
    $subject = $isApproved
        ? "✅ Great News! Your PiatMove Driver Account is Approved"
        : "⚠️ PiatMove Driver Application Status Update";

    $safeName = htmlspecialchars($userName ?: 'Driver', ENT_QUOTES, 'UTF-8');
    $safeVehicle = htmlspecialchars($vehicleNo ?: 'Tricycle', ENT_QUOTES, 'UTF-8');

    $headerBg = $isApproved ? 'linear-gradient(135deg, #059669 0%, #10b981 100%)' : 'linear-gradient(135deg, #dc2626 0%, #ef4444 100%)';
    $statusText = $isApproved ? 'APPROVED' : 'NEEDS ATTENTION';
    $statusBadgeBg = $isApproved ? '#dcfce7' : '#fee2e2';
    $statusTextColor = $isApproved ? '#15803d' : '#b91c1c';

    $bodyMessage = $isApproved
        ? "We are thrilled to inform you that your driver compliance documents have been verified and approved by the municipal transport administrator! Your tricycle unit (<strong>{$safeVehicle}</strong>) is officially registered in our active dispatch fleet."
        : "Your driver application was reviewed by the municipal transport administrator and requires further review or document re-submission. Please ensure your driver's license and vehicle franchise documents are clear and valid.";

    $ctaSection = $isApproved
        ? <<<CTA
        <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 18px; margin: 24px 0; text-align: center;">
          <p style="margin: 0 0 10px 0; font-size: 15px; font-weight: 700; color: #166534;">You are now ready to hit the road!</p>
          <p style="margin: 0; font-size: 13px; color: #15803d; line-height: 1.5;">
            1. Open the <strong>PiatMove Driver App</strong>.<br>
            2. Log in with your email and password.<br>
            3. Toggle the <strong>"Go Online"</strong> switch to start receiving ride requests from nearby commuters.
          </p>
        </div>
CTA
        : <<<CTA
        <div style="background-color: #fff1f2; border: 1px solid #fecdd3; border-radius: 12px; padding: 18px; margin: 24px 0;">
          <p style="margin: 0; font-size: 13px; color: #9f1239; line-height: 1.5;">
            Please coordinate with your TODA officer or visit the municipal hall with your physical LTO driver's license and municipal franchise papers for manual verification.
          </p>
        </div>
CTA;

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Driver Account Status</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 40px 15px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" style="max-width: 540px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05);">
          <!-- Header -->
          <tr>
            <td style="background: {$headerBg}; padding: 32px 24px; text-align: center;">
              <h1 style="margin: 0; font-size: 24px; font-weight: 800; color: #ffffff;">🛺 PiatMove Driver Status</h1>
              <p style="margin: 6px 0 0 0; color: #ffffff; opacity: 0.9; font-size: 14px;">Municipal Transport Compliance</p>
            </td>
          </tr>
          <!-- Body -->
          <tr>
            <td style="padding: 32px 26px;">
              <p style="margin: 0 0 16px 0; font-size: 15px; line-height: 1.6; color: #334155;">
                Hello <strong>{$safeName}</strong>,
              </p>

              <!-- Status Pill -->
              <div style="text-align: center; margin: 20px 0;">
                <span style="display: inline-block; background-color: {$statusBadgeBg}; color: {$statusTextColor}; font-size: 14px; font-weight: 800; padding: 6px 18px; border-radius: 999px; letter-spacing: 0.5px;">
                  {$statusText}
                </span>
              </div>

              <p style="margin: 0 0 16px 0; font-size: 14px; line-height: 1.6; color: #475569;">
                {$bodyMessage}
              </p>

              {$ctaSection}

              <p style="margin: 0; font-size: 12px; color: #94a3b8; text-align: center;">
                Drive safely and obey municipal traffic rules in Piat!
              </p>
            </td>
          </tr>
          <!-- Footer -->
          <tr>
            <td style="background-color: #f8fafc; padding: 20px 24px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8;">
              &copy; 2026 PiatMove Transport Service. All rights reserved.<br>
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
