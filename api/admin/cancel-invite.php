<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
requireMethod('POST');
$admin = requireAdmin();
$data = requestData();
$invitationId = (int) ($data['invitation_id'] ?? 0);

if ($invitationId < 1) {
    jsonResponse(['success' => false, 'message' => 'A valid invitation is required.'], 422);
}

$pdo = database();
$find = $pdo->prepare("SELECT name, email FROM invitations WHERE id = ? AND status = 'pending'");
$find->execute([$invitationId]);
$invitation = $find->fetch();
if (!$invitation) {
    jsonResponse(['success' => false, 'message' => 'Pending invitation not found.'], 404);
}

$update = $pdo->prepare("UPDATE invitations SET status = 'cancelled' WHERE id = ?");
$update->execute([$invitationId]);
writeAuditLog((int) $admin['id'], 'Invitation cancelled', $invitation['name'] . ' (' . $invitation['email'] . ') invitation was cancelled.');

jsonResponse(['success' => true, 'message' => 'Invitation cancelled.']);
