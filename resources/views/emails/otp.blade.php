<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Code</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f7; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td align="center" style="padding: 40px 10px;">
                <table role="presentation" width="100%" style="max-width: 550px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);">
                    
                    <tr>
                        <td style="padding: 40px 40px 30px 40px; text-align: center;">
                            <h1 style="margin: 0; font-size: 28px; font-weight: 900; color: #111827; letter-spacing: -1px;">
                                Intern<span style="color: #6366f1;">match.</span>
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 0 40px 40px 40px;">
                            <h2 style="margin: 0 0 16px 0; font-size: 22px; font-weight: 700; color: #1f2937; text-align: center;">Verify your account</h2>
                            
                            <p style="margin: 0 0 30px 0; font-size: 16px; line-height: 1.6; color: #6b7280; text-align: center;">
                                @if(isset($customMessage))
                                    {{ $customMessage }}
                                @else
                                    Copy and paste the code below to verify your email address. This code is valid for 10 minutes.
                                @endif
                            </p>

                            <div style="background-color: #111827; border-radius: 12px; padding: 25px; text-align: center; margin-bottom: 30px;">
                                <span style="font-family: 'Monaco', 'Consolas', monospace; font-size: 40px; font-weight: 800; letter-spacing: 10px; color: #ffffff !important; display: block; width: 100%;">
                                    {{ $otp }}
                                </span>
                            </div>

                            <p style="margin: 0 0 20px 0; font-size: 14px; color: #9ca3af; text-align: center;">
                                If you didn't request this, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 30px 40px; background-color: #f9fafb; border-top: 1px solid #f3f4f6; text-align: center;">
                            <p style="margin: 0; font-size: 13px; font-weight: 600; color: #4b5563; text-transform: uppercase; letter-spacing: 1px;">
                                The Internmatch Team
                            </p>
                            <p style="margin: 5px 0 0 0; font-size: 12px; color: #9ca3af;">
                                &copy; 2026 Internmatch Inc.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>