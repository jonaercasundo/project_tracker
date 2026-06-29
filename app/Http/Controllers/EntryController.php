<?php

// app/Http/Controllers/EntryController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EntryController extends Controller
{
    public function index(Request $request)
    {
        $id = $request->query('id');
        $delivery_id = $request->query('delivery_id');

        return redirect()->route('scan', [
            'id' => $id,
            'delivery_id' => $delivery_id
        ]);
    }
}
