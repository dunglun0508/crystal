<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="utf-8">
        <title>Sparlex - Spa Website Template</title>
        <meta content="width=device-width, initial-scale=1.0" name="viewport">
        <meta content="" name="keywords">
        <meta content="" name="description">

        <!-- Google Web Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Lora:ital,wght@0,400..700;1,400..700&family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

        <!-- Icon Font Stylesheet -->
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css"/>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

        <!-- Libraries Stylesheet -->
        <link href="{{asset('template/lib/animate/animate.min.css')}}" rel="stylesheet">
        <link href="{{asset('template/lib/lightbox/css/lightbox.min.css')}}" rel="stylesheet">
        <link href="{{asset('template/lib/owlcarousel/assets/owl.carousel.min.css')}}" rel="stylesheet">


        <!-- Customized Bootstrap Stylesheet -->
        <link href="{{asset('template/css/bootstrap.min.css')}}" rel="stylesheet">

        <!-- Template Stylesheet -->
        <link href="{{asset('template/css/style.css')}}" rel="stylesheet">
        <link href="{{mix('css/style.css')}}" rel="stylesheet">
        <link href="{{mix('css/app.css')}}" rel="stylesheet">
    </head>

    <body>

        <!-- Spinner Start -->
        <div id="spinner" class="show w-100 vh-100 bg-white position-fixed translate-middle top-50 start-50  d-flex align-items-center justify-content-center">
            <div class="spinner-grow text-primary" role="status"></div>
        </div>
        <!-- Spinner End -->


        <!-- Navbar start -->
        <div class="container-fluid sticky-top px-0">
            <!-- <div class="container-fluid topbar d-none d-lg-block">
                <div class="container px-0">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <div class="d-flex flex-wrap">
                                <a href="#" class="me-4 text-light"><i class="fas fa-map-marker-alt text-primary me-2"></i>Find A Location</a>
                                <a href="#" class="me-4 text-light"><i class="fas fa-phone-alt text-primary me-2"></i>+01234567890</a>
                                <a href="#" class="text-light"><i class="fas fa-envelope text-primary me-2"></i>Example@gmail.com</a>
                            </div>

                        </div>
                        <div class="col-lg-4">
                            <div class="d-flex align-items-center justify-content-end">
                                <a href="#" class="me-3 btn-square border rounded-circle nav-fill"><i class="fab fa-facebook-f"></i></a>
                                <a href="#" class="me-3 btn-square border rounded-circle nav-fill"><i class="fab fa-twitter"></i></a>
                                <a href="#" class="me-3 btn-square border rounded-circle nav-fill"><i class="fab fa-instagram"></i></a>
                                <a href="#" class="btn-square border rounded-circle nav-fill"><i class="fab fa-linkedin-in"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div> -->
            <div class="container-fluid bg-light" id='header'>
                <div class="container px-0">
                    <!-- Nav desktop (ẩn trên mobile) -->
                    <nav class="navbar navbar-light navbar-expand-xl d-none d-xxl-flex">
                        <a href="index.html" class="navbar-brand">
                            <img src="{{asset('img/logo.png')}}" alt="">
                        </a>
                        <div class="collapse navbar-collapse  py-3 show" id="navbarCollapse">
                            <div class="navbar-nav mx-auto border-top">
                                <a href="index.html" class="nav-item nav-link active">Home</a>
                                <a href="service.html" class="nav-item nav-link">Our Services</a>
                                <a href="price.html" class="nav-item nav-link">Blog & News</a>
                                <a href="about.html" class="nav-item nav-link">About us</a>
                                <div class="nav-item dropdown">
                                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                                        <img src="{{asset('img/uk-flag.png')}}" alt="">
                                        <span>English</span>
                                        <img src='{{asset("img/arrow-down.svg")}}'>
                                    </a>
                                    <div class="dropdown-menu m-0 bg-secondary rounded-0">
                                        <a href="team.html" class="dropdown-item">
                                            <img src="{{asset('img/uk-flag.png')}}" alt="">
                                            <span>English</span>
                                        </a>
                                        <a href="testimonial.html" class="dropdown-item">
                                            <img src="{{asset('img/uk-flag.png')}}" alt="">
                                            <span>English</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center flex-nowrap pt-xl-0">
                                <button class="btn-search btn btn-primary btn-primary-outline-0 rounded-circle btn-lg-square" ><img src="{{asset('img/bag-2.svg')}}"></button>
                                <a href="appointment.html" class="btn btn-primary btn-booking-now "><img src="{{asset('img/calendar.svg')}}" alt="">Book now</a>
                            </div>
                        </div>
                    </nav>
                    <!-- Nav mobile (ẩn trên desktop) -->
                    <nav class="navbar navbar-light d-flex d-xxl-none justify-content-between align-items-center px-2 py-2">
                        <a href="index.html" class="navbar-brand">
                            <img src="{{asset('img/logo.png')}}" alt="" style="height:40px;">
                        </a>
                        <div class="d-flex align-items-center">
                            <button class="p-2 me-2 btn-cart-icon-mobile">
                                <img src="{{asset('img/bag-2.svg')}}" alt="" style="height:28px;">
                            </button>
                            <button class="p-2 btn-menu-icon-mobile" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
                                <img src="{{asset('img/menu-icon.svg')}}" alt="" style="height:24px;">
                            </button>
                        </div>
                    </nav>
                    <!-- Offcanvas Sidebar Bootstrap -->
                    <div class="offcanvas offcanvas-end" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
                      <div class="offcanvas-header">
                        <h5 class="offcanvas-title fw-bold" id="mobileSidebarLabel">Categories</h5>
                        <button type="button" class="btn-close-menu-icon-mobile" data-bs-dismiss="offcanvas" aria-label="Close">
                            <img src="{{asset('img/close-menu-icon.svg')}}" alt="" style="height:24px;">
                        </button>
                      </div>
                      <div class="offcanvas-body d-flex flex-column">
                        <a href="index.html" class="sidebar-link">Home</a>
                        <hr>
                        <a href="service.html" class="sidebar-link">Our Services</a>
                        <hr>
                        <a href="price.html" class="sidebar-link">Blog & News</a>
                        <hr>
                        <a href="about.html" class="sidebar-link">About us</a>
                        <hr>
                        <div class="nav-item nav-item-mobile dropdown">
                            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                                <img src="{{asset('img/uk-flag.png')}}" alt="">
                                <span>English</span>
                                <img src='{{asset("img/arrow-down.svg")}}'>
                            </a>
                            <div class="dropdown-menu m-0 bg-secondary rounded-0">
                                <a href="team.html" class="dropdown-item">
                                    <img src="{{asset('img/uk-flag.png')}}" alt="">
                                    <span>English</span>
                                </a>
                                <a href="testimonial.html" class="dropdown-item">
                                    <img src="{{asset('img/uk-flag.png')}}" alt="">
                                    <span>English</span>
                                </a>
                            </div>
                        </div>
                      </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Navbar End -->
        <?php 
            $routeName = Route::currentRouteName();

            if($routeName != 'home.dashboard'){
        ?>
        <div class="container-fluid container-breadcrumb">
            <div class="container d-flex" >
                <div class="title-breadcrumb">{{$titlePage}}</div>
                <div class="breadcrumb-list d-flex">
                    <img src="{{asset('img/home.svg')}}" alt=""> 
                    <ol class="breadcrumb justify-content-center mb-0">
                        @foreach($breadcrumb as $item => $url)
                            <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}"><a href="{{ $url }}">{{$item}}</a></li>
                        @endforeach
                    </ol>
                </div>
                
            </div>
            <img src="{{asset('img/breadcrumb-bg.png')}}" id="bg-breadcrumb-left" alt="">
            <img src="{{asset('img/breadcrumb-bg.png')}}" id="bg-breadcrumb-right" alt="">

          
        </div>
        <?php }?>

       @yield('content')



        <!-- Footer Start -->
        <div class="container-fluid footer py-5">
            <div class="container py-5">
                <div class="row g-5">
                    <div class="col-md-6 col-lg-6 col-xl-4">
                        <div class="footer-item text-center">
                            <h4 class="mb-4 footer-item-title">Onze Locatie</h4>
                            <p class="footer-content address">Japandi Hair and Head Spa </p>
                            <p class="footer-content address">Van Peltlaan 144, 6533 ZR</p>
                            <p class="footer-content address" style="margin-bottom: 16px;"> Nijmegen</p>
                            <p class="footer-content fw-bold">Gratis parkeren voor de deur!</p>
                             
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-6 col-xl-4">
                        <div class="footer-item text-center">
                            <h4 class="mb-4 footer-item-title">Contacteer ons</h4>
                            <p class="footer-content phone">+31 6 11646869 </p>
                            <p class="footer-content email" style="margin-bottom:26px">info@japandiheadspa.nl</p>
                            <div class="d-flex list-social justify-content-center">
                                <a href=""><img src="{{asset('img/instagram.svg')}}" alt=""></a>
                                <a href=""><img src="{{asset('img/facebook.svg')}}" alt=""></a>
                                <a href=""><img src="{{asset('img/whatup.svg')}}" alt=""></a>
                            </div>
                             
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-6 col-xl-4">
                        <div class="footer-item text-center">
                            <h4 class="mb-4 footer-item-title">Openingstijden</h4>
                            <p class="footer-content "><span class="fw-bold">Maandag - Dinsdag:</span> 10:00 - 18:00</p>
                            <p class="footer-content "><span class="fw-bold">Woensdag:</span> 10:00 - 20:00</p>
                            <p class="footer-content "><span class="fw-bold">Donderdag:</span>1 0:00 - 18:00</p>
                            <p class="footer-content "><span class="fw-bold">Zaterdag:</span> 10:00 - 15:00</p>
                            <p class="footer-content "><span class="fw-bold">Zondag:</span> GESLOTEN</p>
                              <p class="footer-content">Binnen deze tijden bent u op afspraak van harte welkom.</p>
                        </div>
                    </div>

                   <!--  <div class="col-md-6 col-lg-6 col-xl-4">
                        <div class="footer-item d-flex flex-column">
                            <h4 class="mb-4 text-white">Our Services</h4>
                            <a href=""><i class="fas fa-angle-right me-2"></i> Facials</a>
                            <a href=""><i class="fas fa-angle-right me-2"></i> Waxing</a>
                            <a href=""><i class="fas fa-angle-right me-2"></i> Message</a>
                            <a href=""><i class="fas fa-angle-right me-2"></i> Minarel baths</a>
                            <a href=""><i class="fas fa-angle-right me-2"></i> Body treatments</a>
                            <a href=""><i class="fas fa-angle-right me-2"></i> Aroma Therapy</a>
                            <a href=""><i class="fas fa-angle-right me-2"></i> Stone Spa</a>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-6 col-xl-4">
                        <div class="footer-item d-flex flex-column">
                            <h4 class="mb-4 text-white">Schedule</h4>
                            <p class="text-muted mb-0">Monday: <span class="text-white"> 09:00 am – 10:00 pm</span></p>
                            <p class="text-muted mb-0">Saturday: <span class="text-white"> 09:00 am – 08:00 pm</span></p>
                            <p class="text-muted mb-0">Sunday: <span class="text-white"> 09:00 am – 05:00 pm</span></p>
                            <h4 class="my-4 text-white">Address</h4>
                            <p class="mb-0"><i class="fas fa-map-marker-alt text-secondary me-2"></i> 123 ranking street North tower New York, USA</p>
                        </div>
                    </div> -->
                    <!-- <div class="col-md-6 col-lg-6 col-xl-3">
                        <div class="footer-item d-flex flex-column">
                            <h4 class="mb-4 text-white">Follow Us</h4>
                            <a href=""><i class="fas fa-angle-right me-2"></i> Faceboock</a>
                            <a href=""><i class="fas fa-angle-right me-2"></i> Instagram</a>
                            <a href=""><i class="fas fa-angle-right me-2"></i> Twitter</a>
                            <h4 class="my-4 text-white">Contact Us</h4>
                            <p class="mb-0"><i class="fas fa-envelope text-secondary me-2"></i> info@example.com</p>
                            <p class="mb-0"><i class="fas fa-phone text-secondary me-2"></i> (+012) 3456 7890 123</p>
                        </div>
                    </div> -->
                </div>
            </div>
        </div>
        <!-- Footer End -->



        <!-- Copyright Start -->
        <div class="container-fluid copyright py-4">
           <div class="content-copyright">© 2025 <span class="fw-bold">Levina Wellness</span>. All Rights Reserved</div>
        </div>
        <!-- Copyright End -->



        <!-- Back to Top -->
        <a href="#" class="btn btn-primary btn-primary-outline-0 btn-md-square rounded-circle back-to-top"><i class="fa fa-arrow-up"></i></a>   

        
    <!-- JavaScript Libraries -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{asset('template/lib/wow/wow.min.js')}}"></script>
    <script src="{{asset('template/lib/easing/easing.min.js')}}"></script>
    <script src="{{asset('template/lib/waypoints/waypoints.min.js')}}"></script>
    <script src="{{asset('template/lib/counterup/counterup.min.js')}}"></script>
    <script src="{{asset('template/lib/lightbox/js/lightbox.min.js')}}"></script>
    <script src="{{asset('template/lib/owlcarousel/owl.carousel.min.js')}}"></script>
    <script src="{{mix('js/frontend.js')}}" defer></script>
    <!-- Template Javascript -->
    <script src="{{asset('template/js/main.js')}}"></script>
    </body>

</html>