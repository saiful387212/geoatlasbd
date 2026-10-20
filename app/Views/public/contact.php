<?php
declare(strict_types=1);
/** @var string $csrf */
/** @var int $formStartedAt */
/** @var array $old */
/** @var array $errors */

$val = fn(string $k) => e((string) ($old[$k] ?? ''));
$err = fn(string $k) => isset($errors[$k]) ? e($errors[$k]) : null;
?>

<section class="page-hero">
    <div class="container">
        <p class="eyebrow">Contact</p>
        <h1 class="page-hero__title">Get in touch</h1>
        <p class="page-hero__lead">
            Questions about a project, a data request, or a collaboration?
            Send a message below.
        </p>
    </div>
</section>

<section class="section">
    <div class="container contact-grid">

        <div class="contact-main">
            <form class="contact-form"
                  method="post"
                  action="<?= e(url('/contact')) ?>"
                  novalidate
                  data-contact-form>

                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="_form_started_at" value="<?= e((string) $formStartedAt) ?>">

                <!-- Honeypot: hidden from humans; bots fill it in -->
                <div class="hp-field" aria-hidden="true">
                    <label for="contact-reference">Leave this field empty</label>
                    <input type="text" id="contact-reference" name="contact_reference"
                           tabindex="-1" autocomplete="new-password">
                </div>

                <div class="form-row">
                    <div class="form-field">
                        <label for="c-name">Your name <span class="req" aria-hidden="true">*</span></label>
                        <input id="c-name" name="name" type="text" required maxlength="100"
                               value="<?= $val('name') ?>"
                               autocomplete="name"
                               aria-describedby="err-name"
                               <?= $err('name') ? 'aria-invalid="true"' : '' ?>>
                        <p class="field-error" id="err-name" role="alert"><?= (string) $err('name') ?></p>
                    </div>

                    <div class="form-field">
                        <label for="c-email">Email <span class="req" aria-hidden="true">*</span></label>
                        <input id="c-email" name="email" type="email" required maxlength="255"
                               value="<?= $val('email') ?>"
                               autocomplete="email"
                               inputmode="email"
                               aria-describedby="err-email"
                               <?= $err('email') ? 'aria-invalid="true"' : '' ?>>
                        <p class="field-error" id="err-email" role="alert"><?= (string) $err('email') ?></p>
                    </div>
                </div>

                <div class="form-field">
                    <label for="c-subject">Subject <span class="req" aria-hidden="true">*</span></label>
                    <input id="c-subject" name="subject" type="text" required maxlength="150"
                           value="<?= $val('subject') ?>"
                           aria-describedby="err-subject"
                           <?= $err('subject') ? 'aria-invalid="true"' : '' ?>>
                    <p class="field-error" id="err-subject" role="alert"><?= (string) $err('subject') ?></p>
                </div>

                <div class="form-field">
                    <label for="c-message">Message <span class="req" aria-hidden="true">*</span></label>
                    <textarea id="c-message" name="message" rows="8" required maxlength="5000"
                              aria-describedby="err-message hint-message"
                              <?= $err('message') ? 'aria-invalid="true"' : '' ?>><?= $val('message') ?></textarea>
                    <p class="field-hint" id="hint-message">A few sentences is plenty. Do not include passwords or sensitive data.</p>
                    <p class="field-error" id="err-message" role="alert"><?= (string) $err('message') ?></p>
                </div>

                <p class="form-actions">
                    <button class="btn btn--primary" type="submit">Send message</button>
                </p>

                <p class="form-status" role="status" aria-live="polite" data-form-status></p>
            </form>
        </div>

        <aside class="contact-aside" aria-label="Alternative contact details">
            <div class="aside-block">
                <h2 class="aside-block__title">Before you send</h2>
                <p>
                    This form does not send email — it stores your message in the site's
                    local database. The site administrator reviews submissions from the
                    database directly until an email service is configured.
                </p>
            </div>

            <div class="aside-block">
                <h2 class="aside-block__title">Email delivery status</h2>
                <p>
                    <strong>Not configured.</strong> To enable email delivery later,
                    the site administrator will add SMTP credentials to <code>.env</code>
                    and enable the mailer. Until then, submissions are saved, not emailed.
                </p>
            </div>

            <div class="aside-block">
                <h2 class="aside-block__title">Other ways to reach out</h2>
                <p>
                    See the <a href="<?= e(url('/about')) ?>">About page</a> for
                    email and social links once they are configured.
                </p>
            </div>
        </aside>
    </div>
</section>