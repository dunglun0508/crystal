<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    
    public function dashboard(Request $request){
        // $commonVariables = Frontend::getCommonVariables();
        // $listProductBestSellerIds = OrderDetail::getBestSellerProduct();
        // $listProductBestSeller = Product::whereIn('id',$listProductBestSellerIds)->orderBy('total_sale','desc')->where('status','=',1)->get();
        // $bestCollections = Collection::where('is_show','=',1)->whereNotNull('image')->get();
        // $listProductNewUpdate = Product::where('is_show','=',1)->orderby('created_at','desc')->limit(10)->get();
        return view('home.dashboard');
    }
}
