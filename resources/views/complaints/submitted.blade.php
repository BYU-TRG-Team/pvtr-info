<x-layouts.app title="Complaint Submitted">
    <div class="mx-auto max-w-2xl rounded-lg border border-emerald-200 bg-emerald-50 p-6 shadow-sm">
        <p class="text-sm font-medium uppercase tracking-wide text-emerald-700">Complaint submitted</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-emerald-950">
            Your complaint has been received
        </h1>
        <p class="mt-3 text-emerald-900">
            Your complaint reference is <strong>{{ $complaint->public_reference }}</strong>.
        </p>

        <div class="mt-6 rounded-md border border-emerald-200 bg-white p-4">
            <h2 class="font-semibold text-slate-900">Save this private link</h2>
            <p class="mt-2 text-sm text-slate-600">
                You will use this secret link to view replies and add follow-up information. Anyone with the link can access this complaint thread.
            </p>
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <a
                    href="{{ $secretLink }}"
                    class="rounded-md bg-slate-950 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800"
                >
                    Open complaint thread
                </a>
                <button
                    type="button"
                    data-copy-link
                    data-copy-value="{{ $secretLink }}"
                    class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-900 hover:bg-slate-50"
                >
                    Copy private link
                </button>
                <span class="text-sm text-slate-600" data-copy-status aria-live="polite"></span>
            </div>
        </div>
    </div>
</x-layouts.app>
