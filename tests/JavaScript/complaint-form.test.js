// @vitest-environment jsdom

import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { initComplaintForm } from '../../resources/js/app.js';

const complaintFormMarkup = () => `
    <form
        data-complaint-form
        data-license-status-url="http://localhost/complaints/license-status"
        data-invalid-logo-value="invalid_logo"
    >
        <input name="license_number" type="text">
        <select data-complaint-type-select data-poor-quality-value="poor_quality_translation">
            <option value="">Select a complaint type</option>
            <option value="invalid_logo">Invalid logo</option>
            <option value="poor_quality_translation">Poor-quality translation</option>
        </select>

        <section data-shared-location-fields>
            <input name="translation_location" type="text">
        </section>

        <section data-poor-quality-fields>
            <textarea name="major_error" data-required-for-poor-quality></textarea>
            <select name="harm_type" data-required-for-poor-quality>
                <option value="">Select</option>
                <option value="injury">Injury</option>
            </select>
        </section>

        <section data-invalid-logo-fields>
            <div data-license-status-feedback></div>
            <div data-valid-license-explanation-section>
                <textarea name="valid_license_explanation" data-required-for-valid-license></textarea>
            </div>
        </section>
    </form>
`;

const flushLookup = async (ms = 0) => {
    await vi.advanceTimersByTimeAsync(ms);
    await Promise.resolve();
    await Promise.resolve();
};

describe('initComplaintForm', () => {
    beforeEach(() => {
        document.body.innerHTML = complaintFormMarkup();
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.restoreAllMocks();
        document.body.innerHTML = '';
    });

    it('shows only the sections relevant to the selected complaint type', async () => {
        const form = document.querySelector('[data-complaint-form]');
        const typeSelect = form.querySelector('[data-complaint-type-select]');
        const sharedLocation = form.querySelector('[data-shared-location-fields]');
        const poorQuality = form.querySelector('[data-poor-quality-fields]');
        const invalidLogo = form.querySelector('[data-invalid-logo-fields]');
        const translationLocation = form.querySelector('[name="translation_location"]');
        const majorError = form.querySelector('[name="major_error"]');
        const harmType = form.querySelector('[name="harm_type"]');

        initComplaintForm(form, {
            fetchImpl: vi.fn(),
            debounceMs: 0,
        });

        expect(sharedLocation.hidden).toBe(true);
        expect(poorQuality.hidden).toBe(true);
        expect(invalidLogo.hidden).toBe(true);
        expect(translationLocation.required).toBe(false);
        expect(majorError.required).toBe(false);
        expect(harmType.required).toBe(false);

        typeSelect.value = 'poor_quality_translation';
        typeSelect.dispatchEvent(new Event('change'));

        expect(sharedLocation.hidden).toBe(false);
        expect(poorQuality.hidden).toBe(false);
        expect(invalidLogo.hidden).toBe(true);
        expect(translationLocation.required).toBe(true);
        expect(majorError.required).toBe(true);
        expect(harmType.required).toBe(true);

        typeSelect.value = 'invalid_logo';
        typeSelect.dispatchEvent(new Event('change'));
        await flushLookup();

        expect(sharedLocation.hidden).toBe(false);
        expect(poorQuality.hidden).toBe(true);
        expect(invalidLogo.hidden).toBe(false);
        expect(translationLocation.required).toBe(true);
        expect(majorError.required).toBe(false);
        expect(harmType.required).toBe(false);
    });

    it('requires an explanation when an invalid-logo complaint references a valid license', async () => {
        const form = document.querySelector('[data-complaint-form]');
        const typeSelect = form.querySelector('[data-complaint-type-select]');
        const licenseInput = form.querySelector('[name="license_number"]');
        const feedback = form.querySelector('[data-license-status-feedback]');
        const explanationSection = form.querySelector('[data-valid-license-explanation-section]');
        const explanationField = form.querySelector('[name="valid_license_explanation"]');
        const fetchImpl = vi.fn().mockResolvedValue({
            ok: true,
            json: async () => ({
                license_number: '100-001',
                license_status_at_filing: 'valid',
            }),
        });

        initComplaintForm(form, { fetchImpl, debounceMs: 0 });

        licenseInput.value = '100001';
        typeSelect.value = 'invalid_logo';
        typeSelect.dispatchEvent(new Event('change'));
        await flushLookup();

        expect(fetchImpl).toHaveBeenCalledWith(
            'http://localhost/complaints/license-status?license_number=100001',
            expect.objectContaining({
                headers: {
                    Accept: 'application/json',
                },
            }),
        );
        expect(feedback.textContent).toContain('Logo ID 100-001 is currently valid.');
        expect(explanationSection.hidden).toBe(false);
        expect(explanationField.required).toBe(true);
    });

    it('does not require an explanation when the invalid-logo lookup is nonvalid', async () => {
        const form = document.querySelector('[data-complaint-form]');
        const typeSelect = form.querySelector('[data-complaint-type-select]');
        const licenseInput = form.querySelector('[name="license_number"]');
        const feedback = form.querySelector('[data-license-status-feedback]');
        const explanationSection = form.querySelector('[data-valid-license-explanation-section]');
        const explanationField = form.querySelector('[name="valid_license_explanation"]');
        const fetchImpl = vi.fn().mockResolvedValue({
            ok: true,
            json: async () => ({
                license_number: '100-002',
                license_status_at_filing: 'invalid_or_suspended',
            }),
        });

        initComplaintForm(form, { fetchImpl, debounceMs: 0 });

        licenseInput.value = '100-002';
        typeSelect.value = 'invalid_logo';
        typeSelect.dispatchEvent(new Event('change'));
        await flushLookup();

        expect(feedback.textContent).toContain('Logo ID 100-002 is not currently valid.');
        expect(explanationSection.hidden).toBe(true);
        expect(explanationField.required).toBe(false);
    });

    it('ignores stale lookup responses and keeps the newest status visible', async () => {
        const form = document.querySelector('[data-complaint-form]');
        const typeSelect = form.querySelector('[data-complaint-type-select]');
        const licenseInput = form.querySelector('[name="license_number"]');
        const feedback = form.querySelector('[data-license-status-feedback]');
        const explanationSection = form.querySelector('[data-valid-license-explanation-section]');

        let resolveFirst;
        let resolveSecond;

        const fetchImpl = vi
            .fn()
            .mockImplementationOnce(
                () =>
                    new Promise((resolve) => {
                        resolveFirst = resolve;
                    }),
            )
            .mockImplementationOnce(
                () =>
                    new Promise((resolve) => {
                        resolveSecond = resolve;
                    }),
            );

        initComplaintForm(form, { fetchImpl, debounceMs: 0 });

        typeSelect.value = 'invalid_logo';
        licenseInput.value = '100001';
        typeSelect.dispatchEvent(new Event('change'));
        await flushLookup();

        licenseInput.value = '999-999';
        licenseInput.dispatchEvent(new Event('input'));
        await flushLookup();

        resolveFirst({
            ok: true,
            json: async () => ({
                license_number: '100-001',
                license_status_at_filing: 'valid',
            }),
        });
        await Promise.resolve();
        await Promise.resolve();

        expect(feedback.textContent).not.toContain('Logo ID 100-001 is currently valid.');
        expect(explanationSection.hidden).toBe(true);

        resolveSecond({
            ok: true,
            json: async () => ({
                license_number: '999-999',
                license_status_at_filing: 'non_existent',
            }),
        });
        await Promise.resolve();
        await Promise.resolve();

        expect(feedback.textContent).toContain('No matching current logo ID was found for 999-999.');
        expect(explanationSection.hidden).toBe(true);
    });
});
