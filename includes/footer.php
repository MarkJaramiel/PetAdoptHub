<section id="about-us-section" style="background-color: white; padding: 80px 20px; margin-bottom: 0;">
    <!-- About section with mission statement and value proposition -->
    <div class="container" style="max-width: 100%; margin: 0 auto; padding: 0 50px;">
        <div style="max-width: 900px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 50px;">
                <h2 style="color: #1a3a52; font-size: 42px; font-weight: 700; margin: 0 0 15px 0;">
                    <?php echo htmlspecialchars(getSiteSetting('about_heading', 'About PetAdoptHub')); ?>
                </h2>
                <p style="color: #666; font-size: 18px; line-height: 1.6; margin: 0;">
                    Connecting loving homes with pets in need
                </p>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center; margin-bottom: 50px;">
                <div>
                    <h3 style="color: #1a3a52; font-size: 24px; font-weight: 700; margin: 0 0 20px 0;">
                        <?php echo htmlspecialchars(getSiteSetting('mission_heading', 'Our Mission')); ?>
                    </h3>
                    <p style="color: #555; font-size: 16px; line-height: 1.8; margin: 0 0 20px 0;">
                        <?php echo htmlspecialchars(getSiteSetting('mission_text_1', 'PetAdoptHub is dedicated to connecting rescued and abandoned pets with loving families. We believe every pet deserves a second chance at happiness and a caring home.')); ?>
                    </p>
                    <p style="color: #555; font-size: 16px; line-height: 1.8; margin: 0;">
                        <?php echo htmlspecialchars(getSiteSetting('mission_text_2', 'Our platform simplifies the adoption process by partnering with shelters and volunteers to promote responsible pet adoption and give hope to countless animals in need.')); ?>
                    </p>
                </div>
                <div>
                    <h3 style="color: #1a3a52; font-size: 24px; font-weight: 700; margin: 0 0 20px 0;">
                        <?php echo htmlspecialchars(getSiteSetting('what_we_do_heading', 'What We Do')); ?>
                    </h3>
                    <ul style="list-style: none; margin: 0; padding: 0;">
                        <li style="color: #555; font-size: 16px; line-height: 2; display: flex; align-items: center; gap: 12px;">
                            <img src="images/iconcheck.png" alt="Check" style="width: 22px; height: 22px; object-fit: contain;">
                            <?php echo htmlspecialchars(getSiteSetting('what_we_do_item_1', 'Connect pets with loving families')); ?>
                        </li>
                        <li style="color: #555; font-size: 16px; line-height: 2; display: flex; align-items: center; gap: 12px;">
                            <img src="images/iconcheck.png" alt="Check" style="width: 22px; height: 22px; object-fit: contain;">
                            <?php echo htmlspecialchars(getSiteSetting('what_we_do_item_2', 'Partner with local shelters')); ?>
                        </li>
                        <li style="color: #555; font-size: 16px; line-height: 2; display: flex; align-items: center; gap: 12px;">
                            <img src="images/iconcheck.png" alt="Check" style="width: 22px; height: 22px; object-fit: contain;">
                            <?php echo htmlspecialchars(getSiteSetting('what_we_do_item_3', 'Provide adoption guidance')); ?>
                        </li>
                        <li style="color: #555; font-size: 16px; line-height: 2; display: flex; align-items: center; gap: 12px;">
                            <img src="images/iconcheck.png" alt="Check" style="width: 22px; height: 22px; object-fit: contain;">
                            <?php echo htmlspecialchars(getSiteSetting('what_we_do_item_4', 'Promote pet welfare')); ?>
                        </li>
                        <li style="color: #555; font-size: 16px; line-height: 2; display: flex; align-items: center; gap: 12px;">
                            <img src="images/iconcheck.png" alt="Check" style="width: 22px; height: 22px; object-fit: contain;">
                            <?php echo htmlspecialchars(getSiteSetting('what_we_do_item_5', 'Support animal rescue initiatives')); ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<footer style="background-color: #f8f9fa; border-top: 1px solid #ddd; padding: 80px 0 40px; margin-top: 0;">
    <div class="container" style="max-width: 100%; margin: 0 auto; padding: 0 50px;">
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 60px 0; margin-bottom: 60px; align-items: flex-start;">
            <!-- Footer main content: About + quick links + social links -->
            <div>
                <h3 style="color: #1a3a52; font-size: 22px; font-weight: 700; margin-bottom: 25px;">
                    <?php echo htmlspecialchars(getSiteSetting('footer_about_heading', 'About PetAdoptHub')); ?>
                </h3>
                <p style="color: #555; font-size: 15px; line-height: 1.8; margin: 0;">
                    <?php echo htmlspecialchars(getSiteSetting('footer_about_text', 'PetAdoptHub is a platform dedicated to connecting rescued pets with loving families. We work with shelters and volunteers to promote responsible pet adoption.')); ?>
                </p>
            </div>

            <!-- Quick Links and Follow Us Section (Nested Grid) -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0 0;">
                <!-- Quick Links Section -->
                <!-- Includes in-page and external navigation links for common actions -->
                <div>
                    <h3 style="color: #1a3a52; font-size: 16px; font-weight: 700; margin-bottom: 25px; letter-spacing: 0.5px;">
                        <?php echo htmlspecialchars(getSiteSetting('footer_quick_links_label', 'Quick Link')); ?>
                    </h3>
                    <ul style="list-style: none; margin: 0; padding: 0;">
                        <li style="margin-bottom: 15px;">
                            <a href="index.php" style="color: #555; text-decoration: none; font-size: 15px; transition: all 0.3s ease;">
                                Adopt Pets
                            </a>
                        </li>
                        <li style="margin-bottom: 15px;">
                            <button id="donateBtnFooter" class="donate-btn" type="button" style="color: #555; background: none; border: none; font-size: 15px; cursor: pointer; transition: all 0.3s ease; padding: 0;">Make a Donation</button>
                        </li>
                        <li>
                            <a href="contact.php" style="color: #555; text-decoration: none; font-size: 15px; transition: all 0.3s ease;">
                                Contact US
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Follow Us Section -->
                <!-- Social links for external shelter profiles -->
                <div>
                    <h3 style="color: #1a3a52; font-size: 16px; font-weight: 700; margin-bottom: 25px; letter-spacing: 0.5px;">
                        Follow Us
                    </h3>
                    <div style="display: flex; flex-direction: column; gap: 18px;">
                        <a href="https://www.facebook.com/parashelter.ph" target="_blank" rel="noopener noreferrer" style="display: flex; align-items: center; gap: 12px; color: #555; text-decoration: none; font-size: 15px; transition: all 0.3s ease;">
                            <img src="images/iconfacebook.jpg" alt="Facebook" style="width: 24px; height: 24px; object-fit: contain;">
                            <span>Facebook</span>
                        </a>
                        <a href="https://www.instagram.com/parashelter.ph" target="_blank" rel="noopener noreferrer" style="display: flex; align-items: center; gap: 12px; color: #555; text-decoration: none; font-size: 15px; transition: all 0.3s ease;">
                            <img src="images/iconinsta.png" alt="Instagram" style="width: 32px; height: 32px; object-fit: contain;">
                            <span>Instagram</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div style="border-top: 1px solid #ddd; padding-top: 30px; display: flex; justify-content: space-between; align-items: center;">
            <p style="color: #888; font-size: 13px; margin: 0;">
                &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.
            </p>

        </div>
    </div>
