@extends('layouts.app')
@section('content')
    <div class="container py-5 py-md-2 px-md-0">
        <div class="align-items-center py-4">
            <div class="col-md-12">
                <h2 class="service-detail-title">Vietnamese traditional massage</h2>
                <p class="service-detail-text-muted">The perfect preparation for a massage</p>
                <hr class="service-detail-hr">
            </div>
            <div class="row col-md-12">
                <div class="container-service detail col-md-5">
                    <div class="service-img" style="background-image:url({{ asset('img/service/service-1.png') }})"></div>
                </div>
                <div class="col-md-7">
                    <p class="content-blog">This healing bath relaxes and rejuvenates your body using traditional techniques
                        and medicinal herbs
                        harnessed by the Red Dao community in Sapa. The herbal remedies are highly effective for relaxing
                        and
                        therapeutic causes as the high temperature and water pressure can increase blood circulation while
                        calming the mind.</p>
                    <p class="content-blog">Medicinal herbs are used to recover health, fight colds, detoxify skin pores,
                        and create a sense of
                        well-being. It is especially effective for athletes, mountaineers, workers performing heavy physical
                        work, and postpartum women. Yet, it is also just for anyone who wants to prevent illness or seek
                        relaxation after a busy day of work.</p>
                    <div class="benefits d-flex flex-column gap-4 mt-4">
                        <h5 class="benefits-title">Benefits</h5>
                        <div class="row gap-3 gap-xxl-4">
                            <div class="col-12 col-xxl-5 d-flex align-items-center gap-3 benefits-option"><img
                                    src="{{ asset('img/service/benefit.svg') }}" class="icon-title icon-title-left"
                                    alt="">Relax, Balance</div>
                            <div class="col-12 col-xxl-5 d-flex align-items-center gap-3 benefits-option"><img
                                    src="{{ asset('img/service/benefit.svg') }}" class="icon-title icon-title-left"
                                    alt="">Blood Circulation</div>
                            <div class="col-12 col-xxl-5 d-flex align-items-center gap-3 benefits-option"><img
                                    src="{{ asset('img/service/benefit.svg') }}" class="icon-title icon-title-left"
                                    alt="">Regenerate Energy</div>
                            <div class="col-12 col-xxl-5 d-flex align-items-center gap-3 benefits-option"><img
                                    src="{{ asset('img/service/benefit.svg') }}" class="icon-title icon-title-left"
                                    alt="">Detoxify the Body</div>
                        </div>
                    </div>
                    <div class="checkbox-list row gap-4 gap-xxl-4 mt-4 ms-0">
                        <div class="col-10 col-xxl-5 d-flex align-items-center gap-1 gap-xxl-2 checkbox-option">
                            <div class="custom-radio"><input type="radio" name="variant"><label></label></div><span
                                class="price">€ 60,00</span><span class="time">60 mins</span>
                        </div>
                        <div class="col-10 col-xxl-5 d-flex align-items-center gap-1 gap-xxl-2 checkbox-option">
                            <div class="custom-radio"><input type="radio" name="variant"><label></label></div><span
                                class="price sell-price">€ 75,00</span><span class="time">70 mins</span>
                        </div>
                        <div class="col-10 col-xxl-5 d-flex align-items-center gap-1 gap-xxl-2 checkbox-option">
                            <div class="custom-radio"><input type="radio" name="variant"><label></label></div>
                            <span class="price sell-price">€ 85,00</span><span class="time">90 mins</span>
                            <span class="discount">- €5</span>
                        </div>
                        <div class="col-10 col-xxl-5 d-flex align-items-center gap-1 gap-xxl-2 checkbox-option">
                            <div class="custom-radio"><input type="radio" name="variant"><label></label></div>
                            <span class="price sell-price">€ 110,00</span><span class="time">120 mins</span>
                            <span class="discount">- €10</span>
                        </div>
                    </div>
                </div>
                <button class="book-btn mt-4 d-flex align-items-center justify-content-center"><img
                        src="{{ asset('img/calendar.svg') }}" class="icon-title icon-title-left" alt=""> Book now</button>
            </div>
        </div>
        <div class="container-service mt-4" id="other-service">
            <div class="mb-4">
                <span class="other-service-title"><img src="{{ asset('img/service/title-left.svg') }}"
                    class="icon-title icon-title-left" alt="">Other packages of the same type<img></span>
                <hr class="service-detail-hr" style="margin-left: 41px;">
            </div>
            
            <!-- Desktop Layout (>= 1000px) -->
            <div class="row align-items-center d-none d-lg-flex">
                <div class="col-lg-6">
                    <div class="row service-item">
                        <div class="col-lg-6 ">
                            <div class="service-img" style="background-image:url({{ asset('img/service/service-3.png') }})"></div>
                        </div>
                        <div class="col-lg-6">
                            <div class="blog-content">
                                <div class="title-blog">Relaxing oil massage<span class="underline-title"></span></div>
                                <div class="content-blog">
                                    A perfect combination of Hot Stone, Swedish, and Dao massage. Our skilled therapists will harmoniously apply deep-tissue manipulation and soothing massage ...
                                </div>
                                <a href="#" class="view-more">View more <img src="{{ asset('img/arrow-right.svg') }}" alt=""></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="row service-item">
                        <div class="col-lg-6 ">
                            <div class="service-img" style="background-image:url({{ asset('img/service/service-4.png') }})"></div>
                        </div>
                        <div class="col-lg-6">
                            <div class="blog-content">
                                <div class="title-blog">Vietnamese combination massage<span class="underline-title"></span></div>
                                <div class="content-blog">
                                    A perfect combination of Hot Stone, Swedish, and Dao massage. Our skilled therapists will harmoniously apply deep-tissue manipulation and soothing massage ...
                                </div>
                                <a href="#" class="view-more">View more <img src="{{ asset('img/arrow-right.svg') }}" alt=""></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile Slider (< 1000px) -->
            <div class="d-block d-lg-none">
                <div class="custom-slider-container">
                    <div class="custom-slider" id="otherPackagesSlider">
                        <div class="slider-track">
                            <div class="slider-item">
                                <div class="service-item">
                                    <div>
                                        <div class="service-img" style="background-image:url({{ asset('img/service/service-3.png') }}); height: 200px; background-size: cover; background-position: center; background-repeat: no-repeat; border-radius: 8px;"></div>
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
                                        <div class="service-img" style="background-image:url({{ asset('img/service/service-4.png') }}); height: 200px; background-size: cover; background-position: center; background-repeat: no-repeat; border-radius: 8px;"></div>
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
        <div class="py-4 mt-5" id="booking-service">
            <div class="mx-auto text-center mb-5 title-service w-md-220" style="max-width: 930px;">
                <p class="text-center content-title-service"><img src="{{ asset('img/service/title-left.svg') }}"
                        class="icon-title icon-title-left" alt="">Note before your experience<img
                        src="{{ asset('img/service/title-right.svg') }}" class="icon-title icon-title-right"
                        alt=""><span class="underline-title"></span></p>
            </div>
            <div class="row-note-before d-flex">
                <div class="note-item">
                    <div class="note-img" style="background-image:url({{ asset('img/service/book-in-advance.png') }})">
                    </div>
                    <div class="note-content">Book in advance</div>
                </div>
                <div class="note-item">
                    <div class="note-img" style="background-image:url({{ asset('img/service/be-on-time.png') }})"></div>
                    <div class="note-content">Be on time</div>
                </div>
                <div class="note-item">
                    <div class="note-img" style="background-image:url({{ asset('img/service/no-drink.png') }})"></div>
                    <div class="note-content">No drink beforehand</div>
                </div>
                <div class="note-item">
                    <div class="note-img" style="background-image:url({{ asset('img/service/no-camera.png') }})"></div>
                    <div class="note-content">No camera in service area</div>
                </div>
                <div class="note-item">
                    <div class="note-img" style="background-image:url({{ asset('img/service/no-pet.png') }})"></div>
                    <div class="note-content">No pets allow</div>
                </div>
            </div>
        </div>
    </div>
@endsection
