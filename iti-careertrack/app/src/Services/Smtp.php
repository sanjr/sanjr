<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Small SMTP client (no library needed): SSL (port 465) or STARTTLS (587), AUTH LOGIN.
 * Hostinger: host smtp.hostinger.com, port 465, secure ssl, username = the full mailbox address.
 */
final class Smtp
{
    /** @var resource|null */
    private $fp = null;

    public function __construct(private readonly array $c)
    {
    }

    public function open(): void
    {
        $host = (string) $this->c['host'];
        $port = (int) ($this->c['port'] ?? 465);
        $secure = (string) ($this->c['secure'] ?? ($port === 465 ? 'ssl' : 'tls'));   // ssl | tls | none
        $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $host]]);
        $fp = @stream_socket_client(($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port, $no, $err, 20, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) {
            throw new SmtpException(0, "Cannot connect to $host:$port ($err)");
        }
        $this->fp = $fp;
        stream_set_timeout($fp, 30);
        $this->expect([220]);
        $helo = preg_replace('/[^A-Za-z0-9.-]/', '', (string) ($_SERVER['HTTP_HOST'] ?? gethostname() ?: 'localhost')) ?: 'localhost';
        $this->cmd("EHLO $helo", [250]);
        if ($secure === 'tls') {
            $this->cmd('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new SmtpException(0, 'STARTTLS failed');
            }
            $this->cmd("EHLO $helo", [250]);
        }
        if ((string) ($this->c['username'] ?? '') !== '') {
            $this->cmd('AUTH LOGIN', [334]);
            $this->cmd(base64_encode((string) $this->c['username']), [334]);
            $this->cmd(base64_encode((string) ($this->c['password'] ?? '')), [235]);
        }
    }

    public function isOpen(): bool
    {
        return is_resource($this->fp) && !feof($this->fp);
    }

    /** $data = complete message (headers + body) with CRLF line ends. */
    public function send(string $from, string $to, string $data): void
    {
        $this->cmd('MAIL FROM:<' . $from . '>', [250]);
        $this->cmd('RCPT TO:<' . $to . '>', [250, 251]);
        $this->cmd('DATA', [354]);
        $data = preg_replace('/^\./m', '..', $data);          // dot-stuffing
        fwrite($this->fp, $data . "\r\n.\r\n");
        $this->expect([250]);
    }

    public function reset(): void
    {
        if ($this->isOpen()) {
            try {
                $this->cmd('RSET', [250]);
            } catch (SmtpException) {
                $this->close();
            }
        }
    }

    public function close(): void
    {
        if (is_resource($this->fp)) {
            @fwrite($this->fp, "QUIT\r\n");
            @fclose($this->fp);
        }
        $this->fp = null;
    }

    private function cmd(string $line, array $ok): string
    {
        if (!$this->isOpen()) {
            throw new SmtpException(0, 'Connection closed');
        }
        fwrite($this->fp, $line . "\r\n");
        return $this->expect($ok);
    }

    private function expect(array $ok): string
    {
        $text = '';
        while (($line = fgets($this->fp, 1024)) !== false) {
            $text .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        $code = (int) substr($text, 0, 3);
        if (!in_array($code, $ok, true)) {
            throw new SmtpException($code, trim($text) !== '' ? trim($text) : 'No answer from the mail server');
        }
        return $text;
    }
}
