# CLAUDE.md — YForm Addon

This file is the entry point when working on the **YForm addon itself** (not when consuming it from a project). It collects what's not obvious from grepping: the architectural model, the lifecycle, the cache contracts, the do-not-touch zones.

## Was ist YForm?

YForm ist ein REDAXO-AddOn mit zwei Aufgaben:

1. **Frontend-Formularbau** — Pipe-Syntax oder PHP-API, validierung, Aktionen (DB-Insert, Mailversand, Redirect).
2. **Tabellenverwaltung im Backend** — visuell zusammengeklickte Tabellen + Backend-UI + ORM (`rex_yform_manager_dataset` / YOrm).

Beides nutzt denselben Form-Pipeline-Runtime (`rex_yform`). Der Backend-Datasatz-Save geht durch den gleichen Field/Validate/Action-Loop wie ein Frontend-Submit, nur „headless".

## Vorhandene Skills nutzen

Es gibt bereits gut gepflegte Skills für die Anwender-Sicht. Bei Aufgaben, die nur das „Konsumieren" von YForm betreffen (Form bauen, Dataset abfragen, REST-Endpoint registrieren), **erst dort nachschauen**:

| Skill (im `redaxo-yform`-Plugin) | Wofür |
|---|---|
| `redaxo-yform` (Top-level Skill) | Schnellreferenz quer durch alle Themen |
| `yform-tables` | Tabellen in `install.php`, Schema-Migrationen, Tablesets |
| `yform-fields` | Alle Value/Validate/Action-Typen + Pipe-Syntax |
| `yform-frontend` | Public Forms, CSRF, Uploads, Spam-Schutz, objparams |
| `yform-datasets` | YOrm-Queries, Joins, Pagination, Relations |
| `yform-email-templates` | Email-Templates, Platzhalter, programmatischer Versand |
| `yform-rest-api` | REST-Routen, Token-Auth, CORS, Field-Whitelists |

Bei Arbeit **am AddOn selbst** (interne Klassen ändern, Field-Typen hinzufügen, EPs erweitern, Schema migrieren) reichen die Skills nicht. Dafür gibt es die `references/` unten.

## Architektur in 60 Sekunden

```
HTTP-Request (Frontend/Backend/REST)
    │
    ▼
boot.php
    ├── lädt Template-Pfade
    ├── injiziert Backend-Assets (CSS, JS, Inputmask, Daterangepicker)
    ├── kompiliert SCSS bei `compile: 1`
    ├── PACKAGES_INCLUDED → rex_yform_rest::handleRoutes() (nur Frontend)
    ├── registriert complex_perms `yform_manager_table_{view,edit}`
    ├── baut Backend-Pages für jede aktive Tabelle
    ├── verdrahtet MEDIA_IS_IN_USE, YFORM_SAVED (History), Cron
    │
    ▼
Pages (pages/manager.*.php) ──► rex_yform_manager ──► rex_yform_manager_dataset
                                                          │
                                                          ▼
                                                    interner rex_yform (headless)
                                                          │
                                                          ▼
                                                    executeFields → executeActions
                                                          │
                                                          ▼
                                                    action|db (INSERT/UPDATE)
                                                          │
                                                          ▼
                                                    YFORM_SAVED EP
                                                          │
                                                          ▼
                                                    History-Snapshot (wenn aktiv)
```

System-Tabellen (alle in `install.php` per `rex_sql_table::ensure()`):

- `rex_yform_table` — Tabellen-Metadaten
- `rex_yform_field` — Field/Validate/Action-Zeilen
- `rex_yform_history` + `rex_yform_history_field` — Snapshots
- `rex_yform_email_template` — Email-Templates
- `rex_yform_rest_token` + `rex_yform_rest_token_access` — REST-Auth

Alle gemanagten User-Tabellen werden separat (per `setTable()` + manuelles `rex_sql_table::ensure()`) angelegt. YForm besitzt sie nicht — es kennt nur ihre Konfiguration.

## Lifecycle eines Formulars

`rex_yform::getForm()` ruft `executeFields()` → `executeActions()`:

