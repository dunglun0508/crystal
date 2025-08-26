@extends('layouts.app')
@section('content')

<!-- Blog news -->
<div class="container-fluid py-5 px-md-2 py-md-2" style="padding-bottom: 0px !important;">
	<nav class="navbar navbar-light navbar-expand-xl">
		<div class="navbar-nav mx-auto">
			<div class="service-nav d-flex justify-content-center gap-3">
				<div class="d-flex">
					<a href="#service-body-massage" class="nav-item nav-link active">Body massage</a>
				</div>
				<div class="d-flex">
					<a href="#partial-massage" class="nav-item nav-link active">Partial massage</a>
				</div>
				<div class="d-flex">
					<a href="#head-spa-massage" class="nav-item nav-link active">Head spa</a>
				</div>
				<div class="d-flex">
					<a href="#packages-massage" class="nav-item nav-link active">Packages</a>
				</div>
			</div>
		</div>
	</nav>

</div>

<div class="container-fluid py-5 px-md-3 container-service" id="service-body-massage">
	<div class="container py-4 px-md-0">
		<div class="mx-auto text-center mb-3 mb-md-0 title-service" style="max-width: 930px;">
			<p class="text-center content-title-service"><img src="img/service/title-left.svg" class="icon-title icon-title-left" alt="">Body massage<img src="img/service/title-right.svg" class="icon-title icon-title-right" alt=""><span class="underline-title"></span></p>
		</div>
		<!-- Desktop Layout (>= 1000px) -->
		<div class="row align-items-center d-none d-lg-flex" style="padding-top: 4rem;">
			<div class="col-lg-6">
				<div class="row service-item">
					<div class="col-lg-6 ">
						<div class="service-img" style="background-image:url(img/service/service-1.png)"></div>
					</div>
					<div class="col-lg-6">
						<div class="blog-content">
							<div class="title-blog">Vietnamese traditional massage <span class="underline-title"></span></div>
							<div class="content-blog">
								A perfect combination of Hot Stone, Swedish, and Dao massage. Our skilled therapists will harmoniously apply deep-tissue manipulation and soothing massage techniques during ... 
							</div>
							<a href="#" class="view-more">View more <img src="img/arrow-right.svg" alt=""></a>
						</div>
					</div>
				</div>
			</div>
			<div class="col-lg-6">
				<div class="row service-item">
					<div class="col-lg-6 ">
						<div class="service-img" style="background-image:url(img/service/service-2.png)"></div>
					</div>
					<div class="col-lg-6">
						<div class="blog-content">
							<div class="title-blog">Vietnamese herbal massage <span class="underline-title"></span></div>
							<div class="content-blog">
								A perfect combination of Hot Stone, Swedish, and Dao massage. Our skilled therapists will harmoniously apply deep-tissue manipulation and soothing massage techniques during ...
							</div>
							<a href="#" class="view-more">View more <img src="img/arrow-right.svg" alt=""></a>
						</div>
					</div>
				</div>
			</div>
			<div class="col-lg-6">
				<div class="row service-item">
					<div class="col-lg-6 ">
						<div class="service-img" style="background-image:url(img/service/service-3.png)"></div>
					</div>
					<div class="col-lg-6">
						<div class="blog-content">
							<div class="title-blog">Relaxing oil massage<span class="underline-title"></span></div>
							<div class="content-blog">
								A perfect combination of Hot Stone, Swedish, and Dao massage. Our skilled therapists will harmoniously apply deep-tissue manipulation and soothing massage ...
							</div>
							<a href="#" class="view-more">View more <img src="img/arrow-right.svg" alt=""></a>
						</div>
					</div>
				</div>
			</div>
			<div class="col-lg-6">
				<div class="row service-item">
					<div class="col-lg-6 ">
						<div class="service-img" style="background-image:url(img/service/service-4.png)"></div>
					</div>
					<div class="col-lg-6">
						<div class="blog-content">
							<div class="title-blog">Vietnamese combination massage<span class="underline-title"></span></div>
							<div class="content-blog">
								A perfect combination of Hot Stone, Swedish, and Dao massage. Our skilled therapists will harmoniously apply deep-tissue manipulation and soothing massage ...
							</div>
							<a href="#" class="view-more">View more <img src="img/arrow-right.svg" alt=""></a>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Mobile Slider (< 1000px) -->
		<div class="d-block d-lg-none" style="padding-top: 3rem;">
			<div class="custom-slider-container">
				<div class="custom-slider" id="serviceSlider">
					<div class="slider-track">
						<div class="slider-item">
							<div class="service-item">
								<div>
									<div class="service-img" style="background-image:url(img/service/service-1.png); height: 200px; background-size: cover; background-position: center; background-repeat: no-repeat; border-radius: 8px;"></div>
								</div>
								<div>
									<div class="blog-content">
										<div class="title-blog">Vietnamese traditional massage <span class="underline-title"></span></div>
										<div class="content-blog">
											A perfect combination of Hot Stone, Swedish, and Dao massage. Our skilled therapists will harmoniously apply deep-tissue manipulation and soothing massage techniques during ... 
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="slider-item">
							<div class="service-item">
								<div>
									<div class="service-img" style="background-image:url(img/service/service-2.png); height: 200px; background-size: cover; background-position: center; background-repeat: no-repeat; border-radius: 8px;"></div>
								</div>
								<div>
									<div class="blog-content">
										<div class="title-blog">Vietnamese herbal massage <span class="underline-title"></span></div>
										<div class="content-blog">
											A perfect combination of Hot Stone, Swedish, and Dao massage. Our skilled therapists will harmoniously apply deep-tissue manipulation and soothing massage techniques during ...
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="slider-item">
							<div class="service-item">
								<div>
									<div class="service-img" style="background-image:url(img/service/service-3.png); height: 200px; background-size: cover; background-position: center; background-repeat: no-repeat; border-radius: 8px;"></div>
								</div>
								<div>
									<div class="blog-content">
										<div class="title-blog">Relaxing oil massage<span class="underline-title"></span></div>
										<div class="content-blog">
											A perfect combination of Hot Stone, Swedish, and Dao massage. Our skilled therapists will harmoniously apply deep-tissue manipulation and soothing massage ...
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="slider-item">
							<div class="service-item">
								<div>
									<div class="service-img" style="background-image:url(img/service/service-4.png); height: 200px; background-size: cover; background-position: center; background-repeat: no-repeat; border-radius: 8px;"></div>
								</div>
								<div>
									<div class="blog-content">
										<div class="title-blog">Vietnamese combination massage<span class="underline-title"></span></div>
										<div class="content-blog">
											A perfect combination of Hot Stone, Swedish, and Dao massage. Our skilled therapists will harmoniously apply deep-tissue manipulation and soothing massage ...
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="container-fluid py-5 px-md-3 container-service" id="partial-massage">
	
	<div class="container py-4 px-md-0">
		<div class="mx-auto text-center mb-5 title-service" style="max-width: 930px;">
			<p class="text-center content-title-service"><img src="img/service/title-left.svg" class="icon-title icon-title-left" alt="">Partial Massage<img src="img/service/title-right.svg" class="icon-title icon-title-right" alt=""><span class="underline-title"></span></p>
		</div>
		<!-- Desktop Layout (>= 1000px) -->
		<div class="row  align-items-center d-none d-lg-flex" style="padding-top: 4rem;">
			<div class="col-lg-6">
				<div class="row service-item">
					<div class="col-lg-6 ">
						<div class="service-img" style="background-image:url(img/service/service-5.png)"></div>
					</div>
					<div class="col-lg-6">
						<div class="blog-content">
							<div class="title-blog">Neck &amp; shoulders <span class="underline-title"></span></div>
							<div class="content-blog">
								Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.
							</div>
							<a href="#" class="view-more">View more <img src="img/arrow-right.svg" alt=""></a>
						</div>
					</div>
					
				</div>
			</div>
			<div class="col-lg-6">
				<div class="row service-item">
					<div class="col-lg-6 ">
						<div class="service-img" style="background-image:url(img/service/service-6.png)"></div>
					</div>
					<div class="col-lg-6">
						<div class="blog-content">
							<div class="title-blog">Foot massage <span class="underline-title"></span></div>
							<div class="content-blog">
								Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.
							</div>
							<a href="#" class="view-more">View more <img src="img/arrow-right.svg" alt=""></a>
						</div>
					</div>
					
				</div>
			</div>
			<div class="col-lg-6">
				<div class="row service-item">
					<div class="col-lg-6 ">
						<div class="service-img" style="background-image:url(img/service/service-7.png)"></div>
					</div>
					<div class="col-lg-6">
						<div class="blog-content">
							<div class="title-blog">Hands &amp; arms massage <span class="underline-title"></span></div>
							<div class="content-blog">
								Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.
							</div>
							<a href="#" class="view-more">View more <img src="img/arrow-right.svg" alt=""></a>
						</div>
					</div>
					
				</div>
			</div>
			<div class="col-lg-6">
				<div class="row service-item">
					<div class="col-lg-6 ">
						<div class="service-img" style="background-image:url(img/service/service-8.png)"></div>
					</div>
					<div class="col-lg-6">
						<div class="blog-content">
							<div class="title-blog">Head massage<span class="underline-title"></span></div>
							<div class="content-blog">
								Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.
							</div>
							<a href="#" class="view-more">View more <img src="img/arrow-right.svg" alt=""></a>
						</div>
					</div>
					
				</div>
			</div>
			
		</div>

		<!-- Mobile Slider (< 1000px) -->
		<div class="d-block d-lg-none">
			<div class="custom-slider-container">
				<div class="custom-slider" id="partialSlider">
					<div class="slider-track">
						<div class="slider-item">
							<div class="service-item">
								<div>
									<div class="service-img" style="background-image:url(img/service/service-5.png); height: 200px; background-size: cover; background-position: center; background-repeat: no-repeat; border-radius: 8px;"></div>
								</div>
								<div>
									<div class="blog-content">
										<div class="title-blog">Neck &amp; shoulders <span class="underline-title"></span></div>
										<div class="content-blog">
											Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="slider-item">
							<div class="service-item">
								<div>
									<div class="service-img" style="background-image:url(img/service/service-6.png); height: 200px; background-size: cover; background-position: center; background-repeat: no-repeat; border-radius: 8px;"></div>
								</div>
								<div>
									<div class="blog-content">
										<div class="title-blog">Foot massage <span class="underline-title"></span></div>
										<div class="content-blog">
											Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="slider-item">
							<div class="service-item">
								<div>
									<div class="service-img" style="background-image:url(img/service/service-7.png); height: 200px; background-size: cover; background-position: center; background-repeat: no-repeat; border-radius: 8px;"></div>
								</div>
								<div>
									<div class="blog-content">
										<div class="title-blog">Hands &amp; arms massage <span class="underline-title"></span></div>
										<div class="content-blog">
											Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="slider-item">
							<div class="service-item">
								<div>
									<div class="service-img" style="background-image:url(img/service/service-8.png); height: 200px; background-size: cover; background-position: center; background-repeat: no-repeat; border-radius: 8px;"></div>
								</div>
								<div>
									<div class="blog-content">
										<div class="title-blog">Head massage<span class="underline-title"></span></div>
										<div class="content-blog">
											Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="container-fluid py-5 px-md-3 container-service" id="head-spa-massage">
	
	<div class="container py-4 px-md-0">
		<div class="mx-auto text-center mb-5 title-service" style="max-width: 930px;">
			<p class="text-center content-title-service"><img src="img/service/title-left.svg" class="icon-title icon-title-left" alt="">Head Spa<img src="img/service/title-right.svg" class="icon-title icon-title-right" alt=""><span class="underline-title"></span></p>
		</div>
		<!-- Desktop Layout (>= 1000px) -->
		<div class="row  align-items-center d-none d-lg-flex" style="padding-top: 4rem;">
			<div class="col-lg-6">
				<div class="row service-item">
					<div class="col-lg-6 ">
						<div class="service-img" style="background-image:url(img/service/service-9.png)"></div>
					</div>
					<div class="col-lg-6">
						<div class="blog-content">
							<div class="title-blog">Neck &amp; shoulders<span class="underline-title"></span></div>
							<div class="content-blog">
								Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.
							</div>
							<a href="#" class="view-more">View more <img src="img/arrow-right.svg" alt=""></a>
						</div>
					</div>
					
				</div>
			</div>
			<div class="col-lg-6">
				<div class="row service-item">
					<div class="col-lg-6 ">
						<div class="service-img" style="background-image:url(img/service/service-10.png)"></div>
					</div>
					<div class="col-lg-6">
						<div class="blog-content">
							<div class="title-blog">Foot massage <span class="underline-title"></span></div>
							<div class="content-blog">
								Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.
							</div>
							<a href="#" class="view-more">View more <img src="img/arrow-right.svg" alt=""></a>
						</div>
					</div>
					
				</div>
			</div>
			<div class="col-lg-6">
				<div class="row service-item">
					<div class="col-lg-6 ">
						<div class="service-img" style="background-image:url(img/service/service-11.png)"></div>
					</div>
					<div class="col-lg-6">
						<div class="blog-content">
							<div class="title-blog">Hands &amp; arms massage <span class="underline-title"></span></div>
							<div class="content-blog">
								Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.
							</div>
							<a href="#" class="view-more">View more <img src="img/arrow-right.svg" alt=""></a>
						</div>
					</div>
					
				</div>
			</div>
			
		</div>

		<!-- Mobile Slider (< 1000px) -->
		<div class="d-block d-lg-none">
			<div class="custom-slider-container">
				<div class="custom-slider" id="headSpaSlider">
					<div class="slider-track">
						<div class="slider-item">
							<div class="service-item">
								<div>
									<div class="service-img" style="background-image:url(img/service/service-9.png); height: 200px; background-size: cover; background-position: center; background-repeat: no-repeat; border-radius: 8px;"></div>
								</div>
								<div>
									<div class="blog-content">
										<div class="title-blog">Neck &amp; shoulders<span class="underline-title"></span></div>
										<div class="content-blog">
											Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="slider-item">
							<div class="service-item">
								<div>
									<div class="service-img" style="background-image:url(img/service/service-10.png); height: 200px; background-size: cover; background-position: center; background-repeat: no-repeat; border-radius: 8px;"></div>
								</div>
								<div>
									<div class="blog-content">
										<div class="title-blog">Foot massage <span class="underline-title"></span></div>
										<div class="content-blog">
											Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="slider-item">
							<div class="service-item">
								<div>
									<div class="service-img" style="background-image:url(img/service/service-11.png); height: 200px; background-size: cover; background-position: center; background-repeat: no-repeat; border-radius: 8px;"></div>
								</div>
								<div>
									<div class="blog-content">
										<div class="title-blog">Hands &amp; arms massage <span class="underline-title"></span></div>
										<div class="content-blog">
											Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="container-fluid py-5 px-md-3 container-service" id="packages-massage">
	
	<div class="container py-4 px-md-0">
		<div class="mx-auto text-center mb-5 title-service" style="max-width: 930px;">
			<p class="text-center content-title-service"><img src="img/service/title-left.svg" class="icon-title icon-title-left" alt="">Packages<img src="img/service/title-right.svg" class="icon-title icon-title-right" alt=""><span class="underline-title"></span></p>
		</div>
		<!-- Desktop Layout (>= 1000px) -->
		<div class="row  align-items-center d-none d-lg-flex" style="padding-top: 4rem;">
			<div class="col-lg-6">
				<div class="row service-item">
					<div class="col-lg-6 ">
						<div class="service-img" style="background-image:url(img/service/service-12.png)"></div>
					</div>
					<div class="col-lg-6">
						<div class="blog-content">
							<div class="title-blog">Neck &amp; shoulders<span class="underline-title"></span></div>
							<div class="content-blog">
								Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.
							</div>
							<a href="#" class="view-more">View more <img src="img/arrow-right.svg" alt=""></a>
						</div>
					</div>
					
				</div>
			</div>
			<div class="col-lg-6">
				<div class="row service-item">
					<div class="col-lg-6 ">
						<div class="service-img" style="background-image:url(img/service/service-13.png)"></div>
					</div>
					<div class="col-lg-6">
						<div class="blog-content">
							<div class="title-blog">Foot massage<span class="underline-title"></span></div>
							<div class="content-blog">
								Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.
							</div>
							<a href="#" class="view-more">View more <img src="img/arrow-right.svg" alt=""></a>
						</div>
					</div>
					
				</div>
			</div>
			<div class="col-lg-6">
				<div class="row service-item">
					<div class="col-lg-6 ">
						<div class="service-img" style="background-image:url(img/service/service-14.png)"></div>
					</div>
					<div class="col-lg-6">
						<div class="blog-content">
							<div class="title-blog">Hands &amp; arms massage<span class="underline-title"></span></div>
							<div class="content-blog">
								Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.
							</div>
							<a href="#" class="view-more">View more <img src="img/arrow-right.svg" alt=""></a>
						</div>
					</div>
					
				</div>
			</div>
			
		</div>

		<!-- Mobile Slider (< 1000px) -->
		<div class="d-block d-lg-none">
			<div class="custom-slider-container">
				<div class="custom-slider" id="packagesSlider">
					<div class="slider-track">
						<div class="slider-item">
							<div class="service-item">
								<div>
									<div class="service-img" style="background-image:url(img/service/service-12.png); height: 200px; background-size: cover; background-position: center; background-repeat: no-repeat; border-radius: 8px;"></div>
								</div>
								<div>
									<div class="blog-content">
										<div class="title-blog">Neck &amp; shoulders<span class="underline-title"></span></div>
										<div class="content-blog">
											Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="slider-item">
							<div class="service-item">
								<div>
									<div class="service-img" style="background-image:url(img/service/service-13.png); height: 200px; background-size: cover; background-position: center; background-repeat: no-repeat; border-radius: 8px;"></div>
								</div>
								<div>
									<div class="blog-content">
										<div class="title-blog">Foot massage<span class="underline-title"></span></div>
										<div class="content-blog">
											Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="slider-item">
							<div class="service-item">
								<div>
									<div class="service-img" style="background-image:url(img/service/service-14.png); height: 200px; background-size: cover; background-position: center; background-repeat: no-repeat; border-radius: 8px;"></div>
								</div>
								<div>
									<div class="blog-content">
										<div class="title-blog">Hands &amp; arms massage<span class="underline-title"></span></div>
										<div class="content-blog">
											Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<!-- Blog news  -->

