<?php

namespace App\Http\Controllers;

use App\Http\Requests\VerifyLicenseRequest;
use App\Models\LicenseRecord;
use Illuminate\View\View;

class VerificationController extends Controller
{
    public function index(): View
    {
        return view('verification.index', [
            'result' => null,
            'found' => null,
            'license' => null,
        ]);
    }

    public function verify(VerifyLicenseRequest $request): View
    {
        $normalizedLicenseNumber = LicenseRecord::normalizeLicenseNumber($request->validated('license_number'));

        $license = LicenseRecord::query()
            ->where('is_current', true)
            ->whereRaw("REPLACE(REPLACE(license_number, '-', ''), ' ', '') = ?", [$normalizedLicenseNumber])
            ->first();

        $isValid = $license !== null && $license->isValidForVerification();

        return view('verification.index', [
            'result' => $isValid ? 'valid' : 'invalid',
            'found' => $license !== null,
            'license' => $isValid ? $license : null,
        ]);
    }
}
