<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('session.use_only_cookies', 1);
// index.php
if (session_status() == PHP_SESSION_NONE) {
    require_once 'includes/session.php';
    session_init();
}



// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : 'Guest';
$current_crusade_code = $_SESSION['current_crusade_code'] ?? '';
$current_meeting_code = $_SESSION['current_meeting_code'] ?? '';
?>
<style>
        body {
            padding-top: 0 !important;
        }
    </style>
<?php include 'includes/header.php'; ?>
<body>
    <?php include 'includes/navbar.php'; ?>
    <!-- Hero Section -->
    <section class="hero-section" id="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7 mx-auto text-center">
                    <div class="hero-content">
                        <h1 class="display-3 fw-bold mb-4" data-aos="fade-up" data-aos-duration="1000">Praise, Worship, and Connection.</h1>
                        <p class="lead mb-5" data-aos="fade-up" data-aos-duration="1200" data-aos-delay="200">Experience the joy of fellowship and connect with your community in a virtual space designed for praise.</p>
                        <?php if ($is_logged_in): ?>
                            <a href="meeting-room.php" class="btn btn-primary-custom btn-lg me-3 cta-button-test" data-aos="fade-up" data-aos-duration="1400" data-aos-delay="400">Start a Meeting</a>
                            <a href="live_crusade.php" class="btn btn-secondary-custom btn-lg cta-button-test" data-aos="fade-up" data-aos-duration="1400" data-aos-delay="500">Watch Live Crusade</a>
                        <?php else: ?>
                            <div class="d-grid gap-2 d-md-block">
                                <a href="register.php" class="btn btn-primary-custom btn-lg me-md-3 cta-button-test" data-aos="fade-up" data-aos-duration="1400" data-aos-delay="400">Get Started</a>
                                <a href="login.php" class="btn btn-secondary-custom btn-lg cta-button-test" data-aos="fade-up" data-aos-duration="1400" data-aos-delay="500">Sign In</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </section>

    <?php if ($is_logged_in): ?>
    <section class="quick-actions-section py-4">
        <div class="container">
            <div class="row g-3">
                <?php if (!empty($current_crusade_code)): ?>
                <div class="col-md-6">
                    <div class="card p-4 text-center">
                        <h5 class="mb-2">Resume Your Crusade</h5>
                        <p class="text-muted mb-3">Jump back into your live crusade room.</p>
                        <a href="crusade_room.php?code=<?php echo htmlspecialchars($current_crusade_code); ?>" class="btn btn-primary">Resume Crusade</a>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!empty($current_meeting_code)): ?>
                <div class="col-md-6">
                    <div class="card p-4 text-center">
                        <h5 class="mb-2">Resume Your Meeting</h5>
                        <p class="text-muted mb-3">Continue where you left off.</p>
                        <a href="meeting-room.php?code=<?php echo htmlspecialchars($current_meeting_code); ?>" class="btn btn-outline-primary">Resume Meeting</a>
                    </div>
                </div>
                <?php endif; ?>
                <div class="col-md-4">
                    <div class="card p-4 text-center">
                        <h5 class="mb-2">Create Crusade</h5>
                        <p class="text-muted mb-3">Start a new praise event.</p>
                        <a href="create_crusade.php" class="btn btn-primary">Create</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-4 text-center">
                        <h5 class="mb-2">Live TV</h5>
                        <p class="text-muted mb-3">Watch the official stream.</p>
                        <a href="live_tv.php" class="btn btn-outline-primary">Watch</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-4 text-center">
                        <h5 class="mb-2">Video Library</h5>
                        <p class="text-muted mb-3">Browse curated videos.</p>
                        <a href="videos.php" class="btn btn-outline-primary">Browse</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <h2 class="section-title" data-aos="fade-up">Why Choose Virtual Praise Room?</h2>
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4 d-flex align-items-stretch" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-people-group"></i></div>
                        <h3>Community Focused</h3>
                        <p>Our platform is meticulously designed to foster a profound sense of community and connection among users through collective worship.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4 d-flex align-items-stretch" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-church"></i></div>
                        <h3>Dedicated Praise Space</h3>
                        <p>Experience an environment crafted specifically for praise and worship, with unique features to enhance your spiritual journey.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4 d-flex align-items-stretch" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                        <h3>Secure & Private</h3>
                        <p>Your spiritual gatherings are kept private and secure with state-of-the-art encryption, ensuring your moments of worship remain intimate.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Combined CTA Section -->
    <section class="cta-combined-section py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6 mb-4 mb-md-0" data-aos="fade-right">
                    <div class="cta-block p-5 text-center rounded shadow-lg h-100">
                        <h3 class="mb-3">Start an Unending Praise Crusade</h3>
                        <p class="mb-4">Create your own virtual praise room and invite people to join you in praise and worship.</p>
                        <a href="crusade.php" class="btn btn-primary-custom btn-custom cta-button-test">Start a Crusade</a>
                    </div>
                </div>
                <div class="col-md-6" data-aos="fade-left">
                    <div class="cta-block p-5 text-center rounded shadow-lg h-100">
                        <h3 class="mb-3">Watch The Official Live Crusade</h3>
                        <p class="mb-4">Join our main live event, set by the admin. Watch and share with your community!</p>
                        <a href="live_crusade.php" class="btn btn-primary-custom btn-custom cta-button-test">Watch The Official Crusade</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Promo Cards Section -->
    <section class="promo-cards-section">
        <div class="container">
            <h2 class="section-title" data-aos="fade-up">Explore Our Features</h2>
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4 d-flex align-items-stretch" data-aos="fade-up" data-aos-delay="100">
                    <div class="card promo-card">
                        <div class="card-img-top promo-image-placeholder"></div>
                        <div class="card-body text-center">
                            <h5 class="card-title">Seamless Virtual Meetings</h5>
                            <p class="card-text">Host and join praise sessions with crystal-clear audio and video. Connect globally!</p>
                            <a href="create_meeting.php" class="btn btn-primary-custom btn-sm cta-button-test">Learn More</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4 d-flex align-items-stretch" data-aos="fade-up" data-aos-delay="200">
                    <div class="card promo-card">
                        <div class="card-img-top promo-image-placeholder promo-image-alt"></div>
                        <div class="card-body text-center">
                            <h5 class="card-title">Join Live Crusades</h5>
                            <p class="card-text">Participate in official live crusades and connect with a larger community of faith.</p>
                            <a href="live_crusade.php" class="btn btn-secondary-custom btn-sm cta-button-test">Watch Now</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4 d-flex align-items-stretch" data-aos="fade-up" data-aos-delay="300">
                    <div class="card promo-card">
                        <div class="card-img-top promo-image-placeholder promo-image-warm"></div>
                        <div class="card-body text-center">
                            <h5 class="card-title">Share Your Testimony</h5>
                            <p class="card-text">Inspire others by sharing your personal journey of faith and experience.</p>
                            <a href="share_testimony.php" class="btn btn-primary-custom btn-sm cta-button-test">Share Now</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials-section">
        <div class="container">
            <h2 class="section-title" data-aos="fade-up">What Our Users Say</h2>
            <div id="testimonialCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <div class="testimonial-card p-4 text-center">
                                    <img src="https://i.pravatar.cc/150?img=1" alt="User 1" class="testimonial-img rounded-circle mb-3">
                                    <p class="lead">"This platform has been a blessing for our community. It's so easy to use and has helped us stay connected and grow in faith."</p>
                                    <h5 class="mt-3">- Sarah W.</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <div class="testimonial-card p-4 text-center">
                                    <img src="https://i.pravatar.cc/150?img=2" alt="User 2" class="testimonial-img rounded-circle mb-3">
                                    <p class="lead">"I love the focus on praise and worship. It's a great way to connect with God and with others in a truly inspiring virtual space."</p>
                                    <h5 class="mt-3">- John D.</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <div class="testimonial-card p-4 text-center">
                                    <img src="https://i.pravatar.cc/150?img=3" alt="User 3" class="testimonial-img rounded-circle mb-3">
                                    <p class="lead">"The best virtual meeting platform I've ever used. It's so simple, intuitive, and creates a powerful atmosphere for spiritual connection."</p>
                                    <h5 class="mt-3">- Emily R.</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    <p class="mb-0">&copy; 2026 Virtual Praise Room. All Rights Reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <ul class="list-inline footer-links">
                        <li class="list-inline-item"><a href="#">Privacy Policy</a></li>
                        <li class="list-inline-item"><a href="#">Terms of Service</a></li>
                        <li class="list-inline-item"><a href="#">About Us</a></li>
                        <li class="list-inline-item"><a href="#">Contact</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <?php include 'includes/bottom_navbar.php'; ?>
    <?php include 'includes/footer.php'; ?>
