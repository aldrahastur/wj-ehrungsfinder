<?php

namespace Database\Seeders;

use App\Models\Honor;
use App\Models\Organisation;
use App\Models\Question;
use App\Models\ReferenceCase;
use App\Models\RuleVersion;
use App\Models\Source;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Spielt den Startkatalog aus database/data/catalog.php ein.
 *
 * Idempotent: bestehende Einträge werden anhand ihres Codes aktualisiert,
 * vorhandene Regelversionen bleiben unangetastet.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = require database_path('data/catalog.php');

        DB::transaction(function () use ($catalog) {
            $orgs = [];
            foreach ($catalog['organisations'] as $org) {
                $orgs[$org['code']] = Organisation::updateOrCreate(['code' => $org['code']], [
                    'name' => $org['name'],
                    'type' => $org['type'],
                    'parent_id' => $org['parent'] ? $orgs[$org['parent']]->id : null,
                    'country' => $org['country'],
                    'region' => $org['region'],
                ]);
            }

            $sources = [];
            foreach ($catalog['sources'] as $source) {
                $sources[$source['code']] = Source::updateOrCreate(['code' => $source['code']], [
                    'title' => $source['title'],
                    'url' => $source['url'],
                    'note' => $source['note'],
                    'retrieved_at' => $catalog['retrieved_at'],
                ]);
            }

            foreach ($catalog['questions'] as $i => $q) {
                Question::updateOrCreate(['code' => $q['code']], [
                    'text' => $q['text'],
                    'criterion' => $q['criterion'],
                    'explanation' => $q['explanation'] ?? null,
                    'region' => $q['region'] ?? null,
                    'implied_by' => $q['implied_by'] ?? null,
                    'sort' => ($i + 1) * 10,
                ]);
            }

            foreach ($catalog['honors'] as $i => $h) {
                $honor = Honor::updateOrCreate(['code' => $h['code']], [
                    'organisation_id' => $orgs[$h['organisation']]->id,
                    'level' => $h['level'],
                    'region' => $h['region'],
                    'title' => $h['title'],
                    'scope' => $h['scope'],
                    'sort' => ($i + 1) * 10,
                ]);

                $honor->procedure()->updateOrCreate([], $h['procedure']);

                if ($honor->ruleVersions()->exists()) {
                    continue;
                }

                $version = $honor->ruleVersions()->create([
                    'version' => 1,
                    'status' => RuleVersion::STATUS_PUBLISHED,
                    'basis' => $h['rule']['basis'],
                    'basis_note' => $h['rule']['basis_note'],
                    'criteria' => $h['rule']['criteria'],
                    'valid_from' => $h['rule']['valid_from'],
                    'change_note' => 'Startkatalog aus dem Projektplan (Recherche vom '.$catalog['retrieved_at'].').',
                    'approval_role' => 'Projektteam – Startkatalog, fachliche Bestätigung ausstehend',
                    'published_at' => now(),
                ]);

                $version->sources()->sync(array_map(fn ($code) => $sources[$code]->id, $h['rule']['sources']));
                $version->events()->create(['action' => 'created', 'note' => 'Startkatalog aus dem Projektplan eingespielt.']);
            }

            if (ReferenceCase::query()->doesntExist()) {
                foreach ($catalog['reference_cases'] as $i => $case) {
                    ReferenceCase::create([
                        'name' => $case['name'],
                        'region' => $case['region'],
                        'recipient_group' => $case['group'],
                        'answers' => $case['answers'],
                        'expected' => $case['expected'],
                        'show_as_example' => $i < 3,
                    ]);
                }
            }
        });
    }
}
