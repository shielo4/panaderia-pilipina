# Panaderia Pilipina

## Run locally

Requires PHP 8.0 or later with the standard session and JSON extensions.

```sh
php -S 127.0.0.1:8000
```

Open `http://127.0.0.1:8000`. Copy `.env.example` to `.env` and configure
`ADMIN_EMAIL` and a strong `ADMIN_PASSWORD` to enable administrator sign-in.
Demo accounts are disabled unless `ENABLE_DEMO_ACCOUNTS=1`.

## Deploy to Render

The repository includes a Render Blueprint and Docker configuration. To deploy:

1. Push the repository to GitHub and create a Blueprint at
   <https://dashboard.render.com/blueprints>.
2. Connect this repository and apply the `render.yaml` Blueprint.
3. Enter `ADMIN_EMAIL` and a unique, strong `ADMIN_PASSWORD` when prompted.
4. Wait for the deploy and health check to pass. Render will provide an
   always-on `onrender.com` URL. Add a custom domain in the Render dashboard
   if desired.

The Blueprint uses a paid always-on web service and a persistent disk mounted
at `/var/data`, so customer accounts and orders survive deploys and restarts.
The service is intentionally not configured for Render's free tier, which can
spin down while idle and does not provide the required persistent storage.
Keep `ENABLE_DEMO_ACCOUNTS` set to `0` in production. Gmail SMTP variables are
optional; configure `GMAIL_SMTP_USER`, `GMAIL_SMTP_PASS`, and `GMAIL_SMTP_FROM`
in the Render dashboard to enable password-reset email.

Never put production credentials in Git or in the Docker image. If credentials
were committed previously, rotate them; deleting the file in a new commit does
not remove its contents from Git history.

## Password-reset email

The customer password-reset flow sends a one-time code to the registered email
address. Gmail SMTP requires 2-Step Verification and a 16-character Google App
Password. Use that App Password as `GMAIL_SMTP_PASS`, not the regular Gmail
password.