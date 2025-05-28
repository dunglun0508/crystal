@extends('layouts.app')
@section('content')
    <div class="container-fluid py-5 row">
        <div class="booking-container col-8 mx-auto">
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <input type="text" autocomplete="true" name="name" value="Name">
                </div>
                <div class="col-md-6">
                    <input type="text" autocomplete="true" name="phone" value="Phone number">
                </div>
                <div class="col-md-6">
                    <input type="email" autocomplete="true" name="email" value="Email">
                </div>
                <div class="select-container col-md-6">
                    <select name="date">
                        <option selected>Date</option>
                        <option>2024-06-01</option>
                        <option>2024-06-02</option>
                    </select>
                    <span class="select-icon"><img src="{{ asset('img/arrow-down.svg') }}" alt=""></span>
                </div>
            </div>

            <!-- Table -->
            <div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Service name</th>
                            <th>Unit price</th>
                            <th>Quantity</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Vietnamese traditional massage</td>
                            <td>€ 60,00</td>
                            <td>
                                <div class="quantity-control">
                                    <img src="{{ asset('img/booking/down-quantity.svg') }}" alt="">
                                    <span>2</span>
                                    <img src="{{ asset('img/booking/up-quantity.svg') }}" alt="">
                                </div>
                            </td>
                            <td>€ 120,00</td>
                        </tr>
                        <tr>
                            <td>Vietnamese traditional massage</td>
                            <td>€ 75,00</td>
                            <td>
                                <div class="quantity-control">
                                    <img src="{{ asset('img/booking/down-quantity.svg') }}" alt="">
                                    <span>1</span>
                                    <img src="{{ asset('img/booking/up-quantity.svg') }}" alt="">
                                </div>
                            </td>
                            <td>€ 150,00</td>
                        </tr>
                        <tr>
                            <td colspan="3">Total</td>
                            <td class="total-price">€ 270,00</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mb-4">
                <button class="book-btn mt-4 d-flex align-items-center w-auto"><img
                    src="{{ asset('img/calendar.svg') }}" class="icon-title icon-title-left" alt=""> Book now</button>
            </div>
        </div>
    </div>
@endsection