1. **`initializeFields()`** — CSRF-Feld prepend, dann jede `form_elements`-Zeile als `rex_yform_value_*` / `rex_yform_validate_*` / `rex_yform_action_*` instanziieren. `loadParams()` bindet die `objparams` per Referenz, d.h. jedes Feld kann den shared State mutieren.
2. **Value-Population**: REQUEST → sql_object (bei `getdata=true`) → objparams.data → fixdata. Spätere Quellen überschreiben frühere.
3. **`preValidateAction` → Validate-Phase (nur bei `send=1`) → `postValidateAction`**.
4. **Value-`enterObject`** — rendert HTML in `form_output[$id]`, schreibt in `value_pool.email` und `value_pool.sql` (wenn `saveInDb()`).
5. **`postValueAction` (nur send=1) → `postFormAction`**.
6. **Action-Phase (nur send=1 und keine warnings)**: `preAction` → `executeAction` → `postAction`. `action|db` zieht aus `value_pool.sql`, `action|tpl2email` aus `value_pool.email`.

Detaillierter Ablauf siehe **`.claude/references/02-form-pipeline.md`**.

## Was im Repo **nicht** geändert werden darf

- **`ytemplates/bootstrap/*.tpl.php`** — wird bei jedem Update überschrieben. Override stattdessen in Projekt-AddOns via `rex_yform::addTemplatePath()`.
- **`vendor/`** — vom Top-Level redaxo/redaxo Composer-Setup gemanagt.
- **`assets/inputmask/`, `assets/daterangepicker/`** — Third-Party-JS; nur durch saubere Upstream-Updates ersetzen.
- **`composer.lock`** — explizit per `.gitignore` ausgeschlossen.
- Direktes SQL auf `rex_yform_table` / `rex_yform_field` ohne anschließendes `rex_yform_manager_table::deleteCache()` — siehe **Cache-Disziplin** unten.
- Reordering von `getDefinitions()['values']`-Slots in bestehenden Field-Typen — die `f1`-`f9` positional columns aus `rex_yform_field` mappen positional auf diese Reihenfolge. Insert in der Mitte = stille Daten-Korruption.

## Cache-Disziplin

Fünf Cache-Layer (siehe `13-pitfalls-and-conventions.md`):

1. `rex_yform_manager_table::$cache` (Filecache `cache/addons/yform/manager/tables.cache`) — komplette Tabellen-Metadaten. Bust: `rex_yform_manager_table::deleteCache()`. **MUSS nach jedem Write auf `rex_yform_table`/`rex_yform_field`**. Die `*_api`-Methoden machen es automatisch — bei rohen SQL-Writes selbst.
2. `rex_yform_manager_dataset` Instance-Pool (`rex_instance_pool_trait`) — keyed `[table, id]`. Bust: `self::clearInstance([$t, $id])` (macht `delete()` automatisch).
3. `rex_yform_value_be_manager_relation::$yform_list_values` — Relations-Lookups. Bust: `clearCache($table)`.
4. `rex_yform_manager_table_api::$cacheColumnsByTable` — `SHOW COLUMNS`-Cache für eine install.php-Runtime.
5. REDAXO-Autoload-Cache — bei neuen Field-Klassen `rex_autoload::removeCache()` (oder Reinstall).

## update.php delegiert an install.php

Das eigene `update.php` ist:

```php
$this->includeFile(__DIR__ . '/install.php');
```

**Jeder Reinstall durchläuft die komplette install.php.** Die 4.x→5.x Field-Migration (Zeilen ~174–344) ist deshalb idempotent geschrieben — jeder Switch-Case prüft den aktuellen Zustand vor dem Mutate. Wenn du etwas in install.php ergänzt, gilt das gleiche: idempotent oder per `rex_version::compare()` gaten.

Für nachgelagerte AddOn-Versionen (1.0 → 1.1) wird typischerweise ein `update.php` mit `rex_version::compare($installed, 'X.Y.Z', '<')` verwendet — siehe `04-table-manager.md`.

## Aktive Pläne

