<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CrawlController extends Controller
{
    public function crawler(Request $request){
        include 'simple_html_dom.php';
        $url = 'https://www.artcrystal.eu/';
        $data = file_get_html($url);

        dump($data->find('.s1-submenu-item'));
    }
}
