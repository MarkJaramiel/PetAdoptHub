<?php
// Set interview details (date, time, type, and optional meeting link) for an application.
// This endpoint updates the applications table with scheduled interview information.
include_once '../../config/config.php';
include_once '../../config/database.php';
include_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Extract and validate interview scheduling parameters from POST
    $app_id = isset($_POST['app_id']) ? (int)$_POST['app_id'] : 0;
    $interview_type = isset($_POST['interview_type']) ? trim($_POST['interview_type']) : '';
    $interview_date = isset($_POST['interview_date']) ? trim($_POST['interview_date']) : '';
    $interview_time = isset($_POST['interview_time']) ? trim($_POST['interview_time']) : '';
    $meeting_link = isset($_POST['meeting_link']) ? trim($_POST['meeting_link']) : '';

    // Only proceed if all required fields are present
    $valid_date = DateTime::createFromFormat('Y-m-d', $interview_date);
    $valid_time = DateTime::createFromFormat('H:i', $interview_time);
    if ($app_id && in_array($interview_type, ['Online', 'Face-to-Face'], true) && $valid_date && $valid_date->format('Y-m-d') === $interview_date && $valid_time && $valid_time->format('H:i') === $interview_time) {
        $lock_name = 'i_' . substr(hash('sha256', $interview_date . ' ' . $interview_time), 0, 62);
        $lock_stmt = $conn->prepare('SELECT GET_LOCK(?, 5) AS lock_acquired');
        $lock_stmt->bind_param('s', $lock_name);
        $lock_stmt->execute();
        $lock_acquired = (int)($lock_stmt->get_result()->fetch_assoc()['lock_acquired'] ?? 0);
        $lock_stmt->close();

        if ($lock_acquired !== 1) {
            header('Location: ../applications.php?interview_set=0');
            exit;
        }

        $slot_stmt = $conn->prepare('SELECT COUNT(*) AS slot_count FROM applications WHERE interview_date = ? AND interview_time = ? AND id <> ?');
        $slot_stmt->bind_param('ssi', $interview_date, $interview_time, $app_id);
        $slot_stmt->execute();
        $slot_count = (int)($slot_stmt->get_result()->fetch_assoc()['slot_count'] ?? 0);
        $slot_stmt->close();

        if ($slot_count >= 3) {
            $release_stmt = $conn->prepare('SELECT RELEASE_LOCK(?)');
            $release_stmt->bind_param('s', $lock_name);
            $release_stmt->execute();
            $release_stmt->close();
            header('Location: ../applications.php?interview_set=full');
            exit;
        }

        // Check for backward compatibility: does the applications table have a meeting_link column?
        // This allows graceful degradation if the schema hasn't been migrated yet.
        $check_column = $conn->query("SHOW COLUMNS FROM applications LIKE 'meeting_link'");
        $has_meeting_link_column = $check_column->num_rows > 0;

        // Update interview details in the applications table
        // Conditionally include meeting_link only if: column exists AND interview is Online AND link is provided
        if ($has_meeting_link_column && $interview_type === 'Online' && !empty($meeting_link)) {
            // Online interview with meeting link support - store the meeting URL
            $stmt = $conn->prepare("UPDATE applications SET interview_type=?, interview_date=?, interview_time=?, meeting_link=? WHERE id=?");
            $stmt->bind_param("ssssi", $interview_type, $interview_date, $interview_time, $meeting_link, $app_id);
        } else {
            // In-person interview or legacy schema without meeting_link column
            $stmt = $conn->prepare("UPDATE applications SET interview_type=?, interview_date=?, interview_time=? WHERE id=?");
            $stmt->bind_param("sssi", $interview_type, $interview_date, $interview_time, $app_id);
        }

        // Execute update and redirect with status indicator
        if ($stmt->execute()) {
            $stmt->close();
            $release_stmt = $conn->prepare('SELECT RELEASE_LOCK(?)');
            $release_stmt->bind_param('s', $lock_name);
            $release_stmt->execute();
            $release_stmt->close();
            $synced = syncAdoptionHistoryRecord($app_id, 'Under Interview', null, $_SESSION['user_id'] ?? 0, 'Interview scheduled', $interview_date, null, null, null);
            if (!$synced) {
                error_log("[set_interview.php] syncAdoptionHistoryRecord failed for app_id={$app_id}");
            }
            header("Location: ../applications.php?interview_set=1");
            exit;
        } else {
            $stmt->close();
            $release_stmt = $conn->prepare('SELECT RELEASE_LOCK(?)');
            $release_stmt->bind_param('s', $lock_name);
            $release_stmt->execute();
            $release_stmt->close();
            // Failure: redirect with failure flag for error display
            header("Location: ../applications.php?interview_set=0");
            exit;
        }
    } else {
        // Validation failed: missing required parameters
        header("Location: ../applications.php?interview_set=0");
        exit;
    }
} else {
    // Non-POST request: redirect to applications list
    header("Location: ../applications.php");
    exit;
}
