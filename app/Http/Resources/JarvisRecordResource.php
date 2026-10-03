<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JarvisRecordResource extends JsonResource
{
    /**
     * Queries explicitly project public fields. Preserve string business IDs and
     * decimal money values while making aggregate counts consistent across drivers.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $record = (array) $this->resource;
        foreach ($record as $key => $value) {
            if ($value !== null && (str_ends_with($key, '_count') || in_array($key, ['qty', 'recorded_qty', 'approved_qty', 'package_qty', 'received_qty'], true))) {
                $record[$key] = (int) $value;
            }
        }
        if (array_key_exists('active', $record)) {
            $record['active'] = (bool) $record['active'];
        }

        return $record;
    }
}
