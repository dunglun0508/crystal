<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BlogsController extends Controller
{
    public function index(Request $request){
        $titlePage = 'Blogs & News';
        $breadcrumb = [
            'Blogs & News' => route('blogs.index')
        ];
        return view('blogs.index',compact('titlePage', 'breadcrumb'));
    }
    public function detail(Request $request, $blog){
        $titlePage = 'Blogs & News';
        $title = ucwords(str_replace('-', ' ', $blog));
        $breadcrumb = [
            'Blogs & News' => route('blogs.index'),
            $title => ''
        ];
        return view('blogs.detail',compact('titlePage', 'breadcrumb'));
    }
}
