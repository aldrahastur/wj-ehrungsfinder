<?php

/**
 * Startkatalog des WJ Ehrungsfinders.
 *
 * Inhalt aus dem Projektplan „WJ Ehrungsfinder Projektteam“, Stand 5. November 2026.
 * Alle Regeln sind recherchiert und noch nicht fachlich bestätigt. Fehlende Regeln
 * werden nicht ergänzt oder aus Verleihungsberichten abgeleitet.
 *
 * Wird vom CatalogSeeder und von den Unit-Tests der Regel-Engine verwendet.
 */

return [
    'retrieved_at' => '2026-11-05',

    'organisations' => [
        ['code' => 'jci', 'type' => 'international', 'parent' => null, 'country' => null, 'region' => null,
            'name' => ['de' => 'JCI – Junior Chamber International', 'en' => 'JCI – Junior Chamber International']],
        ['code' => 'wjd', 'type' => 'national', 'parent' => 'jci', 'country' => 'DE', 'region' => null,
            'name' => ['de' => 'Wirtschaftsjunioren Deutschland (WJD)', 'en' => 'Wirtschaftsjunioren Deutschland (WJD)']],
        ['code' => 'wj-st', 'type' => 'regional', 'parent' => 'wjd', 'country' => 'DE', 'region' => 'ST',
            'name' => ['de' => 'Wirtschaftsjunioren Sachsen-Anhalt', 'en' => 'Wirtschaftsjunioren Saxony-Anhalt']],
    ],

    'sources' => [
        ['code' => 'Q1', 'title' => 'Offizielles WJD-Glossar', 'url' => 'https://wjd.de/glossar/',
            'note' => ['de' => 'Persönliche Juniorennadeln', 'en' => 'Personal Junior pins']],
        ['code' => 'Q2', 'title' => 'Richtlinie Juniorennadel Silber und Gold, 8. Juli 2023',
            'url' => 'https://wjd.de/wp-content/uploads/sites/2/2023/07/Richtlinie_Goldene-und-silberne-Juniorennadel_08.07.2023.pdf',
            'note' => ['de' => 'Formelle öffentlich verfügbare Richtlinie; Abweichung zum Handbuch prüfen', 'en' => 'Formal public guideline; check deviation from handbook']],
        ['code' => 'Q3', 'title' => 'WJD-Kreissprecherhandbuch 2026',
            'url' => 'https://wjd.de/wp-content/uploads/sites/2/2026/01/WJD_KSP-Handbuch_2026_WEB.pdf',
            'note' => ['de' => 'Abschnitt V/VI, gedruckte Seiten 42–47', 'en' => 'Section V/VI, printed pages 42–47']],
        ['code' => 'Q4', 'title' => 'Landesnewsletter Sachsen-Anhalt, November 2024',
            'url' => 'https://www.verbandonline.org/WJ_SachsenAnhalt/?action=members_newsletter&cmd=html&dialog=1&id=32231',
            'note' => ['de' => 'Goldene Ehrennadel Sachsen-Anhalt und deren Zweck', 'en' => 'Gold pin of Saxony-Anhalt and its purpose']],
        ['code' => 'Q8', 'title' => 'JCI Senate', 'url' => 'https://jci.cc/jci-senate/',
            'note' => ['de' => 'Einordnung der internationalen Senatorenwürde', 'en' => 'Context of the international Senatorship']],
    ],

    'questions' => [
        ['code' => 'q_kreisprojekt',
            'text' => ['de' => 'Hat sich die Person bei einem Kreisprojekt außergewöhnlich eingesetzt?', 'en' => 'Has the person shown exceptional commitment to a chapter project?'],
            'criterion' => ['de' => 'Außergewöhnlicher Einsatz bei einem Kreisprojekt', 'en' => 'Exceptional commitment to a chapter project'],
            'explanation' => ['de' => 'Zum Beispiel: ein Projekt verantwortlich geleitet, eine Veranstaltungsreihe aufgebaut oder deutlich mehr Zeit eingebracht als üblich. Deine Antwort gilt als Einschätzung und wird später mit Nachweisen geprüft.', 'en' => 'For example: led a project, built an event series, or contributed far more time than usual. Your answer counts as your assessment and will be checked against evidence later.']],
        ['code' => 'q_training',
            'text' => ['de' => 'Hat sich die Person besonders bei Training oder Mitgliederentwicklung im Kreis engagiert?', 'en' => 'Has the person been particularly engaged in training or member development in the chapter?'],
            'criterion' => ['de' => 'Besonderes Engagement bei Training oder Mitgliederentwicklung', 'en' => 'Special engagement in training or member development'],
            'explanation' => ['de' => 'Zum Beispiel: Trainings gegeben oder organisiert, neue Mitglieder gewonnen und begleitet, ein Patenprogramm betreut.', 'en' => 'For example: delivered or organised trainings, recruited and onboarded new members, ran a mentoring programme.']],
        ['code' => 'q_ak_vorstand',
            'text' => ['de' => 'Hat sich die Person besonders in einem Arbeitskreis oder Vorstand engagiert?', 'en' => 'Has the person been particularly engaged in a working group or board?'],
            'criterion' => ['de' => 'Besonderes Engagement in Arbeitskreis oder Vorstand', 'en' => 'Special engagement in a working group or board'],
            'explanation' => ['de' => 'Zum Beispiel: Leitung eines Arbeitskreises oder ein Vorstandsamt im Kreis, Land oder Bund.', 'en' => 'For example: chairing a working group or a board role at chapter, state or national level.']],
        ['code' => 'q_ueberregional',
            'text' => ['de' => 'Wirkte das Engagement über den eigenen Kreis hinaus?', 'en' => 'Did the engagement have an impact beyond the person’s own chapter?'],
            'criterion' => ['de' => 'Überregionale Wirkung des Engagements', 'en' => 'Impact beyond the own chapter'],
            'explanation' => ['de' => '„Überregional“ heißt hier: Die Wirkung reicht über den eigenen Kreis hinaus, z. B. Mitarbeit im Landes- oder Bundesvorstand, in einem Bundesarbeitskreis oder bei der Ausrichtung einer Landes- oder Bundeskonferenz.', 'en' => '“Beyond the chapter” means for example work on the state or national board, in a national working group, or hosting a state or national conference.']],
        ['code' => 'q_lsa', 'region' => 'ST',
            'text' => ['de' => 'Hat sich die Person herausragend für Sachsen-Anhalt engagiert, über Kreisgrenzen hinweg?', 'en' => 'Has the person shown outstanding commitment to Saxony-Anhalt across chapter boundaries?'],
            'criterion' => ['de' => 'Herausragender Einsatz auf Landesebene Sachsen-Anhalt', 'en' => 'Outstanding commitment at Saxony-Anhalt state level'],
            'explanation' => ['de' => 'Zum Beispiel: Landesvorstand, landesweite Projekte oder Veranstaltungen. Eine Mindestdauer ist nicht belegt und wird deshalb nicht abgefragt.', 'en' => 'For example: state board, statewide projects or events. No minimum duration is documented, so none is asked.']],
        ['code' => 'q_2jahre',
            'text' => ['de' => 'Besteht die Mitgliedschaft seit mindestens zwei Jahren?', 'en' => 'Has the person been a member for at least two years?'],
            'criterion' => ['de' => 'Mindestens zwei Mitgliedsjahre', 'en' => 'At least two years of membership'],
            'explanation' => ['de' => 'Gemeint ist die Mitgliedschaft bei den Wirtschaftsjunioren, unabhängig vom Kreis.', 'en' => 'Membership in the Wirtschaftsjunioren, regardless of chapter.']],
        ['code' => 'q_7jahre',
            'text' => ['de' => 'Liegen mindestens sieben Jahre herausragender regionaler Einsatz vor?', 'en' => 'Are there at least seven years of outstanding regional commitment?'],
            'criterion' => ['de' => 'Mindestens sieben Jahre herausragender regionaler Einsatz', 'en' => 'At least seven years of outstanding regional commitment'],
            'explanation' => ['de' => 'Dieser alternative Zugang zur goldenen Juniorennadel steht im Handbuch 2026. Er wird erst nach schriftlicher Regelbestätigung aktiviert.', 'en' => 'This alternative path to the gold Junior pin appears in the 2026 handbook. It is activated only after written confirmation of the rule.']],
        ['code' => 'q_international',
            'text' => ['de' => 'Besteht auch internationales Engagement?', 'en' => 'Is there also international engagement?'],
            'criterion' => ['de' => 'Internationales Engagement', 'en' => 'International engagement'],
            'explanation' => ['de' => 'Zum Beispiel: aktive Mitwirkung an JCI-Konferenzen, internationale Partnerschaften oder Ämter bei JCI.', 'en' => 'For example: active participation in JCI conferences, international twinnings or JCI offices.']],
        ['code' => 'q_5jahre',
            'text' => ['de' => 'Ist oder war die Person mindestens fünf Jahre Mitglied?', 'en' => 'Has the person been a member for at least five years?'],
            'criterion' => ['de' => 'Mindestens fünf Mitgliedsjahre', 'en' => 'At least five years of membership'],
            'explanation' => ['de' => 'Bedingung für die JCI-Senatorenwürde nach Handbuch 2026.', 'en' => 'Requirement for JCI Senatorship per the 2026 handbook.'],
            'implied_by' => [['question' => 'q_2jahre', 'answer' => 'no', 'implies' => 'no']]],
        ['code' => 'q_3vor40',
            'text' => ['de' => 'Lagen davon mindestens drei Mitgliedsjahre vor dem 40. Geburtstag?', 'en' => 'Were at least three of those years before the person’s 40th birthday?'],
            'criterion' => ['de' => 'Davon mindestens drei Jahre vor dem 40. Lebensjahr', 'en' => 'Of those, at least three years before age 40'],
            'explanation' => ['de' => 'Für die anonyme Vorprüfung brauchen wir kein Geburtsdatum, nur diese Ja-/Nein-Angabe.', 'en' => 'The anonymous pre-check needs no date of birth, only this yes/no answer.']],
        ['code' => 'q_funktion',
            'text' => ['de' => 'Hatte die Person eine Funktion im Verband inne?', 'en' => 'Has the person held an office in the organisation?'],
            'criterion' => ['de' => 'Funktionsträgerschaft', 'en' => 'Held an office'],
            'explanation' => ['de' => 'Zum Beispiel: Kreissprecher*in, Vorstandsmitglied oder Arbeitskreisleitung.', 'en' => 'For example: chapter president, board member or working group chair.']],
        ['code' => 'q_verdienste',
            'text' => ['de' => 'Hat sich die Person besondere Verdienste um den Verband erworben?', 'en' => 'Has the person made special contributions to the organisation?'],
            'criterion' => ['de' => 'Besondere Verdienste', 'en' => 'Special contributions'],
            'explanation' => ['de' => 'Deine Einschätzung wird dokumentiert und im Antrag mit konkreten Beispielen belegt.', 'en' => 'Your assessment is recorded and backed with concrete examples in the application.']],
        ['code' => 'q_botschafter',
            'text' => ['de' => 'Tritt die Person als Botschafter*in der Wirtschaftsjunioren bzw. von JCI auf?', 'en' => 'Does the person act as an ambassador for the Wirtschaftsjunioren or JCI?'],
            'criterion' => ['de' => 'Botschafterrolle', 'en' => 'Ambassador role'],
            'explanation' => ['de' => 'Zum Beispiel: vertritt den Verband nach außen, wirbt Partner oder Mitglieder, begleitet andere Kreise.', 'en' => 'For example: represents the organisation externally, recruits partners or members, supports other chapters.']],
        ['code' => 'q_referenz',
            'text' => ['de' => 'Stehen zwei JCI-Senator*innen bereit, den Antrag zu unterstützen?', 'en' => 'Are two JCI Senators ready to support the application?'],
            'criterion' => ['de' => 'Zwei Referenzsenator*innen', 'en' => 'Two reference Senators'],
            'explanation' => ['de' => 'Das Handbuch 2026 sieht zwei Referenzsenator*innen für den Antrag vor.', 'en' => 'The 2026 handbook requires two reference Senators for the application.']],
        ['code' => 'q_hat_silber',
            'text' => ['de' => 'Hat die Person die silberne Juniorennadel bereits erhalten?', 'en' => 'Has the person already received the silver Junior pin?'],
            'criterion' => ['de' => 'Silberne Juniorennadel noch nicht erhalten', 'en' => 'Silver Junior pin not yet received'],
            'explanation' => ['de' => 'Eine vorhandene Silbernadel ist weder Vorstufe noch Ausschluss für andere Ehrungen. Gefragt wird nur, ob dieselbe Ehrung erneut infrage kommt.', 'en' => 'An existing silver pin is neither a prerequisite nor an exclusion for other honours. We only ask whether the same honour could be awarded again.']],
        ['code' => 'q_hat_gold',
            'text' => ['de' => 'Hat die Person die goldene Juniorennadel bereits erhalten?', 'en' => 'Has the person already received the gold Junior pin?'],
            'criterion' => ['de' => 'Goldene Juniorennadel noch nicht erhalten', 'en' => 'Gold Junior pin not yet received'],
            'explanation' => ['de' => 'Betrifft nur die erneute Verleihung derselben Ehrung.', 'en' => 'Concerns only a repeat award of the same honour.']],
        ['code' => 'q_hat_lsa', 'region' => 'ST',
            'text' => ['de' => 'Hat die Person die Goldene Ehrennadel Sachsen-Anhalt bereits erhalten?', 'en' => 'Has the person already received the Gold Pin of Honour Saxony-Anhalt?'],
            'criterion' => ['de' => 'Goldene Ehrennadel Sachsen-Anhalt noch nicht erhalten', 'en' => 'Saxony-Anhalt gold pin not yet received'],
            'explanation' => ['de' => 'Betrifft nur die erneute Verleihung derselben Ehrung.', 'en' => 'Concerns only a repeat award of the same honour.']],
        ['code' => 'q_hat_senator',
            'text' => ['de' => 'Ist die Person bereits JCI-Senator*in?', 'en' => 'Is the person already a JCI Senator?'],
            'criterion' => ['de' => 'Noch nicht JCI-Senator*in', 'en' => 'Not yet a JCI Senator'],
            'explanation' => ['de' => 'Die Senatorenwürde wird einer Person nur einmal verliehen.', 'en' => 'Senatorship is awarded to a person only once.']],
    ],

    'honors' => [
        [
            'code' => 'wjd-silberne-juniorennadel', 'organisation' => 'wjd', 'level' => 'national', 'region' => null,
            'title' => ['de' => 'Silberne Juniorennadel', 'en' => 'Silver Junior Pin'],
            'scope' => ['de' => 'Persönliche Auszeichnung der WJD für besonderen Einsatz im Kreis', 'en' => 'Personal WJD award for special commitment in the chapter'],
            'rule' => [
                'basis' => 'pending_confirmation', 'valid_from' => '2023-07-08', 'sources' => ['Q1', 'Q2'],
                'basis_note' => ['de' => 'Öffentlich dokumentiert; aktuelle Fassung bestätigen.', 'en' => 'Publicly documented; confirm current version.'],
                'criteria' => ['all' => [
                    ['recipient' => ['member' => 'T', 'former' => 'B', 'external' => 'B']],
                    ['any' => [['question' => 'q_kreisprojekt'], ['question' => 'q_training']],
                        'label' => ['de' => 'Einer dieser Zugänge genügt', 'en' => 'One of these paths is enough']],
                    ['prior' => 'q_hat_silber', 'mode' => 'unclear'],
                ]],
            ],
            'procedure' => [
                'next_step' => ['de' => 'Vorschlag über den Kreisvorstand vorbereiten. Antragsweg und Formular laut Richtlinie (Q2) bei der WJD-Bundesgeschäftsstelle bestätigen lassen.', 'en' => 'Prepare the nomination via the chapter board. Confirm the application route and form per guideline (Q2) with the WJD national office.'],
            ],
        ],
        [
            'code' => 'wjd-goldene-juniorennadel', 'organisation' => 'wjd', 'level' => 'national', 'region' => null,
            'title' => ['de' => 'Goldene Juniorennadel', 'en' => 'Gold Junior Pin'],
            'scope' => ['de' => 'Persönliche Auszeichnung der WJD auf Bundesebene', 'en' => 'Personal WJD award at national level'],
            'rule' => [
                'basis' => 'conflicting', 'valid_from' => '2026-01-01', 'sources' => ['Q3', 'Q2'],
                'basis_note' => ['de' => 'Die Richtlinie vom 8. Juli 2023 und das Handbuch 2026 unterscheiden sich beim Zugang. Die fachlich zuständige Stelle muss die geltende Regel schriftlich bestätigen.', 'en' => 'The guideline of 8 July 2023 and the 2026 handbook differ on eligibility. The responsible body must confirm the applicable rule in writing.'],
                'criteria' => ['all' => [
                    ['recipient' => ['member' => 'T', 'former' => 'B', 'external' => 'B']],
                    ['any' => [
                        ['all' => [['question' => 'q_ak_vorstand'], ['question' => 'q_ueberregional'], ['question' => 'q_2jahre']],
                            'label' => ['de' => 'Zugang A: überregionale Leistung', 'en' => 'Path A: supra-regional achievement']],
                        ['all' => [['question' => 'q_7jahre'], ['blocked' => ['de' => 'Alternativer Gold-Pfad wird erst nach Regelbestätigung aktiviert.', 'en' => 'Alternative gold path is activated only after the rule is confirmed.']]],
                            'label' => ['de' => 'Zugang B: langjähriger regionaler Einsatz', 'en' => 'Path B: long-term regional commitment']],
                    ], 'label' => ['de' => 'Einer der Zugänge genügt', 'en' => 'One of the paths is enough']],
                    ['prior' => 'q_hat_gold', 'mode' => 'unclear'],
                ]],
            ],
            'procedure' => [
                'next_step' => ['de' => 'Vor einem Vorschlag die geltende Regel bei der WJD klären. Danach Antrag laut bestätigter Richtlinie.', 'en' => 'Clarify the applicable rule with WJD before nominating, then apply per the confirmed guideline.'],
            ],
        ],
        [
            'code' => 'wj-st-goldene-ehrennadel', 'organisation' => 'wj-st', 'level' => 'regional', 'region' => 'ST',
            'title' => ['de' => 'Goldene Ehrennadel der Wirtschaftsjunioren Sachsen-Anhalt', 'en' => 'Gold Pin of Honour, Wirtschaftsjunioren Saxony-Anhalt'],
            'scope' => ['de' => 'Anerkennung herausragenden Einsatzes über Kreisgrenzen auf Landesebene', 'en' => 'Recognition of outstanding commitment across chapters at state level'],
            'rule' => [
                'basis' => 'incomplete', 'valid_from' => null, 'sources' => ['Q4'],
                'basis_note' => ['de' => 'Existenz und Zweck belegt; verbindliche Einzelregeln fehlen. Ehrennadelrichtlinie beim Landesverband anfragen.', 'en' => 'Existence and purpose documented; binding criteria missing. Request the guideline from the state association.'],
                'criteria' => ['all' => [
                    ['recipient' => ['member' => 'T', 'former' => 'B', 'external' => 'B']],
                    ['question' => 'q_lsa'],
                    ['prior' => 'q_hat_lsa', 'mode' => 'unclear'],
                ]],
            ],
            'procedure' => [
                'next_step' => ['de' => 'Beim Landesvorstand der WJ Sachsen-Anhalt Richtlinie, Vorschlagsrecht und Frist erfragen.', 'en' => 'Ask the WJ Saxony-Anhalt state board for the guideline, nomination rights and deadline.'],
            ],
        ],
        [
            'code' => 'jci-senatorenwuerde', 'organisation' => 'jci', 'level' => 'international', 'region' => null,
            'title' => ['de' => 'JCI-Senatorenwürde', 'en' => 'JCI Senatorship'],
            'scope' => ['de' => 'Internationale Ehrung von JCI, deutscher Antragsweg über die WJD', 'en' => 'International JCI honour, German application route via WJD'],
            'rule' => [
                'basis' => 'pending_confirmation', 'valid_from' => '2026-01-01', 'sources' => ['Q3', 'Q8'],
                'basis_note' => ['de' => 'Deutsches Regelwerk und JCI-Vorgaben bestätigen.', 'en' => 'Confirm German rules and JCI requirements.'],
                'criteria' => ['all' => [
                    ['recipient' => ['member' => 'T', 'former' => 'B', 'external' => 'F'],
                        'note' => ['de' => 'setzt Mitgliedsjahre voraus', 'en' => 'requires years of membership']],
                    ['question' => 'q_5jahre'],
                    ['question' => 'q_3vor40'],
                    ['question' => 'q_funktion'],
                    ['question' => 'q_verdienste'],
                    ['question' => 'q_ueberregional'],
                    ['question' => 'q_international'],
                    ['question' => 'q_botschafter'],
                    ['question' => 'q_referenz'],
                    ['prior' => 'q_hat_senator', 'mode' => 'exclude'],
                ]],
            ],
            'procedure' => [
                'next_step' => ['de' => 'Zwei Referenzsenator*innen ansprechen und den deutschen Antragsweg laut Handbuch 2026 (Q3, S. 42–47) nutzen.', 'en' => 'Approach two reference Senators and use the German application route per the 2026 handbook (Q3, pp. 42–47).'],
            ],
        ],
        [
            'code' => 'jci-toyp', 'organisation' => 'jci', 'level' => 'international', 'region' => null,
            'title' => ['de' => 'TOYP – Ten Outstanding Young Persons', 'en' => 'TOYP – Ten Outstanding Young Persons'],
            'scope' => ['de' => 'JCI-Auszeichnung mit nationalem Nominierungsweg', 'en' => 'JCI award with national nomination route'],
            'rule' => [
                'basis' => 'info_only', 'valid_from' => null, 'sources' => ['Q3'],
                'basis_note' => ['de' => 'Im WJD-Handbuch als Ehrung aufgeführt. Aktuelle deutsche Teilnahmebedingungen, Kategorien und Fristen werden noch beschafft.', 'en' => 'Listed as an honour in the WJD handbook. Current German eligibility rules, categories and deadlines are still being obtained.'],
                'criteria' => null,
            ],
            'procedure' => [
                'next_step' => ['de' => 'Aktuelle Ausschreibung bei der WJD erfragen. Eine automatische Eignungsprüfung folgt, sobald die Teilnahmebedingungen vorliegen.', 'en' => 'Ask WJD for the current call. An automatic check follows once eligibility rules are available.'],
            ],
        ],
    ],

    /*
     * Anonymisierte Referenzfälle. Erwartete Ergebnisse beziehen sich auf den
     * Pilotbetrieb (recherchierte Regeln werden ausgewertet).
     */
    'reference_cases' => [
        [
            'name' => 'Projektleiterin im Kreis, 3 Jahre Mitglied, nicht überregional',
            'region' => 'ST', 'group' => 'member',
            'answers' => ['q_kreisprojekt' => 'yes', 'q_training' => 'unknown', 'q_ak_vorstand' => 'yes', 'q_ueberregional' => 'no', 'q_lsa' => 'no', 'q_2jahre' => 'yes', 'q_7jahre' => 'no', 'q_international' => 'no', 'q_hat_silber' => 'no'],
            'expected' => ['wjd-silberne-juniorennadel' => 'suitable', 'wjd-goldene-juniorennadel' => 'clarification', 'wj-st-goldene-ehrennadel' => 'not_suitable', 'jci-senatorenwuerde' => 'not_suitable', 'jci-toyp' => 'info'],
        ],
        [
            'name' => 'Langjähriger Landesvorstand mit JCI-Engagement, Referenzen offen',
            'region' => 'ST', 'group' => 'member',
            'answers' => ['q_kreisprojekt' => 'yes', 'q_training' => 'yes', 'q_ak_vorstand' => 'yes', 'q_ueberregional' => 'yes', 'q_lsa' => 'yes', 'q_2jahre' => 'yes', 'q_7jahre' => 'yes', 'q_international' => 'yes', 'q_5jahre' => 'yes', 'q_3vor40' => 'yes', 'q_funktion' => 'yes', 'q_verdienste' => 'yes', 'q_botschafter' => 'yes', 'q_referenz' => 'unknown', 'q_hat_silber' => 'yes', 'q_hat_gold' => 'no', 'q_hat_lsa' => 'no', 'q_hat_senator' => 'no'],
            'expected' => ['wjd-silberne-juniorennadel' => 'clarification', 'wjd-goldene-juniorennadel' => 'clarification', 'wj-st-goldene-ehrennadel' => 'clarification', 'jci-senatorenwuerde' => 'possible', 'jci-toyp' => 'info'],
        ],
        [
            'name' => 'Externe Unterstützerin, fördert Trainings im Kreis (Sachsen)',
            'region' => 'SN', 'group' => 'external',
            'answers' => ['q_kreisprojekt' => 'no', 'q_training' => 'yes'],
            'expected' => ['wjd-silberne-juniorennadel' => 'clarification', 'wjd-goldene-juniorennadel' => 'clarification', 'jci-senatorenwuerde' => 'not_suitable', 'jci-toyp' => 'info'],
        ],
        [
            'name' => 'Reine Kreisarbeit ohne besonderes Engagement',
            'region' => 'ST', 'group' => 'member',
            'answers' => ['q_kreisprojekt' => 'no', 'q_training' => 'no', 'q_ak_vorstand' => 'no', 'q_lsa' => 'no', 'q_7jahre' => 'no', 'q_international' => 'no'],
            'expected' => ['wjd-silberne-juniorennadel' => 'not_suitable', 'wjd-goldene-juniorennadel' => 'clarification', 'wj-st-goldene-ehrennadel' => 'not_suitable', 'jci-senatorenwuerde' => 'not_suitable', 'jci-toyp' => 'info'],
        ],
        [
            'name' => 'Bereits Senator, alle anderen Kriterien erfüllt',
            'region' => 'BY', 'group' => 'member',
            'answers' => ['q_kreisprojekt' => 'yes', 'q_ak_vorstand' => 'yes', 'q_ueberregional' => 'yes', 'q_2jahre' => 'yes', 'q_international' => 'yes', 'q_5jahre' => 'yes', 'q_3vor40' => 'yes', 'q_funktion' => 'yes', 'q_verdienste' => 'yes', 'q_botschafter' => 'yes', 'q_referenz' => 'yes', 'q_hat_silber' => 'no', 'q_hat_gold' => 'no', 'q_hat_senator' => 'yes'],
            'expected' => ['wjd-silberne-juniorennadel' => 'suitable', 'wjd-goldene-juniorennadel' => 'clarification', 'jci-senatorenwuerde' => 'not_suitable', 'jci-toyp' => 'info'],
        ],
    ],
];
