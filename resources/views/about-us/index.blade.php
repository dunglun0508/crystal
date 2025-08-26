@extends('layouts.app')
@section('content')
    <div class="banner mb-xxl-5 mb-3" id="about-us">
        <div class="banner-overlay"></div>
        <div class="banner-content">
            <img src="{{ asset('img/about-us/banner.png') }}" class="banner-logo">
        </div>
        <div class="d-xxl-none d-block mt-3">
            <img src="{{ asset('img/service/service-1.png') }}" class="blogs-detail-image">
        </div>
    </div>
    <div class="container-fluid py-5 px-md-0">
        <div class="col-xxl-8 col-11 mx-auto">
            <div class="d-flex flex-column gap-4 mb-5">
                <h3 class="about-us-title">Missie (waarom bestaan we?)</h3>
                <p class="about-us-content">
                    Levina Wellness biedt een authentieke wellnesservaring waar ontspanning, persoonlijke aandacht en kwaliteit samenkomen. In een natuurlijke en rustgevende omgeving creëren we een plek waar mensen volledig tot zichzelf kunnen komen. Met zorg en integriteit streven we ernaar om elk bezoek een moment van diepe ontspanning en welzijn te maken.
                </p>
                <h3 class="about-us-title">Visie (waar willen we naartoe?)</h3>
                <p class="about-us-content">
                    Wij geloven dat echte ontspanning ontstaat door een combinatie van hoogwaardige behandelingen, persoonlijke aandacht en een natuurlijke omgeving. Levina Wellness wil uitgroeien tot een toonaangevende plek waar mensen niet alleen genieten van massages en pedicurebehandelingen, maar zich ook kunnen onderdompelen in een totale wellnesservaring, inclusief sauna’s en kruidenbaden. We streven ernaar om een oase van rust en puurheid te zijn, waar mensen steeds opnieuw terugkomen voor een moment van echte balans en welzijn.
                </p>
                <h3 class="about-us-title">Kernwaarden:</h3>
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex align-items-center gap-2 about-us-benefits"><img
                            src="{{ asset('img/service/benefit.svg') }}" class="icon-title icon-title-left"
                            alt="">Integriteit – Eerlijkheid en oprechte zorg voor elke klant.</div>
                    <div class="d-flex align-items-center gap-2 about-us-benefits"><img
                            src="{{ asset('img/service/benefit.svg') }}" class="icon-title icon-title-left"
                            alt="">Ontspanning – Een plek waar lichaam en geest volledig tot rust komen.</div>
                    <div class="d-flex align-items-center gap-2 about-us-benefits"><img
                            src="{{ asset('img/service/benefit.svg') }}" class="icon-title icon-title-left"
                            alt="">Persoonlijke aandacht – Behandelingen op maat, volledig afgestemd op de klant.</div>
                    <div class="d-flex align-items-center gap-2 about-us-benefits"><img
                            src="{{ asset('img/service/benefit.svg') }}" class="icon-title icon-title-left"
                            alt="">Kwaliteit – Hoogwaardige zorg en oog voor detail in elke ervaring.</div>
                    <div class="d-flex align-items-center gap-2 about-us-benefits"><img
                            src="{{ asset('img/service/benefit.svg') }}" class="icon-title icon-title-left"
                            alt="">Authenticiteit – Een pure, oprechte wellnesservaring in harmonie met de natuur.</div>
                </div>
            </div>
            <hr>
            <div class="social-icons d-flex gap-4 ms-4">
                <img src="{{ asset('img/instagram.svg') }}" alt="Instagram">
                <img src="{{ asset('img/facebook.svg') }}" alt="Facebook">
                <img src="{{ asset('img/whatsapp.svg') }}" alt="WhatsApp">
            </div>
            <hr>
        </div>
    </div>
    <div class="container-fluid py-5 px-md-2 container-service mb-xxl-5 mb-2" id="booking-service">
        <div class="container">
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
                                    <button type="button" class="btn btn-book-now w-100 "><img src="{{ asset('img/calendar-green.svg') }}" alt="">Book now</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-lg-5 right-booking" style="background-image: url({{ asset('img/booking.png') }});"></div>
            </div>
        </div>
    </div>
@endsection
