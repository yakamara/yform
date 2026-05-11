# YForm Tests

Tests run as REDAXO console commands. PHPUnit is **not** required.

## Run

```bash
# Show all registered suites
php redaxo/bin/console yform:test:list

# Run system preconditions check first
php redaxo/bin/console yform:test:doctor

# Run all suites
php redaxo/bin/console yform:test

# Run a single suite
php redaxo/bin/console yform:test:tables

# Run only test methods matching a substring
php redaxo/bin/console yform:test:tables --filter=Export

# Stop on first failure
php redaxo/bin/console yform:test --bail

# Verbose: show failure stack traces
php redaxo/bin/console yform:test:tables -v

# Keep fixture tables for debugging
php redaxo/bin/console yform:test:tables --keep-fixtures

# Machine-readable output
php redaxo/bin/console yform:test --json | jq .

# Custom table prefix (for parallel CI runs)
php redaxo/bin/console yform:test:tables --prefix=ci_42_
```

Exit code `0` = all green, `1` = at least one failure.

## Add a suite

1. Add a class under `lib/Tests/Suites/<Name>Suite.php` extending `Redaxo\YForm\Test\AbstractTestSuite`.
2. Add public `test*()` methods. Use `$this->assertSame()`, `$this->assertThrows()`, etc.
3. Register the suite in `lib/Test/SuiteRegistry.php` under a short key.
4. Add a sub-command file `lib/command/test/<key>.php` (copy `tables.php` as template).
5. Add a `console_commands` entry in `package.yml`.
6. Reinstall the addon so `package.yml` is re-read.

## Suite anatomy

```php
namespace Redaxo\YForm\Tests\Suites;

use Redaxo\YForm\Test\AbstractTestSuite;

final class MySuite extends AbstractTestSuite
{
    public function setUp(): void
    {
        // Runs before every test* method.
    }

    public function tearDown(): void
    {
        // Runs after every test* method.
    }

    public function testSomething(): void
    {
        $table = $this->createTestTable('thing', [
            ['type_id' => 'value', 'type_name' => 'text', 'name' => 'a', 'label' => 'A'],
        ]);

        $this->assertSame('thing', $table->getName());
        $this->assertCount(1, $table->getValueFields());
    }
}
```

## Fixtures

Test tables get a unique prefix per run (`unittest_<random>_<shortname>`). The
`FixtureManager` registers them on creation and drops them in cleanup. Cleanup
also catches orphans from crashed previous runs (anything matching the prefix).

JSON fixtures under `tests/fixtures/` can be loaded with
`$this->loadFixture('myfixture.json')` — the file format matches the
Table Manager's tableset export. Table names get rewritten to the run's prefix
automatically.

## Available assertions

`assertSame`, `assertNotSame`, `assertEquals`, `assertTrue`, `assertFalse`,
`assertNull`, `assertNotNull`, `assertCount`, `assertInstanceOf`,
`assertThrows`, `assertStringContains`, `assertArrayHasKey`,
`assertArrayNotHasKey`. Custom: `markSkipped(string $reason)` aborts the
current method as "skipped" (yellow, not red).

## Email assertions

```php
$this->mailer->lastMail();          // ?array — last template sent
$this->mailer->count();             // int — number of mails sent in this test
$this->mailer->reset();             // call between methods if needed
```

The `MailerStub` is auto-activated by the runner. It intercepts
`YFORM_EMAIL_BEFORE_SEND` and short-circuits PHPMailer before any actual SMTP
transport runs.

## CI

GitHub Actions snippet (see `.claude/plans/04-test-commands.md`):

```yaml
- name: Run yform tests
  run: ./redaxo/bin/console yform:test --json | tee yform-test-results.json
```

The `--json` output is machine-readable and structured per suite.

## Layout

```
lib/Test/                       # Infrastructure (namespaced)
├── AbstractTestSuite.php
├── Assert.php
├── FixtureManager.php
├── MailerStub.php
├── OutputFormatter.php
├── SuiteRegistry.php
├── SuiteResult.php
├── TestResult.php
├── TestRunner.php
└── Exception/
    ├── AssertionFailedException.php
    ├── FixtureException.php
    └── TestSkippedException.php

lib/Tests/Suites/               # The actual test classes
└── TablesSuite.php

lib/command/                    # rex_console_command subclasses
├── test.php                    # yform:test (umbrella)
└── test/
    ├── doctor.php              # yform:test:doctor
    ├── list.php                # yform:test:list
    └── tables.php              # yform:test:tables

tests/                          # Legacy PHPUnit (kept for now)
├── README.md                   # this file
├── fixtures/                   # JSON fixtures (created on demand)
└── rex_yform_yorm_test.php     # legacy phpunit test, still runnable
```

## Status

