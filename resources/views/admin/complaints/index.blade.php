<x-layouts.app title="Admin Complaints">
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-medium uppercase tracking-wide text-slate-500">Admin panel</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">Complaint management</h1>
            <p class="mt-2 text-slate-600">Review, edit, archive, and respond to filed complaints.</p>
        </div>

        <form method="GET" action="{{ route('admin.complaints.index') }}" class="flex items-end gap-3">
            <input type="hidden" name="view" value="{{ $selectedView }}">
            <div>
                <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
                <select
                    id="status"
                    name="status"
                    class="mt-2 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-slate-900 focus:outline-none"
                >
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($selectedStatus === $status)>
                            {{ str($status->value)->replace('_', ' ')->title() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-md bg-slate-950 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Filter
            </button>
        </form>
    </div>

    <nav class="mb-4 flex gap-1 border-b border-slate-200" aria-label="Complaint lists">
        <a
            href="{{ route('admin.complaints.index', array_filter(['view' => 'active', 'status' => $selectedStatus?->value])) }}"
            @class([
                'border-b-2 px-4 py-2 text-sm font-medium',
                'border-slate-950 text-slate-950' => $selectedView === 'active',
                'border-transparent text-slate-500 hover:text-slate-800' => $selectedView !== 'active',
            ])
        >
            Active
        </a>
        <a
            href="{{ route('admin.complaints.index', array_filter(['view' => 'archived', 'status' => $selectedStatus?->value])) }}"
            @class([
                'border-b-2 px-4 py-2 text-sm font-medium',
                'border-slate-950 text-slate-950' => $selectedView === 'archived',
                'border-transparent text-slate-500 hover:text-slate-800' => $selectedView !== 'archived',
            ])
        >
            Archived
        </a>
    </nav>

    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-700">
                    <tr>
                        <th class="px-4 py-3 font-medium">Reference</th>
                        <th class="px-4 py-3 font-medium">Complainant</th>
                        <th class="px-4 py-3 font-medium">Logo ID</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Filed</th>
                        @if ($selectedView === 'archived')
                            <th class="px-4 py-3 font-medium">Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($complaints as $complaint)
                        <tr>
                            <td class="px-4 py-3 font-medium">
                                <a href="{{ route('admin.complaints.show', $complaint) }}" class="text-slate-950 underline">
                                    {{ $complaint->public_reference }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ $complaint->complainant_name }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $complaint->license_number }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ str($complaint->status->value)->replace('_', ' ')->title() }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $complaint->filed_at->format('M j, Y') }}</td>
                            @if ($selectedView === 'archived')
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('admin.complaints.restore', $complaint) }}">
                                        @csrf
                                        <button type="submit" class="text-sm font-medium text-slate-950 underline">
                                            Restore
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $selectedView === 'archived' ? 6 : 5 }}" class="px-4 py-8 text-center text-slate-500">
                                No {{ $selectedView }} complaints match this filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.app>
