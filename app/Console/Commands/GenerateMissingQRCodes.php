<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Asset;
use Illuminate\Support\Facades\Storage;

class GenerateMissingQRCodes extends Command
{
    protected $signature = 'qrcodes:generate';

    protected $description = 'Generate and save missing QR codes for older assets';

    public function handle()
    {
        $assets = Asset::whereNull('qr_code')->orWhere('qr_code', '')->get();

        if ($assets->isEmpty()) {
            $this->info('All assets already have QR codes! Nothing to do.');
            return;
        }

        $this->info("Found {$assets->count()} old assets missing QR codes. Generating now...");

        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        $writer = new \BaconQrCode\Writer($renderer);

        foreach ($assets as $asset) {
            $qrCodeSvg = $writer->writeString(route('it.asset.show', $asset->id));
            
            $filename = 'qrcodes/' . $asset->asset_code . '.svg';
            
            Storage::disk('public')->put($filename, $qrCodeSvg);
            
            $asset->update(['qr_code' => $filename]);
            
            $this->line("Successfully generated QR Code for: {$asset->asset_code}");
        }
        $this->info('Finished generating all missing QR codes!');
    }
}