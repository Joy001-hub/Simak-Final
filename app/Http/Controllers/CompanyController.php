<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\SafeExecution;
use App\Models\CompanyProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    use SafeExecution;

    public function index()
    {
        return $this->safeExecute(function () {
            $profile = CompanyProfile::first();
            return view('profile.index', ['company' => $profile]);
        }, 'dashboard');
    }

    public function update(Request $request)
    {
        return $this->safeExecute(function () use ($request) {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'npwp' => ['nullable', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255'],
                'phone' => ['nullable', 'string', 'max:255'],
                'address' => ['nullable', 'string'],
                'signer_name' => ['nullable', 'string', 'max:255'],
                'footer_note' => ['nullable', 'string', 'max:255'],
                'invoice_format' => ['nullable', 'string', 'max:255'],
                'receipt_format' => ['nullable', 'string', 'max:255'],
                'logo' => ['nullable', 'image', 'max:1024'],
            ]);

            $profile = CompanyProfile::firstOrCreate([]);

            if ($request->hasFile('logo')) {
                if ($profile->logo_path && Storage::disk('public')->exists($profile->logo_path)) {
                    Storage::disk('public')->delete($profile->logo_path);
                }
                $path = $request->file('logo')->store('logos', 'public');
                $data['logo_path'] = $path;
            }

            $profile->update($data);

            return redirect()->route('profile.index')->with('success', 'Profil perusahaan diperbarui');
        }, 'profile.index');
    }
}