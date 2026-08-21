<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintMessageAuthorType;
use App\Enums\ComplaintType;
use App\Http\Requests\StoreComplaintReplyRequest;
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

    public function show(string $secretLinkKey): View
    {
        $complaint = Complaint::withTrashed()
            ->where('secret_link_key', $secretLinkKey)
            ->with([
                'messages' => fn ($query) => $query
                    ->orderBy('created_at')
                    ->orderBy('id'),
            ])
            ->firstOrFail();

        return view('complaints.show', [
            'complaint' => $complaint,
        ]);
    }

    public function storeReply(
        StoreComplaintReplyRequest $request,
        string $secretLinkKey,
    ): RedirectResponse {
        $complaint = Complaint::query()
            ->where('secret_link_key', $secretLinkKey)
            ->firstOrFail();

        $complaint->messages()->create([
            'user_id' => null,
            'author_type' => ComplaintMessageAuthorType::Complainant,
            'body' => $request->validated('body'),
        ]);

        return redirect()
            ->route('complaints.show', ['secretLinkKey' => $secretLinkKey])
            ->with('status', 'Reply added.');
    }
}