Größere Umbauten werden vor der Umsetzung als ausgearbeitete Pläne unter `.claude/plans/` abgelegt. **Diese Pläne lesen, bevor du am betreffenden Code arbeitest** — sie definieren Phasen, Test-Voraussetzungen und Rollback-Punkte.

| Plan | Status | Worum geht's |
|---|---|---|
| [`plans/01-json-schema-migration.md`](.claude/plans/01-json-schema-migration.md) | **Draft** | `rex_yform_table` + `rex_yform_field` durch JSON-Definitionen unter `data/addons/yform/tables/*.json` ablösen. Fünf Phasen, jede einzeln releasebar. |
| [`plans/02-test-strategy.md`](.claude/plans/02-test-strategy.md) | **Draft** | **Was** getestet wird: Test-Pyramide (Unit/Integration/E2E), ~100 konkrete Test-Cases, Coverage-Ziel ≥ 80 % auf Storage-Klassen. |
| [`plans/03-upgrade-process.md`](.claude/plans/03-upgrade-process.md) | **Draft** | Migrations-Routine SQL → JSON für bestehende Installationen. Detection, Backup, ParityChecker, 10 Edge-Cases mit Tests, Rollback-Optionen. |
| [`plans/04-test-commands.md`](.claude/plans/04-test-commands.md) | **Draft** | **Wie** getestet wird: Tests als REDAXO Console Commands (`yform:test:*`). Runner-Architektur, Assertion-Library, Fixture-Management, CI-Workflow. |
| Übersicht: [`plans/README.md`](.claude/plans/README.md) | | Status aller Pläne, Reihenfolge der Umsetzung |

**Wichtig**: Pläne sind lebende Dokumente. Wenn du den Code änderst, halte den entsprechenden Plan synchron (Status, offene Fragen, neu entdeckte Risiken). Wenn du am Code-Stand etwas siehst, das einem Plan widerspricht, ist der Plan zu aktualisieren — nicht zu ignorieren.

### Bereits aufgedeckte Bugs & Skill-Abweichungen

Beim Schreiben der Test-Suite (siehe [`tests/README.md`](tests/README.md) → Findings) wurden mehrere Bugs in der yform-5.0.1-Implementation und Abweichungen zwischen dem `redaxo-yform`-Skill und der echten Implementation gefunden. Kurzfassung:

- **`setTableField()`** überschreibt `table_name` aus dem Field-Row → renamed-Import verliert Felder
- **`setTable()`-Whitelist** unvollständig: `history`/`mass_deletion`/`mass_edit` werden silent verworfen
- **`importTablesets()`** wirft keine Exception bei kaputtem JSON
- **`rex_yform_validate_in_table`** hat keine `getDefinitions()` → nur Pipe-Syntax-fähig
- **`compare` / `compare_value`** Operator-Semantik ist INVERS: `==` heißt "Fehler wenn gleich"
- **`manage_db`** macht keine Existenz-Erkennung — entscheidet rein per where-Param
- **`YFORM_DATA_UPDATED.params['old_data']`** ist NICHT der Pre-Update-DB-Stand, sondern der In-Memory-State nach `setValue()`
- **`rex_yform_manager_table_authorization`-Cache** ist cross-user sticky (statisch pro Prozess); Footgun in long-running PHP-Workern
- Diverse Param-Namen falsch in der Skill-Doku — siehe Tabellen in [`tests/README.md`](tests/README.md) und [`references/03-field-types.md`](.claude/references/03-field-types.md)

Die Bugs sind als `markSkipped()` Tests dokumentiert. Fixes nicht jetzt — erst wenn die volle Test-Suite gegen unverändertes Verhalten grün ist (Plan 02 §Rollout).

## Reference-Dokumente

Wenn du tiefer ins AddOn-Interna gehst, sind die folgenden Dateien unter `.claude/references/` die ehrliche Quelle:

