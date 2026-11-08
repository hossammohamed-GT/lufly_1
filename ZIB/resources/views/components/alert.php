<?php
$session = session(); $success = $session->getFlash('_success'); $errors = $session->getFlash('_errors', []); ?>
<?php if ($success): ?>
    <div class="alert alert-success" role="status"><?= e($success) ?></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger" role="alert">
        <ul>
            <?php foreach ((array) $errors as $messages): ?>
                <?php foreach ((array) $messages as $message): ?>
                    <li><?= e($message) ?></li>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
