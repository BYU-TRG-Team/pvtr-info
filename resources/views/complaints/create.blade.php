<x-layouts.app title="File a Complaint">
    <div class="mx-auto max-w-3xl">
        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-medium uppercase tracking-wide text-slate-500">Logo misuse</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">File a complaint</h1>
            <p class="mt-3 text-slate-600">
                Report suspected misuse of a logo license or a quality concern involving a licensed translation.
            </p>

            <div class="mt-8 border-t border-slate-200 pt-6">
                <h2 class="text-lg font-semibold">Complaint form</h2>
                <p class="mt-2 text-sm text-slate-600">
                    Provide your contact, license, and complaint details using the fields in this form.
                </p>
            </div>

            <aside class="mt-6 rounded-md border border-slate-200 bg-slate-50 p-5">
                <h2 class="font-semibold text-slate-900">Which type of complaint should I choose?</h2>
                <div class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <h3 class="font-medium text-slate-900">Invalid logo attached to a translation</h3>
                        <p class="mt-1 text-slate-600">
                            Choose this when a PVTR logo appears on a translation but the displayed logo ID is not valid.
                        </p>
                    </div>
                    <div>
                        <h3 class="font-medium text-slate-900">Valid logo, poor-quality translation</h3>
                        <p class="mt-1 text-slate-600">
                            Choose this when the logo is valid, but the translation contains major or critical errors or appears to be unverified automatic translation.
                        </p>
                    </div>
                </div>
            </aside>

            <form
                method="POST"
                action="{{ route('complaints.store') }}"
                class="mt-6 space-y-6"
                data-complaint-form
                data-license-status-url="{{ route('complaints.license-status') }}"
                data-invalid-logo-value="{{ $invalidLogoType }}"
            >
                @csrf

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="complainant_name" class="block text-sm font-medium text-slate-700">Name</label>
                        <input
                            id="complainant_name"
                            name="complainant_name"
                            type="text"
                            value="{{ old('complainant_name') }}"
                            required
                            class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none"
                        >
                        @error('complainant_name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="complainant_email" class="block text-sm font-medium text-slate-700">Email</label>
                        <input
                            id="complainant_email"
                            name="complainant_email"
                            type="email"
                            value="{{ old('complainant_email') }}"
                            required
                            class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none"
                        >
                        @error('complainant_email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="complainant_phone" class="block text-sm font-medium text-slate-700">
                            Phone <span class="font-normal text-slate-500">(optional)</span>
                        </label>
                        <input
                            id="complainant_phone"
                            name="complainant_phone"
                            type="tel"
                            value="{{ old('complainant_phone') }}"
                            class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none"
                        >
                        @error('complainant_phone')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="license_number" class="block text-sm font-medium text-slate-700">
                            <x-tooltip-label text="Enter the number displayed with the PVTR logo. Dashes are optional.">
                                Relevant logo ID
                            </x-tooltip-label>
                        </label>
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
                </div>

                <div>
                    <label for="complaint_type" class="block text-sm font-medium text-slate-700">
                        <x-tooltip-label text="Choose the scenario that best matches what you are reporting.">
                            Complaint type
                        </x-tooltip-label>
                    </label>
                    <select
                        id="complaint_type"
                        name="complaint_type"
                        data-complaint-type-select
                        data-poor-quality-value="{{ $poorQualityType }}"
                        required
                        class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none"
                    >
                        <option value="">Select a complaint type</option>
                        @foreach ($complaintTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('complaint_type') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('complaint_type')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <fieldset
                    class="space-y-5 rounded-md border border-slate-200 bg-slate-50 p-5"
                    data-shared-location-fields
                >
                    <legend class="px-1 text-sm font-medium text-slate-700">
                        Where to find the translation or logo use
                        <span class="font-normal text-slate-500">(required after selecting a complaint type)</span>
                    </legend>

                    <div>
                        <label for="translation_location" class="block text-sm font-medium text-slate-700">
                            <x-tooltip-label text="Provide a URL, document name, product, screenshot location, or other information that lets reviewers find what you are reporting.">
                                Where to find it
                            </x-tooltip-label>
                        </label>
                        <input
                            id="translation_location"
                            name="translation_location"
                            type="text"
                            value="{{ old('translation_location') }}"
                            data-required-for-poor-quality
                            class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none"
                        >
                        @error('translation_location')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </fieldset>

                <fieldset
                    class="space-y-5 rounded-md border border-slate-200 bg-slate-50 p-5"
                    data-poor-quality-fields
                >
                    <legend class="px-1 text-sm font-medium text-slate-700">
                        Poor-quality translation details
                        <span class="font-normal text-slate-500">(required for poor-quality complaints)</span>
                    </legend>

                    <div>
                        <label for="major_error" class="block text-sm font-medium text-slate-700">
                            Major or critical errors or evidence of unverified automatic translation
                        </label>
                        <textarea
                            id="major_error"
                            name="major_error"
                            rows="4"
                            data-required-for-poor-quality
                            class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none"
                        >{{ old('major_error') }}</textarea>
                        @error('major_error')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="harm_type" class="block text-sm font-medium text-slate-700">
                            <x-tooltip-label text="Choose the most likely consequence of the translation problem.">
                                Potential harm
                            </x-tooltip-label>
                        </label>
                        <select
                            id="harm_type"
                            name="harm_type"
                            data-required-for-poor-quality
                            class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none"
                        >
                            <option value="">Select a type of harm</option>
                            <option value="injury" @selected(old('harm_type') === 'injury')>Injury to a person</option>
                            <option value="equipment_damage" @selected(old('harm_type') === 'equipment_damage')>Damage to equipment</option>
                            <option value="reputational_harm" @selected(old('harm_type') === 'reputational_harm')>Harm to an organization’s image</option>
                            <option value="financial_loss" @selected(old('harm_type') === 'financial_loss')>Financial loss</option>
                            <option value="other" @selected(old('harm_type') === 'other')>Other</option>
                        </select>
                        @error('harm_type')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </fieldset>

                <section class="space-y-5 rounded-md border border-amber-200 bg-amber-50 p-5" data-invalid-logo-fields>
                    <div>
                        <h2 class="font-semibold text-amber-950">Invalid logo guidance</h2>
                        <p class="mt-1 text-sm text-amber-900">
                            We will check the relevant logo ID when you select this complaint type.
                        </p>
                    </div>

                    <div
                        class="rounded-md border border-amber-300 bg-white px-4 py-3 text-sm text-slate-700"
                        data-license-status-feedback
                        aria-live="polite"
                    >
                        Enter the relevant logo ID above to check whether it is currently valid.
                    </div>

                    <div data-valid-license-explanation-section>
                        <label for="valid_license_explanation" class="block text-sm font-medium text-slate-700">
                            Why this should still be treated as an invalid logo complaint
                        </label>
                        <textarea
                            id="valid_license_explanation"
                            name="valid_license_explanation"
                            rows="4"
                            data-required-for-valid-license
                            class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none"
                        >{{ old('valid_license_explanation') }}</textarea>
                        <p class="mt-2 text-sm text-slate-600">
                            Required only when the logo ID is currently valid but the complaint is still about incorrect logo use.
                        </p>
                        @error('valid_license_explanation')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </section>

                <div>
                    <label for="statement" class="block text-sm font-medium text-slate-700">Statement of complaint</label>
                    <textarea
                        id="statement"
                        name="statement"
                        rows="6"
                        required
                        class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none"
                    >{{ old('statement') }}</textarea>
                    @error('statement')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="rounded-md bg-slate-950 px-5 py-2.5 text-sm font-medium text-white hover:bg-slate-800">
                    Submit complaint
                </button>
            </form>
        </section>
    </div>
</x-layouts.app>
