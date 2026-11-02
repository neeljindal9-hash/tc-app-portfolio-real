<?php

class MailService {

    private static ?array $smtpconfig = null;

    private static function smtpConfig(?string $par = null)
    {
        if (self::$smtpconfig === null) {
            self::$smtpconfig = require __DIR__ . '/../config/smtpconfig.php';
        }

        return ($par === null) ? self::$smtpconfig : self::$smtpconfig[$par];
    }


    public static function getRemainingEmails(): ?int {
        try {
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => "https://api.brevo.com/v3/account",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "accept: application/json",
                    "api-key: " . self::smtpConfig('api_key')
                ],
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);

            if ($err) {
                return null;
            }

            $data = json_decode($response, true);
            
            // Loop through the plan details to find the sendLimit credits
            if (isset($data['plan']) && is_array($data['plan'])) {
                foreach ($data['plan'] as $planDetail) {
                    if ($planDetail['creditsType'] === 'sendLimit') {
                        return (int)$planDetail['credits'];
                    }
                }
            }
            error_log("failed to contact Brevo for remaining tokens...");
            return null;
        } catch (Throwable $e) {
            error_log($e->getMessage() . "  **at MailService.php.");
            return null;
        }
    }

    public static function sendIDConfirmation(RequestContext $request, array $data, string $email): bool {
        $url = 'https://api.brevo.com/v3/smtp/email';

        try {

            $htmlBody = self::getEmailHTML(nl2br(htmlspecialchars($data['msg'] ?? 'No Custom Message')), htmlspecialchars($request->session['secure_verification_code']));
            $payload = [
                'sender' => [
                    'name' => 'The Carver - Verified',
                    'email' => 'thecarver@thecarve.app' // Must be an authorized sender in Brevo
                ],
                'to' => [
                    [
                        'email' => $email,
                        'name' => $request->session['name'] ?? null
                    ]
                ],
                'subject' => 'Welcome to The Carve!',
                'htmlContent' => $htmlBody
            ];

            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    "accept: application/json",
                    "api-key: " . self::smtpConfig('api_key'),
                    'content-type: application/json'
                ],
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);

            // Brevo returns HTTP 201 (Created) on success
            if ($httpCode === 201) {
                return true; 
            } else {
                // Log the exact error for debugging
                error_log("Brevo API Error: HTTP {$httpCode} - {$response} - cURL Error: {$error}");
                return false;
            }


        } catch(Throwable $t) {
            error_log("Internal Server ERROR MAILING ALERT ALERT: " . $t->getMessage());
            return false;
        }
    }



    public static function getEmailHTML(string $customMsg, string $verifCode): string {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Welcome to The Carve</title>
            <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@500;600;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
            <style>
                body { font-family: 'Nunito', system-ui, sans-serif !important; }
                h1 { font-family: 'Playfair Display', Georgia, serif !important; }
            </style>
        </head>
        <body style="margin: 0; padding: 0; background-color: #EBD5B9; font-family: 'Nunito', system-ui, sans-serif; color: #3E2723; -webkit-font-smoothing: antialiased;">

            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #EBD5B9; padding: 60px 20px;">
                <tr>
                    <td align="center">
                        
                        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 640px; background-color: #F5E6D3; border-radius: 24px; border: 1px solid rgba(131, 92, 72, 0.15); box-shadow: 0 12px 32px rgba(62, 39, 35, 0.1), 0 2px 6px rgba(62, 39, 35, 0.08); overflow: hidden;">
                            
                            <tr>
                                <td align="center" style="padding: 50px 40px 20px 40px;">
                                    <h1 style="margin: 0 0 16px 0; font-family: 'Playfair Display', Georgia, serif; font-size: 38px; color: #2C3E35; letter-spacing: -0.5px; line-height: 1.1;">Welcome to The Carve</h1>
                                    <p style="margin: 0; font-size: 18px; line-height: 1.6; color: #835C48; font-weight: 500; max-width: 480px;">Thanks for joining the campus network. To get inside and see your classmates, we'll need to review your school credentials.</p>
                                </td>
                            </tr>

                            <tr>
                                <td align="center" style="padding: 20px 40px 40px 40px;">
                                    
                                    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #E0C7A8; border-radius: 16px; border: 1px solid rgba(255,255,255,0.3); box-shadow: inset 0 2px 6px rgba(62, 39, 35, 0.08), inset 0 -1px 2px rgba(255, 255, 255, 0.6);">
                                        <tr>
                                            <td align="center" style="padding: 30px;">
                                                <p style="margin: 0 0 15px 0; font-size: 13px; text-transform: uppercase; letter-spacing: 1.5px; color: #2C3E35; font-weight: 800; border-bottom: 1px solid rgba(131, 92, 72, 0.2); padding-bottom: 15px; box-shadow: 0 1px 0 rgba(255,255,255,0.3);">Secure Verification Code</p>
                                                <h2 style="margin: 0; font-size: 42px; font-family: monospace; color: #C45525; letter-spacing: 6px; font-weight: bold;">{$verifCode}</h2>
                                            </td>
                                        </tr>
                                    </table>

                                </td>
                            </tr>

                            <tr>
                                <td align="center" style="padding: 0 40px 50px 40px;">
                                    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="border-top: 1px solid rgba(131, 92, 72, 0.15);">
                                        <tr>
                                            <td align="center" style="padding-top: 30px;">
                                                <p style="margin: 0; font-size: 16px; line-height: 1.6; color: #3E2723; font-weight: 600;">
                                                    {$customMsg}
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                        </table>

                        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 640px;">
                            <tr>
                                <td align="center" style="padding: 24px; font-size: 13px; color: #835C48; font-weight: 500;">
                                    &copy; The Carve Network. See who's around.
                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>
            </table>

        </body>
        </html>
        HTML;
    }

}
?>