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
        $id = 1;
        $element = $html->find('.s1-sub-group .s1-submenu-item.level-1');
        $results = [];
        foreach ($element as $item) {
            if($level1 = $item->find('a', 0)){
                $currentLevel1Id = $id++;
                $results[] = [
                    'id' => $currentLevel1Id,
                    'level' => '1',
                    'slug' => $level1->href,
                    'title' => trim($level1->find('span', 0)->plaintext ?? '')
                ];
            }
            if($level2 = $item->find('ul li.level-2')){
                foreach($level2 as $item2){
                    if (($img = $item2->find('a img', 0)) && ($imageUrl = $img->getAttribute('data-src'))) {
                        $fullUrl = $url . $imageUrl;
                        $filename = basename($imageUrl);
                        $savePath = public_path('images/' . $filename);
                        if (!file_exists($savePath)) {
                            file_put_contents($savePath, file_get_contents($fullUrl));
                        }
                    }
                    $currentLevel2Id = $id++;
                    $results[] = [
                        'id' => $currentLevel2Id,
                        'level' => '2',
                        'slug' => $item2->find('a', 1)?->href ?? '',
                        'title' => trim(string: $item2->find('a', 1)?->find('span', 0)?->plaintext ?? ''),
                        'image' => $filename ?? '',
                        'parentId' => $currentLevel1Id,
                    ];
                    if($level3 = $item2->find('ul li.level-3')){
                        foreach($level3 as $item3){
                            if (strpos($item3->class, 'rl-hide') !== false) {
                                continue;
                            }
                            $results[] = [
                                'id' => $id++,
                                'level' => '3',
                                'slug' => $item3->find('a', 0)?->href ?? '',
                                'title' => trim(string: $item3->find('a span', 0)?->plaintext ?? ''),
                                'parentId' => $currentLevel2Id,
                            ];
                        }
                    }
                }
            }
        }
        echo '<pre>';
        print_r($results);
    }
}
