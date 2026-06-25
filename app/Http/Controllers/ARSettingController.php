<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ARSetting;

class ARSettingController extends Controller
{
    public function update(Request $request, $projectId)
    {
        $setting = ARSetting::where('project_id', $projectId)->firstOrFail();

        $logoName = $request->ar_logo ?? 'logo.webp';

        if ($request->hasFile('new_logo')) {

            $file = $request->file('new_logo');

            $logoName = time().'_'.$file->getClientOriginalName();

            $file->move(
                public_path('uploads/logo'),
                $logoName
            );
        }

        $setting->update([
            'project_name'       => trim($request->project_name),
            'company'            => trim($request->company),
            'client'             => trim($request->client),

            'ar_company_footer'  => trim($request->ar_company_footer),
            'ar_address_footer'  => trim($request->ar_address_footer),
            'ar_contact_footer'  => trim($request->ar_contact_footer),

            'display_label'      => $request->boolean('display_label'),
            'display_school_id'  => $request->boolean('display_school_id'),

            'label_school_id'    => $request->boolean('label_school_id'),
            'label_municipality' => $request->boolean('label_municipality'),
            'label_division'     => $request->boolean('label_division'),
            'label_region'       => $request->boolean('label_region'),

            'ar_logo'            => $logoName,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Settings updated successfully.');
    }
}