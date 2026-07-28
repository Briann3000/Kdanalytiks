<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>New Contact Submission</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f4f6f8; padding: 20px; color: #333;">
    <div
        style="max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 12px; border: 1px solid #e1e4e8;">
        <h2 style="color: #2271b1; margin-top: 0;">New Contact Message — KDAnalytiks</h2>
        <hr style="border: 0; border-top: 1px solid #eeeeee; margin: 20px 0;">

        <p><strong>Name:</strong> {{ $contactData['name'] }}</p>
        <p><strong>Email:</strong> <a href="mailto:{{ $contactData['email'] }}">{{ $contactData['email'] }}</a></p>
        <p><strong>Subject:</strong> {{ $contactData['subject'] }}</p>

        <div
            style="margin-top: 20px; padding: 15px; background: #f8fafc; border-left: 4px solid #2271b1; border-radius: 6px;">
            <p style="margin: 0; font-weight: bold; color: #475569;">Message:</p>
            <p style="margin-top: 8px; white-space: pre-wrap;">{{ $contactData['message'] }}</p>
        </div>

        <hr style="border: 0; border-top: 1px solid #eeeeee; margin: 30px 0 15px 0;">
        <p style="font-size: 12px; color: #94a3b8; text-align: center;">Sent via KDAnalytiks Research Ecosystem Contact
            Form</p>
    </div>
</body>

</html>