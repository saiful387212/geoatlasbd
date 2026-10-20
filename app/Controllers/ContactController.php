<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\Csrf;
use App\Services\Db;
use App\Services\RateLimiter;

final class ContactController
{
    private const FORM         = 'contact';
    private const MIN_HUMAN_MS = 2500;      // submissions faster than 2.5s are likely bots
    private const MAX_PER_HOUR = 5;

    public function show(): void
    {
        // Token rotation happens after success only — so pre-fill works on retries.
        $csrf = Csrf::token(self::FORM);

        $formStartedAt = (int) (microtime(true) * 1000);
        $old           = $_SESSION['_contact_old'] ?? [];
        unset($_SESSION['_contact_old']);
        $errors = $_SESSION['_contact_errors'] ?? [];
        unset($_SESSION['_contact_errors']);

        $title           = 'Contact — GeoAtlas Bangladesh';
        $metaDescription = 'Contact GeoAtlas Bangladesh about GIS projects, data, or collaborations.';

        ob_start();
        require dirname(__DIR__) . '/Views/public/contact.php';
        $content = ob_get_clean();

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        require dirname(__DIR__) . '/Views/layouts/public.php';
    }

    public function submit(): void
    {
        Csrf::startSession();

        // ---- 1. CSRF ----
        if (!Csrf::verify(self::FORM, $_POST['_csrf'] ?? null)) {
            http_response_code(422);
            $this->flashError('Your session expired. Please try again.');
            $this->redirectBack();
            return;
        }

        // A browser autofill can populate a honeypot, so preserve the message
        // and flag it for review instead of discarding its contents.
        $honeypotTriggered = !empty($_POST['contact_reference'] ?? '');

        // ---- 3. Timing gate ----
        $startedAt = (int) ($_POST['_form_started_at'] ?? 0);
        $elapsed   = (int) (microtime(true) * 1000) - $startedAt;
        if ($startedAt <= 0 || $elapsed < self::MIN_HUMAN_MS || $elapsed > 3_600_000) {
            $this->recordSpam('timing');
            $this->flashSuccess();
            $this->redirectBack();
            return;
        }

        // ---- 4. Rate limit ----
        if (RateLimiter::limited(self::MAX_PER_HOUR)) {
            http_response_code(429);
            $this->flashError('Too many submissions in a short time. Please try again later.');
            $this->redirectBack();
            return;
        }

        // ---- 5. Server-side validation ----
        $name    = $this->cleanText($_POST['name']    ?? '', 100);
        $email   = $this->cleanEmail($_POST['email']  ?? '');
        $subject = $this->cleanText($_POST['subject'] ?? '', 150);
        $message = $this->cleanText($_POST['message'] ?? '', 5000, /* preserveNewlines */ true);

        $errors = [];
        if ($name === '')                               $errors['name']    = 'Please enter your name.';
        if ($email === null || $email === '')           $errors['email']   = 'Please enter a valid email address.';
        if ($subject === '')                            $errors['subject'] = 'Please add a short subject.';
        if ($message === '')                            $errors['message'] = 'Please write a message.';
        if (mb_strlen($message) < 20)                   $errors['message'] = $errors['message'] ?? 'Message must be at least 20 characters.';

        if ($errors !== []) {
            $_SESSION['_contact_old']    = ['name' => $name, 'email' => (string) $email, 'subject' => $subject, 'message' => $message];
            $_SESSION['_contact_errors'] = $errors;
            http_response_code(422);
            $this->redirectBack();
            return;
        }

        // ---- 6. Persist ----
        try {
            $stmt = Db::pdo()->prepare(
                'INSERT INTO contact_messages
                    (name, email, subject, message, ip_hash, user_agent,
                     form_started_at, submitted_at, is_spam, spam_reason)
                 VALUES
                    (:name, :email, :subject, :message, :ip_hash, :ua,
                     :started, :submitted, :is_spam, :spam_reason)'
            );
            $stmt->execute([
                ':name'      => $name,
                ':email'     => $email,
                ':subject'   => $subject,
                ':message'   => $message,
                ':ip_hash'   => RateLimiter::ipHash(),
                ':ua'        => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                ':started'   => date('Y-m-d H:i:s', (int) ($startedAt / 1000)),
                ':submitted' => gmdate('Y-m-d H:i:s'),
                ':is_spam'   => $honeypotTriggered ? 1 : 0,
                ':spam_reason' => $honeypotTriggered ? 'honeypot' : null,
            ]);
        } catch (\Throwable $e) {
            // Do not leak DB detail to the user.
            error_log('[contact] insert failed: ' . $e->getMessage());
            http_response_code(500);
            $this->flashError('Your message could not be saved right now. Please try again later.');
            $this->redirectBack();
            return;
        }

        // Rotate token so it cannot be replayed.
        Csrf::rotate(self::FORM);
        unset($_SESSION['_contact_old'], $_SESSION['_contact_errors']);

        $this->flashSuccess();
        $this->redirectBack();
    }

    // ---------------- helpers ----------------

    private function cleanText(mixed $v, int $max, bool $preserveNewlines = false): string
    {
        if (!is_string($v)) return '';
        // Normalize line endings, strip control chars except \n and \t.
        $v = str_replace(["\r\n", "\r"], "\n", $v);
        $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $v) ?? '';
        if (!$preserveNewlines) {
            $v = preg_replace('/\s+/', ' ', $v) ?? '';
        }
        $v = trim($v);
        if (mb_strlen($v) > $max) {
            $v = mb_substr($v, 0, $max);
        }
        return $v;
    }

    private function cleanEmail(mixed $v): ?string
    {
        if (!is_string($v)) return null;
        $v = trim($v);
        if ($v === '' || strlen($v) > 255) return '';
        // Reject header-injection attempts (CR/LF) and anything filter_var dislikes.
        if (preg_match('/[\r\n]/', $v)) return null;
        $ok = filter_var($v, FILTER_VALIDATE_EMAIL);
        return $ok === false ? '' : (string) $ok;
    }

    private function recordSpam(string $reason): void
    {
        try {
            $stmt = Db::pdo()->prepare(
                'INSERT INTO contact_messages
                    (name, email, subject, message, ip_hash, user_agent,
                     form_started_at, submitted_at, is_spam, spam_reason)
                 VALUES
                    ("", "", "", "", :ip_hash, :ua, NULL, :submitted, 1, :reason)'
            );
            $stmt->execute([
                ':ip_hash'   => RateLimiter::ipHash(),
                ':ua'        => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                ':submitted' => gmdate('Y-m-d H:i:s'),
                ':reason'    => $reason,
            ]);
        } catch (\Throwable $e) {
            error_log('[contact] spam log failed: ' . $e->getMessage());
        }
    }

    private function flashSuccess(): void
    {
        flash('success',
            'Thanks — your message has been recorded. This site does not send email yet, '
            . 'so it is stored locally in the site database for review. '
            . 'See /contact for setup notes.'
        );
    }

    private function flashError(string $msg): void
    {
        flash('error', $msg);
    }

    private function redirectBack(): void
    {
        // Same-origin only; ignore external Referer if provided.
        $target = '/contact';
        header('Location: ' . url($target), true, 303);
        exit;
    }
    
}
