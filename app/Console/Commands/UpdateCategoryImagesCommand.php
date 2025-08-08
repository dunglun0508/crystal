<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateCategoryImagesCommand extends Command
{
    protected $signature = 'categories:update-images';
    protected $description = 'Update category images based on image files in categories directory';

    public function handle()
    {
        $this->info('Updating category images...');

        // Lấy danh sách ảnh trong thư mục categories
        $imageFiles = glob('public/images/categories/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
        $imageMap = [];

        foreach ($imageFiles as $imageFile) {
            $filename = basename($imageFile);
            $slug = str_replace(['-c-', '_2.jpg', '_2.webp'], '', $filename);
            $slug = str_replace('-', ' ', $slug);
            $imageMap[$slug] = 'images/categories/' . $filename;
        }

        $this->info("Found " . count($imageMap) . " image files");

        // Cập nhật categories
        $categories = DB::table('categories')->get();
        $updated = 0;

        foreach ($categories as $category) {
            $title = strtolower($category->title);
            
            // Tìm ảnh phù hợp
            $matchedImage = null;
            foreach ($imageMap as $slug => $imagePath) {
                if (strpos($title, strtolower($slug)) !== false) {
                    $matchedImage = $imagePath;
                    break;
                }
            }
            
            if ($matchedImage) {
                DB::table('categories')
                    ->where('code', $category->code)
                    ->update(['image' => $matchedImage]);
                
                $this->line("Updated {$category->title} -> {$matchedImage}");
                $updated++;
            } else {
                $this->warn("No image found for: {$category->title}");
            }
        }

        $this->info("\nUpdated {$updated} categories with images");
        $this->info('Done!');
    }
} 