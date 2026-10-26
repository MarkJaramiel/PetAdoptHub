<?php
include_once '../../config/config.php';
include_once '../../config/database.php';
include_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../applications.php');
    exit();
}

$app_id = (int)($_POST['app_id'] ?? 0);
$decision = $_POST['decision'] ?? '';
if ($app_id <= 0 || !in_array($decision, ['Approved', 'Rejected'], true)) {
    header('Location: ../applications.php?reschedule_review=error');
    exit();
}

$stmt = $conn->prepare("SELECT requested_date, requested_time, request_status FROM interview_reschedule_requests WHERE application_id = ? LIMIT 1");
$stmt->bind_param('i', $app_id);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$request || $request['request_status'] !== 'Pending') {
    header('Location: ../applications.php?reschedule_review=error');
    exit();
}

$lock_name = null;
if ($decision === 'Approved') {
    $lock_name = 'i_' . substr(hash('sha256', $request['requested_date'] . ' ' . $request['requested_time']), 0, 62);
    $lock_stmt = $conn->prepare('SELECT GET_LOCK(?, 5) AS lock_acquired');
    $lock_stmt->bind_param('s', $lock_name);
    $lock_stmt->execute();
    $lock_acquired = (int)($lock_stmt->get_result()->fetch_assoc()['lock_acquired'] ?? 0);
    $lock_stmt->close();

    if ($lock_acquired !== 1) {
        header('Location: ../applications.php?reschedule_review=error');
        exit();
    }

    $slot_stmt = $conn->prepare('SELECT COUNT(*) AS slot_count FROM applications WHERE interview_date = ? AND interview_time = ? AND id <> ?');
    $slot_stmt->bind_param('ssi', $request['requested_date'], $request['requested_time'], $app_id);
    $slot_stmt->execute();
    $slot_count = (int)($slot_stmt->get_result()->fetch_assoc()['slot_count'] ?? 0);
    $slot_stmt->close();

    if ($slot_count >= 3) {
        $release_stmt = $conn->prepare('SELECT RELEASE_LOCK(?)');
        $release_stmt->bind_param('s', $lock_name);
        $release_stmt->execute();
        $release_stmt->close();
        header('Location: ../applications.php?reschedule_review=full');
        exit();
    }
}

$conn->begin_transaction();
try {
    if ($decision === 'Approved') {
        $update_app = $conn->prepare('UPDATE applications SET interview_date = ?, interview_time = ? WHERE id = ?');
        $update_app->bind_param('ssi', $request['requested_date'], $request['requested_time'], $app_id);
        if (!$update_app->execute()) {
            throw new RuntimeException('Could not update the interview schedule.');
        }
        $update_app->close();
    }

    $review = $conn->prepare('UPDATE interview_reschedule_requests SET request_status = ?, reviewed_at = NOW() WHERE application_id = ? AND request_status = \'Pending\'');
    $review->bind_param('si', $decision, $app_id);
    if (!$review->execute() || $review->affected_rows !== 1) {
        throw new RuntimeException('Could not record the reschedule decision.');
    }
    $review->close();
    $conn->commit();
} catch (Throwable $error) {
    $conn->rollback();
    error_log('[review_reschedule_request.php] ' . $error->getMessage());
    if ($lock_name !== null) {
        $release_stmt = $conn->prepare('SELECT RELEASE_LOCK(?)');
        $release_stmt->bind_param('s', $lock_name);
        $release_stmt->execute();
        $release_stmt->close();
    }
    header('Location: ../applications.php?reschedule_review=error');
    exit();
}

if ($lock_name !== null) {
    $release_stmt = $conn->prepare('SELECT RELEASE_LOCK(?)');
    $release_stmt->bind_param('s', $lock_name);
    $release_stmt->execute();
    $release_stmt->close();
}

if ($decision === 'Approved') {
    syncAdoptionHistoryRecord($app_id, 'Under Interview', null, (int)$_SESSION['user_id'], 'Reschedule request approved', $request['requested_date']);
}

header('Location: ../applications.php?reschedule_review=' . strtolower($decision));
exit();