| Datei | Wenn du… |
|---|---|
| [`01-architecture.md`](.claude/references/01-architecture.md) | Boot/Install-Lifecycle, Verzeichnis-Layout, Request-Flow Frontend/Backend/REST verstehen willst |
| [`02-form-pipeline.md`](.claude/references/02-form-pipeline.md) | Wissen willst, was `executeFields()`/`executeActions()` Schritt für Schritt machen, und wie `value_pool` aufgebaut ist |
| [`03-field-types.md`](.claude/references/03-field-types.md) | Einen neuen Value/Validate/Action-Typ schreiben willst, `getDefinitions()`/`enterObject()` ausbalancieren musst, oder verstehst wie `getElement()` positional vs. keyed funktioniert |
| [`04-table-manager.md`](.claude/references/04-table-manager.md) | Tabellen programmatisch in install.php anlegst, `schema_overwrite` einstellst, `setTable()`/`setTableField()` API benutzt, oder Tablesets ex-/importierst |
| [`05-dataset-orm.md`](.claude/references/05-dataset-orm.md) | YOrm-Modellklassen baust (`setModelClass`), CRUD-Lifecycle inkl. EP-Reihenfolge verstehen willst, History/Snapshots managst |
| [`06-query-builder.md`](.claude/references/06-query-builder.md) | Komplexe `WHERE`/`JOIN`/`HAVING`/OR-Nested-Queries baust, `joinRelation` über alle 6 Relation-Typen (0–5) hin sauber haben willst |
| [`07-email-templates.md`](.claude/references/07-email-templates.md) | `tpl2email` debuggst, `REX_YFORM_DATA`-Platzhalter inkl. `_LABELS`/`_LIST` benutzt, programmatisch Mails versendest, Anhänge aus Uploads route st |
| [`08-rest-api.md`](.claude/references/08-rest-api.md) | REST-Routen registrierst, `preFunc`/`postFunc`/`getItemFunc`/`getAttributeFunc` Hooks brauchst, Token-Auth und CORS richtig setzt |
| [`09-extension-points.md`](.claude/references/09-extension-points.md) | Einen EP wie `YFORM_DATA_UPDATED`/`YFORM_DATA_LIST_QUERY`/`YFORM_EMAIL_SEND` registrierst — vollständige EP-Liste mit Subject/Params/Timing |
| [`10-frontend-templates.md`](.claude/references/10-frontend-templates.md) | Eigene ytemplates ausspielst, cascading-Fallback verstehst, `form.tpl.php` overridest |
| [`11-rex-var-widgets.md`](.claude/references/11-rex-var-widgets.md) | `REX_YFORM_DATA[field=...]` und `REX_YFORM_TABLE_DATA[id=N table=...]` einsetzt — Kontextregeln, Widgets, CSRF |
| [`12-testing-and-tooling.md`](.claude/references/12-testing-and-tooling.md) | PHPUnit-Suite `yorm` laufen lässt, `composer cs-fix`/`composer cs-dry` machst, Debug-Knöpfe (`debug` objparam, `setDebug()` auf SQL) drehst |
| [`13-pitfalls-and-conventions.md`](.claude/references/13-pitfalls-and-conventions.md) | Die wirklich gemeinen Fallen — Element-Offset-Inkonsistenzen, Cache-Vergessen, REST-Kontext, deprecated EPs |
| [`14-class-map.md`](.claude/references/14-class-map.md) | Schnell nachschlagen willst, in welcher Datei welche Klasse lebt |

## Was die Skills nicht behandeln (aber die References schon)

- **EPs mit Subject/Param-Tabelle**: 09-extension-points.md hat sie alle inkl. Trigger-Position und Cancel-Semantik.
- **`getElement()`-Mapping-Offset** (1 für Value, 2 für Validate, 1 für Action): 03-field-types.md.
- **Die fünf Cache-Layer und wann sie invalidiert werden müssen**: 13-pitfalls-and-conventions.md.
- **Wie `setModelClass` mit `tableToModel` / `modelToTable` zusammenspielt**: 05-dataset-orm.md.
- **`joinRelation` über alle Relations-Typen 0-5** inkl. junction-table-Pfad: 06-query-builder.md.
- **`replaceVars()` Stream-Hack** für PHP in Email-Templates: 07-email-templates.md.
- **REST `getItemFunc`/`getAttributeFunc` Hooks** (neu in 5.0): 08-rest-api.md.

## Coding-Konventionen für dieses AddOn

