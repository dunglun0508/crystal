<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CrawlController extends Controller
{
    public function crawler(Request $request){
        include 'simple_html_dom.php';
        $url = 'https://www.artcrystal.eu/';
        $html = file_get_html($url);
        if (!$html) {
            return response()->json(['error' => 'Failed to load URL.'], 500);
        }

        $links = $html->find('.s1-sub-group .s1-submenu-item.level-1>a');
        $results = [];

        foreach ($links as $link) {
            $results[] = [
                'type' => 'level_1',
                'slug' => $link->href,
                'title' => trim($link->find('span', 0)->plaintext ?? '')
            ];
        }

        dd($results);
    }
}
