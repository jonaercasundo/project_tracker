<?php

use App\Services\PublicDnsResolver;
use App\Services\VerifiedListingImageResolver;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\MIWorkflowTestCase;

pest()->extend(MIWorkflowTestCase::class);

it('reads only the matching product image from structured listing metadata', function () {
    $this->mock(PublicDnsResolver::class)->shouldReceive('addresses')->with('store.example.com')->andReturn(['93.184.216.34']);
    $products = ['@graph' => [
        ['@type' => 'Product', 'url' => 'https://store.example.com/unrelated', 'image' => 'https://images.example.com/unrelated.jpg'],
        ['@type' => 'Product', 'url' => 'https://store.example.com/table', 'image' => [['@type' => 'ImageObject', 'contentUrl' => '//images.example.com/table.jpg?token=abc&size=800']]],
    ]];
    Http::preventStrayRequests();
    $options = [];
    Http::fake(function ($request, array $requestOptions) use ($products, &$options) {
        $options = $requestOptions;

        return Http::response('<script type="application/ld+json">'.json_encode($products).'</script>', 200, ['Content-Type' => 'text/html']);
    });

    $image = app(VerifiedListingImageResolver::class)->resolve('https://store.example.com/table');

    expect($image)->toBe(['url' => 'https://images.example.com/table.jpg?token=abc&size=800', 'status' => 'available']);
    expect($options['allow_redirects'])->toBeFalse();
    expect($options['curl'][CURLOPT_RESOLVE])->toBe(['store.example.com:443:93.184.216.34']);
    Http::assertSentCount(1);
});

it('resolves only explicitly supplied product-page image metadata', function (string $html, ?string $expected, string $status) {
    $this->mock(PublicDnsResolver::class)->shouldReceive('addresses')->andReturn(['93.184.216.34']);
    Http::preventStrayRequests();
    Http::fake(['https://store.example.com/table' => Http::response($html, 200, ['Content-Type' => 'text/html'])]);

    expect(app(VerifiedListingImageResolver::class)->resolve('https://store.example.com/table'))->toBe(['url' => $expected, 'status' => $status]);
    Http::assertSentCount(1);
})->with([
    'product OpenGraph image' => ['<meta property="og:type" content="product"><meta property="og:image" content="/images/table.jpg?x=1&amp;y=2">', 'https://store.example.com/images/table.jpg?x=1&y=2', 'available'],
    'product has no image' => ['<meta property="og:type" content="product"><img src="https://store.example.com/logo.png">', null, 'missing'],
    'generic collection banner is not a product image' => ['<meta property="og:type" content="website"><meta property="og:image" content="https://store.example.com/collection-banner.jpg">', null, 'lookup_failed'],
    'private image metadata' => ['<meta property="og:type" content="product"><meta property="og:image" content="http://127.0.0.1/image.jpg">', null, 'missing'],
]);

it('does not request pages resolving to private addresses', function () {
    $this->mock(PublicDnsResolver::class)->shouldReceive('addresses')->andReturn(['127.0.0.1']);
    Http::preventStrayRequests();
    Http::fake();

    expect(app(VerifiedListingImageResolver::class)->resolve('https://store.example.com/table')['status'])->toBe('lookup_failed');
    Http::assertNothingSent();
});

it('validates redirect destinations before making another request', function (string $location) {
    $this->mock(PublicDnsResolver::class)->shouldReceive('addresses')->andReturnUsing(fn (string $host): array => $host === 'store.example.com' ? ['93.184.216.34'] : ['10.0.0.1']);
    Http::preventStrayRequests();
    Http::fake(['https://store.example.com/table' => Http::response('', 302, ['Location' => $location])]);

    expect(app(VerifiedListingImageResolver::class)->resolve('https://store.example.com/table')['status'])->toBe('lookup_failed');
    Http::assertSentCount(1);
})->with(['http://127.0.0.1/private', 'https://internal.example.com/private']);

it('rejects oversized and non-HTML listing responses', function (string $body, string $type) {
    $this->mock(PublicDnsResolver::class)->shouldReceive('addresses')->andReturn(['93.184.216.34']);
    Http::preventStrayRequests();
    Http::fake(['https://store.example.com/table' => Http::response($body, 200, ['Content-Type' => $type])]);

    expect(app(VerifiedListingImageResolver::class)->resolve('https://store.example.com/table')['status'])->toBe('lookup_failed');
    Http::assertSentCount(1);
})->with([
    'size limit' => [str_repeat('x', 2097153), 'text/html'],
    'non-HTML' => ['{}', 'application/json'],
]);

