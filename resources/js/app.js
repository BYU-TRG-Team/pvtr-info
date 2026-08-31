const copyText = async (value) => {
    if (navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(value);

        return;
    }

    const textarea = document.createElement('textarea');
    textarea.value = value;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();

    const copied = document.execCommand('copy');
    textarea.remove();

    if (!copied) {
        throw new Error('Unable to copy text.');
    }
};

const setFeedbackState = (feedback, tone, message) => {
    if (!feedback) {
        return;
    }

    feedback.textContent = message;
    feedback.className = {
        neutral: 'rounded-md border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700',
        success: 'rounded-md border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-900',
        warning: 'rounded-md border border-amber-300 bg-amber-100 px-4 py-3 text-sm text-amber-950',
        danger: 'rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-900',
    }[tone];
};

const buildLicenseStatusUrl = (baseUrl, licenseNumber) => {
    const url = new URL(baseUrl, window.location.origin);
    url.searchParams.set('license_number', licenseNumber);

    return url.toString();
};

export const initComplaintForm = (
    form,
    {
        fetchImpl = (...args) => window.fetch(...args),
        debounceMs = 250,
    } = {},
) => {
    const typeSelect = form.querySelector('[data-complaint-type-select]');
    const licenseInput = form.querySelector('[name="license_number"]');
    const sharedLocationFields = form.querySelector('[data-shared-location-fields]');
    const poorQualityFields = form.querySelector('[data-poor-quality-fields]');
    const invalidLogoFields = form.querySelector('[data-invalid-logo-fields]');
    const feedback = form.querySelector('[data-license-status-feedback]');
    const translationLocation = form.querySelector('[name="translation_location"]');
    const explanationSection = form.querySelector('[data-valid-license-explanation-section]');
    const explanationField = form.querySelector('[data-required-for-valid-license]');

    if (
        !typeSelect
        || !licenseInput
        || !sharedLocationFields
        || !poorQualityFields
        || !invalidLogoFields
        || !translationLocation
        || !explanationSection
        || !explanationField
    ) {
        return;
    }

    const invalidLogoValue = form.dataset.invalidLogoValue;
    const poorQualityValue = typeSelect.dataset.poorQualityValue;
    const licenseStatusUrl = form.dataset.licenseStatusUrl;
    const poorQualityRequiredFields = poorQualityFields.querySelectorAll(
        '[data-required-for-poor-quality]',
    );

    let lookupTimeoutId = null;
    let latestLookupId = 0;

    const setExplanationRequired = (required) => {
        explanationSection.hidden = !required;
        explanationField.required = required;
    };

    const resetInvalidLogoFeedback = () => {
        setFeedbackState(
            feedback,
            'neutral',
            'Enter the relevant logo ID above to check whether it is currently valid.',
        );
        setExplanationRequired(false);
    };

    const applyLookupResult = (result) => {
        switch (result.license_status_at_filing) {
            case 'valid':
                setFeedbackState(
                    feedback,
                    'warning',
                    `Logo ID ${result.license_number} is currently valid. If the issue is translation quality rather than incorrect logo use, choose Poor-quality translation instead.`,
                );
                setExplanationRequired(true);
                break;
            case 'invalid_or_suspended':
                setFeedbackState(
                    feedback,
                    'success',
                    `Logo ID ${result.license_number} is not currently valid. You can continue filing an Invalid logo complaint.`,
                );
                setExplanationRequired(false);
                break;
            default:
                setFeedbackState(
                    feedback,
                    'danger',
                    `No matching current logo ID was found for ${result.license_number}. You can still file an Invalid logo complaint if this is the ID being displayed.`,
                );
                setExplanationRequired(false);
                break;
        }
    };

    const runLookup = async () => {
        const lookupId = ++latestLookupId;
        const licenseNumber = licenseInput.value.trim();

        if (typeSelect.value !== invalidLogoValue) {
            return;
        }

        if (licenseNumber === '' || !licenseStatusUrl) {
            resetInvalidLogoFeedback();
            return;
        }

        setFeedbackState(feedback, 'neutral', 'Checking the current status of this logo ID...');

        try {
            const response = await fetchImpl(
                buildLicenseStatusUrl(licenseStatusUrl, licenseNumber),
                {
                    headers: {
                        Accept: 'application/json',
                    },
                },
            );

            if (lookupId !== latestLookupId) {
                return;
            }

            if (!response.ok) {
                throw new Error('Lookup failed.');
            }

            applyLookupResult(await response.json());
        } catch {
            if (lookupId !== latestLookupId) {
                return;
            }

            setFeedbackState(
                feedback,
                'danger',
                'Unable to check the logo ID right now. You can still submit the complaint, and we will validate it on the server.',
            );
            setExplanationRequired(false);
        }
    };

    const scheduleLookup = () => {
        if (lookupTimeoutId !== null) {
            window.clearTimeout(lookupTimeoutId);
        }

        lookupTimeoutId = window.setTimeout(() => {
            void runLookup();
        }, debounceMs);
    };

    const syncComplaintForm = () => {
        const isInvalidLogo = typeSelect.value === invalidLogoValue;
        const isPoorQuality = typeSelect.value === poorQualityValue;
        const hasSelectedType = isInvalidLogo || isPoorQuality;

        sharedLocationFields.hidden = !hasSelectedType;
        poorQualityFields.hidden = !isPoorQuality;
        invalidLogoFields.hidden = !isInvalidLogo;
        translationLocation.required = hasSelectedType;

        poorQualityRequiredFields.forEach((field) => {
            field.required = isPoorQuality;
        });

        if (!isInvalidLogo) {
            latestLookupId += 1;
            setExplanationRequired(false);
            return;
        }

        resetInvalidLogoFeedback();
        scheduleLookup();
    };

    typeSelect.addEventListener('change', syncComplaintForm);
    licenseInput.addEventListener('input', () => {
        if (typeSelect.value === invalidLogoValue) {
            scheduleLookup();
        }
    });

    syncComplaintForm();
};

