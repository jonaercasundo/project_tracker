<?php

namespace App\Http\Controllers;

use Symfony\Component\Process\Process;

class ActionCrawlerController extends Controller
{
    public function crawl()
    {
        $python = base_path('venv/Scripts/python.exe');
        $script = base_path('python/crawler.py');

        $process = new Process([$python, $script]);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json([
                'success' => false,
                'error' => $process->getErrorOutput(),
                'python' => $python,
                'script' => $script,
            ]);
        }

        return response()->json([
            'success' => true,
            'output' => json_decode($process->getOutput(), true),
        ]);
    }
}