it('reads explicit product OpenGraph listing details without inventing missing image data', function () {
    $this->mock(PublicDnsResolver::class)->shouldReceive('addresses')->andReturn(['93.184.216.34']);
    Http::preventStrayRequests();
    Http::fake(['https://store.example.com/table' => Http::response('<meta property="og:type" content="product"><meta property="og:title" content="Oak Table"><meta property="og:site_name" content="Wood Store"><meta property="product:price:amount" content="125.00"><meta property="product:price:currency" content="USD">', 200, ['Content-Type' => 'text/html'])]);

    $result = app(VerifiedListingImageResolver::class)->comparison('https://store.example.com/table');

    expect($result['status'])->toBe('available');
    expect($result['result'])->toMatchArray(['product_name' => 'Oak Table', 'store_name' => 'Wood Store', 'price' => 125.0, 'currency' => 'USD', 'photo_url' => null]);
    Http::assertSentCount(1);
});

it('uses the exact linked variant price rather than a different listed variant', function () {
    $this->mock(PublicDnsResolver::class)->shouldReceive('addresses')->andReturn(['93.184.216.34']);
    Http::preventStrayRequests();
    $metadata = ['@type' => 'Product', 'name' => 'Oak Table', 'offers' => [
        ['url' => 'https://store.example.com/table?variant=small', 'price' => 100, 'priceCurrency' => 'USD', 'seller' => ['name' => 'Store']],
        ['url' => 'https://store.example.com/table?variant=large', 'price' => 200, 'priceCurrency' => 'USD', 'seller' => ['name' => 'Store']],
    ]];
    Http::fake(['https://store.example.com/table?variant=large&utm_source=test' => Http::response('<script type="application/ld+json">'.json_encode($metadata).'</script>', 200, ['Content-Type' => 'text/html'])]);

    $result = app(VerifiedListingImageResolver::class)->comparison('https://store.example.com/table?variant=large&utm_source=test');

    expect($result['result']['price'])->toBe(200.0);
    Http::assertSentCount(1);
});

it('uses a declared default price only when it matches a structured offer and no variant was requested', function (string $query, string $status) {
    $this->mock(PublicDnsResolver::class)->shouldReceive('addresses')->andReturn(['93.184.216.34']);
    Http::preventStrayRequests();
    $metadata = ['@type' => 'Product', 'name' => 'Oak Table', 'offers' => [
        ['url' => 'https://store.example.com/table?variant=small', 'price' => 100, 'priceCurrency' => 'USD'],
        ['url' => 'https://store.example.com/table?variant=large', 'price' => 200, 'priceCurrency' => 'USD'],
    ]];
    Http::fake(['https://store.example.com/table'.$query => Http::response('<meta property="og:site_name" content="Store"><meta property="og:price:amount" content="100"><meta property="og:price:currency" content="USD"><script type="application/ld+json">'.json_encode($metadata).'</script>', 200, ['Content-Type' => 'text/html'])]);

    $result = app(VerifiedListingImageResolver::class)->comparison('https://store.example.com/table'.$query);

    expect($result['status'])->toBe($status);
    if ($status === 'available') {
        expect($result['result']['price'])->toBe(100.0);
    } else {
        expect($result['result'])->toBeNull();
    }
    Http::assertSentCount(1);
})->with([
    'default with tracking parameter' => ['?utm_source=manual', 'available'],
    'unknown explicit variant' => ['?variant=unknown', 'ambiguous_price'],
]);

it('returns clear unavailable states for empty listing documents', function () {
    $this->mock(PublicDnsResolver::class)->shouldReceive('addresses')->andReturn(['93.184.216.34']);
    Http::preventStrayRequests();
    Http::fakeSequence('https://store.example.com/table')
        ->push('', 200, ['Content-Type' => 'text/html'])
        ->push('', 200, ['Content-Type' => 'text/html']);
    $resolver = app(VerifiedListingImageResolver::class);

    expect($resolver->resolve('https://store.example.com/table')['status'])->toBe('lookup_failed');
    expect($resolver->comparison('https://store.example.com/table')['status'])->toBe('incomplete');
    Http::assertSentCount(2);
});

it('distinguishes a connection timeout from other retrieval failures', function () {
    $this->mock(PublicDnsResolver::class)->shouldReceive('addresses')->andReturn(['93.184.216.34']);
    Http::preventStrayRequests();
    $previous = new ConnectException('Sensitive timeout details', new Request('GET', 'https://store.example.com/table'), null, ['errno' => 28]);
    Http::fake(['https://store.example.com/table' => fn () => throw new ConnectionException('Sensitive timeout details', 0, $previous)]);

    $result = app(VerifiedListingImageResolver::class)->comparison('https://store.example.com/table');

    expect($result['status'])->toBe('timeout');
    expect($result['diagnostics'])->toMatchArray(['reason' => 'timeout', 'exception' => ConnectionException::class]);
});
