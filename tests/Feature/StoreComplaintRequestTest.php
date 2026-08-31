<?php

namespace Tests\Feature;

use App\Enums\ComplaintType;
use App\Http\Requests\StoreComplaintRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreComplaintRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_requires_the_base_complaint_fields(): void
    {
        foreach ([
            'complainant_name',
            'complainant_email',
            'license_number',
            'complaint_type',
            'statement',
        ] as $field) {
            $data = $this->validData();
            unset($data[$field]);

            $validator = Validator::make($data, (new StoreComplaintRequest)->rules());

            $this->assertTrue($validator->errors()->has($field), "{$field} should be required.");
        }
    }

    public function test_it_accepts_a_valid_base_complaint_payload(): void
    {
        $validator = Validator::make(
            $this->validData(),
            (new StoreComplaintRequest)->rules(),
        );

        $this->assertFalse($validator->fails());
    }

    public function test_invalid_logo_complaint_requires_translation_location_but_not_poor_quality_fields(): void
    {
        $data = $this->validData();
        unset(
            $data['translation_location'],
            $data['major_error'],
            $data['harm_type'],
        );

        $validator = Validator::make($data, (new StoreComplaintRequest)->rules());

        $this->assertTrue($validator->errors()->has('translation_location'));
        $this->assertFalse($validator->errors()->has('major_error'));
        $this->assertFalse($validator->errors()->has('harm_type'));

        $data['translation_location'] = 'https://example.com/invalid-logo';

        $validator = Validator::make($data, (new StoreComplaintRequest)->rules());

        $this->assertFalse($validator->fails());

        unset($data['statement']);

        $validator = Validator::make($data, (new StoreComplaintRequest)->rules());

        $this->assertTrue($validator->errors()->has('statement'));
        $this->assertFalse($validator->errors()->has('translation_location'));
        $this->assertFalse($validator->errors()->has('major_error'));
        $this->assertFalse($validator->errors()->has('harm_type'));
    }

    public function test_poor_quality_complaint_requires_its_conditional_fields(): void
    {
        foreach ([
            'translation_location',
            'major_error',
            'harm_type',
        ] as $field) {
            $data = $this->poorQualityData();
            unset($data[$field]);

            $validator = Validator::make($data, (new StoreComplaintRequest)->rules());

            $this->assertTrue($validator->errors()->has($field), "{$field} should be required.");
        }
    }

    public function test_it_accepts_a_complete_poor_quality_complaint(): void
    {
        $validator = Validator::make(
            $this->poorQualityData(),
            (new StoreComplaintRequest)->rules(),
        );

        $this->assertFalse($validator->fails());
    }

    public function test_poor_quality_complaint_requires_a_supported_harm_type(): void
    {
        $data = $this->poorQualityData();
        $data['harm_type'] = 'unsupported';

        $validator = Validator::make($data, (new StoreComplaintRequest)->rules());

        $this->assertTrue($validator->errors()->has('harm_type'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validData(): array
    {
        return [
            'complainant_name' => 'Example Reporter',
            'complainant_email' => 'reporter@example.com',
            'complainant_phone' => '555-0100',
            'license_number' => '100-001',
            'complaint_type' => ComplaintType::InvalidLogo->value,
            'translation_location' => 'https://example.com/logo-usage',
            'statement' => 'The logo attached to this translation appears to be invalid.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function poorQualityData(): array
    {
        return [
            ...$this->validData(),
            'complaint_type' => ComplaintType::PoorQualityTranslation->value,
            'statement' => 'The translation contains a serious quality problem.',
            'translation_location' => 'https://example.com/translation',
            'major_error' => 'A dosage was translated with the wrong unit.',
            'harm_type' => 'injury',
        ];
    }
}
