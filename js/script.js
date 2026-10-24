// Mobile navigation toggle
// toggles the mobile menu open/close states on hamburger click.
const hamburger = document.querySelector('.hamburger');
const navLinks = document.querySelector('.nav-links');

if (hamburger) {
    hamburger.addEventListener('click', () => {
        navLinks.classList.toggle('active');
        hamburger.classList.toggle('active');
    });
}

// Close mobile menu when clicking on a link
// ensures navigation goes away after selecting item on small screens.
document.querySelectorAll('.nav-links a').forEach(link => {
    link.addEventListener('click', () => {
        navLinks.classList.remove('active');
        hamburger.classList.remove('active');
    });
});

// User dropdown menu interaction
// Toggle profile dropdown in header and close when clicking outside.
const userMenuBtn = document.getElementById('userMenuBtn');
const userDropdown = document.getElementById('userDropdown');

if (userMenuBtn && userDropdown) {
    userMenuBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        userDropdown.style.display = userDropdown.style.display === 'block' ? 'none' : 'block';
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', () => {
        userDropdown.style.display = 'none';
    });

    // Prevent dropdown from closing when clicking inside it
    userDropdown.addEventListener('click', (e) => {
        e.stopPropagation();
    });
}

// Form validation
// Globally ensures required fields are not empty before submit.
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', (e) => {
        // Basic validation can be added here
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.style.borderColor = 'red';
            } else {
                field.style.borderColor = '';
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });
});

// Newsletter form handling
// Lightweight placeholder for newsletter subscription workflow.
document.querySelector('.newsletter-form')?.addEventListener('submit', (e) => {
    e.preventDefault();
    const emailInput = e.target.querySelector('input[type="email"]');
    
    if (emailInput.value.trim()) {
        // Here you would typically send the email to your server
        alert('Thank you for subscribing to our newsletter!');
        emailInput.value = '';
    }
});

// Smooth scrolling for anchor links
// Animates anchor link jumps to make page easier to navigate.
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth'
            });
        }
    });
});

// Pet card hover effect
// Adds a small lift animation for cards when hovered.
document.querySelectorAll('.pet-card').forEach(card => {
    card.addEventListener('mouseenter', () => {
        card.style.transform = 'translateY(-5px)';
    });
    
    card.addEventListener('mouseleave', () => {
        card.style.transform = 'translateY(0)';
    });
});

// Password visibility toggle
// Adds visibility icons for password inputs and toggles type between text/password.
document.querySelectorAll('input[type="password"]').forEach(passwordInput => {
    const toggleIcon = document.createElement('i');
    toggleIcon.className = 'fas fa-eye';
    toggleIcon.style.position = 'absolute';
    toggleIcon.style.right = '10px';
    toggleIcon.style.top = '50%';
    toggleIcon.style.transform = 'translateY(-50%)';
    toggleIcon.style.cursor = 'pointer';
    
    passwordInput.style.position = 'relative';
    passwordInput.parentNode.style.position = 'relative';
    passwordInput.parentNode.appendChild(toggleIcon);
    
    toggleIcon.addEventListener('click', () => {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        toggleIcon.className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
    });
});

// Google Sign In (placeholder - would need actual Google API integration)
document.querySelector('.google-signin')?.addEventListener('click', () => {
    alert('Google Sign In would be implemented here with Google API integration.');
});

// Dynamic form field updates
document.querySelectorAll('select').forEach(select => {
    select.addEventListener('change', () => {
        // You can add logic here to show/hide form fields based on selection
        console.log('Selection changed:', select.value);
    });
});