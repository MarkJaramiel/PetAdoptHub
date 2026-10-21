<?php
// === Privacy Policy page ===
$page_title = "Privacy Policy - PetAdoptHub";
include_once 'config/config.php';
include_once 'config/database.php';
include_once 'includes/header.php';
?>

<style>
    .privacy-container {
        min-height: calc(100vh - 200px);
        background: linear-gradient(135deg, #f8f9fa 0%, #f0f2f5 100%);
        padding: 60px 20px;
    }

    .privacy-content {
        max-width: 900px;
        margin: 0 auto;
        background: white;
        border-radius: 24px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
        padding: 60px;
        line-height: 1.8;
        color: #475569;
    }

    .privacy-content h1 {
        font-size: 42px;
        font-weight: 800;
        color: #1a3a52;
        margin-bottom: 10px;
        text-align: center;
    }

    .privacy-content .last-updated {
        text-align: center;
        color: #8b5cf6;
        font-size: 14px;
        margin-bottom: 40px;
        font-weight: 600;
    }

    .privacy-content h2 {
        font-size: 22px;
        font-weight: 700;
        color: #1a3a52;
        margin-top: 40px;
        margin-bottom: 16px;
        border-left: 4px solid #ff8a5b;
        padding-left: 16px;
    }

    .privacy-content h2:first-of-type {
        margin-top: 0;
    }

    .privacy-content p {
        margin-bottom: 14px;
        font-size: 15px;
    }

    .privacy-content ul {
        margin: 16px 0 16px 24px;
        padding: 0;
    }

    .privacy-content li {
        margin-bottom: 10px;
        font-size: 15px;
    }

    .privacy-content strong {
        color: #1a3a52;
        font-weight: 700;
    }

    .privacy-content a {
        color: #ff8a5b;
        text-decoration: none;
        font-weight: 600;
    }

    .privacy-content a:hover {
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
        .privacy-content {
            padding: 40px 30px;
        }

        .privacy-content h1 {
            font-size: 32px;
        }

        .privacy-content h2 {
            font-size: 18px;
        }
    }
</style>

<div class="privacy-container">
    <div class="privacy-content">
        <a href="register.php" class="back-link">
            <i class="fas fa-arrow-left"></i>Back to Register
        </a>

        <h1>PetAdoptHub Privacy Policy</h1>
        <div class="last-updated">Last Updated: August 31, 2026</div>

        <p>PetAdoptHub respects your privacy and is committed to protecting the personal information you provide when using our platform. This Privacy Policy explains what information we may collect, how we use it, how we protect it, and what choices you may have regarding your information.</p>

        <h2>1. Information We Collect</h2>
        <p>Depending on how you use PetAdoptHub, we may collect the following information:</p>

        <h3 style="font-size: 16px; font-weight: 600; margin-top: 20px; margin-bottom: 10px;">Account Information</h3>
        <ul>
            <li>Full name</li>
            <li>Email address</li>
            <li>Phone number</li>
            <li>Password or account credentials</li>
            <li>Profile information</li>
        </ul>

        <h3 style="font-size: 16px; font-weight: 600; margin-top: 20px; margin-bottom: 10px;">Adoption Information</h3>
        <ul>
            <li>Adoption applications</li>
            <li>Preferred pets</li>
            <li>Adoption-related responses and information</li>
            <li>Appointment details</li>
            <li>Communication with shelter personnel</li>
            <li>Adoption status and history</li>
        </ul>

        <h3 style="font-size: 16px; font-weight: 600; margin-top: 20px; margin-bottom: 10px;">Verification Information</h3>
        <p>If required by the shelter, users may be asked to provide information or documents for identity verification, such as a valid government-issued identification document.</p>
        <p>Users should only provide information requested through the official PetAdoptHub platform or by authorized shelter personnel.</p>

        <h3 style="font-size: 16px; font-weight: 600; margin-top: 20px; margin-bottom: 10px;">Uploaded Content</h3>
        <p>Users may upload photographs, documents, messages, and other information related to their account or adoption application.</p>

        <h3 style="font-size: 16px; font-weight: 600; margin-top: 20px; margin-bottom: 10px;">Technical Information</h3>
        <p>The platform may automatically collect limited technical information, such as:</p>
        <ul>
            <li>IP address</li>
            <li>Browser type</li>
            <li>Device information</li>
            <li>Date and time of system access</li>
            <li>Pages or features accessed</li>
            <li>Basic system activity logs</li>
        </ul>

        <h2>2. How We Use Your Information</h2>
        <p>PetAdoptHub may use collected information to:</p>
        <ul>
            <li>Create and manage user accounts.</li>
            <li>Process and manage adoption applications.</li>
            <li>Facilitate communication between adopters and shelters.</li>
            <li>Schedule adoption appointments.</li>
            <li>Verify user identity when required.</li>
            <li>Maintain adoption records and history.</li>
            <li>Provide notifications and system updates.</li>
            <li>Improve the functionality and usability of the platform.</li>
            <li>Protect the security of the system.</li>
            <li>Detect and prevent unauthorized or fraudulent activity.</li>
            <li>Comply with applicable legal requirements.</li>
        </ul>

        <h2>3. Sharing of Information</h2>
        <p>PetAdoptHub does not intend to sell users' personal information.</p>
        <p>Personal information may be accessible to authorized shelter personnel, administrators, or other authorized individuals when necessary to perform adoption-related or system-management activities.</p>
        <p>Information may also be disclosed when required by law, legal process, or a legitimate request from an authorized government authority.</p>

        <h2>4. Adoption Applications and Shelter Access</h2>
        <p>Information submitted as part of an adoption application may be viewed by authorized shelter personnel responsible for evaluating and processing applications.</p>
        <p>Users should understand that information provided for adoption purposes may be necessary for shelters to determine eligibility and coordinate the adoption process.</p>

        <h2>5. Protection of Personal Information</h2>
        <p>PetAdoptHub takes reasonable measures to protect personal information against unauthorized access, alteration, disclosure, or destruction.</p>
        <p>Security measures may include account authentication, access restrictions, password protection, and appropriate technical safeguards.</p>
        <p>However, no online system can guarantee complete security. Users should also protect their passwords and avoid sharing account credentials.</p>

        <h2>6. Passwords</h2>
        <p>User passwords should be kept confidential.</p>
        <p>PetAdoptHub administrators should not request a user's password through ordinary communication. If you believe your account has been compromised, you should report the issue to the system administrator and change your password when possible.</p>

        <h2>7. Data Retention</h2>
        <p>PetAdoptHub may retain personal information for as long as reasonably necessary to provide services, maintain adoption records, fulfill administrative requirements, resolve disputes, maintain system security, or comply with applicable laws and regulations.</p>
        <p>The retention period may vary depending on the type and purpose of the information.</p>

        <h2>8. User Rights</h2>
        <p>Subject to applicable laws and system policies, users may have rights regarding their personal information, including the right to:</p>
        <ul>
            <li>Request access to their personal information.</li>
            <li>Request correction of inaccurate information.</li>
            <li>Request deletion of information when applicable.</li>
            <li>Withdraw consent where processing is based on consent.</li>
            <li>Raise concerns regarding the handling of their personal information.</li>
        </ul>
        <p>Requests may be subject to identity verification and applicable legal or administrative requirements.</p>

        <h2>9. Cookies and Similar Technologies</h2>
        <p>PetAdoptHub may use cookies or similar technologies to support login sessions, remember user preferences, maintain security, and improve the functionality of the website.</p>
        <p>Users may be able to manage cookies through their browser settings. Disabling certain cookies may affect some website features.</p>

        <h2>10. Children's Privacy</h2>
        <p>PetAdoptHub is not intended to collect personal information from children without appropriate authorization or consent where required by applicable law.</p>
        <p>Users should provide accurate information about their age when required by the registration process.</p>

        <h2>11. Third-Party Services</h2>
        <p>PetAdoptHub may use third-party services or technologies to support hosting, security, communication, analytics, or other system functions.</p>
        <p>Where third-party services process personal information, appropriate measures should be taken to ensure that information is handled in accordance with applicable privacy requirements.</p>

        <h2>12. Changes to This Privacy Policy</h2>
        <p>PetAdoptHub may update this Privacy Policy when necessary. Changes will be posted on the platform with an updated "Last Updated" date.</p>
        <p>Users are encouraged to review this Privacy Policy periodically.</p>

        <h2>13. Contact Us</h2>
        <p>If you have questions, concerns, or requests regarding this Privacy Policy or your personal information, please contact the PetAdoptHub administrator through the contact information provided on the platform.</p>

        <p style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e7eb; font-weight: 600;">
            By creating an account or using PetAdoptHub, you acknowledge that you have read and understood this Privacy Policy.
        </p>
    </div>
</div>

</body>
</html>
