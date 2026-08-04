# Modul erstellen

Um ein YForm-Formular in einen Artikel zu bekommen, braucht es ein Modul. In REDAXO 5 installierte
YForm dieses Modul auf Knopfdruck in die Tabelle `rex_module`. **REDAXO 6 kennt weder diese Tabelle
noch eine Modulverwaltung im Backend**: Module sind PHP-Klassen, die aus dem `src/`-Verzeichnis des
Projekts gefunden werden. Dieses Kapitel zeigt ein vollständiges Modul, mit dem sich beliebige
YForm-Formulare in Artikeln platzieren lassen.

## Wie ein Modul in REDAXO 6 aussieht

Ein Modul ist eine Klasse, die

* von `Redaxo\Core\Content\Module` erbt,
* mit `#[AsModule('schlüssel', 'Anzeigename')]` markiert ist,
* die beiden Methoden `input()` (Eingabemaske im Backend) und `output()` (Ausgabe im Frontend)
  implementiert.

Beide bekommen den `ArticleSlice` übergeben. Die Werte des Slices liest man mit `$slice->getValue(1)`
bis `getValue(20)`; die Eingabefelder heißen wie gewohnt `REX_INPUT_VALUE[1]` … `REX_INPUT_VALUE[20]`.
Für Medien und Links gibt es `getMedia()`, `getMediaList()`, `getLink()`, `getLinkList()`.

Es gibt **keine Registrierung** — die Klasse an der richtigen Stelle abzulegen genügt. Nach dem
Anlegen einmal den Cache leeren:

```bash
php bin/console cache:clear
```

## Das Formular-Modul

