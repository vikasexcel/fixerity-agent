<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{request()->get('general_settings')->website_name != null ? request()->get('general_settings')->website_name : 'FOX HANDYMAN'}}</title>
    <link rel="icon"
          href="{{(request()->get('general_settings')->website_favicon != Null) ? asset('assets/images/website-logo-icon/'.request()->get('general_settings')->website_favicon) : ''  }}"
          type="image/x-icon">
    <link
        href="{{ (request()->get('general_settings')->website_favicon != Null) ? asset('assets/images/website-logo-icon/'.request()->get('general_settings')->website_favicon) : '' }}"
        rel="apple-touch-icon">
    <!-- Bootstrap 5 CSS -->
    <link href="{{ asset('assets/front/css/bootstrap_version_5.min.css') }}" rel="stylesheet">
    <style>
        :root {
            --theme-primary: {{ request()->get('general_settings')->theme_color }}; /* Your primary theme color */
            --theme-primary-opacity-2: rgba(40, 194, 113, 0.02);
            --theme-primary-opacity-30: rgba(40, 194, 113, 0.3);
            --theme-light: rgba(40, 194, 113, 0.1);
            --theme-medium-light: rgba(40, 194, 113, 0.5);
        }

        /* Custom Bootstrap-like background class */
        .bg-theme-gradient {
            background: linear-gradient(to right, var(--theme-primary-opacity-2) 2%, var(--theme-primary-opacity-30) 30%);
        }

        .bg-theme-light {
            background-color: var(--theme-light);
        }

        .bg-theme-color {
            background-color: var(--theme-primary);
        }

        .border-theme {
            /*box-shadow: 0px 4px 10px var(--theme-light); !* Custom shadow with theme color *!*/
            border-radius: 1px;
            border-color: var(--theme-primary) !important;
        }

        .custom-hover {
            color: white; /* Default Text Color */
            border-color: white; /* Default Border Color */
            transition: all 0.3s ease-in-out; /* Smooth Transition */
        }

        .custom-hover:hover {
            background-color: white !important; /* White Background */
            color: var(--theme-primary) !important; /* Theme Color Text */
            border-color: var(--theme-primary) !important; /* Theme Color Border */
        }

        .text-theme {
            color: var(--theme-primary);
        }

        .custom-box {
            box-shadow: 0px 0px 8px 0px var(--theme-primary-opacity-30); /* Shadow effect */
        }

        .custom-box:hover{
            border: 2px solid var(--theme-primary);
        }

        .custom-box:hover div h4, .card-box:hover div h4{
            color: var(--theme-primary) !important;
        }

        .card-box:hover{
            border: 1px solid var(--theme-primary);
        }


        body {
            margin-top: 76px; /* Your existing fixed top spacing */
        }

        html {
            scroll-padding-top: 76px; /* Prevent navbar from covering section */
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-theme-gradient fixed-top bg-white">
    <div class="container d-flex justify-content-between align-items-center">
        <!-- Brand Logo -->
        <a class="navbar-brand d-flex align-items-center" href="#">
            <img
                src="{{ !empty(request()->get('general_settings')->website_logo) ? asset('assets/images/website-logo-icon/'.request()->get('general_settings')->website_logo) : '' }}"
                alt="Fox HANDYMAN" height="50">
        </a>

        <!-- Mobile Menu Toggle Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Links (Centered) -->
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item active"><a class="nav-link" href="#">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="#about">About Us</a></li>
                <li class="nav-item"><a class="nav-link" href="#how-it-works">How It Works</a></li>
                <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                <li class="nav-item"><a class="nav-link" href="#more-features">More Features</a></li>
                <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<header class="jumbotron bg-theme-gradient">
    <div class="container">
        <div class="row align-items-center">
            <!-- Left Column (Text) -->
            <div class="col-md-6 text-center text-md-start">
                <h1 class="fw-bold text-theme mb-2">Fox Handyman - Find reliable handyman services near you!</h1>
                <p class="text-muted text-start">Fox-Handyman connects you with reliable, skilled professionals across the country. From plumbing and electrical work to home cleaning and appliance repair we’ve got you covered. Book services quickly and easily with our on-demand handyman app.</p>
                <p class="text-muted text-start">With real-time tracking, secure payments, and top-rated service providers, Fox-Handyman ensures quality service whenever and wherever you need it.</p>
                <p>Download the app today and experience the top handyman services in Country!</p>
                <a href="#contact" class="btn px-4 py-2 rounded-4 custom-hover bg-theme-color">Contact Us</a>
            </div>

            <!-- Right Column (Image) -->
            <div class="col-md-6 text-center">
                <img src="{{ asset('assets/front/img/hero-section-image.png') }}" class="img-fluid" alt="Hero Image">
            </div>
        </div>
    </div>
</header>

<!-- About Us Section -->
<section id="about" class="container py-5">
    <h2 class="text-center mb-4 d-flex flex-wrap justify-content-center align-items-center">
        <img src="{{ asset('assets/front/img/tital-left-icon.png') }}" class="img-fluid me-2" style="max-width: 10%;">
        <span>About Us</span>
        <img src="{{ asset('assets/front/img/tital-right-icon.png') }}" class="img-fluid ms-2" style="max-width: 10%;">
    </h2>

    <div class="row align-items-center">
        <!-- Image Column -->
        <div class="col-sm-12 col-lg-6 text-center">
            <img src="{{ asset('assets/front/img/about-us.png') }}" class="img-fluid rounded"
                 alt="About Us Image">
        </div>

        <!-- Text Column -->
        <div class="col-sm-12 col-lg-6">
            <div class="p-3">
                <p class="text-muted">At Fox-Handyman, we’re redefining home services with unmatched convenience, reliability, and quality. Our mission is to connect users with trusted professionals for plumbing, electrical work, cleaning, appliance repairs, and more.</p>
                <p class="text-muted">Fox-Handyman is built on customer satisfaction and innovation, using advanced technology for easy booking, secure payments, and real-time tracking. From urgent repairs to scheduled maintenance, we deliver a smooth, hassle-free experience.</p>
                <p class="text-muted">Join Fox-Handyman today and enjoy on-demand home services at your fingertips wherever you are in Country!</p>
            </div>
        </div>
    </div>
</section>

<!-- How Does FOX-HANDYMAN Work Section -->
<div class="bg-theme-light pt-0 pb-0" id="how-it-works">
    <section class="container text-center p-4">
        <h2 class="text-center mb-4 d-flex flex-wrap justify-content-center align-items-center">
            <img src="{{ asset('assets/front/img/tital-left-icon.png') }}" class="img-fluid me-2" style="max-width: 10%;">
            <span>How Does FOX-HANDYMAN Work?</span>
            <img src="{{ asset('assets/front/img/tital-right-icon.png') }}" class="img-fluid ms-2" style="max-width: 10%;">
        </h2>

        <div class="row justify-content-center gap-4">
            <!-- Card Start -->
            <div class="col-12 col-sm-10 col-md-8 col-lg-5">
                <div class="card p-3 rounded-4 card-box">
                    <div class="d-flex align-items-center text-start gap-3 mb-2">
                        <div class="bg-theme-color text-white p-2 rounded-3 d-flex align-items-center justify-content-center">
                            <img src="{{ asset('assets/front/img/how-does-app-works-icon.png') }}" alt="Icon" width="40">
                        </div>
                        <h4 class="fs-5 mb-0">Sign Up & Register</h4>
                    </div>
                    <p class="text-muted mb-0 text-start">Download the Fox-Handyman app & register as a user or service provider with basic details.</p>
                </div>
            </div>
            <!-- Card End -->

            <!-- Card Start -->
            <div class="col-12 col-sm-10 col-md-8 col-lg-5">
                <div class="card p-3 rounded-4 card-box">
                    <div class="d-flex align-items-center text-start gap-3 mb-2">
                        <div class="bg-theme-color text-white p-2 rounded-3 d-flex align-items-center justify-content-center">
                            <img src="{{ asset('assets/front/img/how-does-app-works-icon.png') }}" alt="Icon" width="40">
                        </div>
                        <h4 class="fs-5 mb-0">Browse & Select Services</h4>
                    </div>
                    <p class="text-muted mb-0 text-start">Users can explore a wide range of handyman services & choose the required one.</p>
                </div>
            </div>
            <!-- Card End -->

            <!-- Card Start -->
            <div class="col-12 col-sm-10 col-md-8 col-lg-5">
                <div class="card p-3 rounded-4 card-box">
                    <div class="d-flex align-items-center text-start gap-3 mb-2">
                        <div class="bg-theme-color text-white p-2 rounded-3 d-flex align-items-center justify-content-center">
                            <img src="{{ asset('assets/front/img/how-does-app-works-icon.png') }}" alt="Icon" width="40">
                        </div>
                        <h4 class="fs-5 mb-0">Book & Schedule</h4>
                    </div>
                    <p class="text-muted mb-0 text-start">Set the preferred date and time for the service, and confirm the booking.</p>
                </div>
            </div>
            <!-- Card End -->

            <!-- Card Start -->
            <div class="col-12 col-sm-10 col-md-8 col-lg-5">
                <div class="card p-3 rounded-4 card-box">
                    <div class="d-flex align-items-center text-start gap-3 mb-2">
                        <div class="bg-theme-color text-white p-2 rounded-3 d-flex align-items-center justify-content-center">
                            <img src="{{ asset('assets/front/img/how-does-app-works-icon.png') }}" alt="Icon" width="40">
                        </div>
                        <h4 class="fs-5 mb-0">Match with a Handyman</h4>
                    </div>
                    <p class="text-muted mb-0 text-start">Users select a provider & send a request. Once accepted, service begins.</p>
                </div>
            </div>
            <!-- Card End -->

            <!-- Card Start -->
            <div class="col-12 col-sm-10 col-md-8 col-lg-5">
                <div class="card p-3 rounded-4 card-box">
                    <div class="d-flex align-items-center text-start gap-3 mb-2">
                        <div class="bg-theme-color text-white p-2 rounded-3 d-flex align-items-center justify-content-center">
                            <img src="{{ asset('assets/front/img/how-does-app-works-icon.png') }}" alt="Icon" width="40">
                        </div>
                        <h4 class="fs-5 mb-0">Service Execution & Tracking</h4>
                    </div>
                    <p class="text-muted mb-0 text-start">Users can track the handyman’s arrival and progress while receiving real-time updates.</p>
                </div>
            </div>
            <!-- Card End -->

            <!-- Card Start -->
            <div class="col-12 col-sm-10 col-md-8 col-lg-5">
                <div class="card p-3 rounded-4 card-box">
                    <div class="d-flex align-items-center text-start gap-3 mb-2">
                        <div class="bg-theme-color text-white p-2 rounded-3 d-flex align-items-center justify-content-center">
                            <img src="{{ asset('assets/front/img/how-does-app-works-icon.png') }}" alt="Icon" width="40">
                        </div>
                        <h4 class="fs-5 mb-0">Payment & Review</h4>
                    </div>
                    <p class="text-muted mb-0 text-start">After completion, make a secure payment and leave a review based on service quality.</p>
                </div>
            </div>
            <!-- Card End -->
        </div>
    </section>
</div>

<!-- Customer App Features -->
<section id="features" class="container py-5">
    <h2 class="text-center mb-3 d-flex flex-wrap justify-content-center align-items-center">
        <img src="{{ asset('assets/front/img/tital-left-icon.png') }}" class="img-fluid me-2" style="max-width: 10%;">
        <span>Customer App Features</span>
        <img src="{{ asset('assets/front/img/tital-right-icon.png') }}" class="img-fluid ms-2" style="max-width: 10%;">
    </h2>

    <!-- Description Text -->
    <p class="text-center text-muted mx-auto" style="max-width: 600px;">Easily browse, book, and schedule trusted handyman services anytime, anywhere. Track your handyman in real time, pay securely through the app, and rate the service once it's complete.</p>

    <div class="row align-items-center mt-4">
        <div class="col-lg-3 col-md-12 text-center mb-3">
            <img src="{{ asset('assets/front/img/customer-app-features.png') }}"
                 class="img-fluid rounded-4" alt="App Preview">
        </div>
        <div class="col-lg-9">
            <div class="row">
                <div class="col-lg-6 col-md-12 mb-3">
                    <div class="d-flex flex-column gap-3">
                        <div class="card p-3 rounded-4 custom-box">
                            <div class="d-flex align-items-center gap-3">
                                <div
                                    class="icon bg-theme-color text-white p-2 rounded-3 d-flex align-items-center justify-content-center">
                                    <img src="{{ asset('assets/front/img/app-features-icon.svg') }}" alt="Car Icon"
                                         width="30">
                                </div>
                                <h4 class="fw-bold text-dark mb-0">Social Login</h4>
                            </div>
                            <p class="text-muted mt-2 mb-0">Users can quickly register or log in using their social media accounts like Facebook or Google, streamlining access to the app.​</p>
                        </div>
                        <div class="card p-3 rounded-4 custom-box">
                            <div class="d-flex align-items-center gap-3">
                                <div
                                    class="icon bg-theme-color text-white p-2 rounded-3 d-flex align-items-center justify-content-center">
                                    <img src="{{ asset('assets/front/img/app-features-icon.svg') }}" alt="Car Icon"
                                         width="30">
                                </div>
                                <h4 class="fw-bold text-dark mb-0">Schedule Booking</h4>
                            </div>
                            <p class="text-muted mt-2 mb-0">Users have the flexibility to book handyman services immediately or schedule them for a specific date and time that suits their convenience.</p>
                        </div>
                        <div class="card p-3 rounded-4 custom-box">
                            <div class="d-flex align-items-center gap-3">
                                <div
                                    class="icon bg-theme-color text-white p-2 rounded-3 d-flex align-items-center justify-content-center">
                                    <img src="{{ asset('assets/front/img/app-features-icon.svg') }}" alt="Car Icon"
                                         width="30">
                                </div>
                                <h4 class="fw-bold text-dark mb-0">Easy Payment</h4>
                            </div>
                            <p class="text-muted mt-2 mb-0">Multiple payment options are available, including cash, credit cards, and in-app wallets for a seamless and secure transaction.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-md-12 mb-3">
                    <div class="d-flex flex-column gap-3">
                        <div class="card p-3 rounded-4 custom-box">
                            <div class="d-flex align-items-center gap-3">
                                <div
                                    class="icon bg-theme-color text-white p-2 rounded-3 d-flex align-items-center justify-content-center">
                                    <img src="{{ asset('assets/front/img/app-features-icon.svg') }}" alt="Car Icon"
                                         width="30">
                                </div>
                                <h4 class="fw-bold text-dark mb-0">Real-time Tracking</h4>
                            </div>
                            <p class="text-muted mt-2 mb-0">Users can track the real-time location of their service provider, allowing them to monitor arrival times and service progress.</p>
                        </div>
                        <div class="card p-3 rounded-4 custom-box">
                            <div class="d-flex align-items-center gap-3">
                                <div
                                    class="icon bg-theme-color text-white p-2 rounded-3 d-flex align-items-center justify-content-center">
                                    <img src="{{ asset('assets/front/img/app-features-icon.svg') }}" alt="Car Icon"
                                         width="30">
                                </div>
                                <h4 class="fw-bold text-dark mb-0">Offers & Discounts</h4>
                            </div>
                            <p class="text-muted mt-2 mb-0"> Users can take advantage of various offers and discounts to reduce service costs, enhancing affordability and user satisfaction.</p>
                        </div>
                        <div class="card p-3 rounded-4 custom-box">
                            <div class="d-flex align-items-center gap-3">
                                <div
                                    class="icon bg-theme-color text-white p-2 rounded-3 d-flex align-items-center justify-content-center">
                                    <img src="{{ asset('assets/front/img/app-features-icon.svg') }}" alt="Car Icon"
                                         width="30">
                                </div>
                                <h4 class="fw-bold text-dark mb-0">Review & Rating</h4>
                            </div>
                            <p class="text-muted mt-2 mb-0">After service, users can provide feedback by rating and reviewing the, contributing to community trust and service quality.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Provider App Features -->
<section id="provider-features" class="container py-5">
    <h2 class="text-center mb-3 d-flex flex-wrap justify-content-center align-items-center">
        <img src="{{ asset('assets/front/img/tital-left-icon.png') }}" class="img-fluid me-2" style="max-width: 10%;">
        <span>Provider App Features</span>
        <img src="{{ asset('assets/front/img/tital-right-icon.png') }}" class="img-fluid ms-2" style="max-width: 10%;">
    </h2>

    <!-- Description Text -->
    <p class="text-center text-muted mx-auto" style="max-width: 600px;">Get instant job requests and manage them with real-time updates. Track earnings, chat with customers, and set your availability all from a simple, intuitive app.</p>

    <div class="row align-items-center mt-4">
        <div class="col-lg-9">
            <div class="row">
                <div class="col-lg-6 col-md-12 mb-3">
                    <div class="d-flex flex-column gap-3">
                        <div class="card p-3 rounded-4 custom-box">
                            <div class="d-flex align-items-center gap-3">
                                <div
                                    class="icon bg-theme-color text-white p-2 rounded-3 d-flex align-items-center justify-content-center">
                                    <img src="{{ asset('assets/front/img/app-features-icon.svg') }}" alt="Car Icon"
                                         width="30">
                                </div>
                                <h4 class="fw-bold text-dark mb-0">Registration</h4>
                            </div>
                            <p class="text-muted mt-2 mb-0"> Manage job requests in real time with full control over your schedule, earnings, and customer communication.</p>
                        </div>
                        <div class="card p-3 rounded-4 custom-box">
                            <div class="d-flex align-items-center gap-3">
                                <div
                                    class="icon bg-theme-color text-white p-2 rounded-3 d-flex align-items-center justify-content-center">
                                    <img src="{{ asset('assets/front/img/app-features-icon.svg') }}" alt="Car Icon"
                                         width="30">
                                </div>
                                <h4 class="fw-bold text-dark mb-0">Manage Services</h4>
                            </div>
                            <p class="text-muted mt-2 mb-0">Providers can list and manage offered services like plumbing or electrical work, with control over pricing and availability.</p>
                        </div>
                        <div class="card p-3 rounded-4 custom-box">
                            <div class="d-flex align-items-center gap-3">
                                <div
                                    class="icon bg-theme-color text-white p-2 rounded-3 d-flex align-items-center justify-content-center">
                                    <img src="{{ asset('assets/front/img/app-features-icon.svg') }}" alt="Car Icon"
                                         width="30">
                                </div>
                                <h4 class="fw-bold text-dark mb-0">Profile Management</h4>
                            </div>
                            <p class="text-muted mt-2 mb-0"> Providers can easily update their profile details, service radius, and photo to ensure accurate representation to users.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-md-12 mb-3">
                    <div class="d-flex flex-column gap-3">
                        <div class="card p-3 rounded-4 custom-box">
                            <div class="d-flex align-items-center gap-3">
                                <div
                                    class="icon bg-theme-color text-white p-2 rounded-3 d-flex align-items-center justify-content-center">
                                    <img src="{{ asset('assets/front/img/app-features-icon.svg') }}" alt="Car Icon"
                                         width="30">
                                </div>
                                <h4 class="fw-bold text-dark mb-0">Manage Requests</h4>
                            </div>
                            <p class="text-muted mt-2 mb-0">Providers can accept or reject requests based on availability, ensuring efficient scheduling & workload management.</p>
                        </div>
                        <div class="card p-3 rounded-4 custom-box">
                            <div class="d-flex align-items-center gap-3">
                                <div
                                    class="icon bg-theme-color text-white p-2 rounded-3 d-flex align-items-center justify-content-center">
                                    <img src="{{ asset('assets/front/img/app-features-icon.svg') }}" alt="Car Icon"
                                         width="30">
                                </div>
                                <h4 class="fw-bold text-dark mb-0">View Earnings</h4>
                            </div>
                            <p class="text-muted mt-2 mb-0">Providers track earnings through reports, offering insights into completed, canceled, running & pending requests.</p>
                        </div>
                        <div class="card p-3 rounded-4 custom-box">
                            <div class="d-flex align-items-center gap-3">
                                <div
                                    class="icon bg-theme-color text-white p-2 rounded-3 d-flex align-items-center justify-content-center">
                                    <img src="{{ asset('assets/front/img/app-features-icon.svg') }}" alt="Car Icon"
                                         width="30">
                                </div>
                                <h4 class="fw-bold text-dark mb-0">On/Off Status</h4>
                            </div>
                            <p class="text-muted mt-2 mb-0">Providers toggle availability between online & offline to control when they’re ready to accept new requests.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-12 text-center mb-3">
            <img src="{{ asset('assets/front/img/provider-app-features.png') }}"
                 class="img-fluid rounded-4" alt="App Preview">
        </div>
    </div>
</section>

<!-- Be The Part of Handyman Product Customer App -->
<section class="container py-3 py-lg-5">
    <div class="rounded-4 pt-md-5 px-md-5 pb-0">
        <!-- Title -->
        <h2 class="text-center mb-4 d-flex flex-wrap justify-content-center align-items-center">
            <img src="{{ asset('assets/front/img/tital-left-icon.png') }}" class="img-fluid me-2" style="max-width: 8%;">
            <span>Be The Part of Handyman Product Customer App</span>
            <img src="{{ asset('assets/front/img/tital-right-icon.png') }}" class="img-fluid ms-2" style="max-width: 8%;">
        </h2>

        <!-- Description -->
        <p class="text-muted mx-auto mb-4 text-center" style="max-width: 600px;">Join the Fox-Handyman Customer App and experience hassle-free service booking at your fingertips.</p>

        <div class="row align-items-center g-4">
            <!-- App Image -->
            <div class="col-lg-6 order-lg-1 order-2">
                <img src="{{ asset('assets/front/img/be-the-part-of-product-customer-app.png') }}"
                     class="img-fluid w-100 bg-theme-color rounded-5"
                     alt="Join Us Image">
            </div>

            <!-- Features & Download Buttons -->
            <div class="col-lg-6 order-lg-2 order-1">
                <div class="d-flex flex-column h-100">
                    <div class="mb-3">
                        <p class="text-muted">Join the Fox-Handyman Customer App to book trusted handyman services anytime, anywhere. From plumbing and electrical to cleaning and furniture assembly the right expert is just a few taps away. Get top-quality service from verified professionals, right when you need it.</p>
                        <p class="text-muted">Enjoy real-time tracking to monitor your service provider’s arrival and job progress. Pay securely using cards, wallets, or cash with multiple payment options. Access service updates, view past bookings, and quickly rebook your favorite handyman anytime.</p>
                    </div>

                    <!-- Download Buttons -->
                    <div class="d-flex flex-wrap justify-content-lg-start justify-content-center gap-3 mt-auto">
                        <a href="{{ request()->get('general_settings')->user_playstore_link ?? '' }}"
                           class="btn btn-dark d-flex align-items-center px-3 px-md-4 py-2 rounded-4">
                            <img src="{{ asset('assets/front/img/google-play.svg') }}" alt="play-store" width="25" class="me-2">
                            <div>
                                <span class="small text-capitalize">Get it on</span>
                                <span class="fw-bold text-capitalize d-block">Google Play</span>
                            </div>
                        </a>

                        <a href="{{ request()->get('general_settings')->user_appstore_link ?? '' }}"
                           class="btn btn-dark d-flex align-items-center px-3 px-md-4 py-2 rounded-4">
                            <img src="{{ asset('assets/front/img/app-store.svg') }}" alt="app-store" width="30" class="me-2">
                            <div>
                                <span class="small text-capitalize">Get it on</span>
                                <span class="fw-bold text-capitalize d-block">App Store</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Be The Part of Handyman Product Provider App -->
<section class="container py-3 py-lg-5">
    <div class="rounded-4 pt-md-5 px-md-5 pb-0">
        <!-- Title -->
        <h2 class="text-center mb-4 d-flex flex-wrap justify-content-center align-items-center">
            <img src="{{ asset('assets/front/img/tital-left-icon.png') }}" class="img-fluid me-2" style="max-width: 8%;">
            <span>Be The Part of Handyman Product  Provider App</span>
            <img src="{{ asset('assets/front/img/tital-right-icon.png') }}" class="img-fluid ms-2" style="max-width: 8%;">
        </h2>

        <!-- Description -->
        <p class="text-muted mx-auto mb-4 text-center" style="max-width: 600px;">Join Fox-Handyman as a provider and grow your business with ease.</p>
        <div class="row align-items-center g-4">
            <!-- Features & Download Buttons (Now First) -->
            <div class="col-lg-6 order-1">
                <div class="d-flex flex-column h-100">
                    <div class="mb-3">
                        <p class="text-muted">Join the Fox-Handyman Provider App and take your skills to the next level. Whether it’s carpentry, electrical work, or general home maintenance, finding customers is quick and easy. Get real-time job requests and choose what fits your schedule.</p>
                        <p class="text-muted">Track service locations with built-in navigation, chat with customers, and get paid instantly after completing tasks. Manage your profile, earnings, and service history through an easy-to-use dashboard. Join Fox-Handyman and unlock consistent job opportunities.</p>
                    </div>

                    <!-- Download Buttons -->
                    <div class="d-flex flex-wrap justify-content-lg-start justify-content-center gap-3 mt-auto">
                        <a href="{{ request()->get('general_settings')->provider_playstore_link ?? '' }}"
                           class="btn btn-dark d-flex align-items-center px-3 px-md-4 py-2 rounded-4">
                            <img src="{{ asset('assets/front/img/google-play.svg') }}" alt="play-store" width="25" class="me-2">
                            <div>
                                <span class="small text-capitalize">Get it on</span>
                                <span class="fw-bold text-capitalize d-block">Google Play</span>
                            </div>
                        </a>

                        <a href="{{ request()->get('general_settings')->provider_appstore_link ?? '' }}"
                           class="btn btn-dark d-flex align-items-center px-3 px-md-4 py-2 rounded-4">
                            <img src="{{ asset('assets/front/img/app-store.svg') }}" alt="app-store" width="30" class="me-2">
                            <div>
                                <span class="small text-capitalize">Get it on</span>
                                <span class="fw-bold text-capitalize d-block">App Store</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- App Image (Now Second) -->
            <div class="col-lg-6 order-2">
                <img src="{{ asset('assets/front/img/be-the-part-of-product-provider-app.png') }}"
                     class="img-fluid w-100 bg-theme-color rounded-5"
                     alt="Join Us Image">
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Section -->
<section class="container py-5">
    <div class="row align-items-center bg-theme-color text-white p-1 rounded-5">
        <!-- Left Section: Text & Buttons -->
        <div class="col-md-6">
            <h3 class="fw-bold ps-4">Book services or grow your business all with Fox-Handyman. Fast. Reliable. Easy.</h3>
            <div class="d-flex gap-3 mt-3 ps-4">
                <a href="#contact" class="btn btn-outline-light px-4 py-2 rounded-4 custom-hover">Contact Us</a>
            </div>
        </div>

        <!-- Right Section: Image (Fixed Positioning) -->
        <div class="col-md-6 text-center">
            <img src="{{ asset('assets/front/img/group.png') }}" class="img-fluid w-100" style="max-width: 400px;" alt="Car Sharing Image">
        </div>
    </div>
</section>

<!-- More Features Section -->
<section id="more-features" class="container py-5">
    <h2 class="text-center mb-4 d-flex flex-wrap justify-content-center align-items-center">
        <img src="{{ asset('assets/front/img/tital-left-icon.png') }}" class="img-fluid me-2" style="max-width: 10%;">
        <span>More Features</span>
        <img src="{{ asset('assets/front/img/tital-right-icon.png') }}" class="img-fluid ms-2" style="max-width: 10%;">
    </h2>

    <div class="row">
        <!-- Feature Cards (2 per row) -->
        <div class="col-md-6 mb-4">
            <div class="card p-3 rounded-4 shadow-sm border-theme">
                <!-- Row 1: Icon + Title -->
                <div class="d-flex align-items-center mb-2">
                    <div class="icon bg-theme-color p-2 rounded-3 me-3">
                        <img src="{{ asset('assets/front/img/app-features-icon.svg') }}" alt="Feature Icon"
                             width="30">
                    </div>
                    <h5 class="font-weight-bold mb-0">Instant Notifications</h5>
                </div>
                <!-- Row 2: Description -->
                <p class="mb-0">Stay updated with real-time alerts for bookings, job status, and payments. Get notified instantly about any changes.</p>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card p-3 rounded-4 shadow-sm border-theme">
                <!-- Row 1: Icon + Title -->
                <div class="d-flex align-items-center mb-2">
                    <div class="icon bg-theme-color p-2 rounded-3 me-3">
                        <img src="{{ asset('assets/front/img/app-features-icon.svg') }}" alt="Feature Icon"
                             width="30">
                    </div>
                    <h5 class="font-weight-bold mb-0">In-App Chat & Call</h5>
                </div>
                <!-- Row 2: Description -->
                <p class="mb-0">Communicate in-app via chat or call for seamless coordination between users & providers.</p>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card p-3 rounded-4 shadow-sm border-theme">
                <!-- Row 1: Icon + Title -->
                <div class="d-flex align-items-center mb-2">
                    <div class="icon bg-theme-color p-2 rounded-3 me-3">
                        <img src="{{ asset('assets/front/img/app-features-icon.svg') }}" alt="Feature Icon"
                             width="30">
                    </div>
                    <h5 class="font-weight-bold mb-0">Promo Codes & Discounts</h5>
                </div>
                <!-- Row 2: Description -->
                <p class="mb-0">Enjoy exclusive promo codes and seasonal discounts. Save more on every handyman service.</p>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card p-3 rounded-4 shadow-sm border-theme">
                <!-- Row 1: Icon + Title -->
                <div class="d-flex align-items-center mb-2">
                    <div class="icon bg-theme-color p-2 rounded-3 me-3">
                        <img src="{{ asset('assets/front/img/app-features-icon.svg') }}" alt="Feature Icon"
                             width="30">
                    </div>
                    <h5 class="font-weight-bold mb-0">Multi-Service Booking</h5>
                </div>
                <!-- Row 2: Description -->
                <p class="mb-0">Need more than one service? Book and schedule multiple handyman tasks in a single go</p>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card p-3 rounded-4 shadow-sm border-theme">
                <!-- Row 1: Icon + Title -->
                <div class="d-flex align-items-center mb-2">
                    <div class="icon bg-theme-color p-2 rounded-3 me-3">
                        <img src="{{ asset('assets/front/img/app-features-icon.svg') }}" alt="Feature Icon"
                             width="30">
                    </div>
                    <h5 class="font-weight-bold mb-0">Service History & Records</h5>
                </div>
                <!-- Row 2: Description -->
                <p class="mb-0"> View past bookings and transactions anytime. Rebook your favorite services in seconds.</p>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card p-3 rounded-4 shadow-sm border-theme">
                <!-- Row 1: Icon + Title -->
                <div class="d-flex align-items-center mb-2">
                    <div class="icon bg-theme-color p-2 rounded-3 me-3">
                        <img src="{{ asset('assets/front/img/app-features-icon.svg') }}" alt="Feature Icon"
                             width="30">
                    </div>
                    <h5 class="font-weight-bold mb-0">Ratings & Reviews</h5>
                </div>
                <!-- Row 2: Description -->
                <p class="mb-0">Share feedback and check provider ratings before booking. Ensure quality service by choosing top-rated professionals.</p>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer id="contact" class="bg-light text-dark py-4">
    <div class="container">
        <div class="row">
            <!-- Company Info -->
            <div class="col-md-3 text-start text-md-left mb-3 ps-1">
                <img src="{{ !empty(request()->get('general_settings')->website_logo) ? asset('assets/images/website-logo-icon/'.request()->get('general_settings')->website_logo) : '' }}" alt="Fox HANDYMAN" width="150" class="me-2">
                <p class="mt-2 text-start">From quick home repairs to expert maintenance, Fox-Handyman connects you instantly with trusted professionals. Get reliable handyman services, and more anytime, anywhere.</p>

                <!-- Social Media -->
                <h5 class="mt-4 text-start">Reach Out Us</h5>
                <div class="text-start">
                    <a href="{{ request()->get('general_settings')->instagram_link != null ? request()->get('general_settings')->instagram_link : 'https://www.instagram.com/whitelabelfox/'}}"><img src="{{ asset('assets\front\img\instagram.svg') }}" class="bg-theme-color img-fluid me-1 rounded-circle p-2" width="40"></a>

                    <a href="{{ isset($general_setting) && request()->get('general_settings')->facebook_link != null ? request()->get('general_settings')->facebook_link : 'https://www.facebook.com/'}}"><img src="{{ asset('assets\front\img\facebook.svg') }}" class="bg-theme-color img-fluid me-1 rounded-circle p-2" width="40"></a>

                    <a href="{{ isset($general_setting) && request()->get('general_settings')->linkedin_link != null ? request()->get('general_settings')->linkedin_link : 'https://www.linkedin.com/company/whitelabelfox/'}}"><img src="{{ asset('assets\front\img\linkedin.svg') }}" class="bg-theme-color img-fluid me-1 rounded-circle p-2" width="40"></a>
                </div>
            </div>

            <!-- Static Map -->
            <div class="col-md-6 text-center">
                <h5 class="mb-3">Our Location</h5>
                <img src="{{ asset('assets/front/img/map.png') }}" class="img-fluid" alt="Static Map" />
            </div>

            <div class="col-md-3">
                <!-- Contact Info -->
                <div class="text-start">
                    <h5 class="mb-3">Contact Us</h5>
                    <div class="d-flex align-items-center mb-2">
                        <img src="{{ asset('assets/front/img/location.svg') }}" class="bg-theme-color img-fluid me-2 rounded-circle p-2" width="40" />
                        <span>{{ request()->get('general_settings')->address ?? 'Rajkot, Gujarat, India. 360007' }}</span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <img src="{{ asset('assets/front/img/mail.svg') }}" class="bg-theme-color img-fluid me-2 rounded-circle p-2" width="40" />
                        <span>{{ request()->get('general_settings')->email ?? 'sales@whitelabelfox.com' }}</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <img src="{{ asset('assets/front/img/call.svg') }}" class="bg-theme-color img-fluid me-2 rounded-circle p-2" width="40" />
                        <span>{{ request()->get('general_settings')->contact_no ?? '+917984931943' }}</span>
                    </div>
                </div>
            </div>
            <!-- Divider -->
            <hr class="my-4">

            <!-- Copyright -->
            <p class="mt-3 mb-0 text-muted text-center">
                {{ request()->get('general_settings')->copy_right ?? '© Copyright 2022 White Label Fox.' }}
            </p>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JavaScript Bundle (Includes Popper) -->
<script src="{{ asset('assets/front/js/bootstrap_version_5.bundle.min.js') }}"></script>
</body>
</html>
