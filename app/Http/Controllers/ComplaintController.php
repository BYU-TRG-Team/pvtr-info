<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintMessageAuthorType;
use App\Enums\ComplaintType;
use App\Http\Requests\StoreComplaintReplyRequest;
use App\Http\Requests\StoreComplaintRequest;
use App\Models\Complaint;
use App\Services\ComplaintFilingService;
use App\Services\LicenseStatusAtFilingResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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
            'invalidLogoType' => ComplaintType::InvalidLogo->value,
            'poorQualityType' => ComplaintType::PoorQualityTranslation->value,
        ]);
    }

    public function lookupLicenseStatus(
        Request $request,
        LicenseStatusAtFilingResolver $licenseStatusResolver,
    ): JsonResponse {
        $validator = Validator::make($request->query(), [
            'license_number' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $resolution = $licenseStatusResolver->resolve(
            $validator->validated()['license_number'],
        );

        return response()->json([
            'license_number' => $resolution->license?->license_number
                ?? trim((string) $validator->validated()['license_number']),
            'license_status_at_filing' => $resolution->status->value,
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
        $complaint = Complaint::withTrashed()
            ->where('secret_link_key', $secretLinkKey)
            ->firstOrFail();

        if ($complaint->trashed()) {
            return redirect()
                ->route('complaints.show', ['secretLinkKey' => $secretLinkKey])
                ->with(
                    'error',
                    'This complaint has been archived and can no longer accept replies.',
                );
        }

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
