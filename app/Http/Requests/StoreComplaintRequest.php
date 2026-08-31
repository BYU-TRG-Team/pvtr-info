<?php

namespace App\Http\Requests;

use App\Enums\ComplaintType;
use App\Enums\LicenseStatusAtFiling;
use App\Services\LicenseStatusAtFilingResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'complainant_name' => ['required', 'string', 'max:255'],
            'complainant_email' => ['required', 'email', 'max:255'],
            'complainant_phone' => ['nullable', 'string', 'max:50'],
            'license_number' => ['required', 'string', 'max:255'],
            'complaint_type' => ['required', Rule::enum(ComplaintType::class)],
            'statement' => ['required', 'string', 'max:10000'],
            'translation_location' => [
                'required_if:complaint_type,'.ComplaintType::InvalidLogo->value.','.ComplaintType::PoorQualityTranslation->value,
                'nullable',
                'string',
                'max:2048',
            ],
            'major_error' => [
                'required_if:complaint_type,'.ComplaintType::PoorQualityTranslation->value,
                'nullable',
                'string',
                'max:10000',
            ],
            'harm_type' => [
                'required_if:complaint_type,'.ComplaintType::PoorQualityTranslation->value,
                'nullable',
                'string',
                Rule::in([
                    'injury',
                    'equipment_damage',
                    'reputational_harm',
                    'financial_loss',
                    'other',
                ]),
            ],
            'valid_license_explanation' => ['nullable', 'string', 'max:10000'],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (
                $validator->errors()->hasAny(['complaint_type', 'license_number'])
                || $this->input('complaint_type') !== ComplaintType::InvalidLogo->value
            ) {
                return;
            }

            $resolution = app(LicenseStatusAtFilingResolver::class)
                ->resolve((string) $this->input('license_number'));

            if (
                $resolution->status === LicenseStatusAtFiling::Valid
                && blank($this->input('valid_license_explanation'))
            ) {
                $validator->errors()->add(
                    'valid_license_explanation',
                    'Explain why this should still be treated as an invalid logo complaint.',
                );
            }
        }];
    }
}
