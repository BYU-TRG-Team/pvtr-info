<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintType;
use App\Http\Requests\StoreComplaintRequest;
use App\Models\Complaint;
use App\Services\ComplaintFilingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ComplaintController extends Controller
{
    public function create(): View
    {
        return view('complaints.create', [
            'complaintTypes' => [
                ComplaintType::InvalidLogo->value => 'Invalid logo',
                ComplaintType::PoorQualityTranslation->value => 'Poor-quality translation',
            ],
            'poorQualityType' => ComplaintType::PoorQualityTranslation->value,
        ]);
    }

    public function store(
        StoreComplaintRequest $request,
        ComplaintFilingService $filingService,
    ): RedirectResponse {
        $complaint = $filingService->file($request->validated());

        return redirect()
            ->route('complaints.submitted')
            ->with('submitted_complaint_id', $complaint->id);
    }

    public function submitted(): View|RedirectResponse
    {
        $complaintId = session('submitted_complaint_id');

        if ($complaintId === null) {
            return redirect()->route('complaints.create');
        }

        $complaint = Complaint::query()->find($complaintId);

        if ($complaint === null) {
            return redirect()->route('complaints.create');
        }

        return view('complaints.submitted', [
            'complaint' => $complaint,
            'secretLink' => url('/complaints/'.$complaint->secret_link_key),
        ]);
    }
}
