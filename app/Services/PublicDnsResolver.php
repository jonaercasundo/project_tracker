<?php

namespace App\Services;

class PublicDnsResolver
{
    /** @return array<int, string> */
    public function addresses(string $host): array
    {
        $host = trim($host, '[]');
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE) ? [$host] : [];
        }
        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        $addresses = [];
        foreach ($records ?: [] as $record) {
            $address = $record['ip'] ?? $record['ipv6'] ?? null;
            if ($address === null) {
                continue;
            }
            if (! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE)) {
                return [];
            }
            $addresses[] = $address;
        }

        return array_values(array_unique($addresses));
    }
}