| Suite | Implemented | Plan §  |
|---|---|---|
| `tables` | ✓ 10 tests | 02 §3.U.3 |
| `table-read` | ✓ 16 tests | 02 §3.U.1 |
| `fields` | ✓ 15 tests | 02 §3.U.2 |
| `datasets` | ✓ 14 tests | 02 §3.I.1 |
| `queries` | ✓ 21 tests | 02 §3.I.2 |
| `validators` | ✓ 15 tests | 02 §3.U.5 |
| `actions` | ✓ 11 tests | 02 §3.U.6 |
| `extension-points` | ✓ 9 tests | 02 §3.I.4 |
| `authorization` | ✓ 7 tests | 02 §3.U.8 |
| `cache` | ✓ 7 tests | 02 §3.U.7 |
| `field-types` | ✓ 11 tests | 02 §3.U.4 |
| `tablesets` | — (von `tables` abgedeckt) | 02 §3.I.3 |
| `e2e` | — | 02 §3.E2E |

**Stand: 132 passed, 0 failed, 6 skipped, ~3 min Runtime auf 11 Suiten.**

## Findings (echte Bugs + falsche Skill-Doku)

Beim Schreiben der Suiten gefundene Probleme in der bestehenden yform-Implementation
bzw. inkorrekter Skill-Dokumentation. Tests dokumentieren das aktuelle Verhalten;
Fixes werden später im Rahmen von Plan 01 nachgezogen.

### A. Echte Bugs

1. **`rex_yform_manager_table_api::setTableField()` überschreibt `$table_name`.**
   Der INSERT-Branch ruft erst `setValue('table_name', $table_name)` mit dem
   expliziten Argument auf, danach iteriert `foreach ($table_field as $k => $v)`
   und überschreibt jeden Key inklusive `table_name`. Beim Re-Import unter
   anderem Namen landen die Felder auf der falschen Tabelle.
   *Test:* `tables::testImportTablesetsKnownIssueRenameLosesFields` (skipped).
   *Fix-Vorschlag:* `unset($table_field['table_name']);` vor der foreach-Schleife.

2. **`rex_yform_manager_table_api::$table_fields` ist unvollständig.** Die
   Whitelist enthält weder `history` noch `mass_deletion` noch `mass_edit`,
   obwohl diese Spalten existieren und Getter (`hasHistory()`, …) bereitstehen.
   `setTable(['history' => 1])` ist silent no-op.
   *Test:* `table-read::testSetTableApiKnownIssueDoesNotPropagateHistoryFlag` (skipped).
   *Fix:* `'history', 'mass_deletion', 'mass_edit'` zur Whitelist hinzufügen.

3. **`importTablesets()` validiert die JSON-Eingabe nicht.** Bei kaputtem JSON
   (z.B. `"garbage"`) returnt `json_decode` `null`, dann triggert das `foreach`
   eine PHP-Warning und die Funktion endet mit `return true`. Sollte stattdessen
   eine Exception werfen.
   *Test:* `tables::testImportTablesetsWithMalformedStructureThrows` umgeht das
   mit einem structurell-validen Payload.
   *Fix:* nach `json_decode` Type-Check + `throw`.

4. **`rex_yform_validate_in_table` hat keine `getDefinitions()`-Methode.** Damit
   ist der Validator nicht via `setTableField()` konfigurierbar — `dataset::save()`
   greift in `dataset.php:704` auf `$definitions['values']` zu und erhält
   "Undefined array key 'values'". Funktioniert nur über Pipe-Syntax.
   *Test:* `validators::testInTableValidatorKnownIssueNoDefinitions` (skipped).
   *Fix:* `getDefinitions()` zur Klasse hinzufügen.

5. **`compare` / `compare_value` Operator-Semantik ist INVERS.** Der Operator
   markiert NICHT die erwünschte Bedingung, sondern die FEHLERBEDINGUNG:
   - `compare_type='=='` → Fehler wenn Werte gleich
   - `compare_type='!='` → Fehler wenn Werte ungleich
   "Passwort wiederholen" braucht also `compare_type='!='` (Fehler, wenn sich
   die Felder unterscheiden), nicht `'=='`. Komplett unintuitiv.
   *Tests dokumentieren das Verhalten und enthalten Kommentare dazu.*
   *Fix-Frage:* sollten wir Operator-Semantik invertieren? Major-Breaking-Change.

6. **`rex_yform_action_manage_db` macht keine Existenz-Erkennung.** Trotz Namens
   "manage_db" prüft die Action nicht, ob eine Zeile mit der gegebenen ID
   existiert. Sie verhält sich identisch zu `db`: nimmt sie Slot 3 (where-clause)
   als nicht-leer wahr → `UPDATE`, sonst → `INSERT`. Der Name ist irreführend.
   *Tests:* `actions::testManageDbInsertsWhenWhereIsEmpty` / `…UpdatesWhenWhereProvided`.

7. **`YFORM_DATA_UPDATED.params['old_data']` ist NICHT der Pre-Update-Stand.**
   `dataset->executeForm()` captured `$oldData = $this->getData()` direkt bevor
   die Form-Pipeline läuft. Aber `setValue('after')` hat `$this->data` zu dem
   Zeitpunkt schon mutiert — der Wert wurde nur noch nicht in die DB geschrieben.
   `old_data` ist also der In-Memory-State **nach** der User-Mutation,
   **vor** dem SQL-Write. Wer einen Diff "alt vs neu" will, muss in
   `YFORM_DATA_UPDATE` eine frische Kopie via SQL laden. Name ist irreführend.
   *Test:* `extension-points::testYformDataUpdateAndUpdatedFireOnUpdate` mit
   Kommentar.