- **PHP 8.1+**, PSR-12 via redaxo/php-cs-fixer-config. `composer cs-fix` vor jedem Commit.
- **`declare(strict_types=1);`** in neuen Dateien — Altbestand teils ohne.
- **Klassennamen, Methodennamen, Code-Kommentare auf Englisch.** Backend-Strings in `lang/`-Files (deutsch default). Dokumentation in `docs/` und `CHANGELOG.md` auf Deutsch.
- **Neue Field-Typen**: Slot-Reihenfolge in `getDefinitions()['values']` ist API-Vertrag. Slots **nur anhängen**, nie einfügen oder umordnen, ohne Migration für `f1`-`f9`-Positionsdaten.
- **EP-Namen**: `YFORM_*` für formbuilder-allgemein, `YFORM_DATA_*` für Dataset-Lifecycle, `YFORM_MANAGER_*` für Backend-UI, `YFORM_EMAIL_*` für Mail-Pipeline.
- **Cache nach Writes flushen**. Wenn unsicher: `rex_yform_manager_table::deleteCache()` aufrufen ist nie falsch (nur teuer).
- **Keine `composer.lock` committen** (gitignored).

## Entwicklungsfluss

```bash
# Test-Suite laufen lassen (aus diesem Verzeichnis)
composer install
composer test

# Code-Style fixen
composer cs-fix

# Vom REDAXO-Core-Verzeichnis aus volle Checks
cd ../../../..
composer check           # cs + phpstan + psalm + phpunit
composer phpstan
composer psalm
composer taint           # Psalm-Taint-Analyse

# Lokal via Docker testen
docker-compose up -d                      # localhost:80
REDAXO_PORT=8080 docker-compose up -d     # alternativer Port
```

Nach Schema-Änderungen an `install.php`: AddOn im Backend (System → AddOns → YForm) **deinstallieren + reinstallieren**, oder den Cache leeren (System → Cache → Generieren). Für reine `lib/`-Änderungen reicht ein Cache-Clear.

## Häufigste Beitragspfade

1. **Neuer Value-Field-Typ** — `lib/Field/value/<name>.php` schreiben, dabei `enterObject()` mit Value-Pool-Disziplin (`saveInDb` Check, `needsOutput`/`isViewable` für Render-Skip), `getDefinitions()` slot-stabil, `getDescription()` pipe-syntax-doc. Optional eigene `value.<name>.tpl.php` unter `ytemplates/bootstrap/`. Tests in `tests/`. CHANGELOG-Eintrag.
2. **Neuer Validator** — `lib/Field/validate/<name>.php`. In `enterObject()` per `getValueObject()` Zielfeld auflösen, `params['warning'][$id]` + `params['warning_messages'][$id]` setzen bei Fehler.
3. **Neue Action** — `lib/Field/action/<name>.php`. `executeAction()` macht den Side Effect; `preAction`/`postAction` für Vor-/Nacharbeit. `getDescription()` für Pipe-Syntax-Hilfe.
4. **REST-Hook erweitern** — Anpassungen meist in `lib/Rest/route.php`; bei Hook-API-Erweiterung auch `08-rest-api.md` aktualisieren.
5. **Backend-UI-Änderung** — `pages/`, `fragments/yform/`, `assets/manager.js` / `assets/widget.js`. SCSS in `scss/`, Build durch `compile: 1` in `package.yml` oder manuell via `rex_scss_compiler`.

## Beim Commit beachten

Die globalen Repo-Regeln (siehe `~/.claude/CLAUDE.md` und `redaxo/CLAUDE.md`):

- **Keine persönlichen Daten** in tracked Files. Pfade projekt-relativ, keine Heim-Verzeichnis-Strings.
- **Keine `.claude/settings.local.json` ins Repo** (das hier ist Dokumentation, nicht Tool-Config — die ist okay).
- **`composer.lock` nicht committen** (gitignored auf AddOn-Ebene).
- Code-Stil via `composer cs-fix`, Tests via `composer test`.
- CHANGELOG-Eintrag auf Deutsch in neuestem Release-Block (oben), mit Kontributor-Erwähnung wenn extern.
