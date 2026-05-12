<?php
/**
 * Azure Sands Resort - Main Frontend
 */
require_once __DIR__ . '/includes/common.php';
$user     = getCurrentUser();
$isLogged = isLoggedIn();
$csrf     = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Azure Sands Resort | Luxury Beachfront Experience</title>
    <meta name="description" content="Azure Sands Resort – unparalleled luxury, world-class amenities, and impeccable service on the beachfront.">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Preloader -->
    <div class="preloader">
        <div class="loader">
            <div class="wave"></div>
            <div class="wave"></div>
            <div class="wave"></div>
        </div>
    </div>

    <!-- Header & Navigation -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <a href="#home" class="logo">
                    <img src="assets/images/logo.jfif" alt="Azure Sands Resort">
                    <span class="logo-text">Azure Sands</span>
                </a>
                
                <div class="nav-menu" id="navMenu">
                    <ul class="nav-list">
                        <li><a href="#home" class="nav-link active">Home</a></li>
                        <li><a href="#rooms-section" class="nav-link">Rooms & Suites</a></li>
                        <li><a href="#dining-section" class="nav-link">Dining</a></li>
                        <li><a href="#about" class="nav-link">About</a></li>
                        <li><a href="#contact" class="nav-link">Contact</a></li>
                        <li><a href="#" class="nav-link" id="openMembership">Membership</a></li>
                    </ul>
                    
                    <div class="nav-actions">
                        <a href="#" class="btn-secondary" id="openBooking">Book Now</a>
                        <div class="user-dropdown">
                            <button class="user-toggle" id="userToggle">
                                <?php if ($isLogged): ?>
                                    <span class="user-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></span>
                                <?php else: ?>
                                    <i class="bx bx-user-circle"></i>
                                <?php endif; ?>
                            </button>
                            <div class="dropdown-content" id="userDropdown">
                                <?php if ($isLogged): ?>
                                    <div class="dropdown-user-info">
                                        <span class="dropdown-user-name"><?= sanitize($user['full_name']) ?></span>
                                        <span class="dropdown-user-tier"><?= sanitize($user['membership_tier']) ?> Member</span>
                                    </div>
                                    <a href="memberships/dashboard.php"><i class="bx bx-user-circle"></i> My Dashboard</a>
                                    <a href="#" id="openBookingDropdown"><i class="bx bxs-bed"></i> My Bookings</a>
                                    <a href="#" id="openOrderDropdown"><i class="bx bx-restaurant"></i> My Orders</a>
                                    <a href="#" id="openApplicationDropdown"><i class="bx bx-briefcase"></i> Careers</a>
                                    <a href="#" id="logoutBtn"><i class="bx bx-log-out"></i> Sign Out</a>
                                <?php else: ?>
                                    <a href="#" id="openBookingDropdown"><i class="bx bxs-bed"></i> My Bookings</a>
                                    <a href="#" id="openOrderDropdown"><i class="bx bx-restaurant"></i> My Orders</a>
                                    <a href="#" id="openApplicationDropdown"><i class="bx bx-briefcase"></i> Careers</a>
                                    <a href="#" id="openMembershipDropdown"><i class="bx bx-log-in"></i> Sign In</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button class="hamburger" id="hamburger" aria-label="Toggle navigation">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-overlay"></div>
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">Experience Unparalleled Luxury</h1>
                <p class="hero-subtitle">Azure Sands Resort offers breathtaking ocean views, world-class amenities, and impeccable service for the perfect getaway.</p>
                <div class="hero-buttons">
                    <a href="#" class="btn-primary" id="exploreFood">Explore Food Items</a>
                    <a href="#rooms-section" id="exroom" class="btn-secondary">Explore Rooms</a>
                </div>
                <div class="hero-scroll">
                    <a href="#rooms-section" aria-label="Scroll down">
                        <i class="bx bx-chevron-down"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Rooms Section -->
    <section class="section rooms-section" id="rooms-section">
        <div class="container">
            <div class="section-header">
                <span class="section-subtitle">Luxury Accommodation</span>
                <h2 class="section-title">Featured <span class="highlight">Rooms</span></h2>
                <p class="section-description">Each room is designed with elegance and comfort, offering premium amenities and stunning views.</p>
            </div>
            
            <div class="rooms-grid">
                <!-- Room 1 -->
                <div class="room-card fade-in">
                    <div class="room-image">
                        <img src="assets/images/14.jpg" alt="Deluxe Ocean View Room" loading="lazy">
                        <div class="room-badge">Popular</div>
                        <button class="wishlist-btn" aria-label="Add to wishlist"><i class="bx bxs-heart"></i></button>
                    </div>
                    <div class="room-content">
                        <h3 class="room-title">Deluxe Ocean View</h3>
                        <p class="room-description">Spacious room with panoramic ocean views, king-sized bed, and modern amenities.</p>
                        <div class="room-features">
                            <span><i class="bx bxs-bed"></i> King Bed</span>
                            <span><i class="bx bxs-swim"></i> Pool Access</span>
                            <span><i class="bx bx-wifi"></i> Free WiFi</span>
                        </div>
                        <div class="room-footer">
                            <div class="room-price"><span class="price">$199</span><span class="period">/ night</span></div>
                            <a href="#" class="btn-room open-booking" data-room="1001">Book Now</a>
                        </div>
                    </div>
                </div>
                
                <!-- Room 2 -->
                <div class="room-card fade-in">
                    <div class="room-image">
                        <img src="assets/images/15.jpg" alt="Executive Suite" loading="lazy">
                        <div class="room-badge">Best Seller</div>
                        <button class="wishlist-btn" aria-label="Add to wishlist"><i class="bx bxs-heart"></i></button>
                    </div>
                    <div class="room-content">
                        <h3 class="room-title">Executive Suite</h3>
                        <p class="room-description">Luxurious suite with separate living area, premium furnishings, and exclusive amenities.</p>
                        <div class="room-features">
                            <span><i class="bx bxs-bed"></i> King Bed</span>
                            <span><i class="bx bxs-bath"></i> Jacuzzi</span>
                            <span><i class="bx bx-coffee"></i> Breakfast</span>
                        </div>
                        <div class="room-footer">
                            <div class="room-price"><span class="price">$299</span><span class="period">/ night</span></div>
                            <a href="#" class="btn-room open-booking" data-room="1002">Book Now</a>
                        </div>
                    </div>
                </div>
                
                <!-- Room 3 -->
                <div class="room-card fade-in">
                    <div class="room-image">
                        <img src="assets/images/16.jpg" alt="Presidential Suite" loading="lazy">
                        <div class="room-badge">Luxury</div>
                        <button class="wishlist-btn" aria-label="Add to wishlist"><i class="bx bxs-heart"></i></button>
                    </div>
                    <div class="room-content">
                        <h3 class="room-title">Presidential Suite</h3>
                        <p class="room-description">Ultimate luxury with private balcony, butler service, and premium amenities.</p>
                        <div class="room-features">
                            <span><i class="bx bxs-bed"></i> Super King</span>
                            <span><i class="bx bxs-spa"></i> Private Spa</span>
                            <span><i class="bx bx-car"></i> Limo Service</span>
                        </div>
                        <div class="room-footer">
                            <div class="room-price"><span class="price">$499</span><span class="period">/ night</span></div>
                            <a href="#" class="btn-room open-booking" data-room="1003">Book Now</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="section-cta">
                <a href="#" class="btn-primary" id="viewAllRooms">View All Rooms</a>
            </div>
        </div>
    </section>

    <!-- Featured Dining Section -->
    <section class="section dining-section" id="dining-section">
        <div class="container">
            <div class="section-header">
                <span class="section-subtitle">Culinary Excellence</span>
                <h2 class="section-title">Featured <span class="highlight">Dining</span></h2>
                <p class="section-description">Experience world-class cuisine prepared by our award-winning chefs using the freshest local ingredients.</p>
            </div>
            
            <div class="food-grid">
                <div class="food-card fade-in">
                    <div class="food-image">
                        <img src="assets/images/f1.jpg" alt="Signature Biryani" loading="lazy">
                        <div class="food-overlay"><button class="order-btn open-order">Order Now</button></div>
                    </div>
                    <div class="food-content">
                        <div class="food-header">
                            <h3 class="food-title">Signature Biryani</h3>
                            <button class="favorite-btn" aria-label="Favourite"><i class="bx bxs-heart"></i></button>
                        </div>
                        <p class="food-description">Traditional aromatic rice dish with tender meat and exotic spices.</p>
                        <div class="food-footer">
                            <span class="food-code">Item #2001</span>
                            <span class="food-price">$24.99</span>
                        </div>
                    </div>
                </div>
                
                <div class="food-card fade-in">
                    <div class="food-image">
                        <img src="assets/images/f2.jpg" alt="Premium Karhai" loading="lazy">
                        <div class="food-overlay"><button class="order-btn open-order">Order Now</button></div>
                    </div>
                    <div class="food-content">
                        <div class="food-header">
                            <h3 class="food-title">Premium Karhai</h3>
                            <button class="favorite-btn" aria-label="Favourite"><i class="bx bxs-heart"></i></button>
                        </div>
                        <p class="food-description">Savory traditional curry cooked in a wok with fresh herbs and spices.</p>
                        <div class="food-footer">
                            <span class="food-code">Item #2002</span>
                            <span class="food-price">$29.99</span>
                        </div>
                    </div>
                </div>
                
                <div class="food-card fade-in">
                    <div class="food-image">
                        <img src="assets/images/f3.jpg" alt="Truffle Pizza" loading="lazy">
                        <div class="food-overlay"><button class="order-btn open-order">Order Now</button></div>
                    </div>
                    <div class="food-content">
                        <div class="food-header">
                            <h3 class="food-title">Truffle Pizza</h3>
                            <button class="favorite-btn" aria-label="Favourite"><i class="bx bxs-heart"></i></button>
                        </div>
                        <p class="food-description">Artisanal pizza with black truffle, mozzarella, and wild mushrooms.</p>
                        <div class="food-footer">
                            <span class="food-code">Item #2003</span>
                            <span class="food-price">$18.99</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="section-cta">
                <a href="#" class="btn-primary" id="viewFullMenu">View Full Menu</a>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="section about-section" id="about">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <span class="section-subtitle">Our Story</span>
                    <h2 class="section-title">About <span class="highlight">Azure Sands</span></h2>
                    <p class="about-description">Nestled along a pristine coastline, Azure Sands Resort redefines luxury hospitality. Our commitment to excellence is reflected in every detail, from our meticulously designed accommodations to our world-class amenities.</p>
                    <p class="about-description">Established with a vision to create unforgettable experiences, we combine traditional hospitality with modern luxury. Our team of dedicated professionals ensures every guest receives personalized attention and exceptional service.</p>
                    
                    <div class="about-features">
                        <div class="feature fade-in">
                            <i class="bx bx-award"></i>
                            <h3>Award Winning</h3>
                            <p>Recognized globally for excellence</p>
                        </div>
                        <div class="feature fade-in">
                            <i class="bx bx-heart"></i>
                            <h3>Luxury Service</h3>
                            <p>Personalized attention to detail</p>
                        </div>
                        <div class="feature fade-in">
                            <i class="bx bx-map"></i>
                            <h3>Prime Location</h3>
                            <p>Breathtaking oceanfront views</p>
                        </div>
                    </div>
                    
                    <a href="#" class="btn-primary" id="viewAllRoomsAbout">View All Rooms</a>
                </div>
                
                <div class="about-image">
                    <div class="image-wrapper">
                        <img src="assets/images/resort2.jfif" alt="Azure Sands Resort" loading="lazy">
                        <div class="image-overlay"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="section contact-section" id="contact">
        <div class="container">
            <div class="section-header">
                <span class="section-subtitle">Get In Touch</span>
                <h2 class="section-title">Contact <span class="highlight">Us</span></h2>
                <p class="section-description">We'd love to hear from you. Send us a message and we'll respond within 24 hours.</p>
            </div>
            <div class="contact-grid">
                <div class="contact-info-cards">
                    <div class="contact-card fade-in">
                        <i class="bx bx-map"></i>
                        <h3>Address</h3>
                        <p>123 Ocean Drive, Luxury Beach, Maldives</p>
                    </div>
                    <div class="contact-card fade-in">
                        <i class="bx bx-phone"></i>
                        <h3>Phone</h3>
                        <p>+1 (234) 567-8900</p>
                        <p>+1 (234) 567-8901</p>
                    </div>
                    <div class="contact-card fade-in">
                        <i class="bx bx-envelope"></i>
                        <h3>Email</h3>
                        <p>info@azuresands.com</p>
                        <p>bookings@azuresands.com</p>
                    </div>
                    <div class="contact-card fade-in">
                        <i class="bx bx-time"></i>
                        <h3>Hours</h3>
                        <p>Reception: 24/7</p>
                        <p>Dining: 6am – 11pm</p>
                    </div>
                </div>
                <div class="contact-form-wrap fade-in">
                    <form id="contactForm" class="contact-form" novalidate>
                        <input type="hidden" name="action" value="contact">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="contact-name">Full Name</label>
                                <input type="text" id="contact-name" name="name" placeholder="Your full name" required autocomplete="name">
                                <i class="bx bx-user icon"></i>
                            </div>
                            <div class="form-group">
                                <label for="contact-email">Email Address</label>
                                <input type="email" id="contact-email" name="email" placeholder="Your email" required autocomplete="email">
                                <i class="bx bx-envelope icon"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="contact-subject">Subject</label>
                            <input type="text" id="contact-subject" name="subject" placeholder="How can we help?">
                            <i class="bx bx-edit icon"></i>
                        </div>
                        <div class="form-group">
                            <label for="contact-message">Message</label>
                            <textarea id="contact-message" name="message" placeholder="Write your message here…" rows="5" required></textarea>
                            <i class="bx bx-message-detail icon"></i>
                        </div>
                        <div class="form-message" id="contactMessage"></div>
                        <button type="submit" class="btn-primary contact-submit">
                            <i class="bx bx-send"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <img src="assets/images/logo.jfif" alt="Azure Sands">
                        <h3>Azure Sands Resort</h3>
                    </div>
                    <p class="footer-description">Experience unparalleled luxury and service at our award-winning beachfront resort. Your perfect getaway awaits.</p>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook"><i class="bx bxl-facebook"></i></a>
                        <a href="#" aria-label="Instagram"><i class="bx bxl-instagram"></i></a>
                        <a href="#" aria-label="Twitter"><i class="bx bxl-twitter"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="bx bxl-linkedin"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3 class="footer-title">Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="#home">Home</a></li>
                        <li><a href="#rooms-section">Rooms & Suites</a></li>
                        <li><a href="#dining-section">Dining</a></li>
                        <li><a href="#about">About Us</a></li>
                        <li><a href="#" id="openMembershipFooter">Membership</a></li>
                        <li><a href="#" class="open-booking">Book Now</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3 class="footer-title">Contact Info</h3>
                    <div class="contact-info">
                        <p><i class="bx bx-map"></i> 123 Ocean Drive, Luxury Beach, Maldives</p>
                        <p><i class="bx bx-phone"></i> +1 (234) 567-8900</p>
                        <p><i class="bx bx-envelope"></i> info@azuresands.com</p>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3 class="footer-title">Newsletter</h3>
                    <p class="newsletter-text">Subscribe for exclusive offers and updates.</p>
                    <form class="newsletter-form" id="newsletterForm">
                        <input type="email" placeholder="Your email address" required>
                        <button type="submit" aria-label="Subscribe"><i class="bx bx-send"></i></button>
                    </form>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 Azure Sands Resort. All rights reserved. | <a href="#">Privacy Policy</a> | <a href="#">Terms of Service</a></p>
            </div>
        </div>
    </footer>

    <!-- ============================================================
         MODAL OVERLAY
         ============================================================ -->
    <div class="modal-overlay" id="modalOverlay"></div>

    <!-- ============================================================
         BOOKING MODAL — opens via: #openBooking, .open-booking, #openBookingDropdown
         ============================================================ -->
    <div class="modal" id="bookingModal" role="dialog" aria-modal="true" aria-labelledby="bookingModalTitle">
        <div class="modal-container">
            <button class="modal-close" aria-label="Close">&times;</button>
            <div class="modal-content">
                <h2 class="modal-title" id="bookingModalTitle">Book Your <span class="highlight">Stay</span></h2>
                <p class="modal-subtitle">Secure your luxury accommodation with our easy booking system.</p>
                
                <form id="bookingForm" class="booking-form" novalidate>
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="booking-name">Full Name</label>
                            <input type="text" id="booking-name" name="name" placeholder="Enter your full name" required autocomplete="name">
                            <i class="bx bx-user icon"></i>
                        </div>
                        
                        <div class="form-group">
                            <label for="booking-email">Email Address</label>
                            <input type="email" id="booking-email" name="email" placeholder="Enter your email" required autocomplete="email">
                            <i class="bx bx-envelope icon"></i>
                        </div>
                        
                        <div class="form-group">
                            <label for="booking-room">Select Room</label>
                            <select id="booking-room" name="rooms" required>
                                <option value="" disabled selected>Choose your room</option>
                                <option value="1001">Room 1001 – Deluxe Ocean View ($199/night)</option>
                                <option value="1002">Room 1002 – Executive Suite ($299/night)</option>
                                <option value="1003">Room 1003 – Presidential Suite ($499/night)</option>
                                <option value="1004">Room 1004 – Family Suite ($349/night)</option>
                                <option value="1005">Room 1005 – Premium Villa ($599/night)</option>
                                <option value="1006">Room 1006 – Oceanfront Bungalow ($449/night)</option>
                            </select>
                            <i class="bx bxs-bed icon"></i>
                        </div>
                        
                        <div class="form-group">
                            <label for="booking-city">City</label>
                            <input type="text" id="booking-city" name="city" placeholder="Enter your city" required autocomplete="address-level2">
                            <i class="bx bx-map icon"></i>
                        </div>
                        
                        <div class="form-group">
                            <label for="booking-date">Check-in Date</label>
                            <input type="date" id="booking-date" name="date" required>
                            <i class="bx bx-calendar icon"></i>
                        </div>
                        
                        <div class="form-group">
                            <label for="booking-nights">Number of Nights</label>
                            <select id="booking-nights" name="nights">
                                <option value="1">1 Night</option>
                                <option value="2" selected>2 Nights</option>
                                <option value="3">3 Nights</option>
                                <option value="4">4 Nights</option>
                                <option value="5">5 Nights</option>
                                <option value="6">6 Nights</option>
                                <option value="7">7+ Nights</option>
                            </select>
                            <i class="bx bx-moon icon"></i>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="booking-requests">Special Requests (Optional)</label>
                            <textarea id="booking-requests" name="special_requests" placeholder="Any special requests or notes..."></textarea>
                            <i class="bx bx-note icon"></i>
                        </div>
                    </div>
                    
                    <div class="price-summary" id="bookingPriceSummary" style="display:none;">
                        <div class="price-summary-row">
                            <span>Room Rate</span><span id="summaryRate">-</span>
                        </div>
                        <div class="price-summary-row">
                            <span>Nights</span><span id="summaryNights">-</span>
                        </div>
                        <div class="price-summary-row total">
                            <span>Total</span><span id="summaryTotal">-</span>
                        </div>
                    </div>
                    
                    <div class="form-message" id="bookingMessage"></div>
                    <button type="submit" class="btn-primary booking-submit">Confirm Booking</button>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================================
         MEMBERSHIP / SIGN-IN MODAL — opens via: #openMembership, #openMembershipDropdown
         ============================================================ -->
    <div class="modal" id="membershipModal" role="dialog" aria-modal="true" aria-labelledby="membershipModalTitle">
        <div class="modal-container">
            <button class="modal-close" aria-label="Close">&times;</button>
            <div class="modal-content">
                <!-- Tabs -->
                <div class="auth-tabs">
                    <button class="auth-tab active" data-tab="login">Sign In</button>
                    <button class="auth-tab" data-tab="register">Create Account</button>
                </div>

                <!-- Sign In Form -->
                <div class="auth-panel active" id="loginPanel">
                    <h2 class="modal-title" id="membershipModalTitle">Welcome <span class="highlight">Back</span></h2>
                    <p class="modal-subtitle">Sign in to manage your bookings and enjoy member benefits.</p>
                    <form id="loginForm" class="modern-form" novalidate>
                        <input type="hidden" name="action" value="login">
                        <div class="form-group">
                            <label for="login-email">Email Address</label>
                            <input type="email" id="login-email" name="email" placeholder="Enter your email" required autocomplete="email">
                            <i class="bx bx-envelope icon"></i>
                        </div>
                        <div class="form-group">
                            <label for="login-password">Password</label>
                            <input type="password" id="login-password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                            <i class="bx bx-lock-alt icon"></i>
                            <button type="button" class="password-toggle" aria-label="Toggle password"><i class="bx bx-show"></i></button>
                        </div>
                        <div class="form-row-inline">
                            <label class="checkbox-label">
                                <input type="checkbox" name="remember"> <span>Remember me</span>
                            </label>
                            <a href="#" class="forgot-link" id="forgotPasswordLink">Forgot Password?</a>
                        </div>
                        <div class="form-message" id="loginMessage"></div>
                        <button type="submit" class="btn-primary form-submit">Sign In</button>
                    </form>
                </div>

                <!-- Register Form -->
                <div class="auth-panel" id="registerPanel">
                    <h2 class="modal-title">Create Your <span class="highlight">Account</span></h2>
                    <p class="modal-subtitle">Join our luxury resort membership for exclusive benefits and offers.</p>
                    <form id="registerForm" class="modern-form" novalidate>
                        <input type="hidden" name="action" value="register">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="reg-name">Full Name</label>
                                <input type="text" id="reg-name" name="name" placeholder="Enter your full name" required autocomplete="name">
                                <i class="bx bx-user icon"></i>
                            </div>
                            <div class="form-group">
                                <label for="reg-email">Email Address</label>
                                <input type="email" id="reg-email" name="email" placeholder="Enter your email" required autocomplete="email">
                                <i class="bx bx-envelope icon"></i>
                            </div>
                            <div class="form-group">
                                <label for="reg-password">Password</label>
                                <input type="password" id="reg-password" name="password" placeholder="Min 8 characters" required autocomplete="new-password">
                                <i class="bx bx-lock-alt icon"></i>
                                <button type="button" class="password-toggle" aria-label="Toggle password"><i class="bx bx-show"></i></button>
                            </div>
                            <div class="form-group">
                                <label for="reg-confirm">Confirm Password</label>
                                <input type="password" id="reg-confirm" name="confirm" placeholder="Repeat password" required autocomplete="new-password">
                                <i class="bx bx-lock icon"></i>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Gender</label>
                                <div class="radio-group">
                                    <label class="radio-label"><input type="radio" name="gender" value="Male"><span class="radio-custom"></span> Male</label>
                                    <label class="radio-label"><input type="radio" name="gender" value="Female"><span class="radio-custom"></span> Female</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="reg-city">City</label>
                                <select id="reg-city" name="city">
                                    <option value="" disabled selected>Select your city</option>
                                    <option value="Karachi">Karachi</option>
                                    <option value="Lahore">Lahore</option>
                                    <option value="Multan">Multan</option>
                                    <option value="Mianwali">Mianwali</option>
                                    <option value="Other">Other</option>
                                </select>
                                <i class="bx bx-chevron-down icon"></i>
                            </div>
                        </div>
                        <div class="form-message" id="registerMessage"></div>
                        <button type="submit" class="btn-primary form-submit">Create Account</button>
                    </form>
                </div>

                <!-- Forgot Password Form -->
                <div class="auth-panel" id="forgotPanel">
                    <h2 class="modal-title">Reset <span class="highlight">Password</span></h2>
                    <p class="modal-subtitle">Enter your email and we'll send you a reset link.</p>
                    <form id="forgotForm" class="modern-form" novalidate>
                        <input type="hidden" name="action" value="forgot_password">
                        <div class="form-group">
                            <label for="forgot-email">Email Address</label>
                            <input type="email" id="forgot-email" name="email" placeholder="Enter your email" required autocomplete="email">
                            <i class="bx bx-envelope icon"></i>
                        </div>
                        <div class="form-message" id="forgotMessage"></div>
                        <button type="submit" class="btn-primary form-submit">Send Reset Link</button>
                        <button type="button" class="btn-text" id="backToLoginBtn">← Back to Sign In</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         FOOD ORDER MODAL — opens via: .open-order, #exploreFood, #openOrderDropdown
         ============================================================ -->
    <div class="modal" id="orderModal" role="dialog" aria-modal="true" aria-labelledby="orderModalTitle">
        <div class="modal-container">
            <button class="modal-close" aria-label="Close">&times;</button>
            <div class="modal-content">
                <h2 class="modal-title" id="orderModalTitle">Room <span class="highlight">Service</span></h2>
                <p class="modal-subtitle">Order gourmet meals directly to your room.</p>
                
                <form id="orderForm" class="order-form" novalidate>
                    <input type="hidden" name="action" value="place">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="order-name">Full Name</label>
                            <input type="text" id="order-name" name="name" placeholder="Enter your name" required autocomplete="name">
                            <i class="bx bx-user icon"></i>
                        </div>
                        <div class="form-group">
                            <label for="order-room">Room Number</label>
                            <select id="order-room" name="Room" required>
                                <option value="" disabled selected>Select your room</option>
                                <option value="1001">Room 1001</option>
                                <option value="1002">Room 1002</option>
                                <option value="1003">Room 1003</option>
                                <option value="1004">Room 1004</option>
                                <option value="1005">Room 1005</option>
                                <option value="1006">Room 1006</option>
                            </select>
                            <i class="bx bxs-bed icon"></i>
                        </div>
                    </div>
                    
                    <div class="order-items">
                        <h3 class="order-subheading">Select Your Items</h3>
                        
                        <div class="order-item">
                            <div class="item-header">
                                <label for="order-food1">First Item</label>
                                <span class="item-required">Required</span>
                            </div>
                            <div class="item-row">
                                <select id="order-food1" name="Food1" class="item-select" required>
                                    <option value="" disabled selected>Choose your first item</option>
                                    <option value="2001">Signature Biryani – $24.99</option>
                                    <option value="2002">Premium Karhai – $29.99</option>
                                    <option value="2003">Truffle Pizza – $18.99</option>
                                    <option value="2004">Gourmet Burger – $14.99</option>
                                    <option value="2005">Truffle Fries – $9.99</option>
                                    <option value="2006">Artisan Kheer – $12.99</option>
                                    <option value="2007">Seafood Platter – $34.99</option>
                                    <option value="2008">Chocolate Lava Cake – $10.99</option>
                                </select>
                                <div class="quantity-control">
                                    <button type="button" class="quantity-btn minus">-</button>
                                    <input type="number" name="Q1" value="1" min="1" max="10" class="quantity-input">
                                    <button type="button" class="quantity-btn plus">+</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="order-item">
                            <div class="item-header">
                                <label for="order-food2">Second Item</label>
                                <span class="item-optional">Optional</span>
                            </div>
                            <div class="item-row">
                                <select id="order-food2" name="Food2" class="item-select">
                                    <option value="">No second item</option>
                                    <option value="2001">Signature Biryani – $24.99</option>
                                    <option value="2002">Premium Karhai – $29.99</option>
                                    <option value="2003">Truffle Pizza – $18.99</option>
                                    <option value="2004">Gourmet Burger – $14.99</option>
                                    <option value="2005">Truffle Fries – $9.99</option>
                                    <option value="2006">Artisan Kheer – $12.99</option>
                                    <option value="2007">Seafood Platter – $34.99</option>
                                    <option value="2008">Chocolate Lava Cake – $10.99</option>
                                </select>
                                <div class="quantity-control">
                                    <button type="button" class="quantity-btn minus">-</button>
                                    <input type="number" name="Q2" value="0" min="0" max="10" class="quantity-input">
                                    <button type="button" class="quantity-btn plus">+</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="order-time">Delivery Date &amp; Time</label>
                            <input type="datetime-local" id="order-time" name="Time" required>
                            <i class="bx bx-time icon"></i>
                        </div>
                        <div class="form-group">
                            <label for="order-date">Delivery Date (Alt)</label>
                            <input type="date" id="order-date" name="date">
                            <i class="bx bx-calendar icon"></i>
                        </div>
                    </div>
                    
                    <div class="form-message" id="orderMessage"></div>
                    <button type="submit" class="btn-primary order-submit">Place Order</button>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================================
         APPLICATION / CAREERS MODAL — opens via: #openApplicationDropdown
         ============================================================ -->
    <div class="modal" id="applicationModal" role="dialog" aria-modal="true" aria-labelledby="applicationModalTitle">
        <div class="modal-container">
            <button class="modal-close" aria-label="Close">&times;</button>
            <div class="modal-content">
                <h2 class="modal-title" id="applicationModalTitle">Join Our <span class="highlight">Team</span></h2>
                <p class="modal-subtitle">Be part of a world-class luxury resort team dedicated to excellence.</p>
                
                <form id="applicationForm" class="careers-form" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="action" value="apply">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="career-name">Full Name</label>
                            <input type="text" id="career-name" name="name" placeholder="Enter your full name" required autocomplete="name">
                            <i class="bx bx-user icon"></i>
                        </div>
                        <div class="form-group">
                            <label for="career-email">Email Address</label>
                            <input type="email" id="career-email" name="email" placeholder="Enter your email" required autocomplete="email">
                            <i class="bx bx-envelope icon"></i>
                        </div>
                        <div class="form-group">
                            <label for="career-job">Position</label>
                            <select id="career-job" name="Job" required>
                                <option value="" disabled selected>Select desired position</option>
                                <option value="Manager">Resort Manager</option>
                                <option value="Waiter">Food & Beverage Server</option>
                                <option value="Room cleaner">Housekeeping Supervisor</option>
                                <option value="Cashier">Front Desk Associate</option>
                            </select>
                            <i class="bx bx-briefcase icon"></i>
                        </div>
                        <div class="form-group">
                            <label for="career-date">Available Start Date</label>
                            <input type="date" id="career-date" name="date" required>
                            <i class="bx bx-calendar icon"></i>
                        </div>
                        <div class="form-group full-width">
                            <label for="career-cv">Upload CV (PDF/DOC/DOCX – max 5MB)</label>
                            <input type="file" id="career-cv" name="cv" accept=".pdf,.doc,.docx">
                            <i class="bx bx-file icon"></i>
                        </div>
                    </div>
                    
                    <div class="form-message" id="applicationMessage"></div>
                    <button type="submit" class="btn-primary careers-submit">Submit Application</button>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================================
         ALL ROOMS MODAL — opens via: #viewAllRooms, #exroom
         ============================================================ -->
    <div class="modal full-modal" id="allRoomsModal" role="dialog" aria-modal="true" aria-labelledby="allRoomsModalTitle">
        <div class="modal-container">
            <button class="modal-close" aria-label="Close">&times;</button>
            <div class="modal-content">
                <h2 class="modal-title" id="allRoomsModalTitle">All <span class="highlight">Rooms</span> &amp; Suites</h2>
                <p class="modal-subtitle">Browse our complete collection of luxury accommodations</p>
                <div class="all-rooms-grid" id="allRoomsContainer"></div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         ALL FOOD MODAL — opens via: #viewFullMenu
         ============================================================ -->
    <div class="modal full-modal" id="allFoodModal" role="dialog" aria-modal="true" aria-labelledby="allFoodModalTitle">
        <div class="modal-container">
            <button class="modal-close" aria-label="Close">&times;</button>
            <div class="modal-content">
                <h2 class="modal-title" id="allFoodModalTitle">Complete <span class="highlight">Menu</span></h2>
                <p class="modal-subtitle">Explore our entire culinary selection</p>
                <div class="all-food-grid" id="allFoodContainer"></div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         MY BOOKINGS MODAL (shown when logged in + openBookingDropdown)
         ============================================================ -->
    <div class="modal full-modal" id="myBookingsModal" role="dialog" aria-modal="true" aria-labelledby="myBookingsModalTitle">
        <div class="modal-container">
            <button class="modal-close" aria-label="Close">&times;</button>
            <div class="modal-content">
                <h2 class="modal-title" id="myBookingsModalTitle">My <span class="highlight">Bookings</span></h2>
                <p class="modal-subtitle">Your booking history and current reservations</p>
                <div id="myBookingsContainer"><div class="loading-spinner"></div></div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         MY ORDERS MODAL (shown when logged in + openOrderDropdown)
         ============================================================ -->
    <div class="modal full-modal" id="myOrdersModal" role="dialog" aria-modal="true" aria-labelledby="myOrdersModalTitle">
        <div class="modal-container">
            <button class="modal-close" aria-label="Close">&times;</button>
            <div class="modal-content">
                <h2 class="modal-title" id="myOrdersModalTitle">My <span class="highlight">Orders</span></h2>
                <p class="modal-subtitle">Your food order history</p>
                <div id="myOrdersContainer"><div class="loading-spinner"></div></div>
            </div>
        </div>
    </div>

    <!-- Back to Top -->
    <a href="#home" class="back-to-top" aria-label="Back to top">
        <i class="bx bx-chevron-up"></i>
    </a>

    <!-- Pass PHP session state to JS -->
    <script>
        window.AZURE_CONFIG = {
            isLoggedIn: <?= $isLogged ? 'true' : 'false' ?>,
            userName: <?= $isLogged ? json_encode($user['full_name']) : 'null' ?>,
            siteUrl: '<?= SITE_URL ?>'
        };
    </script>
    <script src="assets/js/app.js"></script>
</body>
</html>
