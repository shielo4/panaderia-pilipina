# Bread

## Password Reset Email OTP

The customer password-reset flow sends a one-time code to the email address registered on the account. To configure Gmail SMTP:

1. Enable 2-Step Verification on the Gmail account and create a Google App Password.
2. Copy `Project1/.env.example` to `Project1/.env`.
3. Set `GMAIL_SMTP_USER`, `GMAIL_SMTP_PASS`, and `GMAIL_SMTP_FROM` in `Project1/.env`. Use the App Password for `GMAIL_SMTP_PASS`, not the regular Gmail password.

The app reads this local file directly, so shell exports and server restarts are not needed. `Project1/.env` is outside the web root and is ignored by Git. Never commit or share it.

If Gmail SMTP settings are missing or invalid, the app will not issue a demo OTP or allow the password reset.