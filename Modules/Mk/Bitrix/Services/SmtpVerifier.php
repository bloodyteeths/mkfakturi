<?php

namespace Modules\Mk\Bitrix\Services;

use Illuminate\Support\Facades\Log;

/**
 * SmtpVerifier
 *
 * Performs SMTP-level RCPT TO verification to check if a mailbox exists.
 * This catches dead mailboxes on valid MX domains (e.g. abandoned Yahoo accounts,
 * old employee emails on live company domains).
 *
 * Returns: true (valid), false (rejected), null (inconclusive/skip).
 *
 * Limitations:
 * - Freemail providers (Gmail, Yahoo, Hotmail) block RCPT TO → always inconclusive
 * - Catch-all domains accept everything → always inconclusive
 * - Greylisting servers reject first attempt → treated as inconclusive
 */
class SmtpVerifier
{
    /**
     * Freemail domains that block RCPT TO verification.
     */
    protected const FREEMAIL_DOMAINS = [
        'yahoo.com', 'yahoo.co.uk', 'yahoo.com.br', 'yahoo.fr', 'yahoo.de',
        'yahoo.it', 'yahoo.es', 'yahoo.ca', 'yahoo.com.au', 'yahoo.co.in',
        'ymail.com', 'rocketmail.com',
        'gmail.com', 'googlemail.com',
        'hotmail.com', 'outlook.com', 'live.com', 'msn.com', 'hotmail.co.uk',
        'aol.com',
        'icloud.com', 'me.com', 'mac.com',
        'protonmail.com', 'proton.me',
        'mail.ru', 'yandex.ru', 'yandex.com',
        'zoho.com',
    ];

    protected const CONNECT_TIMEOUT = 5;

    protected const READ_TIMEOUT = 10;

    /**
     * Cache of catch-all detection results per domain.
     *
     * @var array<string, bool|null>
     */
    protected array $catchAllCache = [];

    /**
     * Check if a domain is a known freemail provider.
     */
    public function isFreemailDomain(string $domain): bool
    {
        return in_array(strtolower($domain), self::FREEMAIL_DOMAINS);
    }

    /**
     * Verify a single email address via SMTP RCPT TO.
     *
     * @return bool|null true = valid, false = invalid, null = inconclusive
     */
    public function verify(string $email): ?bool
    {
        $domain = strtolower(substr($email, strpos($email, '@') + 1));

        if ($this->isFreemailDomain($domain)) {
            return null;
        }

        $mxHosts = $this->getMxHosts($domain);

        if (empty($mxHosts)) {
            return false;
        }

        // Try each MX host (ordered by priority)
        foreach ($mxHosts as $host) {
            $result = $this->checkMailbox($host, $email, $domain);

            if ($result !== null) {
                return $result;
            }
        }

        return null; // All MX hosts unreachable
    }

    /**
     * Get MX hosts for a domain, sorted by priority.
     *
     * @return string[]
     */
    protected function getMxHosts(string $domain): array
    {
        $hosts = [];
        $weights = [];

        if (@getmxrr($domain, $hosts, $weights)) {
            array_multisort($weights, SORT_ASC, $hosts);

            return array_slice($hosts, 0, 2); // Only try top 2
        }

        return [];
    }

    /**
     * Connect to an MX host and check if the mailbox accepts mail.
     * Also detects catch-all servers.
     *
     * @return bool|null true = valid, false = invalid, null = inconclusive/error
     */
    protected function checkMailbox(string $mxHost, string $email, string $domain): ?bool
    {
        $socket = @stream_socket_client(
            "tcp://{$mxHost}:25",
            $errno,
            $errstr,
            self::CONNECT_TIMEOUT,
            STREAM_CLIENT_CONNECT
        );

        if (! $socket) {
            Log::debug('SMTP verify: connection failed', [
                'host' => $mxHost,
                'error' => "{$errno}: {$errstr}",
            ]);

            return null;
        }

        stream_set_timeout($socket, self::READ_TIMEOUT);

        try {
            // Read server greeting
            $greeting = $this->readResponse($socket);

            if (! $this->isPositiveResponse($greeting)) {
                return null;
            }

            // EHLO
            $this->sendCommand($socket, 'EHLO facturino.mk');
            $ehlo = $this->readResponse($socket);

            if (! $this->isPositiveResponse($ehlo)) {
                return null;
            }

            // MAIL FROM
            $this->sendCommand($socket, 'MAIL FROM:<verify@facturino.mk>');
            $mailFrom = $this->readResponse($socket);

            if (! $this->isPositiveResponse($mailFrom)) {
                return null;
            }

            // Catch-all detection: test with a fake address first
            if (! isset($this->catchAllCache[$domain])) {
                $fakeEmail = 'fct-probe-' . bin2hex(random_bytes(6)) . '@' . $domain;
                $this->sendCommand($socket, "RCPT TO:<{$fakeEmail}>");
                $fakeResponse = $this->readResponse($socket);
                $fakeCode = $this->getResponseCode($fakeResponse);

                // If the server accepts a random address, it's catch-all
                $this->catchAllCache[$domain] = ($fakeCode >= 200 && $fakeCode < 300);

                // Reset the session for the real check
                $this->sendCommand($socket, 'RSET');
                $this->readResponse($socket);
                $this->sendCommand($socket, 'MAIL FROM:<verify@facturino.mk>');
                $this->readResponse($socket);
            }

            if ($this->catchAllCache[$domain]) {
                $this->sendCommand($socket, 'QUIT');

                return null; // Catch-all — can't determine validity
            }

            // RCPT TO — the real check
            $this->sendCommand($socket, "RCPT TO:<{$email}>");
            $rcptResponse = $this->readResponse($socket);
            $code = $this->getResponseCode($rcptResponse);

            $this->sendCommand($socket, 'QUIT');

            // 250/251 = accepted
            if ($code >= 250 && $code <= 259) {
                return true;
            }

            // 550-559 = mailbox doesn't exist (permanent rejection)
            if ($code >= 550 && $code <= 559) {
                Log::info('SMTP verify: mailbox rejected', [
                    'email' => $email,
                    'code' => $code,
                    'response' => substr($rcptResponse, 0, 200),
                ]);

                return false;
            }

            // 450-459 = greylisting / temporary rejection
            if ($code >= 450 && $code <= 459) {
                return null;
            }

            // Anything else = inconclusive
            return null;

        } catch (\Throwable $e) {
            Log::debug('SMTP verify: exception', [
                'email' => $email,
                'host' => $mxHost,
                'error' => $e->getMessage(),
            ]);

            return null;
        } finally {
            @fclose($socket);
        }
    }

    /**
     * Send an SMTP command.
     */
    protected function sendCommand($socket, string $command): void
    {
        @fwrite($socket, $command . "\r\n");
    }

    /**
     * Read a full SMTP response (handles multi-line).
     */
    protected function readResponse($socket): string
    {
        $response = '';
        $maxLines = 20;

        while ($maxLines-- > 0) {
            $line = @fgets($socket, 512);

            if ($line === false) {
                break;
            }

            $response .= $line;

            // Multi-line: "250-..." continues, "250 ..." is final
            if (isset($line[3]) && $line[3] !== '-') {
                break;
            }
        }

        return $response;
    }

    /**
     * Check if the response code is 2xx (positive).
     */
    protected function isPositiveResponse(string $response): bool
    {
        $code = $this->getResponseCode($response);

        return $code >= 200 && $code < 300;
    }

    /**
     * Extract the 3-digit response code.
     */
    protected function getResponseCode(string $response): int
    {
        return (int) substr(trim($response), 0, 3);
    }
}