export const initComplaintEditToggles = (root = document) => {
    root.querySelectorAll('[data-complaint-edit]').forEach((container) => {
        const form = container.querySelector('[data-complaint-edit-form]');
        const toggles = container.querySelectorAll('[data-edit-toggle]');

        if (!form || toggles.length === 0) {
            return;
        }

        const setEditing = (editing) => {
            container.dataset.editing = editing ? 'true' : 'false';
            form.hidden = !editing;

            toggles.forEach((toggle) => {
                toggle.textContent = editing ? 'Cancel editing' : 'Correct classification';
            });
        };

        toggles.forEach((toggle) => {
            toggle.addEventListener('click', () => {
                setEditing(container.dataset.editing !== 'true');
            });
        });
    });
};

export const initCopyLinkButtons = (root = document) => {
    root.querySelectorAll('[data-copy-link]').forEach((button) => {
        const status = button.parentElement?.querySelector('[data-copy-status]');
        const defaultLabel = button.textContent.trim();

        button.addEventListener('click', async () => {
            try {
                await copyText(button.dataset.copyValue);
                button.textContent = 'Copied';

                if (status) {
                    status.textContent = 'Private link copied.';
                }

                window.setTimeout(() => {
                    button.textContent = defaultLabel;
                }, 2000);
            } catch {
                if (status) {
                    status.textContent = 'Unable to copy. Open the link and copy it from your browser.';
                }
            }
        });
    });
};

export const initializeApp = (root = document) => {
    root.querySelectorAll('[data-complaint-form]').forEach((form) => {
        initComplaintForm(form);
    });

    initComplaintEditToggles(root);
    initCopyLinkButtons(root);
};

if (typeof document !== 'undefined') {
    initializeApp(document);
}
