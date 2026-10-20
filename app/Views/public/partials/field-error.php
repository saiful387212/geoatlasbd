<?php
declare(strict_types=1);
/** @var string $fieldId */
/** @var ?string $message */
if ($message === null) return;
?>
<p class="field-error" id="<?= e($fieldId) ?>" role="alert"><?= e($message) ?></p>