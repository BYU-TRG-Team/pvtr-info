<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintMessageAuthorType;
use App\Enums\ComplaintStatus;
use App\Enums\ComplaintType;
use App\Http\Requests\StoreAdminComplaintReplyRequest;
use App\Http\Requests\UpdateComplaintRequest;
use App\Http\Requests\UpdateComplaintStatusRequest;
use App\Models\Complaint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminComplaintController extends Controller
{
    public function index(Request $request): View
    {
        $selectedView = $request->query('view') === 'archived'
            ? 'archived'
            : 'active';
        $selectedStatus = ComplaintStatus::tryFrom(
            (string) $request->query('status', ''),
        );

        $complaints = ($selectedView === 'archived'
            ? Complaint::onlyTrashed()
            : Complaint::query())
            ->when(
                $selectedStatus,
                fn ($query) => $query->where('status', $selectedStatus->value),
            )
            ->orderByDesc('filed_at')
            ->orderByDesc('id')
            ->get();

        return view('admin.complaints.index', [
            'complaints' => $complaints,
            'statuses' => ComplaintStatus::cases(),
            'selectedStatus' => $selectedStatus,
            'selectedView' => $selectedView,
        ]);
    }

    public function show(int $complaint): View
    {
        $complaint = Complaint::withTrashed()->findOrFail($complaint);
        $complaint->load([
            'messages' => fn ($query) => $query
                ->with('user')
                ->orderBy('created_at')
                ->orderBy('id'),
        ]);

        return view('admin.complaints.show', [
            'complaint' => $complaint,
            'statuses' => ComplaintStatus::cases(),
            'complaintTypes' => ComplaintType::cases(),
            'statement' => $complaint->messages
                ->firstWhere('author_type', ComplaintMessageAuthorType::Complainant),
            'secretLink' => url('/complaints/'.$complaint->secret_link_key),
        ]);
    }

    public function update(
        UpdateComplaintRequest $request,
        Complaint $complaint,
    ): RedirectResponse {
        $attributes = $request->validated();
        $details = $complaint->details ?? [];
        $details['harm_type'] = $attributes['harm_type'] ?? null;

        $complaint->update([
            'complaint_type' => $attributes['complaint_type'],
            'details' => array_filter(
                $details,
                fn (mixed $value): bool => $value !== null && $value !== '',
            ),
        ]);

        return redirect()
            ->route('admin.complaints.show', $complaint)
            ->with('status', 'Complaint classification updated.');
    }

    public function updateStatus(
        UpdateComplaintStatusRequest $request,
        Complaint $complaint,
    ): RedirectResponse {
        $complaint->update([
            'status' => $request->validated('status'),
        ]);

        return redirect()
            ->route('admin.complaints.show', $complaint)
            ->with('status', 'Complaint status updated.');
    }

    public function storeReply(
        StoreAdminComplaintReplyRequest $request,
        Complaint $complaint,
    ): RedirectResponse {
        DB::transaction(function () use ($request, $complaint): void {
            $complaint->messages()->create([
                'user_id' => $request->user()->id,
                'author_type' => ComplaintMessageAuthorType::Admin,
                'body' => $request->validated('body'),
            ]);

            $complaint->update([
                'status' => ComplaintStatus::ReplySent,
            ]);
        });

        return redirect()
            ->route('admin.complaints.show', $complaint)
            ->with('status', 'Reply sent.');
    }

    public function destroy(Complaint $complaint): RedirectResponse
    {
        $complaint->delete();

        return redirect()
            ->route('admin.complaints.index')
            ->with('status', 'Complaint archived.');
    }

    public function restore(int $complaint): RedirectResponse
    {
        $complaint = Complaint::onlyTrashed()->findOrFail($complaint);
        $complaint->restore();

        return redirect()
            ->route('admin.complaints.show', $complaint)
            ->with('status', 'Complaint restored.');
    }
}