Ablegen unter `src/Module/YFormModule.php` (Namespace entsprechend dem PSR-4-Prefix des Projekts,
in der Standard-Projektvorlage ist das `Project\`):

```php
<?php

namespace Project\Module;

use Override;
use Redaxo\Core\Content\ArticleSlice;
use Redaxo\Core\Content\AsModule;
use Redaxo\Core\Content\Module;
use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\Language\Language;
use Yakamara\YForm\YForm;

use function Redaxo\Core\View\escape;

/**
 * Gibt ein YForm-Formular aus einer Pipe-Notation-Definition aus.
 *
 *   value1 = Formular-Definition
 *   value2 = Zieltabelle für die `db`-Action, leer = nicht speichern
 *   value3 = "1" → Debug-Ausgabe
 */
#[AsModule('yform_form', 'YForm: Formular')]
final class YFormModule extends Module
{
    #[Override]
    public function input(ArticleSlice $slice): string
    {
        $definition = escape((string) $slice->getValue(1));
        $table = escape((string) $slice->getValue(2));
        $debug = '1' === $slice->getValue(3) ? ' checked' : '';

        return <<<HTML
            <div class="rex-form-group form-group">
                <label for="yform-definition">Formular-Definition</label>
                <textarea class="form-control" id="yform-definition" rows="12"
                          name="REX_INPUT_VALUE[1]">{$definition}</textarea>
                <p class="help-block">
                    Pipe-Notation, eine Zeile je Feld. Den Code einer bestehenden Tabelle liefert
                    <em>YForm &rsaquo; Table Manager &rsaquo; Felder editieren &rsaquo; Formular-Code</em>.
                </p>
            </div>
            <div class="rex-form-group form-group">
                <label for="yform-table">Zieltabelle</label>
                <input class="form-control" type="text" id="yform-table"
                       name="REX_INPUT_VALUE[2]" value="{$table}">
                <p class="help-block">Leer lassen, wenn die Eingaben nicht gespeichert werden sollen.</p>
            </div>
            <div class="rex-form-group form-group checkbox">
                <label><input type="checkbox" name="REX_INPUT_VALUE[3]" value="1"{$debug}> Debug-Ausgabe</label>
            </div>
            HTML;
    }

    #[Override]
    public function output(ArticleSlice $slice): string
    {
        $definition = trim((string) $slice->getValue(1));

        if ('' === $definition) {
            return '';
        }

        $yform = new YForm();
        $yform->setDebug('1' === $slice->getValue(3));

        // Zurück auf den Artikel senden, in dem der Slice steckt, damit das Formular
        // seine eigenen Fehlermeldungen wieder anzeigen kann.
        $yform->setObjectparams(
            'form_action',
            Url::article($slice->articleId, Language::getCurrentId()),
        );

        // Pro Slice ein eigener Formularname, damit sich zwei Formulare in einem
        // Artikel nicht die Feldnamen teilen.
        $yform->setObjectparams('form_name', 'yform_' . $slice->id);

        $yform->setFormData($definition);

        $table = trim((string) $slice->getValue(2));
        if ('' !== $table) {
            $yform->setObjectparams('main_table', $table);
            $yform->setActionField('db', [$table]);
        }

        return $yform->getForm();
    }
}
```

Danach steht im Artikel unter *Modul hinzufügen* der Eintrag **YForm: Formular** bereit.

## Ausprobieren

In die Formular-Definition zum Beispiel:

```
text|vorname|Vorname|
text|name|Name|
email|email|E-Mail|
validate|empty|vorname|Bitte Vornamen angeben
submit|submit|Absenden
```

Das Frontend rendert daraus ein Bootstrap-Formular. Wird es leer abgeschickt, erscheint
„Bitte Vornamen angeben“ am Feld `vorname`, und das Formular wird erneut angezeigt.

Trägt man in *Zieltabelle* eine mit dem Table Manager angelegte Tabelle ein, z. B. `rex_yf_kontakt`,
landen die Eingaben dort als Datensatz. Die Feldnamen der Definition müssen dann den Spaltennamen der
Tabelle entsprechen — genau das liefert *Formular-Code* im Table Manager.

## Zwei wichtige Details

**Formularname pro Slice.** YForm benennt seine Felder nach dem Schema
`FORM[<form_name>][<feldname>]`. Ohne einen eindeutigen `form_name` würden zwei Formulare im selben
Artikel dieselben Feldnamen benutzen und sich gegenseitig überschreiben. Die Slice-ID ist dafür der
naheliegende Schlüssel.

**`form_action` explizit setzen.** Ohne Angabe sendet YForm an `index.php`. Im Frontend soll das
Formular aber auf seinen eigenen Artikel zurückgehen, sonst gehen Fehlermeldungen verloren.

## Varianten

**Formular fest im Modul verdrahten.** Wenn das Formular nicht redaktionell änderbar sein soll,
entfällt die Eingabemaske; `input()` gibt dann einen Hinweistext zurück und `output()` enthält die
Definition direkt:

```php
$yform->setFormData(<<<'YFORM'
    text|name|Name|
    email|email|E-Mail|
    textarea|nachricht|Nachricht|
    validate|empty|email|Bitte E-Mail angeben
    validate|type|email|email|Keine gültige E-Mail-Adresse
    submit|submit|Absenden
    YFORM);
```

**Statt einzelner Felder eine E-Mail versenden.** Dafür eine Action ergänzen — entweder direkt:

```php
$yform->setActionField('email', ['absender@example.com', 'empfaenger@example.com', 'Betreff', 'Text']);
```

… oder über ein [E-Mail-Template](?page=yform/docs&mdfile=02_email), was flexibler ist:

```php
$yform->setActionField('tpl2email', ['kontaktformular', 'empfaenger@example.com']);
```

**Nach dem Absenden weiterleiten:**

```php
$yform->setActionField('redirect', ['12']); // Artikel-ID der Dankeseite
```

Alle verfügbaren Actions stehen im Kapitel
[Formbuilder](?page=yform/docs&mdfile=07_formbuilder#actions).

## Kein REX_YFORM_DATA mehr

In REDAXO 5 konnte man mit `REX_YFORM_DATASET[…]` in Modulen und Templates auf Datensätze zugreifen.
REDAXO 6 hat das REX_VAR-System entfernt. Der Ersatz ist das ORM, direkt in der Modulklasse:

```php
use Yakamara\YForm\Manager\Dataset;

$beitraege = Dataset::query('rex_yf_post')
    ->where('status', 1)
    ->orderBy('createdate', 'desc')
    ->limit(0, 10)
    ->find();

foreach ($beitraege as $beitrag) {
    echo escape($beitrag->getValue('title'));
}
```

Mehr dazu im Kapitel [YOrm](?page=yform/docs&mdfile=04_yorm).
