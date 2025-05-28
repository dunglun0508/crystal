<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request){
        $titlePage = 'Booking';
        $breadcrumb = [
            'Booking' => route('booking.index')
        ];
        return view('booking.index',compact('titlePage', 'breadcrumb'));
    }
}
