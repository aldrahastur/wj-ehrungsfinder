<?php

return [

    /*
     * Pilotbetrieb: Regelversionen mit Grundlage „Bestätigung ausstehend“ werden
     * ausgewertet und deutlich gekennzeichnet. Ausgeschaltet führen sie zu
     * „Fachliche Klärung erforderlich“.
     */
    'pilot_mode' => (bool) env('EHRUNGSFINDER_PILOT_MODE', true),

    /*
     * Vier-Augen-Prinzip: Wer eine Regelversion zur Freigabe gibt, kann sie nicht selbst freigeben.
     */
    'four_eyes' => (bool) env('EHRUNGSFINDER_FOUR_EYES', true),

    /*
     * Freigabe nur, wenn alle Referenzfälle mit der neuen Regelversion das erwartete Ergebnis liefern.
     */
    'require_passing_reference_cases' => (bool) env('EHRUNGSFINDER_REQUIRE_REFERENCE_CASES', true),

    /*
     * Anonyme Vorprüfungen werden nach dieser Zahl von Tagen gelöscht (php artisan ehrungsfinder:prune).
     */
    'assessment_retention_days' => (int) env('EHRUNGSFINDER_RETENTION_DAYS', 180),

    'locales' => ['de', 'en'],

    /*
     * Länder bzw. Nationalverbände, deren Regelpakete freigeschaltet sind.
     */
    'countries' => [
        'DE' => ['name' => ['de' => 'Deutschland · Wirtschaftsjunioren Deutschland', 'en' => 'Germany · Wirtschaftsjunioren Deutschland'], 'organisation' => 'wjd'],
    ],

    'regions' => [
        'BW' => 'Baden-Württemberg', 'BY' => 'Bayern', 'BE' => 'Berlin', 'BB' => 'Brandenburg',
        'HB' => 'Bremen', 'HH' => 'Hamburg', 'HE' => 'Hessen', 'MV' => 'Mecklenburg-Vorpommern',
        'NI' => 'Niedersachsen', 'NW' => 'Nordrhein-Westfalen', 'RP' => 'Rheinland-Pfalz', 'SL' => 'Saarland',
        'SN' => 'Sachsen', 'ST' => 'Sachsen-Anhalt', 'SH' => 'Schleswig-Holstein', 'TH' => 'Thüringen',
    ],

    'default_region' => env('EHRUNGSFINDER_DEFAULT_REGION', 'ST'),

    /*
     * Kontaktweg, wenn keine passende Ehrung gefunden wurde.
     */
    'contact' => [
        'name' => env('EHRUNGSFINDER_CONTACT_NAME', 'Landesgeschäftsstelle der Wirtschaftsjunioren'),
        'email' => env('EHRUNGSFINDER_CONTACT_EMAIL'),
    ],
];
