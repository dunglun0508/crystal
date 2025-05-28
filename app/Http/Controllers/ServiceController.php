<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ServiceController extends Controller
{
     public function index(){
        $titlePage = 'Our service';
        $breadcrumb = [
            'Our service' => route('service.index')
        ];
        return view('service.index',compact('titlePage', 'breadcrumb'));
    }
    public function detail(Request $request, $category, $code){
        $titlePage = 'Body Massage';
        $title = ucwords(str_replace('-', ' ', $code));
        $breadcrumb = [
            'Our service' => route('service.index'),
            'Body Massage' => route('service.detail', ['category' => $category, 'code' => 'vietnamese-traditional-massage']),
            $title => '#',
        ];
        return view('service.detail',compact('titlePage', 'breadcrumb'));
    }
}
