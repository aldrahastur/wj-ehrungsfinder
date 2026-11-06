<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Anonyme Vorprüfungen: keine Namen, kein Geburtsdatum, keine IP-Adresse.
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('country', 2)->default('DE');
            $table->string('region', 8)->nullable();
            $table->string('recipient_group');
            $table->string('locale', 5);
            $table->json('answers');
            $table->json('results'); // je Ehrung: Regelversion, Zustand, erfüllte/offene Kriterien
            $table->boolean('completed')->default(false);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamps();
        });

        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message');
            $table->string('status')->default('open'); // open | done
            $table->text('internal_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
        Schema::dropIfExists('assessments');
    }
};
