@extends('layouts.app')
@section('content')
 		<!-- Modal Search Start -->
       <!--  <div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-fullscreen">
                <div class="modal-content rounded-0">
                    <div class="modal-header">
                        <h4 class="modal-title mb-0" id="exampleModalLabel">Search by keyword</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body d-flex align-items-center">
                        <div class="input-group w-75 mx-auto d-flex">
                            <input type="search" class="form-control p-3" placeholder="keywords" aria-describedby="search-icon-1">
                            <span id="search-icon-1" class="input-group-text p-3"><i class="fa fa-search"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div> -->
        <!-- Modal Search End -->



        <!-- Carousel Start -->
        <div class="container-fluid carousel-header px-0">
            <div id="carouselId" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner" role="listbox">
                    <div class="carousel-item active">
                        <img src="{{asset('img/banner-1.png')}}" class="img-fluid" alt="Image">
                        <div class="carousel-caption">
                        	Totale ontspanning in de natuur
                            <!-- <div class="p-3" style="max-width: 900px;">
                                <h4 class="text-primary text-uppercase mb-3">Spa & Beauty Center</h4>
                                <h1 class="display-1 text-capitalize text-dark mb-3">Massage Treatment</h1>
                                <p class="mx-md-5 fs-4 px-4 mb-5 text-dark">Lorem rebum magna dolore amet lorem eirmod magna erat diam stet. Sadips duo stet amet amet ndiam elitr ipsum</p>
                                <div class="d-flex align-items-center justify-content-center">
                                    <a class="btn btn-light btn-light-outline-0 rounded-pill py-3 px-5 me-4" href="#">Get Start</a>
                                    <a class="btn btn-primary btn-primary-outline-0 rounded-pill py-3 px-5" href="#">Book Now</a>
                                </div>
                            </div> -->
                        </div>
                        <div class="carousel-overlay"></div>
                    </div>
                   <!--  <div class="carousel-item">
                        <img src="{{asset('template/img/carousel-2.jpg')}}" class="img-fluid" alt="Image">
                        <div class="carousel-caption">
                            <div class="p-3" style="max-width: 900px;">
                                <h4 class="text-primary text-uppercase mb-3" style="letter-spacing: 3px;">Spa & Beauty Center</h4>
                                <h1 class="display-1 text-capitalize text-dark mb-3">Facial Treatment</h1>
                                <p class="mx-md-5 fs-4 px-5 mb-5 text-dark">Lorem rebum magna dolore amet lorem eirmod magna erat diam stet. Sadips duo stet amet amet ndiam elitr ipsum</p>
                                <div class="d-flex align-items-center justify-content-center">
                                    <a class="btn btn-light btn-light-outline-0 rounded-pill py-3 px-5 me-4" href="#">Get Start</a>
                                    <a class="btn btn-primary btn-primary-outline-0 rounded-pill py-3 px-5" href="#">Book Now</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <img src="{{asset('template/img/carousel-1.jpg')}}" class="img-fluid" alt="Image">
                        <div class="carousel-caption">
                            <div class="p-3" style="max-width: 900px;">
                                <h4 class="text-primary text-uppercase mb-3" style="letter-spacing: 3px;">Spa & Beauty Center</h4>
                                <h1 class="display-1 text-capitalize text-dark">Cellulite Treatment</h1>
                                <p class="mx-md-5 fs-4 px-5 mb-5 text-dark">Lorem rebum magna dolore amet lorem eirmod magna erat diam stet. Sadips duo stet amet amet ndiam elitr ipsum</p>
                                <div class="d-flex align-items-center justify-content-center">
                                    <a class="btn btn-light btn-light-outline-0 rounded-pill py-3 px-5 me-4" href="#">Get Start</a>
                                    <a class="btn btn-primary btn-primary-outline-0 rounded-pill py-3 px-5" href="#">Book Now</a>
                                </div>
                            </div>
                        </div>
                    </div> -->
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#carouselId" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#carouselId" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        </div>
        <!-- Carousel End -->
        <!-- About Start -->
        <div class="container-fluid about py-5" id="about">
            <div class="container py-5">
                <div class="row ">
                    <div class="col-lg-6">
                        <div class="video">
                        	<div class="img-fluid img-about-top"></div>
                            <div class="position-absolute img-about-bottom" >
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <p class="title-about-us">About us</p>

                        <div class="content-about-us">
                        	<h3>Exclusieve oase voor innerlijke balans en rust</h3>
                        	<div class="content">
                        		Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting,
                        	</div>
                        </div>
                        <div class="bottom-about">
                        	<a href="#" class="btn btn-primary btn-booking-now btn-view-more">View more</a>
                        	<a href="appointment.html" class="btn btn-primary btn-booking-now "><img src="img/calendar.svg" alt="">Book now</a>
                        </div>
                    </div> 
                </div>
                <img src="img/leaf-1.png" alt="" class="position-absolute img-leaf-1">
                <img src="img/leaf-2.png" alt="" class="position-absolute img-leaf-2">
                <img src="img/leaf-3.png" alt="" class="position-absolute img-leaf-3">
            </div>
        </div>
        <!-- Modal Video -->
        <!-- About End -->

        <!-- Services Start -->
        <div class="container-fluid services py-5">
            <div class="container py-5">
                <div class="mx-auto text-center mb-5 title-service" style="max-width: 930px;">
                	<p class="header-title">PAMPER YOURSELF</p>
                    <p class="text-center content-title-service">Our Service</p>
                    <p class="content-title">Step into a world of calm and recovery. Discover the benefits of our treatments, from stress relief and improved blood circulation to stimulating hair growth and easing headaches. Each experience embraces your well-being.</p>
                </div>
                <div class="row g-4 row-service">
                    <div class="col-lg-3">
                       <div class="service-item">
                       	<div class="img-service" style="background-image: url(img/body.png);">
                       		
                       	</div>
                       	<div class="content-service">
                       		<div class="title-item">Body Massage</div>
                       		<div class="content-item">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's ...</div>
                       		<div class="footer-item">
                       			<a href="#" class="view-more">View more <img src="img/arrow-right.svg" alt=""></a>
                       		</div>
                       	</div>

                       </div>
                    </div>
                    <div class="col-lg-3">
                       <div class="service-item">
                       	<div class="img-service" style="background-image: url(img/foot.png);">
                       		
                       	</div>
                       	<div class="content-service">
                       		<div class="title-item">Partial Body</div>
                       		<div class="content-item">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's ...</div>
                       		<div class="footer-item">
                       			<a href="#" class="view-more">View more <img src="img/arrow-right.svg" alt=""></a>
                       		</div>
                       	</div>

                       </div>
                    </div>
                    <div class="col-lg-3">
                       <div class="service-item">
                       	<div class="img-service" style="background-image: url(img/head.png);">
                       		
                       	</div>
                       	<div class="content-service">
                       		<div class="title-item">Head Spa</div>
                       		<div class="content-item">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's ...</div>
                       		<div class="footer-item">
                       			<a href="#" class="view-more">View more <img src="img/arrow-right.svg" alt=""></a>
                       		</div>
                       	</div>

                       </div>
                    </div>
                    <div class="col-lg-3">
                       <div class="service-item">
                       	<div class="img-service" style="background-image: url(img/packages.png);">
                       		
                       	</div>
                       	<div class="content-service">
                       		<div class="title-item">Packages</div>
                       		<div class="content-item">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's ...</div>
                       		<div class="footer-item">
                       			<a href="#" class="view-more">View more <img src="img/arrow-right.svg" alt=""></a>
                       		</div>
                       	</div>

                       </div>
                    </div>
                   
                   
                </div>
                <div class="row row-booking">
	                <div class="col-lg-7 left-booking">
	                	 <div class="appointment-form">
                            <h1 class="display-4  text-white">Booking</h1>
                            <form>
                                <div class="row gy-3 gx-4">
                                    <div class="col-lg-6">
                                        <input type="text" class="form-control " placeholder="First Name">
                                    </div>
                                    <div class="col-lg-6">
                                        <input type="text" class="form-control " placeholder="Phone">
                                    </div>
                                    <div class="col-lg-6">
                                        <input type="email" class="form-control " placeholder="Email">
                                    </div>
                                    <div class="col-lg-6">
                                        <select class="form-select form-control" aria-label="Default select example">
                                            <option selected>Open this select menu</option>
                                            <option value="1">One</option>
                                            <option value="2">Two</option>
                                            <option value="3">Three</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-6">
                                        <input type="date" class="form-control ">
                                    </div>
                                    <div class="col-lg-6">
                                        <select class="form-select form-control" aria-label="Default select example">
                                            <option selected>Open this select menu</option>
                                            <option value="1">One</option>
                                            <option value="2">Two</option>
                                            <option value="3">Three</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-12">
                                        <button type="button" class="btn btn-book-now w-100 "><img src="img/calendar-green.svg" alt="">Book now</button>
                                    </div>
                                </div>
                            </form>
                        </div>
	                </div>
	                <div class="col-lg-5 right-booking" style="background-image: url(img/booking.png);">
	                	
	                </div>
                </div>
            </div>
        </div>
        <!-- Services End -->

        
        






        <!-- Testimonial Start -->
        <div class="container-fluid testimonial py-5">
            <div class="container py-5">
            	<div class="row">
            		<div class="col-lg-6">
            			<div class=" mx-auto mb-5 title-service" style="max-width: 800px;">
            				<p class="header-title">Our Testimonials</p>
		                    <p class=" content-title-service">What they’re saying</p>
		                    <p class="content-title">Lorem Ipsum is simply dummy text of the printing and typesetting industry.</p>
            			</div>
            			<div class="owl-carousel testimonial-carousel">
            				<div class="testimonial-item  p-4">
            					<div class="name-testimonial">Van Sella </div>
            					<div class="address-testimonial">Amsterdam</div>
            					<div class="content-testimonial">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.</div>
            				</div>
            				<div class="testimonial-item  p-4">
            					<div class="name-testimonial">Van Sella </div>
            					<div class="address-testimonial">Amsterdam</div>
            					<div class="content-testimonial">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.</div>
            				</div>
            				<div class="testimonial-item  p-4">
            					<div class="name-testimonial">Van Sella </div>
            					<div class="address-testimonial">Amsterdam</div>
            					<div class="content-testimonial">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.</div>
            				</div>
            			</div>
            		</div>
            		<div class="col-lg-6 right-testimonial">
            			<div class="img-testimonial img-testimonial-1">
            				<!-- <img src="img/testimonial-1.png" alt=""> -->
            					<img src="img/leaf-1.png" class="leaf-img leaf-img-1" alt="">
            					<img src="img/leaf-1.png" class="leaf-img leaf-img-2"  alt="">
            			</div>
            			<div class="img-testimonial img-testimonial-2">
            				<!-- <img src="img/testimonial-2.png" alt=""> -->
            				<img src="img/lotus.png" alt="">

            			
            			</div>

            		</div>
            	</div>
                
            </div>
        </div>
        <!-- Testimonial End -->


        <!-- Blog news -->
        <div class="container-fluid py-5">
            <div class="container py-5">
            	<div class="mx-auto text-center mb-5 title-service" style="max-width: 930px;">
                	<p class="header-title">VERWEN UWZELF</p>
                    <p class="text-center content-title-service">Blog & News</p>
                </div>
                <div class="row g-4 align-items-center">
                    <div class="col-lg-4">
                    	<div class="blog-item">
                    		<div class="blog-img" style="background-image:url(img/blog-1.png)"></div>
                    		<div class="blog-content">
                    			<div class="title-blog">Fitness and Strength Trainings <span class="underline-title"></span></div>
                    			<div class="content-blog">
                    				Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.
                    			</div>
                    			<a href="#" class="view-more">View more <img src="img/arrow-right.svg" alt=""></a>
                    		</div>
                    	</div>
                    </div>
                    <div class="col-lg-4">
                    	<div class="blog-item">
                    		<div class="blog-img" style="background-image:url(img/blog-2.jpg)"></div>
                    		<div class="blog-content">
                    			<div class="title-blog">Stress Management and Coping Strategies <span class="underline-title"></span></div>
                    			<div class="content-blog">
                    				Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.
                    			</div>
                    			<a href="#" class="view-more">View more <img src="img/arrow-right.svg" alt=""></a>
                    		</div>
                    		
                    	</div>
                    </div>
                    <div class="col-lg-4">
                    	<div class="blog-item">
                    		<div class="blog-img" style="background-image:url(img/blog-3.jpg)"></div>
                    		<div class="blog-content">
                    			<div class="title-blog">Nutrition and Healthy Eating Habits <span class="underline-title"></span></div>
                    			<div class="content-blog">
                    				Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.
                    			</div>
                    			<a href="#" class="view-more">View more <img src="img/arrow-right.svg" alt=""></a>
                    		</div>
                    		
                    	</div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Blog news  -->
@endsection