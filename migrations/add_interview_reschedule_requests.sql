CREATE TABLE IF NOT EXISTS interview_reschedule_requests (
    application_id INT PRIMARY KEY,
    requested_date DATE NOT NULL,
    requested_time TIME NOT NULL,
    reason VARCHAR(1000) NOT NULL,
    request_status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT fk_reschedule_request_application
        FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    INDEX idx_reschedule_status (request_status),
    INDEX idx_reschedule_slot (requested_date, requested_time)
);
