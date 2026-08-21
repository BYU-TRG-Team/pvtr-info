<x-layouts.app title="Complaint {{ $complaint->public_reference }}">
    <div class="mb-6">
        <a href="{{ route('admin.complaints.index') }}" class="text-sm text-slate-600 underline">Back to complaints</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
        <div class="space-y-6">
            @php
                $editFields = ['complaint_type', 'harm_type'];
                $showEditForm = ! $complaint->trashed() && $errors->hasAny($editFields);
            @endphp

            <section
                class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm"
                data-complaint-edit
                data-editing="{{ $showEditForm ? 'true' : 'false' }}"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3">
                            <p class="text-sm font-medium uppercase tracking-wide text-slate-500">Complaint details</p>
                            @if ($complaint->trashed())
                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800">Archived</span>
                            @endif
                        </div>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight">{{ $complaint->public_reference }}</h1>
                    </div>

                    @unless ($complaint->trashed())
                        <button
                            type="button"
                            class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-800 hover:bg-slate-50"
                            data-edit-toggle
                        >
                            {{ $showEditForm ? 'Cancel editing' : 'Correct classification' }}
                        </button>
                    @endunless
                </div>

                <dl
                    class="mt-6 grid gap-5 text-sm sm:grid-cols-2"
                >
                    <div>
                        <dt class="font-medium text-slate-700">Complainant</dt>
                        <dd class="mt-1 text-slate-900">{{ $complaint->complainant_name }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-700">Email</dt>
                        <dd class="mt-1 text-slate-900">{{ $complaint->complainant_email }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-700">Phone</dt>
                        <dd class="mt-1 text-slate-900">{{ $complaint->complainant_phone ?: 'Not provided' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-700">Filed</dt>
                        <dd class="mt-1 text-slate-900">{{ $complaint->filed_at->format('M j, Y g:i A') }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-700">Relevant logo ID</dt>
                        <dd class="mt-1 text-slate-900">{{ $complaint->license_number }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-700">Logo status at filing</dt>
                        <dd class="mt-1 text-slate-900">{{ str($complaint->license_status_at_filing->value)->replace('_', ' ')->title() }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-700">Complaint type</dt>
                        <dd class="mt-1 text-slate-900">{{ str($complaint->complaint_type->value)->replace('_', ' ')->title() }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-700">Current status</dt>
                        <dd class="mt-1 text-slate-900">{{ str($complaint->status->value)->replace('_', ' ')->title() }}</dd>
                    </div>
                    @if ($complaint->details)
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
                    @endif
                    <div class="sm:col-span-2">
                        <dt class="font-medium text-slate-700">Statement of complaint</dt>
                        <dd class="mt-1 whitespace-pre-line text-slate-900">{{ $statement?->body ?? 'Not provided' }}</dd>
                    </div>
                </dl>

                @unless ($complaint->trashed())
                    <form
                        method="POST"
                        action="{{ route('admin.complaints.update', $complaint) }}"
                        class="mt-6 space-y-5 rounded-md border border-slate-200 bg-slate-50 p-5"
                        data-complaint-edit-form
                        @if (! $showEditForm) hidden @endif
                    >
                        @csrf
                        @method('PUT')

                        <div>
                            <h2 class="font-semibold">Correct complaint classification</h2>
                            <p class="mt-1 text-sm text-slate-600">The complainant’s identity, contact information, statement, and supporting details cannot be edited.</p>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="edit_complaint_type" class="block text-sm font-medium text-slate-700">Complaint type</label>
                                <select id="edit_complaint_type" name="complaint_type" required class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none">
                                    @foreach ($complaintTypes as $complaintType)
                                        <option value="{{ $complaintType->value }}" @selected(old('complaint_type', $complaint->complaint_type->value) === $complaintType->value)>
                                            {{ str($complaintType->value)->replace('_', ' ')->title() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('complaint_type') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="edit_harm_type" class="block text-sm font-medium text-slate-700">Potential harm</label>
                                <select id="edit_harm_type" name="harm_type" class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none">
                                    <option value="">Not categorized</option>
                                    @foreach (['injury' => 'Injury to a person', 'equipment_damage' => 'Damage to equipment', 'reputational_harm' => 'Reputational harm', 'financial_loss' => 'Financial loss', 'other' => 'Other'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('harm_type', $complaint->details['harm_type'] ?? '') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('harm_type') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <button type="submit" class="rounded-md bg-slate-950 px-5 py-2.5 text-sm font-medium text-white hover:bg-slate-800">Save classification</button>
                            <button type="button" class="rounded-md border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-800 hover:bg-slate-50" data-edit-toggle>Cancel</button>
                        </div>
                    </form>
                @endunless
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold">Message thread</h2>

                <div class="mt-5 space-y-4">
                    @foreach ($complaint->messages as $message)
                        <article class="rounded-md border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center justify-between gap-4 text-sm">
                                <p class="font-medium text-slate-900">
                                    @if ($message->author_type === \App\Enums\ComplaintMessageAuthorType::Admin)
                                        PVTR administrator{{ $message->user ? ' — '.$message->user->name : '' }}
                                    @else
                                        {{ $complaint->complainant_name }}
                                    @endif
                                </p>
                                <time class="text-slate-500" datetime="{{ $message->created_at->toAtomString() }}">
                                    {{ $message->created_at->format('M j, Y g:i A') }}
                                </time>
                            </div>
                            <p class="mt-3 whitespace-pre-line text-sm text-slate-700">{{ $message->body }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold">Complainant private link</h2>
                <p class="mt-2 break-all text-xs text-slate-600">{{ $secretLink }}</p>
                @if ($complaint->trashed())
                    <p class="mt-3 text-sm text-amber-700">The link remains available in read-only mode while this complaint is archived.</p>
                @endif
                <div class="mt-4 flex flex-wrap gap-3">
                    <a
                        href="{{ $secretLink }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="rounded-md bg-slate-950 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800"
                    >
                        View private thread
                    </a>
                    <button
                        type="button"
                        data-copy-link
                        data-copy-value="{{ $secretLink }}"
                        class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-900 hover:bg-slate-50"
                    >
                        Copy private link
                    </button>
                    <span class="w-full text-sm text-slate-600" data-copy-status aria-live="polite"></span>
                </div>
            </section>

            @if ($complaint->trashed())
                <section class="rounded-lg border border-amber-200 bg-amber-50 p-5 shadow-sm">
                    <h2 class="font-semibold text-amber-950">Archived complaint</h2>
                    <p class="mt-2 text-sm text-amber-900">Restore this complaint to edit it, reply, or make its private thread available again.</p>
                    <form method="POST" action="{{ route('admin.complaints.restore', $complaint) }}" class="mt-4">
                        @csrf
                        <button type="submit" class="rounded-md bg-amber-900 px-4 py-2 text-sm font-medium text-white hover:bg-amber-800">
                            Restore complaint
                        </button>
                    </form>
                </section>
            @else
                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold">Update complaint status</h2>
                <form method="POST" action="{{ route('admin.complaints.status.update', $complaint) }}" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
                        <select
                            id="status"
                            name="status"
                            required
                            class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-slate-900 focus:outline-none"
                        >
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected(old('status', $complaint->status->value) === $status->value)>
                                    {{ str($status->value)->replace('_', ' ')->title() }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="rounded-md bg-slate-950 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                        Update status
                    </button>
                </form>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold">Send a reply</h2>
                <p class="mt-1 text-sm text-slate-600">The complainant will see this reply through their private link.</p>

                <form method="POST" action="{{ route('admin.complaints.replies.store', $complaint) }}" class="mt-4 space-y-4">
                    @csrf

                    <div>
                        <label for="body" class="block text-sm font-medium text-slate-700">Message</label>
                        <textarea
                            id="body"
                            name="body"
                            rows="6"
                            required
                            class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-900 focus:outline-none"
                        >{{ old('body') }}</textarea>
                        @error('body')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="rounded-md bg-slate-950 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                        Send reply
                    </button>
                </form>
                </section>

                <section class="rounded-lg border border-red-200 bg-white p-5 shadow-sm">
                    <h2 class="font-semibold text-red-900">Archive complaint</h2>
                    <p class="mt-2 text-sm text-slate-600">The complaint and its messages will be preserved and can be restored later.</p>
                    <form method="POST" action="{{ route('admin.complaints.destroy', $complaint) }}" class="mt-4">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-md border border-red-300 px-4 py-2 text-sm font-medium text-red-800 hover:bg-red-50">
                            Archive complaint
                        </button>
                    </form>
                </section>
            @endif
        </aside>
    </div>
</x-layouts.app>
