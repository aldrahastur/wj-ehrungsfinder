<?php

namespace App\Support;

use App\Domain\Ehrungen\Answer;
use App\Domain\Ehrungen\Assessor;
use App\Domain\Ehrungen\RecipientGroup;
use App\Domain\Ehrungen\ResultState;
use App\Domain\Ehrungen\RuleBasis;
use App\Models\Question;
use App\Models\RuleVersion;

/**
 * Übersetzte Bezeichnungen für Zustände und Codes der Regel-Engine.
 */
final class Labels
{
    public static function state(ResultState|string $state): string
    {
        return match (self::enum(ResultState::class, $state)) {
            ResultState::Suitable => __('Nach deinen Angaben passend'),
            ResultState::Possible => __('Möglicherweise passend'),
            ResultState::NotSuitable => __('Aktuell nicht passend'),
            ResultState::Clarification => __('Fachliche Klärung erforderlich'),
            ResultState::Info => __('Information'),
        };
    }

    public static function stateExplanation(ResultState|string $state): string
    {
        return match (self::enum(ResultState::class, $state)) {
            ResultState::Suitable => __('Die hinterlegten Regeln sind nach deinen Angaben erfüllt. Die Entscheidung des zuständigen Gremiums steht aus.'),
            ResultState::Possible => __('Einzelne Angaben oder Nachweise fehlen. Kläre die offenen Punkte.'),
            ResultState::NotSuitable => __('Mindestens eine Pflichtbedingung ist nach deinen Angaben nicht erfüllt.'),
            ResultState::Clarification => __('Die Regelgrundlage fehlt oder widerspricht sich. Eine fachlich zuständige Stelle muss sie bestätigen.'),
            ResultState::Info => __('Für diese Ehrung gibt es noch keine automatische Eignungsprüfung.'),
        };
    }

    /**
     * Flux-Badge-Farbe je Zustand.
     */
    public static function stateColor(ResultState|string $state): string
    {
        return match (self::enum(ResultState::class, $state)) {
            ResultState::Suitable => 'green',
            ResultState::Possible => 'amber',
            ResultState::NotSuitable => 'red',
            ResultState::Clarification => 'violet',
            ResultState::Info => 'blue',
        };
    }

    public static function basis(RuleBasis|string $basis): string
    {
        return match (self::enum(RuleBasis::class, $basis)) {
            RuleBasis::Confirmed => __('Vom Verband bestätigt'),
            RuleBasis::PendingConfirmation => __('Recherchiert, Bestätigung ausstehend'),
            RuleBasis::Conflicting => __('Widersprüchliche Quellen'),
            RuleBasis::Incomplete => __('Regeln unvollständig'),
            RuleBasis::InfoOnly => __('Nur Information'),
        };
    }

    public static function reason(?string $reason, mixed $detail = null): ?string
    {
        if ($reason === null) {
            return null;
        }

        return match ($reason) {
            Assessor::REASON_NO_PUBLISHED_RULE => __('Für diese Ehrung ist noch keine Regelversion freigegeben.'),
            Assessor::REASON_PENDING_CONFIRMATION => __('Die recherchierte Regel ist noch nicht vom Verband bestätigt.'),
            Assessor::REASON_CONFLICTING => __('Die Quellen widersprechen sich. Die geltende Regel muss schriftlich bestätigt werden.'),
            Assessor::REASON_INCOMPLETE => __('Existenz und Zweck sind belegt, verbindliche Einzelregeln fehlen noch.'),
            Assessor::REASON_RECIPIENT_UNCLEAR => __('Ob :group geehrt werden können, ist nicht belegt.', ['group' => self::groupPlural((string) $detail)]),
            Assessor::REASON_PRIOR_UNCLEAR => __('Eine Regel zur erneuten Verleihung derselben Ehrung ist nicht belegt.'),
            Assessor::REASON_BLOCKED_PATH => is_array($detail) ? Localized::pick($detail) : __('Ein Zugang ist bis zur Regelbestätigung gesperrt.'),
            default => $reason,
        };
    }

    public static function level(string $level): string
    {
        return match ($level) {
            'regional' => __('Landesebene'),
            'national' => __('Bundesebene'),
            'international' => __('Internationale Ebene'),
            default => $level,
        };
    }

    public static function group(RecipientGroup|string $group): string
    {
        return match (self::enum(RecipientGroup::class, $group)) {
            RecipientGroup::Member => __('Mitglied'),
            RecipientGroup::Former => __('ehemaliges Mitglied'),
            RecipientGroup::External => __('externe unterstützende Person'),
        };
    }

    public static function groupPlural(string $group): string
    {
        return match (RecipientGroup::tryFrom($group)) {
            RecipientGroup::Member => __('Mitglieder'),
            RecipientGroup::Former => __('ehemalige Mitglieder'),
            RecipientGroup::External => __('externe Unterstützende'),
            null => $group,
        };
    }

    public static function answer(?string $answer): string
    {
        return match (Answer::tryFrom((string) $answer)) {
            Answer::Yes => __('Ja'),
            Answer::No => __('Nein'),
            Answer::Unknown => __('Weiß ich nicht'),
            null => '–',
        };
    }

    public static function truth(string $value, bool $answered = true): string
    {
        if ($value === 'U' && ! $answered) {
            return __('nicht geprüft');
        }

        return match ($value) {
            'T' => __('erfüllt'),
            'F' => __('nicht erfüllt'),
            'U' => __('offen'),
            'B' => __('Klärung nötig'),
            default => $value,
        };
    }

    public static function versionStatus(string $status): string
    {
        return match ($status) {
            RuleVersion::STATUS_DRAFT => __('Entwurf'),
            RuleVersion::STATUS_IN_REVIEW => __('Zur Freigabe'),
            RuleVersion::STATUS_PUBLISHED => __('Veröffentlicht'),
            RuleVersion::STATUS_SUPERSEDED => __('Abgelöst'),
            default => $status,
        };
    }

    public static function versionStatusColor(string $status): string
    {
        return match ($status) {
            RuleVersion::STATUS_DRAFT => 'zinc',
            RuleVersion::STATUS_IN_REVIEW => 'amber',
            RuleVersion::STATUS_PUBLISHED => 'green',
            default => 'zinc',
        };
    }

    public static function event(string $action): string
    {
        return match ($action) {
            'created' => __('Angelegt'),
            'updated' => __('Bearbeitet'),
            'submitted' => __('Zur Freigabe gegeben'),
            'approved' => __('Freigegeben und veröffentlicht'),
            'rejected' => __('Zurückgewiesen'),
            'superseded' => __('Abgelöst'),
            default => $action,
        };
    }

    public static function questionCriterion(string $code): string
    {
        static $cache = [];

        return $cache[app()->getLocale()][$code] ??= (Question::firstWhere('code', $code)?->tr('criterion') ?: $code);
    }

    public static function region(?string $code): string
    {
        return $code ? (config("ehrungsfinder.regions.{$code}") ?? $code) : '–';
    }

    /**
     * @template T of \BackedEnum
     *
     * @param  class-string<T>  $class
     * @return T
     */
    private static function enum(string $class, \BackedEnum|string $value): \BackedEnum
    {
        return $class::from($value instanceof \BackedEnum ? $value->value : $value);
    }
}
