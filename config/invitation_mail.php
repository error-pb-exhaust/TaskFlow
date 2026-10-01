<?php
declare(strict_types=1);
require_once __DIR__ . '/mail.php';

function invitationUrl(string $token): string
{
    $base = rtrim(APP_URL, '/');
    if (!filter_var($base, FILTER_VALIDATE_URL) || !in_array(parse_url($base, PHP_URL_SCHEME), ['http', 'https'], true)) {
        throw new RuntimeException('Set a valid APP_URL in config/mail.php.');
    }
    return $base . '/join.php#token=' . rawurlencode($token);
}

// Returns SMTP acceptance, not a guarantee of inbox delivery.
function sendProjectInvitation(string $email, string $projectName, string $url): string
{
    if (SMTP_HOST === '' || SMTP_FROM === '') return 'not_configured';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !filter_var(SMTP_FROM, FILTER_VALIDATE_EMAIL)) return 'failed';
    $socket = null;
    try {
        if (!in_array(SMTP_SECURITY, ['tls', 'ssl'], true)) throw new RuntimeException('SMTP requires TLS.');
        $context = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => SMTP_HOST]]);
        $socket = stream_socket_client((SMTP_SECURITY === 'ssl' ? 'ssl://' : 'tcp://') . SMTP_HOST . ':' . SMTP_PORT, $errorNumber, $errorMessage, 10, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) throw new RuntimeException('SMTP connection failed.');
        stream_set_timeout($socket, 10);
        $read = static function (array $codes) use ($socket): void {
            $last = '';
            do {
                $line = fgets($socket, 2048);
                if ($line === false) throw new RuntimeException('SMTP response timed out.');
                $last = $line;
            } while (strlen($line) > 3 && $line[3] === '-');
            if (!in_array((int) substr($last, 0, 3), $codes, true)) throw new RuntimeException('SMTP rejected a command.');
        };
        $write = static function (string $text) use ($socket): void {
            $offset = 0;
            while ($offset < strlen($text)) {
                $bytes = fwrite($socket, substr($text, $offset));
                if (!$bytes) throw new RuntimeException('SMTP write failed.');
                $offset += $bytes;
            }
        };
        $command = static function (string $command, array $codes) use ($write, $read): void {
            $write($command . "\r\n");
            $read($codes);
        };
        $read([220]);
        $command('EHLO taskflow.local', [250]);
        if (SMTP_SECURITY === 'tls') {
            $command('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new RuntimeException('SMTP TLS negotiation failed.');
            $command('EHLO taskflow.local', [250]);
        }
        if (SMTP_USERNAME !== '') {
            $command('AUTH LOGIN', [334]);
            $command(base64_encode(SMTP_USERNAME), [334]);
            $command(base64_encode(SMTP_PASSWORD), [235]);
        }
        $command('MAIL FROM:<' . SMTP_FROM . '>', [250]);
        $command('RCPT TO:<' . $email . '>', [250, 251]);
        $command('DATA', [354]);
        $body = "You have been invited to join the project: " . $projectName . "\n\nOpen this link:\n" . $url . "\n\nSign in or create an account with " . $email . ", then choose Accept invitation.\nThe link expires in 7 days.\n\nTaskFlow";
        $headers = ['From: TaskFlow <' . SMTP_FROM . '>', 'To: <' . $email . '>', 'Subject: Your TaskFlow project invitation', 'Date: ' . date(DATE_RFC2822), 'Message-ID: <' . bin2hex(random_bytes(16)) . '@taskflow.local>', 'MIME-Version: 1.0', 'Content-Type: text/plain; charset=UTF-8', 'Content-Transfer-Encoding: base64'];
        $write(implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($body), 76, "\r\n") . ".\r\n");
        $read([250]);
        // Receipt of 250 after DATA means the provider accepted the message.
        @fwrite($socket, "QUIT\r\n");
        return 'sent';
    } catch (Throwable $exception) {
        error_log('TaskFlow invitation SMTP error: ' . $exception->getMessage());
        return 'failed';
    } finally {
        if (is_resource($socket)) fclose($socket);
    }
}
