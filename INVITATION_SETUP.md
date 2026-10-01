# Email and link invitations

## Update an existing XAMPP installation

Replace the application files, keep your own database credentials, then open `http://localhost/taskflow-php/install.php` and click Install database again. The installer adds `project_invitations` without deleting projects, members, or tasks.

## Manager flow

1. Sign in as a Manager. Create a project if necessary.
2. Open Team, choose your project, enter the teammate's email and press Send invitation. The teammate does not need an account yet.
3. When SMTP is configured, the app submits an email with the invitation link. Otherwise it shows the link for manual sharing. A delivery error is displayed honestly and does not discard the invitation.
4. The teammate opens the link, signs in or creates a Team Member account with that email, then clicks Accept invitation.
5. Reopen Team or refresh the page. The accepted teammate appears in the member list and the task assignee menu.
6. Pending invitations can be cancelled or resent. Resending creates a new link and invalidates the old one. Wait one minute between sends to the same email and project.

Each project has its own team. Tasks are assigned to an individual member of that team after they accept.

## Email configuration

Edit `config/mail.php`:

| Setting | Value to enter |
|---|---|
| APP_URL | Public or LAN address of your TaskFlow folder, with no trailing slash |
| SMTP_HOST | Your email provider's SMTP host |
| SMTP_PORT | Provider port, normally 587 for STARTTLS or 465 for implicit TLS |
| SMTP_SECURITY | `tls` for STARTTLS or `ssl` for implicit TLS |
| SMTP_USERNAME | Provider SMTP username |
| SMTP_PASSWORD | Provider SMTP password or app password |
| SMTP_FROM | An email address your provider allows as sender |

PHP's OpenSSL extension must be enabled. The mailer verifies the server certificate. SMTP acceptance means the provider accepted the message; it does not guarantee inbox placement.

Do not send a `localhost` link to someone on another computer: it points to their own computer. For devices on the same network, use the host computer's LAN IP and allow Apache through its firewall. For recipients elsewhere, deploy the PHP/MySQL app at a reachable HTTPS address and set APP_URL to that address. This ZIP does not deploy the app or configure your mailbox automatically.

## Invitation controls

- Invitation tokens contain 32 random bytes; only their SHA-256 hashes are stored.
- Links expire after seven days and grant access to exactly one project.
- Viewing a link does not join the project. Acceptance requires a signed-in active account with the invited email and an explicit POST request protected by CSRF verification.
- Cancelling or accepting consumes the pending invitation. Reusing it cannot grant another membership.
- A manager can invite only to projects they own. Invitation acceptance does not grant manager or administrator privileges.

## Verification limits

JavaScript syntax and simulated recipient flows were checked. PHP/MySQL execution and real SMTP delivery still need testing on your configured server. No actual invitation email was sent while preparing this package.
