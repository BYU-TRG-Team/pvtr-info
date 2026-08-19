<?php

use App\Enums\ComplaintStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_record_id')->nullable()->constrained()->nullOnDelete();
            $table->string('public_reference')->unique();
            $table->string('secret_link_key', 64)->unique();
            $table->string('complainant_name');
            $table->string('complainant_email');
            $table->string('complainant_phone')->nullable();
            $table->string('license_number');
            $table->string('license_status_at_filing', 32);
            $table->string('complaint_type', 64);
            $table->string('status', 64)->default(ComplaintStatus::UnderReview->value);
            $table->json('details')->nullable();
            $table->timestamp('filed_at');
            $table->timestamps();

            $table->index(['status', 'filed_at']);
            $table->index('license_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
