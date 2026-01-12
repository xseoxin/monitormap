<?php
/**
 * Delete Phrase
 */

define('APP_ROOT', __DIR__);
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth_check.php';

$currentUser = Auth::user();
$userId = $currentUser['id'];

// Validate CSRF token
validateCsrf();

$phraseId = (int)input('id');

try {
    $phraseModel = new Phrase();

    if ($phraseModel->delete($phraseId, $userId)) {
        setFlash('success', MSG_SUCCESS_PHRASE_DELETED);
    } else {
        setFlash('error', 'Failed to delete phrase');
    }
} catch (Exception $e) {
    setFlash('error', 'Error: ' . $e->getMessage());
}

redirect('/phrases.php');
