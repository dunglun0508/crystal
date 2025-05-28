@extends('layouts.app')
@section('content')
    <div class="container-fluid py-5 row">
        <div class="blogs-detail py-4 col-8 mx-auto">
            <h4 class="blogs-detail-title mb-4">
                Important Announcement: Upcoming Price Change applied from August 1st, 2023
            </h4>
            <img src="{{ asset('img/service/service-1.png') }}" class="blogs-detail-image mb-4">
            <div class="blogs-detail-content d-flex flex-column gap-3">
                <p>After careful consideration, we have made the decision to adjust our pricing to ensure that we continue
                    delivering the exceptional services and experiences you have come to expect from us. This adjustment
                    will take effect on August 1st, 2023 and it is necessary to account for rising operational costs and to
                    further enhance our offerings, allowing us to maintain the highest standards of quality and provide you
                    with an unforgettable spa journey.</p>
                <p>We understand that changes in pricing can generate questions or concerns, and we want to assure you that
                    we have taken great care in evaluating the impact on our valued customers. Our goal is to strike a
                    balance between providing exceptional services and ensuring affordability for all.</p>
                <p>In light of this change, we would like to address our customers who have already purchased our membership
                    card. We are pleased to inform you that the current membership policy will remain unchanged for those
                    who have already purchased it before July 10 until you use up all the registered cards. For prepaid
                    cards, the service price charged to the card will continue to be charged at the old rate until the end
                    of August 15. After this date, the service price charged to the card will apply at the new rate.</p>
                <p>After careful consideration, we have made the decision to adjust our pricing to ensure that we continue
                    delivering the exceptional services and experiences you have come to expect from us. This adjustment
                    will take effect on August 1st, 2023 and it is necessary to account for rising operational costs and to
                    further enhance our offerings, allowing us to maintain the highest standards of quality and provide you
                    with an unforgettable spa journey.</p>
                <p>We understand that changes in pricing can generate questions or concerns, and we want to assure you that
                    we have taken great care in evaluating the impact on our valued customers. Our goal is to strike a
                    balance between providing exceptional services and ensuring affordability for all.</p>
                <p class="mt-3">In light of this change, we would like to address our customers who have already purchased
                    our membership card. We are pleased to inform you that the current membership policy will remain
                    unchanged for those who have already purchased it before July 10 until you use up all the registered
                    cards. For prepaid cards, the service price charged to the card will continue to be charged at the old
                    rate until the end of August 15. After this date, the service price charged to the card will apply at
                    the new rate.</p>
            </div>
            <hr>
            <div class="social-icons d-flex gap-4 ms-5">
                <img src="{{ asset('img/instagram.svg') }}" alt="Instagram">
                <img src="{{ asset('img/facebook.svg') }}" alt="Facebook">
                <img src="{{ asset('img/whatsapp.svg') }}" alt="WhatsApp">
            </div>
            <hr>
        </div>
        <div class="container-service mt-4 col-8 mx-auto mb-4">
            <div class="mb-4">
                <span class="other-service-title"><img src="{{ asset('img/service/title-left.svg') }}"
                    class="icon-title icon-title-left" alt="">Recent Posts<img></span>
                <hr class="service-detail-hr" style="margin-left: 41px;">
            </div>
            <div class="row align-items-center">
                <div class="row g-4 align-items-center">
                    <div class="col-6">
                        <div class="blog-item">
                            <div class="blog-img" style="background-image:url({{ asset('img/blog-1.png') }})"></div>
                            <div class="blog-content">
                                <div class="title-blog">Fitness and Strength Trainings <span class="underline-title"></span></div>
                                <div class="content-blog">
                                    Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an
                                    unknown printer took a galley of type and scrambled it to make a type specimen book.
                                </div>
                                <a href="#" class="view-more">View more <img src="{{ asset('img/arrow-right.svg') }}" alt=""></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="blog-item">
                            <div class="blog-img" style="background-image:url({{ asset('img/blog-2.jpg') }})"></div>
                            <div class="blog-content">
                                <div class="title-blog">Stress Management and Coping Strategies <span
                                        class="underline-title"></span></div>
                                <div class="content-blog">
                                    Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an
                                    unknown printer took a galley of type and scrambled it to make a type specimen book.
                                </div>
                                <a href="#" class="view-more">View more <img src="{{ asset('img/arrow-right.svg') }}" alt=""></a>
                            </div>
    
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
