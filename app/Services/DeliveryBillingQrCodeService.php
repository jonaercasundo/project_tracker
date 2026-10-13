<?php

namespace App\Services;

use App\Exceptions\ExpiredBillingQrCodeException;
use App\Exceptions\InvalidBillingQrCodeException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DeliveryBillingQrCodeService
{
    private const PREFIX = 'MMCBILLING:1';

    private const DR_NUMBER_PREFIX = 'MMCBILLING:2';

    public function makePayload(int $deliveryId): string
    {
        $expiresAt = now()->addYears(10)->timestamp;
        $signedValue = $this->signedValue($deliveryId, $expiresAt);
        $signature = hash_hmac('sha256', $signedValue, $this->key());

        return sprintf('%s:%d:%d:%s', self::PREFIX, $deliveryId, $expiresAt, $signature);
    }

    public function makeDrNumberPayload(string $drNumber): string
    {
        if (trim($drNumber) === '' || mb_strlen($drNumber) > 100) {
            throw new \InvalidArgumentException('A delivery receipt number of 1 to 100 characters is required.');
        }

        $encodedDrNumber = rtrim(strtr(base64_encode($drNumber), '+/', '-_'), '=');
        $expiresAt = now()->addYears(10)->timestamp;
        $signedValue = sprintf('%s:%s:%d', self::DR_NUMBER_PREFIX, $encodedDrNumber, $expiresAt);
        $signature = hash_hmac('sha256', $signedValue, $this->key());

        return sprintf('%s:%s', $signedValue, $signature);
    }

    /**
     * @return array{delivery_id?: int, dr_no?: string}|null
     */
    public function resolveTarget(string $payload): ?array
    {
        if (str_starts_with($payload, self::DR_NUMBER_PREFIX.':')) {
            return ['dr_no' => $this->resolveDrNumberPayload($payload)];
        }

        $deliveryId = $this->resolveDeliveryId($payload);

        return $deliveryId === null ? null : ['delivery_id' => $deliveryId];
    }

    public function resolveDeliveryId(string $payload): ?int
    {
        if (str_starts_with($payload, self::PREFIX.':')) {
            if (! preg_match('/^MMCBILLING:1:([1-9][0-9]*):([0-9]+):([a-f0-9]{64})$/', $payload, $matches)) {
                throw new InvalidBillingQrCodeException('This billing QR code is not valid.');
            }

            $deliveryId = (int) $matches[1];
            $expiresAt = (int) $matches[2];
            $expectedSignature = hash_hmac('sha256', $this->signedValue($deliveryId, $expiresAt), $this->key());

            if (! hash_equals($expectedSignature, $matches[3])) {
                throw new InvalidBillingQrCodeException('This billing QR code is not valid.');
            }

            if ($expiresAt < now()->timestamp) {
                throw new ExpiredBillingQrCodeException('This billing QR code has expired. Print a current delivery label.');
            }

            return $deliveryId;
        }

        return $this->resolveLegacyDeliveryUrl($payload);
    }

    private function resolveDrNumberPayload(string $payload): string
    {
        if (! preg_match('/^MMCBILLING:2:([A-Za-z0-9_-]+):([0-9]+):([a-f0-9]{64})$/', $payload, $matches)) {
            throw new InvalidBillingQrCodeException('This billing QR code is not valid.');
        }

        $padding = str_repeat('=', (4 - mb_strlen($matches[1]) % 4) % 4);
        $drNumber = base64_decode(strtr($matches[1], '-_', '+/').$padding, true);

        if (
            $drNumber === false
            || rtrim(strtr(base64_encode($drNumber), '+/', '-_'), '=') !== $matches[1]
            || trim($drNumber) === ''
            || mb_strlen($drNumber) > 100
        ) {
            throw new InvalidBillingQrCodeException('This billing QR code is not valid.');
        }

        $expiresAt = (int) $matches[2];
        $signedValue = sprintf('%s:%s:%d', self::DR_NUMBER_PREFIX, $matches[1], $expiresAt);
        $expectedSignature = hash_hmac('sha256', $signedValue, $this->key());

        if (! hash_equals($expectedSignature, $matches[3])) {
            throw new InvalidBillingQrCodeException('This billing QR code is not valid.');
        }

        if ($expiresAt < now()->timestamp) {
            throw new ExpiredBillingQrCodeException('This billing QR code has expired. Print a current delivery label.');
        }

        return $drNumber;
    }

    private function resolveLegacyDeliveryUrl(string $payload): ?int
    {
        $parts = parse_url($payload);
        if (! is_array($parts) || strtolower((string) ($parts['host'] ?? '')) !== 'philgeps.metro-mobilia.com' || ($parts['path'] ?? '') !== '/entry.php') {
            return null;
        }

        parse_str((string) ($parts['query'] ?? ''), $query);
        $packageStatusId = filter_var($query['id'] ?? null, FILTER_VALIDATE_INT);
        $deliveryId = filter_var($query['delivery_id'] ?? null, FILTER_VALIDATE_INT);

        if (! $packageStatusId || ! $deliveryId) {
            throw new InvalidBillingQrCodeException('This legacy QR code does not identify a delivery.');
        }

        $isValidPair = DB::table('package_status')
            ->where('package_status_id', $packageStatusId)
            ->where('delivery_id', $deliveryId)
            ->exists();

        return $isValidPair ? $deliveryId : null;
    }

    private function signedValue(int $deliveryId, int $expiresAt): string
    {
        return sprintf('%s:%d:%d', self::PREFIX, $deliveryId, $expiresAt);
    }

    private function key(): string
    {
        $key = (string) config('app.key');
        if ($key === '') {
            throw new RuntimeException('The application key is required to validate billing QR codes.');
        }

        return $key;
    }
}
