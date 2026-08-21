<?php

namespace App\Services;

use App\Enums\ComplaintMessageAuthorType;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use Illuminate\Support\Facades\DB;

class ComplaintFilingService
{
    public function __construct(
        private readonly LicenseStatusAtFilingResolver $licenseStatusResolver,
        private readonly ComplaintReferenceGenerator $referenceGenerator,
        private readonly ComplaintSecretLinkGenerator $secretLinkGenerator,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function file(array $attributes): Complaint
    {
        return DB::transaction(function () use ($attributes): Complaint {
            $resolution = $this->licenseStatusResolver->resolve(
                (string) $attributes['license_number'],
            );

            $complaint = Complaint::create([
                'license_record_id' => $resolution->license?->id,
                'public_reference' => $this->referenceGenerator->generate(),
                'secret_link_key' => $this->secretLinkGenerator->generate(),
                'complainant_name' => $attributes['complainant_name'],
                'complainant_email' => $attributes['complainant_email'],
                'complainant_phone' => $attributes['complainant_phone'] ?? null,
                'license_number' => $resolution->license?->license_number
                    ?? trim((string) $attributes['license_number']),
                'license_status_at_filing' => $resolution->status,
                'complaint_type' => $attributes['complaint_type'],
                'status' => ComplaintStatus::UnderReview,
                'details' => $this->detailsFrom($attributes),
                'filed_at' => now(),
            ]);

            $complaint->messages()->create([
                'user_id' => null,
                'author_type' => ComplaintMessageAuthorType::Complainant,
                'body' => $attributes['statement'],
            ]);

            return $complaint->load('messages');
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function detailsFrom(array $attributes): array
    {
        return array_filter([
            'translation_location' => $attributes['translation_location'] ?? null,
            'major_error' => $attributes['major_error'] ?? null,
            'harm_type' => $attributes['harm_type'] ?? null,
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
