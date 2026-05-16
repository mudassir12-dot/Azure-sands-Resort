/**
 * Azure Sands Resort – Main JavaScript
 * Full-Stack Edition | All modal logic strictly preserved
 *
 * UX UPGRADES ADDED (non-breaking):
 *  1. Login-first protection for booking, ordering, membership
 *  2. pendingIntent system — remembers what user wanted, fires after login
 *  3. Auto-fill user name/email/city into booking & order forms on open
 *  4. Food item preselection via data-food-code attribute
 *  5. Room number auto-fill from active booking (async, non-blocking)
 *  6. After login/register: execute pending intent instead of hard reload
 */

document.addEventListener('DOMContentLoaded', function () {

    const cfg = window.AZURE_CONFIG || { isLoggedIn: false, siteUrl: '' };

    /* ============================================================
       PRELOADER
       ============================================================ */
    const preloader = document.querySelector('.preloader');
    window.addEventListener('load', () => {
        setTimeout(() => {
            preloader.classList.add('hidden');
            setTimeout(() => { preloader.style.display = 'none'; }, 500);
        }, 900);
    });

    /* ============================================================
       STICKY HEADER
       ============================================================ */
    const header = document.querySelector('.header');
    window.addEventListener('scroll', () => {
        header.classList.toggle('scrolled', window.scrollY > 80);
    });

    /* ============================================================
       MOBILE HAMBURGER
       ============================================================ */
    const hamburger = document.getElementById('hamburger');
    const navMenu   = document.getElementById('navMenu');

    hamburger.addEventListener('click', () => {
        hamburger.classList.toggle('active');
        navMenu.classList.toggle('active');
        document.body.style.overflow = navMenu.classList.contains('active') ? 'hidden' : '';
    });

    function closeMobileMenu() {
        hamburger.classList.remove('active');
        navMenu.classList.remove('active');
        document.body.style.overflow = '';
    }

    /* ============================================================
       USER DROPDOWN TOGGLE
       ============================================================ */
    const userToggle       = document.getElementById('userToggle');
    const userToggleMobile = document.getElementById('userToggleMobile');
    const userDropdown     = document.getElementById('userDropdown');

    function handleUserToggleClick(e) {
        e.stopPropagation();
        userDropdown.classList.toggle('active');
        const btn = e.currentTarget;
        const isMobileBtn = btn.id === 'userToggleMobile';
        if (isMobileBtn) {
            const mobileWrap = document.querySelector('.mobile-user-dropdown');
            if (mobileWrap && !mobileWrap.contains(userDropdown)) {
                mobileWrap.appendChild(userDropdown);
            }
        } else {
            const desktopWrap = document.querySelector('.nav-actions .user-dropdown');
            if (desktopWrap && !desktopWrap.contains(userDropdown)) {
                desktopWrap.appendChild(userDropdown);
            }
        }
    }

    userToggle?.addEventListener('click', handleUserToggleClick);
    userToggleMobile?.addEventListener('click', handleUserToggleClick);

    document.addEventListener('click', (e) => {
        const clickedInsideToggle   = userToggle?.contains(e.target) || userToggleMobile?.contains(e.target);
        const clickedInsideDropdown = userDropdown?.contains(e.target);
        if (!clickedInsideToggle && !clickedInsideDropdown) {
            userDropdown?.classList.remove('active');
        }
    });

    function closeUserDropdown() {
        userDropdown?.classList.remove('active');
    }

    /* ============================================================
       MODAL MANAGEMENT SYSTEM
       Unchanged — every modal ID, open/close behaviour preserved.
       ============================================================ */
    const modalOverlay = document.getElementById('modalOverlay');
    const allModals    = document.querySelectorAll('.modal');

    function openModal(modalId) {
        allModals.forEach(m => m.classList.remove('active'));
        modalOverlay.classList.add('active');
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal() {
        modalOverlay.classList.remove('active');
        allModals.forEach(m => m.classList.remove('active'));
        document.body.style.overflow = '';
    }

    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });
    modalOverlay.addEventListener('click', closeModal);
    document.querySelectorAll('.modal-close').forEach(btn => btn.addEventListener('click', closeModal));

    /* ============================================================
       ★ PENDING INTENT SYSTEM
       ----------------------------------------------------------
       Stores what the user was trying to do before login was
       required. After successful login the intent is executed
       automatically — no second click needed.

       Structure:
         pendingIntent = {
           modalId : 'bookingModal' | 'orderModal' | ...
           extras  : { room: '1002', foodCode: '2001' }
         }
       ============================================================ */
    let pendingIntent = null;

    function savePendingIntent(modalId, extras = {}) {
        pendingIntent = { modalId, extras };
    }

    function executePendingIntent() {
        if (!pendingIntent) return;
        const { modalId, extras } = pendingIntent;
        pendingIntent = null;       // clear immediately so it only fires once

        // Small delay so the auth modal finishes closing first
        setTimeout(() => {
            if (extras.room) {
                const sel = document.getElementById('booking-room');
                if (sel) { sel.value = extras.room; updatePriceSummary(); }
            }
            if (extras.foodCode) {
                preselectFood(extras.foodCode);
            }
            openModal(modalId);

            // After opening modal, run auto-fill with latest session data
            autoFillForms();
        }, 350);
    }

    /* ============================================================
       ★ LOGIN-FIRST GUARD
       ----------------------------------------------------------
       Call this instead of openModal() for any protected action.
       • If user IS logged in  → opens the modal directly
       • If user is NOT logged in → saves intent, shows login modal
         with a friendly "please login first" message
       ============================================================ */
    function requireLogin(modalId, extras = {}) {
        if (cfg.isLoggedIn) {
            // Already logged in — open directly
            if (extras.room) {
                const sel = document.getElementById('booking-room');
                if (sel) { sel.value = extras.room; updatePriceSummary(); }
            }
            if (extras.foodCode) preselectFood(extras.foodCode);
            openModal(modalId);
            autoFillForms();
        } else {
            // Not logged in — save intent, redirect to login
            savePendingIntent(modalId, extras);
            openModal('membershipModal');
            showAuthPanel('loginPanel');

            // Show gentle prompt inside the login modal
            const msg = document.getElementById('loginMessage');
            showMessage(msg,
                '🔒 Please sign in or create an account to continue.',
                'info'
            );
        }
    }

    /* ============================================================
       ★ AUTO-FILL FORMS
       ----------------------------------------------------------
       Fills name/email/city into booking and order forms using
       session data already injected into AZURE_CONFIG by PHP.
       Fields remain fully editable by the user.
       Room number is fetched async from active booking.
       ============================================================ */
    function autoFillForms() {
        if (!cfg.isLoggedIn) return;

        // ── Booking form ─────────────────────────────────────────
        fillField('booking-name',  cfg.userName);
        fillField('booking-email', cfg.userEmail);
        fillField('booking-city',  cfg.userCity);

        // ── Order form ───────────────────────────────────────────
        fillField('order-name', cfg.userName);

        // Room number: async from active booking
        fetchActiveRoom();
    }

    function fillField(id, value) {
        if (!value) return;
        const el = document.getElementById(id);
        // Only fill if field is currently empty (don't overwrite user's edits)
        if (el && !el.value) el.value = value;
    }

    // Fetch active room number and fill the order form room select
    async function fetchActiveRoom() {
        try {
            const resp = await fetch(cfg.siteUrl + '/bookings/booking.php?action=active_room');
            const data = await resp.json();
            if (data.success && data.room_number) {
                const roomSel = document.getElementById('order-room');
                if (roomSel && !roomSel.value) {
                    // Try to set the value — works if option exists
                    roomSel.value = data.room_number;
                    // If the option didn't match, add it dynamically
                    if (roomSel.value !== data.room_number) {
                        const opt = document.createElement('option');
                        opt.value = data.room_number;
                        opt.textContent = `Room ${data.room_number} (Active Booking)`;
                        roomSel.insertBefore(opt, roomSel.options[1]);
                        roomSel.value = data.room_number;
                    }
                }
            }
        } catch { /* non-blocking — fail silently */ }
    }

    /* ============================================================
       ★ FOOD PRESELECTION
       ----------------------------------------------------------
       Sets the first food dropdown to the item code passed via
       data-food-code attribute on the trigger button.
       User can still change it freely.
       ============================================================ */
    function preselectFood(code) {
        if (!code) return;
        const sel = document.getElementById('order-food1');
        if (sel) sel.value = code;
    }

    /* ============================================================
       MODAL TRIGGER MAP
       ALL original mappings STRICTLY preserved.
       Protected actions now go through requireLogin().
       ============================================================ */

    // ── BOOKING triggers → bookingModal ─────────────────────────
    // Protected: login required to book
    document.getElementById('openBooking')?.addEventListener('click', (e) => {
        e.preventDefault(); closeMobileMenu();
        requireLogin('bookingModal');
    });

    document.querySelectorAll('.open-booking').forEach(el => {
        el.addEventListener('click', (e) => {
            e.preventDefault(); closeMobileMenu();
            requireLogin('bookingModal', { room: el.dataset.room || '' });
        });
    });

    // ── MEMBERSHIP / SIGN-IN triggers → membershipModal ─────────
    // Not protected — this IS the login modal
    ['openMembership', 'openMembershipDropdown', 'openMembershipFooter'].forEach(id => {
        document.getElementById(id)?.addEventListener('click', (e) => {
            e.preventDefault(); closeMobileMenu(); closeUserDropdown();
            openModal('membershipModal');
            showAuthPanel('loginPanel');
        });
    });

    // ── FOOD ORDER triggers → orderModal ────────────────────────
    // Protected: login required to order
    document.getElementById('exploreFood')?.addEventListener('click', (e) => {
        e.preventDefault(); closeMobileMenu();
        requireLogin('orderModal');
    });

    document.querySelectorAll('.open-order').forEach(el => {
        el.addEventListener('click', (e) => {
            e.preventDefault();
            // data-food-code on button enables preselection
            requireLogin('orderModal', { foodCode: el.dataset.foodCode || '' });
        });
    });

    // ── CAREERS trigger → applicationModal ──────────────────────
    // Not protected — anyone can apply
    document.getElementById('openApplicationDropdown')?.addEventListener('click', (e) => {
        e.preventDefault(); closeUserDropdown();
        openModal('applicationModal');
    });

    // ── DROPDOWN: My Bookings ────────────────────────────────────
    document.getElementById('openBookingDropdown')?.addEventListener('click', (e) => {
        e.preventDefault(); closeUserDropdown();
        if (cfg.isLoggedIn) {
            openModal('myBookingsModal');
            loadMyBookings();
        } else {
            requireLogin('bookingModal');
        }
    });

    // ── DROPDOWN: My Orders ──────────────────────────────────────
    document.getElementById('openOrderDropdown')?.addEventListener('click', (e) => {
        e.preventDefault(); closeUserDropdown();
        if (cfg.isLoggedIn) {
            openModal('myOrdersModal');
            loadMyOrders();
        } else {
            requireLogin('orderModal');
        }
    });

    // ── ALL ROOMS trigger ────────────────────────────────────────
    ['viewAllRooms', 'viewAllRoomsAbout'].forEach(id => {
        document.getElementById(id)?.addEventListener('click', (e) => {
            e.preventDefault(); closeMobileMenu();
            openModal('allRoomsModal');
            loadAllRooms();
        });
    });

    // #exroom → smooth scroll to rooms section
    document.getElementById('exroom')?.addEventListener('click', (e) => {
        e.preventDefault(); closeMobileMenu();
        const target = document.getElementById('rooms-section');
        if (target) window.scrollTo({ top: target.offsetTop - 80, behavior: 'smooth' });
    });

    // ── FULL MENU trigger ────────────────────────────────────────
    document.getElementById('viewFullMenu')?.addEventListener('click', (e) => {
        e.preventDefault();
        openModal('allFoodModal');
        loadAllFoodItems();
    });

    /* ============================================================
       AUTH TABS  (Sign In / Create Account / Forgot Password)
       ============================================================ */
    function showAuthPanel(panelId) {
        document.querySelectorAll('.auth-panel').forEach(p => p.classList.remove('active'));
        document.getElementById(panelId)?.classList.add('active');
        // Clear the info message when switching tabs manually
        if (panelId !== 'loginPanel') {
            clearMessage(document.getElementById('loginMessage'));
        }
    }

    document.querySelectorAll('.auth-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            showAuthPanel(tab.dataset.tab === 'login' ? 'loginPanel' : 'registerPanel');
        });
    });

    document.getElementById('forgotPasswordLink')?.addEventListener('click', (e) => {
        e.preventDefault();
        document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
        showAuthPanel('forgotPanel');
    });

    document.getElementById('backToLoginBtn')?.addEventListener('click', () => {
        document.querySelectorAll('.auth-tab')[0]?.classList.add('active');
        showAuthPanel('loginPanel');
    });

    /* ============================================================
       PASSWORD TOGGLE
       ============================================================ */
    document.querySelectorAll('.password-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.previousElementSibling;
            const icon  = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bx bx-hide';
            } else {
                input.type = 'password';
                icon.className = 'bx bx-show';
            }
        });
    });

    /* ============================================================
       QUANTITY CONTROLS
       ============================================================ */
    document.querySelectorAll('.quantity-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const ctrl  = this.closest('.quantity-control');
            const input = ctrl.querySelector('.quantity-input');
            let val     = parseInt(input.value) || 0;
            if (this.classList.contains('plus')) {
                input.value = Math.min(val + 1, parseInt(input.max) || 10);
            } else {
                input.value = Math.max(val - 1, parseInt(input.min) || 0);
            }
        });
    });

    /* ============================================================
       WISHLIST & FAVOURITE TOGGLES
       ============================================================ */
    function initWishlist(scope) {
        (scope || document).querySelectorAll('.wishlist-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                this.classList.toggle('active');
                const icon = this.querySelector('i');
                icon.className = this.classList.contains('active') ? 'bx bxs-heart' : 'bx bx-heart';
            });
        });
    }
    function initFavourites(scope) {
        (scope || document).querySelectorAll('.favorite-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                this.classList.toggle('active');
                const icon = this.querySelector('i');
                icon.className = this.classList.contains('active') ? 'bx bxs-heart' : 'bx bx-heart';
            });
        });
    }
    initWishlist(); initFavourites();

    /* ============================================================
       BOOKING PRICE SUMMARY (live update)
       ============================================================ */
    const roomPrices   = { 1001:199, 1002:299, 1003:499, 1004:349, 1005:599, 1006:449 };
    const roomSelect   = document.getElementById('booking-room');
    const nightSelect  = document.getElementById('booking-nights');
    const priceSummary = document.getElementById('bookingPriceSummary');

    function updatePriceSummary() {
        const roomVal   = roomSelect?.value;
        const nightsVal = parseInt(nightSelect?.value) || 1;
        if (!roomVal) { if (priceSummary) priceSummary.style.display = 'none'; return; }
        const rate  = roomPrices[roomVal] || 0;
        const total = rate * nightsVal;
        document.getElementById('summaryRate').textContent   = '$' + rate.toFixed(2) + '/night';
        document.getElementById('summaryNights').textContent = nightsVal;
        document.getElementById('summaryTotal').textContent  = '$' + total.toFixed(2);
        if (priceSummary) priceSummary.style.display = 'block';
    }
    roomSelect?.addEventListener('change', updatePriceSummary);
    nightSelect?.addEventListener('change', updatePriceSummary);

    /* ============================================================
       FORM SUBMISSIONS (AJAX → PHP API)
       ============================================================ */

    // ── Booking Form ─────────────────────────────────────────────
    document.getElementById('bookingForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('[type=submit]');
        const msg = document.getElementById('bookingMessage');
        await submitForm(e.target, btn, msg, cfg.siteUrl + '/bookings/booking.php', (data) => {
            if (data.success) {
                showNotification(`✅ ${data.message}`, 'success');
                setTimeout(closeModal, 600);
                e.target.reset();
                if (priceSummary) priceSummary.style.display = 'none';
            }
        });
    });

    // ── Login Form ───────────────────────────────────────────────
    // ★ After login: execute pendingIntent instead of hard reload
    //   so user lands on the modal they originally wanted.
    document.getElementById('loginForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('[type=submit]');
        const msg = document.getElementById('loginMessage');
        await submitForm(e.target, btn, msg, cfg.siteUrl + '/auth/auth.php', (data) => {
            if (data.success) {
                showNotification(`✅ ${data.message}`, 'success');
                cfg.isLoggedIn  = true;
                cfg.userName    = data.user?.name  || cfg.userName;
                cfg.userEmail   = data.user?.email || cfg.userEmail;

                if (pendingIntent) {
                    // Close auth modal, then fire the original action
                    closeModal();
                    executePendingIntent();
                } else {
                    // No pending intent — standard reload to refresh nav state
                    setTimeout(() => window.location.reload(), 800);
                }
            }
        });
    });

    // ── Register Form ─────────────────────────────────────────────
    // ★ Same pattern — execute pending intent after registration
    document.getElementById('registerForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('[type=submit]');
        const msg = document.getElementById('registerMessage');
        const pwd = document.getElementById('reg-password')?.value;
        const cfm = document.getElementById('reg-confirm')?.value;
        if (pwd !== cfm) {
            showMessage(msg, 'Passwords do not match.', 'error');
            return;
        }
        await submitForm(e.target, btn, msg, cfg.siteUrl + '/auth/auth.php', (data) => {
            if (data.success) {
                showNotification(`✅ ${data.message}`, 'success');
                cfg.isLoggedIn = true;
                cfg.userName   = data.user?.name  || cfg.userName;
                cfg.userEmail  = data.user?.email || cfg.userEmail;

                if (pendingIntent) {
                    closeModal();
                    executePendingIntent();
                } else {
                    setTimeout(() => window.location.reload(), 800);
                }
            }
        });
    });

    // ── Forgot Password Form ─────────────────────────────────────
    document.getElementById('forgotForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('[type=submit]');
        const msg = document.getElementById('forgotMessage');
        await submitForm(e.target, btn, msg, cfg.siteUrl + '/auth/auth.php');
    });

    // ── Order Form ───────────────────────────────────────────────
    document.getElementById('orderForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('[type=submit]');
        const msg = document.getElementById('orderMessage');
        await submitForm(e.target, btn, msg, cfg.siteUrl + '/orders/order.php', (data) => {
            if (data.success) {
                showNotification(`✅ ${data.message}`, 'success');
                setTimeout(closeModal, 600);
                e.target.reset();
            }
        });
    });

    // ── Application Form (file upload) ───────────────────────────
    document.getElementById('applicationForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('[type=submit]');
        const msg = document.getElementById('applicationMessage');
        setButtonLoading(btn, true);
        try {
            const fd   = new FormData(e.target);
            const resp = await fetch(cfg.siteUrl + '/careers/apply.php', { method:'POST', body:fd });
            const data = await resp.json();
            showMessage(msg, data.message, data.success ? 'success' : 'error');
            if (data.success) {
                showNotification(`✅ ${data.message}`, 'success');
                setTimeout(closeModal, 1000);
                e.target.reset();
            }
        } catch (err) {
            showMessage(msg, 'Network error. Please try again.', 'error');
        } finally {
            setButtonLoading(btn, false);
        }
    });

    // ── Logout ───────────────────────────────────────────────────
    document.getElementById('logoutBtn')?.addEventListener('click', async (e) => {
        e.preventDefault(); closeUserDropdown();
        const resp = await fetch(cfg.siteUrl + '/auth/auth.php', {
            method: 'POST',
            body:   new URLSearchParams({ action:'logout' })
        });
        const data = await resp.json();
        if (data.success) window.location.reload();
    });

    /* ============================================================
       GENERIC AJAX FORM SUBMIT
       ============================================================ */
    async function submitForm(form, btn, msgEl, url, onSuccess) {
        setButtonLoading(btn, true);
        clearMessage(msgEl);
        try {
            const body = new URLSearchParams(new FormData(form));
            const resp = await fetch(url, { method:'POST', body });
            const data = await resp.json();
            if (msgEl) showMessage(msgEl, data.message, data.success ? 'success' : 'error');
            if (data.success && onSuccess) onSuccess(data);
        } catch (err) {
            if (msgEl) showMessage(msgEl, 'Network error. Please try again.', 'error');
        } finally {
            setButtonLoading(btn, false);
        }
    }

    function setButtonLoading(btn, loading) {
        if (!btn) return;
        if (loading) {
            btn.disabled = true;
            btn.dataset.original = btn.innerHTML;
            btn.innerHTML = '<span class="loading-spinner" style="width:20px;height:20px;margin:0 auto;"></span>';
        } else {
            btn.disabled = false;
            if (btn.dataset.original) btn.innerHTML = btn.dataset.original;
        }
    }

    function showMessage(el, text, type) {
        if (!el) return;
        el.textContent = text;
        el.className   = 'form-message ' + type;
    }
    function clearMessage(el) {
        if (!el) return;
        el.textContent = '';
        el.className   = 'form-message';
    }

    /* ============================================================
       LOAD ALL ROOMS (dynamic cards)
       ============================================================ */
    const allRoomsData = [
        { id:1, image:'assets/images/14.jpg',  title:'Deluxe Ocean View',   desc:'Spacious room with panoramic ocean views, king-sized bed, and modern amenities.', badge:'Most Popular',    price:199, features:['King Bed','Pool Access','Free WiFi','Smart TV'] },
        { id:2, image:'assets/images/15.jpg',  title:'Executive Suite',     desc:'Luxurious suite with separate living area, premium furnishings, and exclusive amenities.', badge:'Best Seller',    price:299, features:['King Bed','Jacuzzi','Breakfast Included','Work Desk'] },
        { id:3, image:'assets/images/16.jpg',  title:'Presidential Suite',  desc:'Ultimate luxury with private balcony, butler service, and premium amenities.', badge:'Premium',        price:499, features:['Super King Bed','Private Spa','Limo Service','Minibar'] },
        { id:4, image:'assets/images/11.jfif', title:'Family Suite',        desc:'Spacious accommodation with separate bedrooms, perfect for families.', badge:'Family Friendly', price:349, features:['2 Queen Beds','2 Smart TVs','Play Area','Kids Menu'] },
        { id:5, image:'assets/images/9.jfif',  title:'Premium Villa',       desc:'Private villa with personal pool, garden, and dedicated staff.', badge:'Ultimate Luxury', price:599, features:['Private Pool','Private Garden','Private Chef','Private Garage'] },
        { id:6, image:'assets/images/17.jpg',  title:'Oceanfront Bungalow', desc:'Beachfront bungalow with direct ocean access and uninterrupted sea views.', badge:'Beachfront',     price:449, features:['King Bed','Beach Access','Sun Deck','Coffee Maker'] }
    ];

    function loadAllRooms() {
        const container = document.getElementById('allRoomsContainer');
        if (!container || container.dataset.loaded) return;
        container.dataset.loaded = '1';
        container.innerHTML = '';
        allRoomsData.forEach(room => {
            const card = document.createElement('div');
            card.className = 'room-card fade-in';
            card.innerHTML = `
                <div class="room-image">
                    <img src="${room.image}" alt="${room.title}" loading="lazy">
                    <div class="room-badge">${room.badge}</div>
                    <button class="wishlist-btn" aria-label="Wishlist"><i class="bx bxs-heart"></i></button>
                </div>
                <div class="room-content">
                    <h3 class="room-title">${room.title}</h3>
                    <p class="room-description">${room.desc}</p>
                    <div class="room-features">
                        ${room.features.map(f => `<span><i class="bx bx-check"></i> ${f}</span>`).join('')}
                    </div>
                    <div class="room-footer">
                        <div class="room-price">
                            <span class="price">$${room.price}</span>
                            <span class="period">/ night</span>
                        </div>
                        <button class="btn-room allroom-book-btn" data-room="100${room.id}">Book Now</button>
                    </div>
                </div>`;
            container.appendChild(card);
        });
        initWishlist(container);
        container.querySelectorAll('.allroom-book-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const roomVal = btn.dataset.room;
                closeModal();
                setTimeout(() => {
                    requireLogin('bookingModal', { room: roomVal || '' });
                }, 300);
            });
        });
        setTimeout(() => container.querySelectorAll('.fade-in').forEach(el => el.classList.add('visible')), 50);
    }

    /* ============================================================
       LOAD ALL FOOD ITEMS (dynamic cards)
       ============================================================ */
    const allFoodData = [
        { id:1, image:'assets/images/f1.jpg', title:'Signature Biryani',   desc:'Traditional aromatic rice dish with tender meat and exotic spices.', code:'2001', price:'24.99' },
        { id:2, image:'assets/images/f2.jpg', title:'Premium Karhai',      desc:'Savory traditional curry cooked in a wok with fresh herbs and spices.', code:'2002', price:'29.99' },
        { id:3, image:'assets/images/f3.jpg', title:'Truffle Pizza',       desc:'Artisanal pizza with black truffle, mozzarella, and wild mushrooms.', code:'2003', price:'18.99' },
        { id:4, image:'assets/images/f4.jpg', title:'Gourmet Burger',      desc:'Premium beef patty with aged cheddar, truffle aioli, and fresh veggies.', code:'2004', price:'14.99' },
        { id:5, image:'assets/images/f5.jpg', title:'Truffle Fries',       desc:'Crispy golden fries tossed in truffle oil with garlic aioli.', code:'2005', price:'9.99' },
        { id:6, image:'assets/images/f6.jpg', title:'Artisan Kheer',       desc:'Traditional rice pudding infused with saffron and cardamom.', code:'2006', price:'12.99' },
        { id:7, image:'assets/images/f1.jpg', title:'Seafood Platter',     desc:'Fresh lobster, shrimp, and scallops grilled with lemon butter.', code:'2007', price:'34.99' },
        { id:8, image:'assets/images/f3.jpg', title:'Chocolate Lava Cake', desc:'Warm chocolate cake with molten center and vanilla ice cream.', code:'2008', price:'10.99' }
    ];

    function loadAllFoodItems() {
        const container = document.getElementById('allFoodContainer');
        if (!container || container.dataset.loaded) return;
        container.dataset.loaded = '1';
        container.innerHTML = '';
        allFoodData.forEach(food => {
            const card = document.createElement('div');
            card.className = 'food-card fade-in';
            card.innerHTML = `
                <div class="food-image">
                    <img src="${food.image}" alt="${food.title}" loading="lazy">
                    <div class="food-overlay">
                        <button class="order-btn allfood-order-btn" data-food-code="${food.code}">Order Now</button>
                    </div>
                </div>
                <div class="food-content">
                    <div class="food-header">
                        <h3 class="food-title">${food.title}</h3>
                        <button class="favorite-btn" aria-label="Favourite"><i class="bx bxs-heart"></i></button>
                    </div>
                    <p class="food-description">${food.desc}</p>
                    <div class="food-footer">
                        <span class="food-code">Item #${food.code}</span>
                        <span class="food-price">$${food.price}</span>
                    </div>
                </div>`;
            container.appendChild(card);
        });
        initFavourites(container);
        // ★ Dynamic food cards also use requireLogin + preselection
        container.querySelectorAll('.allfood-order-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                closeModal();
                setTimeout(() => {
                    requireLogin('orderModal', { foodCode: btn.dataset.foodCode || '' });
                }, 300);
            });
        });
        setTimeout(() => container.querySelectorAll('.fade-in').forEach(el => el.classList.add('visible')), 50);
    }

    /* ============================================================
       LOAD MY BOOKINGS
       ============================================================ */
    async function loadMyBookings() {
        const container = document.getElementById('myBookingsContainer');
        if (!container) return;
        container.innerHTML = '<div class="loading-spinner"></div>';
        try {
            const resp = await fetch(cfg.siteUrl + '/bookings/booking.php?action=my_bookings');
            const data = await resp.json();
            if (!data.success || !data.bookings.length) {
                container.innerHTML = `<div class="empty-state"><i class="bx bx-calendar-x"></i><p>No bookings found.</p></div>`;
                return;
            }
            container.innerHTML = `
                <div class="data-table-wrapper">
                    <table class="data-table">
                        <thead><tr>
                            <th>Ref</th><th>Room</th><th>Check-In</th><th>Nights</th><th>Total</th><th>Status</th>
                        </tr></thead>
                        <tbody>
                        ${data.bookings.map(b => `<tr>
                            <td>${b.booking_ref}</td>
                            <td>${b.room_type}<br><small>${b.room_number}</small></td>
                            <td>${b.check_in}</td>
                            <td>${b.nights}</td>
                            <td>$${parseFloat(b.total_price).toFixed(2)}</td>
                            <td><span class="status-badge status-${b.status}">${b.status}</span></td>
                        </tr>`).join('')}
                        </tbody>
                    </table>
                </div>`;
        } catch (err) {
            container.innerHTML = `<div class="empty-state"><i class="bx bx-error"></i><p>Failed to load bookings.</p></div>`;
        }
    }

    /* ============================================================
       LOAD MY ORDERS
       ============================================================ */
    async function loadMyOrders() {
        const container = document.getElementById('myOrdersContainer');
        if (!container) return;
        container.innerHTML = '<div class="loading-spinner"></div>';
        try {
            const resp = await fetch(cfg.siteUrl + '/orders/order.php?action=my_orders');
            const data = await resp.json();
            if (!data.success || !data.orders.length) {
                container.innerHTML = `<div class="empty-state"><i class="bx bx-bowl-rice"></i><p>No orders found.</p></div>`;
                return;
            }
            container.innerHTML = `
                <div class="data-table-wrapper">
                    <table class="data-table">
                        <thead><tr>
                            <th>Ref</th><th>Items</th><th>Room</th><th>Total</th><th>Status</th>
                        </tr></thead>
                        <tbody>
                        ${data.orders.map(o => `<tr>
                            <td>${o.order_ref}</td>
                            <td>${o.items_summary || '—'}</td>
                            <td>${o.room_number}</td>
                            <td>$${parseFloat(o.total_amount).toFixed(2)}</td>
                            <td><span class="status-badge status-${o.status}">${o.status}</span></td>
                        </tr>`).join('')}
                        </tbody>
                    </table>
                </div>`;
        } catch (err) {
            container.innerHTML = `<div class="empty-state"><i class="bx bx-error"></i><p>Failed to load orders.</p></div>`;
        }
    }

    // ── Contact Form ─────────────────────────────────────────────
    document.getElementById('contactForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('[type=submit]');
        const msg = document.getElementById('contactMessage');
        setButtonLoading(btn, true);
        clearMessage(msg);
        try {
            const body = new URLSearchParams(new FormData(e.target));
            const resp = await fetch(cfg.siteUrl + '/api/newsletter.php', { method: 'POST', body });
            const data = await resp.json();
            showMessage(msg, data.message, data.success ? 'success' : 'error');
            if (data.success) { e.target.reset(); showNotification('✅ Message sent! We\'ll be in touch soon.', 'success'); }
        } catch {
            showMessage(msg, 'Network error. Please try again.', 'error');
        } finally {
            setButtonLoading(btn, false);
        }
    });

    /* ============================================================
       NEWSLETTER FORM
       ============================================================ */
    document.getElementById('newsletterForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = e.target.querySelector('input[type="email"]');
        if (!input?.value) return;
        try {
            const body = new URLSearchParams({ action: 'subscribe', email: input.value });
            const resp = await fetch(cfg.siteUrl + '/api/newsletter.php', { method: 'POST', body });
            const data = await resp.json();
            showNotification(data.message, data.success ? 'success' : 'error');
            if (data.success) input.value = '';
        } catch {
            showNotification('Thank you for subscribing!', 'success');
            input.value = '';
        }
    });

    /* ============================================================
       SMOOTH SCROLL
       ============================================================ */
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                closeMobileMenu();
                window.scrollTo({ top: target.offsetTop - 80, behavior: 'smooth' });
            }
        });
    });

    /* ============================================================
       SCROLL ANIMATIONS
       ============================================================ */
    function animateOnScroll() {
        document.querySelectorAll('.fade-in:not(.visible)').forEach(el => {
            if (el.getBoundingClientRect().top < window.innerHeight * 0.88) {
                el.classList.add('visible');
            }
        });
    }
    window.addEventListener('scroll', animateOnScroll, { passive: true });
    setTimeout(animateOnScroll, 300);

    /* ============================================================
       ACTIVE NAV LINK ON SCROLL
       ============================================================ */
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav-link');
    window.addEventListener('scroll', () => {
        let current = '';
        sections.forEach(s => { if (window.scrollY >= s.offsetTop - 100) current = s.id; });
        navLinks.forEach(link => {
            link.classList.toggle('active', link.getAttribute('href') === `#${current}`);
        });
    }, { passive: true });

    /* ============================================================
       BACK TO TOP
       ============================================================ */
    const backToTop = document.querySelector('.back-to-top');
    window.addEventListener('scroll', () => {
        backToTop?.classList.toggle('visible', window.scrollY > 300);
    }, { passive: true });

    /* ============================================================
       NOTIFICATION SYSTEM
       ============================================================ */
    function showNotification(message, type = 'info') {
        const n = document.createElement('div');
        n.className = `notification notification-${type}`;
        n.innerHTML = `
            <i class="bx ${type === 'success' ? 'bx-check-circle' : type === 'error' ? 'bx-error-circle' : 'bx-info-circle'}"></i>
            <span>${message}</span>
            <button class="notification-close">&times;</button>`;
        document.body.appendChild(n);
        setTimeout(() => n.classList.add('show'), 10);
        n.querySelector('.notification-close').addEventListener('click', () => dismiss(n));
        setTimeout(() => dismiss(n), 5000);
    }
    function dismiss(el) {
        el.classList.remove('show');
        setTimeout(() => el.parentNode?.removeChild(el), 350);
    }

    /* ============================================================
       OPEN MODAL ON PAGE LOAD (e.g. ?modal=membership)
       ============================================================ */
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('modal') === 'membership') {
        setTimeout(() => openModal('membershipModal'), 600);
    }

    /* ============================================================
       ★ AUTO-FILL ON PAGE LOAD
       If user is already logged in when page loads, pre-fill forms
       so fields are ready before any modal is opened.
       ============================================================ */
    if (cfg.isLoggedIn) {
        // Fill booking form fields (they exist in DOM even before modal opens)
        fillField('booking-name',  cfg.userName);
        fillField('booking-email', cfg.userEmail);
        fillField('booking-city',  cfg.userCity);
        fillField('order-name',    cfg.userName);
        // Fetch active room async — runs in background, non-blocking
        fetchActiveRoom();
    }

}); // end DOMContentLoaded
