<?php

namespace App\Http\Requests;

class UpdatePplFormRequest extends StorePplFormRequest
{
    public function rules(): array
    {
        return array_diff_key(parent::rules(), array_flip([
            'source_project_id',
            'source_project_information_id',
            'source_bidding_lot_id',
            'source_legacy_lot_id',
        ]));
    }
}
