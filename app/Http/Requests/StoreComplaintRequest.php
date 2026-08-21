<?php

namespace App\Http\Requests;

use App\Enums\ComplaintType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
                'required_if:complaint_type,'.ComplaintType::PoorQualityTranslation->value,
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
        ];
    }
}
