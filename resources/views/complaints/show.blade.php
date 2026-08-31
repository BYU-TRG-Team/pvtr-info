<x-layouts.app title="Complaint {{ $complaint->public_reference }}">
    <div class="mx-auto max-w-3xl space-y-6">
        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-medium uppercase tracking-wide text-slate-500">Complaint thread</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">{{ $complaint->public_reference }}</h1>
            <p class="mt-3 text-slate-600">
                Review the complaint details and messages associated with this private link.
            </p>

            @if ($complaint->trashed())
                <div class="mt-5 rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <p class="font-medium">This complaint has been archived.</p>
                    <p class="mt-1">Its details and message history remain available, but no further replies can be added.</p>
                </div>
            @endif

            <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="font-medium text-slate-700">Relevant logo ID</dt>
                    <dd class="mt-1 text-slate-900">{{ $complaint->license_number }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-700">Filed</dt>
                    <dd class="mt-1 text-slate-900">{{ $complaint->filed_at->format('M j, Y') }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-700">Complaint type</dt>
                    <dd class="mt-1 text-slate-900">{{ str($complaint->complaint_type->value)->replace('_', ' ')->title() }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-700">Status</dt>
                    <dd class="mt-1 text-slate-900">{{ str($complaint->status->value)->replace('_', ' ')->title() }}</dd>
                </div>
            </dl>

            @if ($complaint->details)
                <dl class="mt-6 space-y-4 border-t border-slate-200 pt-6 text-sm">
                    @if ($complaint->details['translation_location'] ?? null)
                        <div>
                            <dt class="font-medium text-slate-700">Translation location</dt>
                            <dd class="mt-1 break-words text-slate-900">{{ $complaint->details['translation_location'] }}</dd>
                        </div>
                    @endif
                    @if ($complaint->details['major_error'] ?? null)
                        <div>
                            <dt class="font-medium text-slate-700">Reported major error</dt>
                            <dd class="mt-1 whitespace-pre-line text-slate-900">{{ $complaint->details['major_error'] }}</dd>
                        </div>
                    @endif
                    @if ($complaint->details['harm_type'] ?? null)
                        <div>
                            <dt class="font-medium text-slate-700">Potential harm</dt>
                            <dd class="mt-1 text-slate-900">{{ str($complaint->details['harm_type'])->replace('_', ' ')->title() }}</dd>
                        </div>
                    @endif
                    @if ($complaint->details['valid_license_explanation'] ?? null)
                        <div>
                            <dt class="font-medium text-slate-700">Why this was still filed as an invalid logo complaint</dt>
                            <dd class="mt-1 whitespace-pre-line text-slate-900">{{ $complaint->details['valid_license_explanation'] }}</dd>
                        </div>
                    @endif
                </dl>
            @endif
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-semibold">Messages</h2>

            <div class="mt-5 space-y-4">
                @foreach ($complaint->messages as $message)
                    <article class="rounded-md border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-center justify-between gap-4 text-sm">
                            <p class="font-medium text-slate-900">
                                {{ $message->author_type === \App\Enums\ComplaintMessageAuthorType::Admin ? 'PVTR administrator' : $complaint->complainant_name }}
                            </p>
                            <time class="text-slate-500" datetime="{{ $message->created_at->toAtomString() }}">
                                {{ $message->created_at->format('M j, Y g:i A') }}
                            </time>
                        </div>
                        <p class="mt-3 whitespace-pre-line text-sm text-slate-700">{{ $message->body }}</p>
                    </article>
                @endforeach
            </div>

            @unless ($complaint->trashed())
                <form
                    method="POST"
                    action="{{ route('complaints.replies.store', ['secretLinkKey' => $complaint->secret_link_key]) }}"
                    class="mt-6 border-t border-slate-200 pt-6"
                >
                    @csrf

                    <label for="body" class="block text-sm font-medium text-slate-700">Add follow-up information</label>
                    <textarea
                        id="body"
                        name="body"
                        rows="5"
                        required
                        class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none"
                    >{{ old('body') }}</textarea>
                    @error('body')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <button type="submit" class="mt-4 rounded-md bg-slate-950 px-5 py-2.5 text-sm font-medium text-white hover:bg-slate-800">
                        Send reply
                    </button>
                </form>
            @endunless
        </section>
    </div>
</x-layouts.app>
