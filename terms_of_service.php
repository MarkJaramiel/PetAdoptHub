<?php
// === Terms of Service page ===
$page_title = "Terms of Service - PetAdoptHub";
include_once 'config/config.php';
include_once 'config/database.php';
include_once 'includes/header.php';
?>

<style>
    .terms-container {
        min-height: calc(100vh - 200px);
        background: linear-gradient(135deg, #f8f9fa 0%, #f0f2f5 100%);
        padding: 60px 20px;
    }

    .terms-content {
        max-width: 900px;
        margin: 0 auto;
        background: white;
        border-radius: 24px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
        padding: 60px;
        line-height: 1.8;
        color: #475569;
    }

    .terms-content h1 {
        font-size: 42px;
        font-weight: 800;
        color: #1a3a52;
        margin-bottom: 10px;
        text-align: center;
    }

    .terms-content .last-updated {
        text-align: center;
        color: #8b5cf6;
        font-size: 14px;
        margin-bottom: 40px;
        font-weight: 600;
    }

    .terms-content h2 {
        font-size: 22px;
        font-weight: 700;
        color: #1a3a52;
        margin-top: 40px;
        margin-bottom: 16px;
        border-left: 4px solid #ff8a5b;
        padding-left: 16px;
    }

    .terms-content h2:first-of-type {
        margin-top: 0;
    }

    .terms-content p {
        margin-bottom: 14px;
        font-size: 15px;
    }

    .terms-content ul {
        margin: 16px 0 16px 24px;
        padding: 0;
    }

    .terms-content li {
        margin-bottom: 10px;
        font-size: 15px;
    }

    .terms-content strong {
        color: #1a3a52;
        font-weight: 700;
    }

    .terms-content a {
        color: #ff8a5b;
        text-decoration: none;
        font-weight: 600;
    }

    .terms-content a:hover {
        text-decoration: underline;
    }

    .back-link {
        display: inline-block;
        margin-bottom: 30px;
        color: #ff8a5b;
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
    }

    .back-link:hover {
        text-decoration: underline;
    }

    .back-link i {
        margin-right: 6px;
    }

    @media (max-width: 768px) {
        .terms-content {
            padding: 40px 30px;
        }

        .terms-content h1 {
            font-size: 32px;
        }

        .terms-content h2 {
            font-size: 18px;
        }
    }
</style>

<div class="terms-container">
    <div class="terms-content">
        <a href="register.php" class="back-link">
            <i class="fas fa-arrow-left"></i>Back to Register
        </a>

        <h1>PetAdoptHub Terms of Service</h1>
        <div class="last-updated">Last Updated: August 31, 2026</div>

        <p>Welcome to <strong>PetAdoptHub</strong>. These Terms of Service ("Terms") govern your access to and use of the PetAdoptHub website and its services. By creating an account or using PetAdoptHub, you agree to comply with these Terms.</p>

        <h2>1. Acceptance of Terms</h2>
        <p>By registering for an account or using PetAdoptHub, you confirm that you have read, understood, and agreed to these Terms. If you do not agree with any part of these Terms, you should not use the platform.</p>

        <h2>2. About PetAdoptHub</h2>
        <p>PetAdoptHub is a web-based platform designed to help connect potential pet adopters with animal shelters and facilitate the pet adoption process. The platform may provide pet profiles, adoption applications, appointment scheduling, communication, and related shelter-management services.</p>

        <h2>3. User Accounts</h2>
        <p>Users may be required to create an account to access certain features.</p>
        <p>When creating an account, you agree to:</p>
        <ul>
            <li>Provide accurate and complete information.</li>
            <li>Keep your account information updated.</li>
            <li>Keep your password and login credentials confidential.</li>
            <li>Not use another person's account without permission.</li>
            <li>Notify the administrator if you suspect unauthorized access to your account.</li>
        </ul>
        <p>PetAdoptHub reserves the right to suspend or terminate accounts that contain false information or are used in violation of these Terms.</p>

        <h2>4. Adoption Applications</h2>
        <p>Submitting an adoption application does not guarantee that a pet will be approved for adoption.</p>
        <p>Shelter staff or authorized personnel may review applications based on their adoption policies and requirements. They may request additional information or documents, including valid identification, contact information, or other information necessary to evaluate an application.</p>
        <p>PetAdoptHub does not guarantee that a particular pet will remain available while an application is being processed.</p>

        <h2>5. Appointments and Communication</h2>
        <p>Users may be able to schedule appointments or communicate with shelter staff through the platform.</p>
        <p>Users agree to:</p>
        <ul>
            <li>Provide accurate contact information.</li>
            <li>Attend scheduled appointments when possible.</li>
            <li>Communicate respectfully with shelter staff and other users.</li>
            <li>Avoid sending abusive, threatening, misleading, or inappropriate messages.</li>
        </ul>
        <p>PetAdoptHub may restrict communication features for users who misuse the platform.</p>

        <h2>6. User Responsibilities</h2>
        <p>Users are responsible for the information and content they submit to PetAdoptHub.</p>
        <p>Users must not:</p>
        <ul>
            <li>Submit fraudulent or misleading information.</li>
            <li>Create accounts for fraudulent purposes.</li>
            <li>Impersonate another person.</li>
            <li>Upload harmful, offensive, or unlawful content.</li>
            <li>Attempt to gain unauthorized access to the system.</li>
            <li>Interfere with the operation or security of the platform.</li>
            <li>Use the platform for activities unrelated to its intended purpose.</li>
        </ul>

        <h2>7. Pet Information</h2>
        <p>Pet information, including photographs, descriptions, health information, availability, and other details, may be provided or managed by shelters or authorized personnel.</p>
        <p>Although PetAdoptHub aims to provide accurate information, pet availability and information may change. Users should verify important information with the responsible shelter before making adoption decisions.</p>

        <h2>8. Inventory and Shelter Information</h2>
        <p>Authorized shelter personnel and administrators may use PetAdoptHub to manage shelter supplies, donations, medical records, and other administrative information.</p>
        <p>Users without appropriate authorization must not access, modify, delete, or misuse administrative or shelter-management information.</p>

        <h2>9. Privacy</h2>
        <p>PetAdoptHub collects and processes certain personal information to provide its services. Our collection and use of personal information are described in the <a href="privacy_policy.php">Privacy Policy</a>.</p>
        <p>By using PetAdoptHub, you acknowledge that your information may be processed as described in the Privacy Policy.</p>

        <h2>10. System Availability</h2>
        <p>PetAdoptHub aims to keep the platform available and functional; however, temporary interruptions may occur due to maintenance, technical problems, updates, or circumstances beyond our control.</p>
        <p>PetAdoptHub does not guarantee uninterrupted or error-free access to the platform.</p>

        <h2>11. Intellectual Property</h2>
        <p>The PetAdoptHub name, system design, software, interface, logos, and other original materials may be protected by applicable intellectual property laws.</p>
        <p>Users may not copy, modify, distribute, or reproduce protected parts of the platform without appropriate authorization.</p>

        <h2>12. Account Suspension or Termination</h2>
        <p>PetAdoptHub administrators may suspend or terminate an account when necessary, including when a user:</p>
        <ul>
            <li>Violates these Terms.</li>
            <li>Provides fraudulent information.</li>
            <li>Misuses the platform.</li>
            <li>Attempts unauthorized access.</li>
            <li>Engages in harmful or abusive behavior.</li>
        </ul>
        <p>Users may also request the deletion or termination of their account, subject to applicable requirements.</p>

        <h2>13. Limitation of Responsibility</h2>
        <p>PetAdoptHub provides a platform to facilitate communication and adoption-related activities. The platform does not independently determine adoption eligibility or guarantee the behavior, health, or future circumstances of any animal.</p>
        <p>Users should communicate directly with the responsible shelter regarding important adoption decisions.</p>

        <h2>14. Changes to These Terms</h2>
        <p>PetAdoptHub may update these Terms when necessary. Updated Terms will be made available through the platform. Continued use of PetAdoptHub after changes are posted may constitute acceptance of the updated Terms.</p>

        <h2>15. Contact</h2>
        <p>If you have questions, concerns, or requests regarding these Terms, please contact the PetAdoptHub administrator through the contact information provided on the platform.</p>

        <p style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
            By using PetAdoptHub, you acknowledge that you have read and agree to these Terms of Service.
        </p>
    </div>
</div>

</body>
</html>
