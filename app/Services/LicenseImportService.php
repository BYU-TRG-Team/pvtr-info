<?php

namespace App\Services;

use App\Enums\ImportStatus;
use App\Models\ImportBatch;
use App\Models\LicenseRecord;
use App\Models\User;
use App\Support\ParsedLicenseRow;
use App\Support\TextNormalizer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LicenseImportService
{
    public function __construct(private readonly LicenseFileParser $parser) {}

    public function import(
        string $path,
        string $originalFilename,
        ?User $user = null,
        ?string $storedPath = null,
    ): ImportBatch {
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        $rows = $this->parser->parse($path, $extension);

        if ($rows === []) {
            throw new InvalidArgumentException('The uploaded file did not contain any license rows.');
        }

        return DB::transaction(function () use ($rows, $originalFilename, $extension, $user, $storedPath): ImportBatch {
            $batch = ImportBatch::create([
                'user_id' => $user?->id,
                'original_filename' => $originalFilename,
                'stored_path' => $storedPath,
                'file_type' => $extension,
                'status' => ImportStatus::Completed,
                'total_rows' => count($rows),
                'imported_rows' => 0,
                'skipped_rows' => 0,
            ]);

            $seenLicenseNumbers = [];
            $imported = 0;
            $skipped = 0;

            foreach ($rows as $row) {
                $record = $this->normalizeRow($row);

                if ($record === null) {
                    $skipped++;

                    continue;
                }

                LicenseRecord::updateOrCreate(
                    ['license_number' => $record['license_number']],
                    [
                        ...$record,
                        'import_batch_id' => $batch->id,
                        'is_current' => true,
                        'source_row' => [
                            'worksheet' => $row->worksheet,
                            'row_number' => $row->rowNumber,
                            'values' => $row->values,
                        ],
                    ],
                );

                $seenLicenseNumbers[] = $record['license_number'];
                $imported++;
            }

            if ($imported === 0) {
                throw new InvalidArgumentException('The uploaded file did not contain any importable license rows.');
            }

            LicenseRecord::query()
                ->whereNotIn('license_number', array_unique($seenLicenseNumbers))
                ->update(['is_current' => false]);

            $batch->update([
                'imported_rows' => $imported,
                'skipped_rows' => $skipped,
            ]);

            return $batch->refresh();
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizeRow(ParsedLicenseRow $row): ?array
    {
        $values = $row->values;
        $licenseNumber = TextNormalizer::string($values['license_number'] ?? null);
        $entityName = TextNormalizer::string($values['entity_name'] ?? null);
        $status = TextNormalizer::string($values['license_status'] ?? null);
        $expirationDate = $this->parseDate($values['expiration_date'] ?? null);

        if ($licenseNumber === '' || $entityName === '' || $status === '' || $expirationDate === null) {
            return null;
        }

        return [
            'license_number' => $licenseNumber,
            'license_prefix' => TextNormalizer::string($values['license_prefix'] ?? null) ?: null,
            'entity_name' => $entityName,
            'entity_type' => TextNormalizer::string($values['entity_type'] ?? null) ?: null,
            'license_status' => $status,
            'email' => TextNormalizer::email($values['email'] ?? null),
            'expiration_date' => $expirationDate->toDateString(),
        ];
    }

    private function parseDate(mixed $value): ?CarbonImmutable
    {
        $value = TextNormalizer::string($value);

        if ($value === '') {
            return null;
        }

        foreach (['!n/j/Y', '!m/d/Y', '!Y-m-d'] as $format) {
            try {
                $date = CarbonImmutable::createFromFormat($format, $value);
            } catch (\Throwable) {
                continue;
            }

            if ($date instanceof CarbonImmutable && $date->format(str_replace('!', '', $format)) === $value) {
                return $date;
            }
        }

        try {
            return CarbonImmutable::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