<div class="container-fluid py-5 px-md-2 container-service" id="booking-service">
	<div class="container py-5 px-md-2">
		<div class="row row-booking">
			<div class="col-12 col-lg-7 left-booking">
				<div class="appointment-form">
					<h1 class="display-4  text-white">Booking</h1>
					<form>
						<div class="row gy-3 gx-4">
							<div class="col-12 col-lg-6">
								<input type="text" class="form-control " placeholder="First Name">
							</div>
							<div class="col-12 col-lg-6">
								<input type="text" class="form-control " placeholder="Phone">
							</div>
							<div class="col-12 col-lg-6">
								<input type="email" class="form-control " placeholder="Email">
							</div>
							<div class="col-12 col-lg-6">
								<select class="form-select form-control" aria-label="Default select example">
									<option selected>Open this select menu</option>
									<option value="1">One</option>
									<option value="2">Two</option>
									<option value="3">Three</option>
								</select>
							</div>
							<div class="col-12 col-lg-6">
								<input type="date" class="form-control ">
							</div>
							<div class="col-12 col-lg-6">
								<select class="form-select form-control" aria-label="Default select example">
									<option selected>Open this select menu</option>
									<option value="1">One</option>
									<option value="2">Two</option>
									<option value="3">Three</option>
								</select>
							</div>
							<div class="col-12 col-lg-12">
								<button type="button" class="btn btn-book-now w-100 "><img src="img/calendar-green.svg" alt="">Book now</button>
							</div>
						</div>
					</form>
				</div>
			</div>
			<div class="col-12 col-lg-5 right-booking" style="background-image: url(img/booking.png);"></div>
		</div>
		<div class="mx-auto text-center mb-5 title-service w-md-220" style="max-width: 930px;margin-top: 100px;">
			<p class="text-center content-title-service"><img src="img/service/title-left.svg" class="icon-title icon-title-left" alt="">Note before your experience<img src="img/service/title-right.svg" class="icon-title icon-title-right" alt=""><span class="underline-title"></span></p>
		</div>
		<div class="row-note-before d-flex">
			<div class="note-item">
				<div class="note-img" style="background-image:url(img/service/book-in-advance.png)"></div>
				<div class="note-content">Book in advance</div>
			</div>
			<div class="note-item">
				<div class="note-img" style="background-image:url(img/service/be-on-time.png)"></div>
				<div class="note-content">Be on time</div>
			</div>
			<div class="note-item">
				<div class="note-img" style="background-image:url(img/service/no-drink.png)"></div>
				<div class="note-content">No drink beforehand</div>
			</div>
			<div class="note-item">
				<div class="note-img" style="background-image:url(img/service/no-camera.png)"></div>
				<div class="note-content">No camera in service area</div>
			</div>
			<div class="note-item">
				<div class="note-img" style="background-image:url(img/service/no-pet.png)"></div>
				<div class="note-content">No pets allow</div>
			</div>
		</div>
	</div>
	
</div>
@endsection