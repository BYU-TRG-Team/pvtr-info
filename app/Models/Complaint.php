<?php

namespace App\Models;

use App\Enums\ComplaintStatus;
use App\Enums\ComplaintType;
use Database\Factories\ComplaintFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'license_record_id',
    'public_reference',
    'secret_link_key',
    'complainant_name',
    'complainant_email',
    'complainant_phone',
    'license_number',
    'license_status_at_filing',
    'complaint_type',
    'status',
    'details',
    'filed_at',
])]
class Complaint extends Model
{
    /** @use HasFactory<ComplaintFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<LicenseRecord, $this>
     */
    public function licenseRecord(): BelongsTo
    {
        return $this->belongsTo(LicenseRecord::class);
    }

    /**
     * @return HasMany<ComplaintMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ComplaintMessage::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'complaint_type' => ComplaintType::class,
            'status' => ComplaintStatus::class,
            'details' => 'array',
            'filed_at' => 'datetime',
        ];
    }
}
