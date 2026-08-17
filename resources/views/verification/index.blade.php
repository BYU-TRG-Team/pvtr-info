<x-layouts.app title="Look Up License">
    <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <div class="max-w-2xl">
                <p class="text-sm font-medium uppercase tracking-wide text-slate-500">Logo license lookup</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">Check whether a logo license is valid</h1>
                <p class="mt-3 text-slate-600">
                    Enter the license number from your license record.
                </p>
            </div>

            <form method="POST" action="{{ route('verification.verify') }}" class="mt-8 space-y-6">
                @csrf

                <div>
                    <label for="license_number" class="block text-sm font-medium text-slate-700">License #</label>
                    <input
                        id="license_number"
                        name="license_number"
                        type="text"
                        value="{{ old('license_number') }}"
                        placeholder="###### or ###-###"
                        required
                        class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none"
                    >
                    @error('license_number')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="rounded-md bg-slate-950 px-5 py-2.5 text-sm font-medium text-white hover:bg-slate-800">
                    Look up license
                </button>
            </form>
        </section>

        <aside class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold">Lookup result</h2>

            @if ($result === 'valid' && $license)
                <div class="mt-4 rounded-md border border-emerald-200 bg-emerald-50 p-4">
                    <p class="font-medium text-emerald-900">License is valid.</p>
                    <dl class="mt-4 space-y-3 text-sm text-emerald-900">
                        <div>
                            <dt class="font-medium">
                                <x-tooltip-label text="There are two types of entity: an individual and a corporation/organization.">
                                    Entity
                                </x-tooltip-label>
                            </dt>
                            <dd>{{ $license->entity_name }}</dd>
                        </div>
                        @if ($license->email)
                            <div>
                                <dt class="font-medium">
                                    <x-tooltip-label text="The email address associated with this license record.">
                                        Contact
                                    </x-tooltip-label>
                                </dt>
                                <dd>{{ $license->email }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="font-medium">
                                <x-tooltip-label text="The current license status. Only an Active status is considered valid when checked.">
                                    Status
                                </x-tooltip-label>
                            </dt>
                            <dd>{{ $license->license_status }}</dd>
                        </div>
                    </dl>
                </div>
            @elseif ($result === 'invalid')
                <div class="mt-4 rounded-md border border-red-200 bg-red-50 p-4">
                    <p class="font-medium text-red-900">No valid matching license was found.</p>
                    <p class="mt-2 text-sm text-red-800">Check the license number and try again.</p>
                </div>
            @else
                <p class="mt-4 text-sm text-slate-600">Look up a license number to check whether it is currently valid.</p>
            @endif
        </aside>
    </div>
</x-layouts.app>