9. **`rex_yform_value_hidden` hat keine `getDefinitions()`-Methode.** Gleiches
   Muster wie `rex_yform_validate_in_table` (Finding A.4). Verwendung über
   `setTableField()` + `dataset->save()` crashed mit "Undefined array key
   'values'" in `dataset.php:704`, gefolgt von einer PDOException ("bindValue
   parameter must not be empty"). Hidden-Felder funktionieren nur via Pipe-Syntax
   oder `rex_yform`-PHP-API.
   *Test:* `field-types::testHiddenViaSetTableFieldKnownIssue` (skipped).
   *Fix:* `getDefinitions()` zur Klasse hinzufügen.

8. **`rex_yform_manager_table_authorization::$tableAuthorizations` ist
   cross-user sticky.** Die Klasse cached den ersten gefragten User-Kontext
   statisch im Prozess. Ein zweiter Aufruf mit anderem User (oder `null`)
   liefert WEITER das Ergebnis des ersten Users — die Klasse re-evaluiert nicht
   beim User-Wechsel. In Standard-Webrequests (ein User pro Request) unkritisch,
   aber in long-running PHP-Workern, CLI-Tools, oder Tests ein bedeutsamer
   Footgun. **Workaround:** vor jedem User-Wechsel
   `$tableAuthorizations = null;` setzen. **Fix:** den Cache pro
   User-ID schlüsseln statt global im Prozess.
   *Test:* `authorization::testTableAuthorizationsCacheIsStickyUntilExplicitReset`.

### B. Skill-Doku weicht von der Implementation ab

Die folgenden Parameter-Namen / Aufruf-Konventionen aus dem `redaxo-yform`-Skill
stimmen NICHT mit dem yform-5.0.1-Source überein. Im Code wurden die korrekten
Namen verifiziert; siehe Tests + `.claude/references/03-field-types.md` (sollte
nachgezogen werden).

| Komponente | Skill sagt | Code verlangt |
|---|---|---|
| `type`-Validator | `type_name_internal` | `type` |
| `compare`-Validator | `name1` + `name2` + `compare_operator` | `name` + `name2` + `compare_type` |
| `compare_value`-Validator | `compare_operator` | `compare_type` |
| `customfunction`-Callback-Signatur | `($label, $value, $params, $return)` | `($names, $values, $parameter, $validator, $Objects)` — `$parameter` ist mixed (string\|null), nicht array |
| `callback`-Action | erhält `rex_yform` | erhält die `rex_yform_action_callback`-Instanz; rex_yform via `$action->params['this']` |
| `copy_value`-Action | (unklar) | schreibt nach `value_pool.sql`, nicht `.email` |
| `manage_db`-Action | "insert or update based on existence" | insert vs. update entscheidet ausschließlich der where-Parameter (Slot 3) |
| `objparams.actions_executed` | (impliziert int) | boolean `true` nach erfolgreichem Action-Run |
| `YFORM_DATA_UPDATED.params['old_data']` | (impliziert: Pre-Update-DB-Stand) | In-Memory-State nach `setValue()`, vor SQL-Write |

### C. Konzeptionell überarbeitungsbedürftig

- **Validator-Namens-Inkonsistenz**: `compare` benutzt `name` + `name2`, NICHT
  `name1` + `name2`. Ein Mensch beim Lesen erwartet symmetrisches Naming. Sollte
  irgendwann auf `name1` + `name2` umbenannt werden (mit BC-Layer).
- **Validator-Operator-Inversion** (siehe A.5): die Validator-Action gehört
  semantisch umgedreht, sodass `compare_type='=='` "muss gleich sein" bedeutet.
  Major-Breaking-Change, nicht jetzt anfassen.
- **`getElement(int)` vs `getElement('name')`**: positional access ist eine
  Falle (siehe Plan 01 §13 / `13-pitfalls-and-conventions.md`). Sollte langfristig
  durch reine named-keys ersetzt werden.
- **Inkonsistente `value_pool`-Ziele**: manche Actions (`copy_value`) schreiben
  nach `value_pool.sql`, andere (`tpl2email`) nach `.email`. Convention sollte
  irgendwann explizit dokumentiert/festgelegt werden.
- **`rex_yform_validate_in_table` ohne `getDefinitions()`**: muss nachgezogen
  werden (siehe A.4) — andernfalls bleibt der Validator nur per Pipe-Syntax
  nutzbar, was die API-Symmetrie bricht.
- **`unique.inc.php`** statt `unique.php` als Dateinamen-Konvention. Funktioniert
  trotzdem (rex_autoload), aber sollte irgendwann angepasst werden.

See `.claude/plans/04-test-commands.md` for the implementation plan, and
`.claude/plans/02-test-strategy.md` for the full list of test cases.
