# YForm für REDAXO 6

YForm hat zwei Seiten: es erzeugt **Formulare** – im Frontend wie im Backend – und es verwaltet
**Tabellen samt Datensätzen**. Beides greift ineinander: aus einer im Table Manager angelegten
Tabelle lässt sich der Code für das passende Formular direkt erzeugen.

Das ist YForm für REDAXO 6 (Composer-Paket `yakamara/yform`, Namespace `Yakamara\YForm`,
Branch `6.x`). Die REDAXO-5-Version liegt auf `master` und wird nicht an Ort und Stelle
aktualisiert – siehe [Unterschiede zu REDAXO 5](#unterschiede-zu-redaxo-5).

## Funktionen

- Formulare in kompakter Pipe-Notation oder in PHP-Schreibweise
- 37 Feldtypen (Values), 13 Validierungen und 16 Aktionen, erweiterbar um eigene
- Table Manager: Datenbanktabellen im Backend zusammenstellen, Schema pflegen, Relationen definieren
- Vollständige Datenverwaltung je Tabelle mit Liste, Suche, Historie, Import und Export
- ORM (`Dataset`, `Collection`, `Query`) für den Zugriff im Code
- E-Mail-Templates mit Platzhaltern aus den Formulardaten
- Tablesets zum Ex- und Importieren kompletter Tabellendefinitionen
- HTML-Ausgabe über Core-Fragmente, pro Projekt überschreibbar

## Voraussetzungen

- REDAXO 6
- PHP 8.5 oder neuer

## Installation

```bash
composer require yakamara/yform
php bin/console addon:install yform
```

## Ein Formular

```
text|vorname|Vorname|
text|name|Name|
email|email|E-Mail|
validate|empty|vorname|Bitte Vornamen angeben
submit|submit|Absenden
```

Jede Zeile ist ein Feld. Es gibt drei Sorten:

| Sorte | Zweck | Beispiele |
| --- | --- | --- |
| **Value** | Eingabefelder und Ausgaben | `text`, `email`, `choice`, `upload` |
| **Validate** | prüft Values, bevor gespeichert oder gesendet wird | `empty`, `type`, `unique` |
| **Action** | läuft nach erfolgreicher Validierung | `db`, `email`, `redirect` |

In PHP-Schreibweise:

```php
use Yakamara\YForm\YForm;

$yform = new YForm();
$yform->setObjectparams('form_showformafterupdate', 1);
$yform->setValueField('text', ['name', 'Name']);
$yform->setValidateField('empty', ['name', 'Bitte Namen angeben']);
$yform->setActionField('db', ['rex_yf_contact']);

echo $yform->getForm();
```

Eine immer aktuelle Kurzliste aller installierten Feldtypen steht im Backend unter
**YForm → Setup** – auch dann, wenn ein Projekt eigene Typen mitbringt. Die vollständige Referenz
liegt in der mitgelieferten Dokumentation unter **YForm → Dokumentation**.

## Datensätze im Code

```php
use Yakamara\YForm\Manager\Dataset;

$news = Dataset::query('rex_yf_news')
    ->where('online', 1)
    ->orderBy('createdate', 'DESC')
    ->limit(10)
    ->find();

foreach ($news as $item) {
    echo $item->getValue('title');
}
```

## Eigene Feldtypen

Ein eigener Value-, Validate- oder Action-Typ ist eine Klasse mit dem passenden Attribut. YForm
findet sie über die Klassen-Discovery, eine Registrierung von Hand ist nicht nötig.

```php
namespace App\YForm\Value;

use Yakamara\YForm\Attribute\AsValue;
use Yakamara\YForm\Value\AbstractValue;

#[AsValue('mycolor')]
class MyColor extends AbstractValue
{
    public function enterObject()
    {
        // …
    }
}
```

Das Markup kommt aus Core-Fragmenten unter `fragments/yform/value/{backend,frontend}/`. Eigene
Fragmente im Projekt überschreiben die von YForm gelieferten; ein AddOn kann mit
`Yakamara\YForm\View\Fragment::addSearchPath()` einen eigenen Fragment-Pfad ergänzen.

## Unterschiede zu REDAXO 5

- **Kein `REX_YFORM_DATA` / `REX_YFORM_DATASET` in Templates und Modulen.** REDAXO 6 hat das
  REX_VAR-System entfernt; in einer Modulklasse fragt man die Daten direkt über das ORM ab. Einzige
  Ausnahme: in **E-Mail-Templates** funktioniert `REX_YFORM_DATA[field="…"]` unverändert weiter.
- **Kein Formbuilder-Modul.** REDAXO 6 hat keine Modultabelle und keine Modulverwaltung im Backend –
  Module sind PHP-Klassen im Projekt. Das Doku-Kapitel *Modul erstellen* zeigt ein lauffähiges
  Beispiel.
- **Keine REST-API.**
- **Klassennamen haben Namespaces.** Aus `rex_yform_manager_dataset` wird
  `Yakamara\YForm\Manager\Dataset`, aus `rex_yform` wird `Yakamara\YForm\YForm` und so weiter.
- **Eigene Feldtypen brauchen ein Attribut** (`#[AsValue('xyz')]`) statt der Namenskonvention
  `rex_yform_value_xyz`. Die *Typnamen* bleiben unverändert, bestehende Tabellen und
  Formular-Definitionen funktionieren also weiter.
- **`ytemplates` sind Core-Fragmente.** Projekteigene Templates müssen als Fragment neu angelegt
  werden.
- **MySQL läuft im Strict Mode.** REDAXO 5 verband sich mit `SQL_MODE=""`, REDAXO 6 nicht. Spalten,
  die YForm anlegt, sind deshalb `NULL`-fähig, und ein leeres Datum wird als `NULL` statt
  `0000-00-00` gespeichert. Daraus folgen drei Verhaltensänderungen:
    - **`number` akzeptiert das deutsche Dezimalkomma.** „2,50" wird zu `2.50` normalisiert. Unter
      REDAXO 5 hat MySQL „2,50" stillschweigend zu `2.00` abgeschnitten; im Strict Mode würde die
      ganze Zeile abgelehnt. Was auch nach der Normalisierung keine Zahl ist, wird `NULL` – wer das
      gemeldet haben will, ergänzt `validate|type|feldname|numeric`.
    - **Ein leeres `prio`-Feld heißt weiter „ans Ende".** Es landet aber nicht als `''` in der
      `INT`-Spalte, sondern als berechnete Position.
    - **Die Suchoption `(leer)` findet auch `NULL`.** Bisher verglich sie nur gegen `''`, was bei
      `NULL`-fähigen Spalten nie zutraf.

Aus REDAXO 5 übernommene Projekte bleiben unterstützt: eigene `rex_yform_value_*`-Klassen werden
weiterhin aufgelöst, R5-Templatenamen werden als Fragment-Kandidaten mitgeprüft, und `NOT NULL`
gesetzte Einstellungsspalten einer bestehenden Installation werden bei der Installation repariert.

## Dokumentation

Die vollständige Dokumentation liegt im AddOn unter **YForm → Dokumentation** und als Markdown in `docs/`.

## Tests

YForm bringt eine Test-Suite als Konsolenbefehle mit, die gegen die laufende Installation arbeitet:

```bash
php bin/console yform:test
```

Einzelne Bereiche lassen sich gezielt prüfen, etwa `yform:test:field-types`, `yform:test:relations`,
`yform:test:backend-pages` oder `yform:test:e2e`. `yform:test:doctor` prüft die Installation auf
Schema- und Konfigurationsprobleme.

## Lizenz

MIT
