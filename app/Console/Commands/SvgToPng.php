<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Imagick;
use ImagickPixel;

class ConvertSvgToPng extends Command
{
    protected $signature = 'qr:convert-png';

    protected $description = 'Converts all SVG files in the public storage disk to PNG';

    public function handle()
    {
        if (!extension_loaded('imagick')) {
            $this->error('The Imagick PHP extension is missing. It is required to convert images.');
            return;
        }

        $files = Storage::disk('public')->allFiles();
        
        $svgFiles = array_filter($files, function ($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'svg';
        });

        if (empty($svgFiles)) {
            $this->info('No SVG files found in your public storage.');
            return;
        }

        $this->info('Found ' . count($svgFiles) . ' SVG files. Starting conversion...');

        $this->withProgressBar($svgFiles, function ($file) {
            $svgContent = Storage::disk('public')->get($file);
            
            $image = new Imagick();
            $image->setBackgroundColor(new ImagickPixel('transparent')); 
            $image->readImageBlob($svgContent);
            
            $image->setImageFormat('png32'); 
            
            $newName = str_replace('.svg', '.png', $file);
            
            Storage::disk('public')->put($newName, $image->getImageBlob());
            
            $image->clear();
            $image->destroy();
            
            //add "Storage::disk('public')->delete($file);" if storage needs to be cleared
        });

        $this->newLine();
        $this->info('Successfully converted all SVG files to PNG!');
    }
}