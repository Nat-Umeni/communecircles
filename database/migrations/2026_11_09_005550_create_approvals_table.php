<?php

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
        // Approvals for new circles, requests to join and invites (App\Enums\ApprovalType): one per kind, thing and
        // person, reopened when someone asks again. Their history is in the audit log.
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();
            $table->morphs('approvable');
            // Who it's about: the circle's creator, the person asking to join, or the person invited. Always set,
            // since a unique index lets rows with an empty user_id repeat.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->index();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->unique(['type', 'approvable_type', 'approvable_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
