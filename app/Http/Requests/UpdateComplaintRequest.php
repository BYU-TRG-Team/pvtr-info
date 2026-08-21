<?php

namespace App\Http\Requests;

use App\Enums\ComplaintType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'complaint_type' => ['required', Rule::enum(ComplaintType::class)],
            'harm_type' => [
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
