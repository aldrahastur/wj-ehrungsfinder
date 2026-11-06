# WJ Ehrungsfinder

Frageassistent für persönliche Ehrungen der Wirtschaftsjunioren Deutschland, des Landesverbands Sachsen-Anhalt und von JCI. Mit einfachen Ja-/Nein-Fragen führt er Kreisvorstände und weitere Vorschlagende zu begründeten Empfehlungen mit Antragshinweisen.

Die Empfehlung bereitet eine Entscheidung vor. Über die Verleihung entscheiden die zuständigen Verbandsgremien.

## Technik

- Laravel 13, Livewire 4, Flux UI, Tailwind CSS 4 (Basis: offizielles Laravel Livewire Starter Kit)
- Anmeldung für den Verwaltungsbereich über Laravel Fortify (ohne öffentliche Registrierung)
- Deterministische Regel-Engine ohne KI, framework-unabhängig in `app/Domain/Ehrungen`
- SQLite für Entwicklung und Pilot, MySQL/PostgreSQL für den Betrieb möglich

## Schnellstart

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed          # Startkatalog + lokaler Admin admin@example.com / password
npm install && npm run build
php artisan serve
```

Frageassistent: <http://localhost:8000> · Verwaltung: <http://localhost:8000/verwaltung>

Zugänge für die Verwaltung anlegen (eine öffentliche Registrierung gibt es nicht):

```bash
php artisan ehrungsfinder:user vorname@example.org --name="Vorname Nachname" --role=wjd:approver
php artisan ehrungsfinder:user admin@example.org --admin
```

Rollen gelten je Regelpaket (Organisation): `editor` (Redaktion) bearbeitet Entwürfe, `approver` (Freigabe) gibt sie frei. Admins verwalten Fragen, Referenzfälle und Personen.

Tests: `php artisan test`

## Fachliche Logik

### Ergebniszustände

| Zustand | Bedeutung |
|---|---|
| Nach deinen Angaben passend | Regeln nach Angaben erfüllt, Entscheidung des Gremiums steht aus |
| Möglicherweise passend | Angaben oder Nachweise fehlen („Weiß ich nicht“) |
| Aktuell nicht passend | Konkrete Pflichtbedingung nicht erfüllt |
| Fachliche Klärung erforderlich | Regelgrundlage fehlt, ist gesperrt oder widersprüchlich |
| Information | Nur Informationskarte (TOYP), keine automatische Prüfung |

Jede Ehrung wird unabhängig geprüft. Es gibt keine Rangfolge zwischen Landesnadel, Bundesnadel und Senatorenwürde.

### Auswertung

Kriterien werden als JSON-Baum je Regelversion gespeichert:

```json
{"all": [
  {"recipient": {"member": "T", "former": "B", "external": "B"}},
  {"any": [{"question": "q_kreisprojekt"}, {"question": "q_training"}], "label": {"de": "Einer dieser Zugänge genügt"}},
  {"prior": "q_hat_silber", "mode": "unclear"}
]}
```

| Knoten | Wirkung |
|---|---|
| `all` / `any` / `not` | UND / ODER / NICHT |
| `question` | Ja = erfüllt, Nein = nicht erfüllt, „Weiß ich nicht“ = offen |
| `recipient` | Wer geehrt werden kann: `T` ja, `F` nein, `B` nicht belegt (→ Klärung) |
| `prior` | Vorehrung derselben Ehrung: `exclude` schließt aus, `unclear` führt zur Klärung |
| `blocked` | Gesperrter Pfad, z. B. bis zur Regelbestätigung |

Dreiwertige Logik mit zusätzlichem Klärungszustand: Bei UND schließt ein sicher nicht erfülltes Pflichtkriterium aus, sonst halten Klärung oder Unbekannt das Ergebnis offen. Bei ODER genügt ein sicher erfüllter Zugang. Unbekannt wird nie zu Nein.

Der Assistent stellt nur Fragen, die das Ergebnis noch verändern können. „Kein internationales Engagement“ beendet so den Senatorenpfad, ohne den alternativen regionalen Gold-Pfad auszuschließen. Ableitungen (z. B. „unter 2 Jahren“ ⇒ „nicht 5 Jahre“) werden je Frage in `implied_by` hinterlegt.

### Regelgrundlage einer Regelversion

| Grundlage | Auswertung |
|---|---|
| Vom Verband bestätigt | normal |
| Recherchiert, Bestätigung ausstehend | im Pilotbetrieb normal und gekennzeichnet, sonst Klärung |
| Widersprüchliche Quellen | immer Klärung (z. B. Goldene Juniorennadel: Richtlinie 2023 vs. Handbuch 2026) |
| Regeln unvollständig | Klärung; nur ein sicher nicht erfüllter Zweck führt zu „nicht passend“ |
| Nur Information | Informationskarte |

Pilotbetrieb: `EHRUNGSFINDER_PILOT_MODE=true`.

### Freigabeprozess und Versionen

1. Redaktion legt aus der veröffentlichten Version einen **Entwurf** an und bearbeitet Kriterien, Regelgrundlage, Gültigkeit und Quellen.
2. **Zur Freigabe geben**: Kriterien müssen gültig sein, mindestens eine Quelle ist Pflicht.
3. Eine Person mit Freigaberolle **gibt frei** oder **weist mit Begründung zurück**. Vier-Augen-Prinzip: Wer einreicht, gibt nicht selbst frei.
4. Bei der Freigabe laufen alle **Referenzfälle** gegen die neue Version. Weicht ein Fall ab, wird die Freigabe blockiert.
5. Die freigegebene Version wird veröffentlicht, die bisherige als „abgelöst“ archiviert. Jeder Schritt steht im Verlauf.

Reaktionen oder Schweigen gelten nicht als Freigabe. Gespeicherte Ergebnisse verweisen auf die Regelversion, mit der sie berechnet wurden.

## Startkatalog

`database/data/catalog.php` enthält Organisationen, Quellen (Q1–Q4, Q8), 18 Fragen, 5 Ehrungen und 5 anonymisierte Referenzfälle aus dem Projektplan (Recherche 5. November 2026). Der Seeder ist idempotent und lässt vorhandene Regelversionen unangetastet.

**Alle Regeln sind recherchiert und noch nicht fachlich bestätigt.** Die erste Version jeder Ehrung ist als „Projektteam – Startkatalog, fachliche Bestätigung ausstehend“ veröffentlicht, damit der Pilot laufen kann.

Offene Annahmen zur fachlichen Prüfung:

- Zusätzliche Frage zu Engagement in Arbeitskreis/Vorstand (Gold-Zugang laut Handbuch 2026)
- Senatorenwürde: Externe Unterstützende „nicht passend“ (fünf Mitgliedsjahre gefordert); wer schon Senator*in ist, wird ausgeschlossen
- Ableitung „unter 2 Mitgliedsjahren“ ⇒ „nicht 5 Mitgliedsjahre“
- Ehemalige Mitglieder und Externe bei den Nadeln: „Klärung erforderlich“ (nicht belegt)
- Erläuterungstexte, Beispiele und Formulierungen der nächsten Antragsschritte

## Datenschutz

- Vorprüfungen sind anonym: keine Namen, kein Geburtsdatum, keine IP-Adresse
- Ergebnisse werden nach `EHRUNGSFINDER_RETENTION_DAYS` Tagen gelöscht (`php artisan ehrungsfinder:prune`, täglich per Scheduler)
- Keine automatische Benachrichtigung vorgeschlagener Personen, keine Einreichung über das Tool
- Rückmeldungen sollen keine Kandidatendaten enthalten (Hinweis im Formular)

Für den Scheduler im Betrieb: `* * * * * php /pfad/artisan schedule:run`.

## Neue Landesverbände und Länder

- Landesehrung: Organisation (Typ `regional`, Region) anlegen, Ehrung mit `region` anlegen, Regelversion pflegen und freigeben. Landesbezogene Fragen erhalten ebenfalls eine `region`.
- Weiteres Land: Eintrag unter `countries` in `config/ehrungsfinder.php`, eigenes Regelpaket mit Freigaberolle, geprüfte Übersetzung und eigene Referenzfälle. Die Auswertungslogik bleibt unverändert.

## Verzeichnisse

| Pfad | Inhalt |
|---|---|
| `app/Domain/Ehrungen` | Regel-Engine (Wahrheitswerte, Auswertung, Fragenfluss, Validierung) |
| `app/Services/Ehrungsfinder` | Katalog, Auswertung, Referenzfälle, Freigabeprozess |
| `resources/views/pages/finder` | Öffentlicher Frageassistent |
| `resources/views/pages/admin` | Verwaltungsbereich |
| `database/data/catalog.php` | Startkatalog |
| `tests/Unit/Ehrungen` | Tests der Regel-Engine inkl. Referenzfälle |
| `tests/Feature/Ehrungsfinder` | Assistent, Freigabeprozess, Zugriffsrechte |