</footer>
</footer>

<!-- Donation Modal (Styled for Footer) -->
<!-- Popup shown when top-level Donate button in footer is clicked -->
<div id="donationModal" class="modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; overflow:auto; background:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background:#222 url('images/bg.jpeg') center/cover no-repeat; margin:7% auto; padding:0; border-radius:18px; max-width:420px; position:relative; box-shadow:0 8px 32px rgba(0,0,0,0.18); overflow:hidden;">
        <span id="closeDonationModal" style="position:absolute; top:18px; right:22px; font-size:28px; font-weight:bold; color:#fff; cursor:pointer; text-shadow:0 2px 8px #000; z-index:2;">&times;</span>
        <div style="background:rgba(0,0,0,0.68); padding:36px 28px 18px 28px;">
            <h2 style="color:#fff; font-size:22px; font-weight:800; margin-bottom:18px; text-shadow:0 2px 8px #000;">Our Most Urgent Needs:</h2>
            <ul style="color:#eee; font-size:16px; margin-bottom:18px; padding-left:22px;">
                <li>Rice</li>
                <li>Dog Food</li>
                <li>Cat Food</li>
                <li>Antibiotics</li>
                <li>Vitamins</li>
            </ul>
            <div style="color:#fff; font-size:18px; font-weight:700; margin-bottom:10px; text-shadow:0 2px 8px #000;">Even the smallest donation matters.</div>
            <div style="color:#ccc; font-size:15px; margin-bottom:18px;">Thank you for being part of our mission.<br>Let’s give our rescues a hopeful life.</div>
        </div>
        <div style="background:rgba(245, 243, 241, 0.93); padding:22px 18px 18px 18px; border-radius:0 0 18px 18px;">
            <div style="font-weight:800; color:#444; font-size:18px; margin-bottom:8px;">How You Can Help:</div>
            <div style="color:#333; font-size:15px; margin-bottom:6px;">Donate in-kind (Pulo 4, Isla, Barangay Adlas Dasmarinas, Cavite)</div>
            <div style="color:#222; font-size:16px; font-weight:700; margin-bottom:8px;">GCash: <span style="color:#1a3a52;">09951279006</span> – Juliene Guillermo</div>
        </div>
    </div>
</div>
<script>
// Donation modal behavior: open/close handlers.
// Uses click outside modal to dismiss as usability improvement.
document.addEventListener('DOMContentLoaded', function() {
    function showDonationModal() {
        document.getElementById('donationModal').style.display = 'block';
    }
    function hideDonationModal() {
        document.getElementById('donationModal').style.display = 'none';
    }
    var btnFooter = document.getElementById('donateBtnFooter');
    var closeBtn = document.getElementById('closeDonationModal');
    if (btnFooter) btnFooter.addEventListener('click', showDonationModal);
    if (closeBtn) closeBtn.addEventListener('click', hideDonationModal);
    window.onclick = function(event) {
        var modal = document.getElementById('donationModal');
        if (event.target == modal) {
            hideDonationModal();
        }
    }
});
</script>
<script src="js/script.js"></script>
</body>
</html>