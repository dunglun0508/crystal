<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AboutUsController extends Controller
{
    public function index(Request $request){
        $titlePage = 'About us';
        $breadcrumb = [
            'About us' => route('about-us.index')
        ];
        return view('about-us.index',compact('titlePage', 'breadcrumb'));
    }
}
