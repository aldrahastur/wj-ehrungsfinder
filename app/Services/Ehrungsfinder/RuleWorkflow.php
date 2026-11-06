<?php

namespace App\Services\Ehrungsfinder;

use App\Domain\Ehrungen\Evaluator;
use App\Domain\Ehrungen\ExpressionValidator;
use App\Domain\Ehrungen\RuleBasis;
use App\Models\Honor;
use App\Models\Question;
use App\Models\RuleVersion;
use App\Models\Source;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Freigabeprozess für Regelversionen: Entwurf → zur Freigabe → veröffentlicht.
 *
 * Reaktionen oder Schweigen gelten nicht als Freigabe: Veröffentlicht wird nur
 * durch eine ausdrückliche Freigabe einer berechtigten Rolle.
 */
class RuleWorkflow
{
    public function __construct(
        private readonly ReferenceCaseRunner $runner,
        private readonly ExpressionValidator $validator = new ExpressionValidator,
    ) {}

    public function createDraft(Honor $honor, User $user, ?RuleVersion $from = null): RuleVersion
    {
        $from ??= $honor->publishedRule ?? $honor->ruleVersions()->first();

        return DB::transaction(function () use ($honor, $user, $from) {
            $draft = $honor->ruleVersions()->create([
                'version' => $honor->nextVersionNumber(),
                'status' => RuleVersion::STATUS_DRAFT,
                'basis' => $from->basis ?? RuleBasis::PendingConfirmation,
                'basis_note' => $from?->basis_note,
                'criteria' => $from?->criteria,
                'valid_from' => $from?->valid_from,
                'valid_until' => $from?->valid_until,
                'created_by' => $user->id,
            ]);

            if ($from) {
                $draft->sources()->sync($from->sources->mapWithKeys(fn (Source $s) => [$s->id => ['locator' => $s->pivot->locator]])->all());
            }

            $this->log($draft, $user, 'created', $from ? "Kopie von {$from->label()}" : null);

            return $draft;
        });
    }

    /**
     * @param  array<string, mixed>|null  $criteria
     * @return list<string>
     */
    public function validationErrors(?array $criteria, RuleBasis $basis): array
    {
        if ($basis === RuleBasis::InfoOnly) {
            return [];
        }

        if ($criteria === null) {
            return ['Kriterien fehlen.'];
        }

        $known = array_values(Question::query()->get(['code'])->map(fn (Question $q): string => $q->code)->all());

        return $this->validator->validate($criteria, $known);
    }

    public function submit(RuleVersion $version, User $user, ?string $note = null): void
    {
        $this->assertStatus($version, RuleVersion::STATUS_DRAFT);
        $this->assertValid($version);

        if ($version->sources()->doesntExist()) {
            throw ValidationException::withMessages(['sources' => 'Jede Regelversion braucht mindestens eine datierte Quelle.']);
        }

        $version->update(['status' => RuleVersion::STATUS_IN_REVIEW, 'submitted_by' => $user->id, 'submitted_at' => now()]);
        $this->log($version, $user, 'submitted', $note);
    }

    public function reject(RuleVersion $version, User $user, string $note): void
    {
        $this->assertStatus($version, RuleVersion::STATUS_IN_REVIEW);
        $this->assertMayApprove($version, $user, checkFourEyes: false);

        $version->update(['status' => RuleVersion::STATUS_DRAFT, 'submitted_by' => null, 'submitted_at' => null]);
        $this->log($version, $user, 'rejected', $note);
    }

    public function approve(RuleVersion $version, User $user, string $approvalRole, ?string $note = null): void
    {
        $this->assertStatus($version, RuleVersion::STATUS_IN_REVIEW);
        $this->assertMayApprove($version, $user, checkFourEyes: true);
        $this->assertValid($version);

        if (config('ehrungsfinder.require_passing_reference_cases')) {
            $failed = $this->runner->run([$version->honor_id => $version])->where('passed', false);

            if ($failed->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'reference_cases' => 'Folgende Referenzfälle liefern nicht das erwartete Ergebnis: '.$failed->pluck('case.name')->implode(', ').'. Passe die Regel oder die erwarteten Ergebnisse an.',
                ]);
            }
        }

        DB::transaction(function () use ($version, $user, $approvalRole, $note) {
            RuleVersion::query()
                ->where('honor_id', $version->honor_id)
                ->where('status', RuleVersion::STATUS_PUBLISHED)
                ->get()
                ->each(function (RuleVersion $old) use ($user, $version) {
                    $old->update(['status' => RuleVersion::STATUS_SUPERSEDED]);
                    $this->log($old, $user, 'superseded', "Ersetzt durch {$version->label()}");
                });

            $version->update([
                'status' => RuleVersion::STATUS_PUBLISHED,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'approval_role' => $approvalRole,
                'published_at' => now(),
            ]);

            $this->log($version, $user, 'approved', $note);
        });
    }

    public function log(RuleVersion $version, ?User $user, string $action, ?string $note = null): void
    {
        $version->events()->create(['user_id' => $user?->id, 'action' => $action, 'note' => $note]);
    }

    private function assertStatus(RuleVersion $version, string $status): void
    {
        if ($version->status !== $status) {
            throw ValidationException::withMessages(['status' => 'Diese Aktion ist im aktuellen Status nicht möglich.']);
        }
    }

    private function assertValid(RuleVersion $version): void
    {
        $errors = $this->validationErrors($version->criteria, $version->basis);

        if ($errors !== []) {
            throw ValidationException::withMessages(['criteria' => $errors]);
        }
    }

    private function assertMayApprove(RuleVersion $version, User $user, bool $checkFourEyes): void
    {
        if (! $user->canApproveHonor($version->honor)) {
            throw ValidationException::withMessages(['approval' => 'Dir fehlt die Freigaberolle für dieses Regelpaket.']);
        }

        if ($checkFourEyes && config('ehrungsfinder.four_eyes') && $version->submitted_by === $user->id) {
            throw ValidationException::withMessages(['approval' => 'Vier-Augen-Prinzip: Eine andere Person muss die Version freigeben.']);
        }
    }

    /**
     * @return list<string>
     */
    public static function usedQuestions(RuleVersion $version): array
    {
        return $version->criteria ? Evaluator::questionCodes($version->criteria) : [];
    }
}
