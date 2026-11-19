<?php

namespace App\Helpers;

use Closure;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Throwable;

class PublicImageUrl
{
    /** @param array<string, mixed> $offer
     * @return array{url: ?string, status: string}
     */
    public static function fromOffer(array $offer, ?string $baseUrl = null): array
    {
        $supplied = false;
        foreach ($offer as $key => $reference) {
            $field = strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $key));
            if (! in_array($field, ['photourl', 'photo', 'photos', 'imageurl', 'imageurls', 'image', 'images', 'productimage', 'productimages', 'productimageurl', 'primaryimage', 'primaryimageurl', 'thumbnail', 'thumbnailurl', 'thumbnails', 'imageref', 'imagereference', 'imagereferences', 'imagesource'], true)) {
                if (preg_match('/image|photo|thumbnail/', $field) && ! in_array($field, ['imagestatus', 'imagealt', 'imagedescription'], true) && $reference !== null && $reference !== '' && $reference !== []) {
                    $supplied = true;
                }

                continue;
            }
            if ($reference !== null && $reference !== '' && $reference !== []) {
                $supplied = true;
            }
            if ($url = self::fromReference($reference, $baseUrl)) {
                return ['url' => $url, 'status' => 'available'];
            }
        }

        return ['url' => null, 'status' => $supplied ? 'unresolved' : 'missing'];
    }

    public static function fromReference(mixed $reference, ?string $baseUrl = null, int $depth = 0): ?string
    {
        if ($depth > 6) {
            return null;
        }
        if (is_array($reference)) {
            $references = $reference;
            if (! array_is_list($reference)) {
                $references = [];
                foreach ($reference as $key => $candidate) {
                    $field = strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $key));
                    if (in_array($field, ['url', 'src', 'contenturl', 'imageurl', 'photourl', 'image', 'images', 'source', 'reference', 'id'], true)) {
                        $references[] = $candidate;
                    }
                }
            }
            foreach ($references as $candidate) {
                if ($url = self::fromReference($candidate, $baseUrl, $depth + 1)) {
                    return $url;
                }
            }

            return null;
        }
        if (! is_string($reference)) {
            return null;
        }
        $value = html_entity_decode(trim($reference), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (preg_match('~^!?\[[^\]]*\]\((https?://[^\s]+)\)$~i', $value, $markdown)) {
            $value = $markdown[1];
        }
        if ($url = self::normalize($value)) {
            return $url;
        }
        if (preg_match('~^https?://~i', $value) && ! preg_match('/[\x00-\x1f\x7f\\\\]/', $value)) {
            try {
                if ($url = self::normalize((string) new Uri($value))) {
                    return $url;
                }
            } catch (Throwable) {
                return null;
            }
        }
        if ($baseUrl && self::normalize($baseUrl) && preg_match('~^(?:/|\./|\.\./)~', $value)) {
            try {
                return self::normalize((string) UriResolver::resolve(new Uri($baseUrl), new Uri($value)));
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }

    public static function normalize(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $url = trim($value);
        if ($url === '' || strlen($url) > 2048 || preg_match('/[\x00-\x20\x7f\\\\]/', $url)
            || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }
        $parts = parse_url($url);
        if (! is_array($parts) || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }
        $host = rtrim(strtolower(trim($parts['host'] ?? '', '[]')), '.');
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE) ? $url : null;
        }
        if (! str_contains($host, '.') || ! filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
            || preg_match('/^(?:0x[0-9a-f]+|[0-9]+)(?:\.(?:0x[0-9a-f]+|[0-9]+))*$/i', $host)
            || preg_match('/(?:^|\.)(?:localhost|local|internal|lan|home|localdomain|test|invalid|onion)$/', $host)
            || str_ends_with($host, '.home.arpa')) {
            return null;
        }

        return $url;
    }

    public static function rule(string $subject = 'product image'): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail) use ($subject): void {
            if (self::normalize($value) === null) {
                $fail('The '.$subject.' must use a public HTTP or HTTPS URL without embedded credentials.');
            }
        };
    }
}
