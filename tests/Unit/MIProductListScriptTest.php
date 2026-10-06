<?php

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Laravel\Boost\Middleware\InjectBoost;
use Tests\TestCase;

pest()->extend(TestCase::class);

test('product list JavaScript stays inside its script element when browser logging is injected', function () {
    $this->withoutVite();
    $html = (string) $this->view('mi_app.designer_module.index', [
        'products' => new LengthAwarePaginator([], 0, 15),
    ]);

    $response = app(InjectBoost::class)->handle(
        Request::create('/mi/designer'),
        fn (Request $request): Response => new Response($html, 200, ['Content-Type' => 'text/html']),
    );

    $content = $response->getContent();
    preg_match_all('~<script\b[^>]*>(.*?)</script\s*>~is', $content, $scripts);
    $productScript = collect($scripts[1])->first(
        fn (string $script): bool => str_contains($script, 'function txPrintQr'),
    );
    $visibleText = strip_tags(preg_replace('~<(script|style)\b[^>]*>.*?</\1\s*>~is', '', $content));

    expect($visibleText)->not->toContain('printWindow.onload', 'txEscapeHtml', 'txConfirmArchive', 'Keyboard Controls', 'beforeunload');
    expect($productScript)->toContain('function txConfirmArchive', 'function txEscapeHtml', "window.addEventListener('beforeunload'");
    expect($content)->toContain('id="browser-logger-active"');
});
