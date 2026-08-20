document.querySelectorAll('[data-complaint-form]').forEach((form) => {
    const typeSelect = form.querySelector('[data-complaint-type-select]');
    const poorQualityFields = form.querySelector('[data-poor-quality-fields]');

    if (!typeSelect || !poorQualityFields) {
        return;
    }

    const requiredFields = poorQualityFields.querySelectorAll('[data-required-for-poor-quality]');

    const syncPoorQualityFields = () => {
        const isPoorQuality = typeSelect.value === typeSelect.dataset.poorQualityValue;

        poorQualityFields.hidden = !isPoorQuality;

        requiredFields.forEach((field) => {
            field.disabled = !isPoorQuality;
            field.required = isPoorQuality;
        });
    };

    typeSelect.addEventListener('change', syncPoorQualityFields);
    syncPoorQualityFields();
});

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

document.querySelectorAll('[data-copy-link]').forEach((button) => {
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
