<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->json('name');
            $table->string('type'); // international | national | regional
            $table->foreignId('parent_id')->nullable()->constrained('organisations')->nullOnDelete();
            $table->string('country', 2)->nullable();
            $table->string('region', 8)->nullable();
            $table->timestamps();
        });

        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('title');
            $table->string('url', 1000)->nullable();
            $table->json('note')->nullable();
            $table->date('retrieved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // stabile ID, wird in Regeln referenziert
            $table->string('answer_type')->default('yes_no_unknown');
            $table->json('text');
            $table->json('criterion');
            $table->json('explanation')->nullable();
            $table->string('region', 8)->nullable();
            $table->json('implied_by')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('honors', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('organisation_id')->constrained()->restrictOnDelete();
            $table->string('level'); // regional | national | international
            $table->string('region', 8)->nullable();
            $table->string('recipient_type')->default('person');
            $table->json('title');
            $table->json('scope')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('procedures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('honor_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('next_step')->nullable();
            $table->json('nomination_right')->nullable();
            $table->json('decision_body')->nullable();
            $table->json('application')->nullable();
            $table->json('deadlines')->nullable();
            $table->json('lead_time')->nullable();
            $table->json('evidence')->nullable();
            $table->decimal('fee_amount', 10, 2)->nullable();
            $table->string('fee_currency', 3)->nullable();
            $table->string('application_url', 1000)->nullable();
            $table->timestamps();
        });

        Schema::create('rule_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('honor_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('status')->default('draft'); // draft | in_review | published | superseded
            $table->string('basis'); // siehe App\Domain\Ehrungen\RuleBasis
            $table->json('basis_note')->nullable();
            $table->json('criteria')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->text('change_note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('approval_role')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['honor_id', 'version']);
            $table->index(['honor_id', 'status']);
        });

        Schema::create('rule_version_source', function (Blueprint $table) {
            $table->foreignId('rule_version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_id')->constrained()->cascadeOnDelete();
            $table->string('locator')->nullable(); // z. B. Seitenangabe
            $table->primary(['rule_version_id', 'source_id']);
        });

        Schema::create('rule_version_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); // created | updated | submitted | approved | rejected | superseded
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('reference_cases', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('region', 8)->nullable();
            $table->string('recipient_group');
            $table->json('answers');
            $table->json('expected');
            $table->boolean('show_as_example')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['reference_cases', 'rule_version_events', 'rule_version_source', 'rule_versions', 'procedures', 'honors', 'questions', 'sources', 'organisations'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
