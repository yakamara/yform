# YForm für REDAXO 6 (Neubau)

YForm verwaltet **Tabellen samt Datensätzen** im Backend und erzeugt **Formulare** fürs Frontend. Dieser Branch
ist ein Neubau: Tabellen, Felder, Layouts und Formulare sind **PHP-Klassen im Projekt**. In der Datenbank liegen
nur noch die Daten selbst. Es gibt keine `yform_table`- oder `yform_field`-Tabellen mehr, keine Pipe-Notation und
keine `objparams`.

## Voraussetzungen

- REDAXO 6
- PHP 8.5 oder neuer

## Eine Tabelle definieren

```php
namespace Project\Table;

use Yakamara\YForm\Attribute\AsTable;
use Yakamara\YForm\Field\{Checkbox, Choice, CreatedAt, Decimal, Media, Text, Textarea, UpdatedAt};
use Yakamara\YForm\Field\Relation\{BelongsTo, ManyToMany, OnDelete};
use Yakamara\YForm\Form\Layout\{Cell, Layout, Tab};
use Yakamara\YForm\Table\{ListConfig, Table};

#[AsTable('rex_product', label: 'Produkte', icon: 'fa-cube')]
final class ProductTable extends Table
{
    public function fields(): iterable
    {
        yield Text::new('name')->label('Name')->required()->maxLength(120);
        yield Text::new('sku')->label('Artikelnummer')->unique();
        yield Choice::new('status')->label('Status')->options(ProductStatus::class)->default(ProductStatus::Draft);
        yield BelongsTo::new('category_id')->label('Kategorie')->target(CategoryTable::class)->onDelete(OnDelete::Restrict);
        yield ManyToMany::new('labels')->label('Siegel')->target(LabelTable::class)->expanded();
        yield Decimal::new('price')->label('Preis')->precision(10, 2)->min(0);
        yield Checkbox::new('featured')->label('Startseite');
        yield Textarea::new('description')->label('Beschreibung');
        yield Media::new('image')->label('Bild')->types('jpg', 'png', 'webp');
        yield CreatedAt::create();
        yield UpdatedAt::create();
    }

    public function layout(Layout $layout): Layout
    {
        return $layout
            ->tab('Produkt', fn (Tab $t) => $t->row(Cell::field('name', 8), Cell::field('sku', 4))->row('status', 'category_id', 'price'))
            ->tab('Medien', fn (Tab $t) => $t->row('image'));
    }

    public function list(ListConfig $list): ListConfig
    {
        return $list->columns('id', 'name', 'status', 'price')->sortBy('name');
    }
}
```

Was dann automatisch passiert:

- **Datenbank:** `console migrate` legt die Tabelle an und ergänzt Spalten, Indizes und Pivot-Tabellen. Die
  Spaltenreihenfolge folgt der Feldreihenfolge. Spalten ohne Feld werden **nie** stillschweigend gelöscht,
  siehe [Schema](#schema).
- **Backend:** Jede Tabelle bekommt einen Navigationseintrag mit Liste ([Suche](#suche), Sortierung, Seiten),
  Anlegen/Bearbeiten/Löschen und Rechten pro Tabelle.
- **Code:** `ProductTable::query()`, `::find()`, `::create()` usw., siehe [Daten](#daten).

Neue Klassen findet REDAXO über die Class Discovery. Im Dev-Modus (`REX_MODE=dev`) sofort, sonst nach
`console cache:clear`.

## Feldtypen

Die Klasse *ist* der Feldtyp. Konfiguriert wird fluent, der Konstruktor ist für alle gleich:
`Text::new('name')->label('Name')`.

| Klasse | Spalte | PHP-Wert | Hinweise |
|---|---|---|---|
| `Text` | `varchar(n)` | `?string` | `maxLength()`, `placeholder()` |
| `Textarea` | `text`/`mediumtext` | `?string` | `rows()`, `large()` |
| `Email`, `Url` | `varchar`/`text` | `?string` | Formatprüfung |
| `Password` | `varchar(255)` | Hash | `hashed()` (Standard), leere Eingabe behält den Wert |
| `Integer` | `int`/`bigint` | `?int` | `min()`, `max()`, `unsigned()`, `big()` |
| `Decimal` | `decimal(p,s)` | numerischer String | Dezimalkomma wird akzeptiert |
| `Checkbox` | `tinyint(1)` | `bool` | nie `NULL` |
| `Choice` | `varchar` / JSON in `text` | `?string` / `list<string>` | Optionen als Array, Enum oder Closure; `multiple()`, `expanded()` |
| `Date`, `DateTime` | `date`/`datetime` | `?DateTimeImmutable` | native Picker, strikte Prüfung (kein 31.02.) |
| `Time` | `time` | `?string` (`HH:MM`) | |
| `Hidden` | `varchar` | `?string` | Frontend |
| `Media` | `varchar` / JSON | Dateiname(n) | Medienpool-Widget; Löschen verwendeter Dateien wird verhindert |
| `Article` | `int` / JSON | Artikel-ID(s) | Linkmap-Widget |
| `Uuid` | `varchar(36)` | `string` | wird beim ersten Speichern erzeugt, eindeutig |
| `CreatedAt`, `UpdatedAt` | `datetime` | `DateTimeImmutable` | automatisch |
| `CreatedBy`, `UpdatedBy` | `varchar` | Login | automatisch |
| `Markup` | – | – | statischer Inhalt zwischen Feldern |
| `Relation\BelongsTo` | `int unsigned` + Index | `?int` | `target()`, `filter()`, `onDelete()` |
| `Relation\ManyToMany` | Pivot-Tabelle | `list<int>` | `pivot()`, `expanded()` |
| `Relation\HasMany` | – | `list<int>` | Gegenstück zu `BelongsTo`; mit `inline()` werden die Datensätze direkt in der Maske bearbeitet, `sortField()` macht sie sortierbar |

Diese Optionen haben alle Felder: `label()`, `notice()`, `required()`, `default()`, `readonly()`, `listed()`,
`filterable()`, `sortable()`, `cloneable()`, `unique()`, `rule()` und `attributes()`.

**Inline-Relationen** (`HasMany` mit `inline()`) bearbeiten die verknüpften Datensätze direkt in der Maske. Mit
`sortField('prio')` bekommt jede Zeile Buttons zum Verschieben; die Position wird in diesem Integer-Feld der
Zieltabelle gespeichert (1, 2, …) und gilt auch beim Lesen über `related()`. Inline-Relationen lassen sich
verschachteln, etwa Links eines Beitrags mit Kommentaren je Link:

```php
// PostTable
yield HasMany::new('links')->target(PostLinkTable::class, 'post_id')->inline()->sortField('prio');
// PostLinkTable
yield Integer::new('prio')->readonly()->listed(false);
yield HasMany::new('comments')->target(PostLinkCommentTable::class, 'link_id')->inline()->sortField('prio');
```

Ausgenommen ist eine Relation, die auf die eigene Tabelle zeigt (das wäre eine endlose Verschachtelung).

**Eigene Feldtypen** sind Unterklassen im Projekt. Überschrieben wird, was abweicht: `columnType()`, `fromInput()`,
`fromDb()`, `toDb()`, `validate()`, `listValue()` usw. Das Markup liegt in einer View-Datei neben der Klasse
(`MyField.view.php`, gibt eine Closure zurück). Ohne eigene View wird die der Elternklasse verwendet.

## Suche

Die Suche wird pro Feld mit `filterable()` eingeschaltet und ist standardmäßig aus. Jedes freigeschaltete Feld
bekommt ein eigenes, zum Typ passendes Suchfeld; mehrere Felder werden mit UND verknüpft. Die Suchfelder stehen
als Spalte links neben der Liste und laufen beim Scrollen mit. Hat eine Tabelle kein solches Feld, gibt es keine
Suche.

```php
yield Text::new('title')->label('Titel')->filterable();
yield BelongsTo::new('author_id')->label('Autor')->target(AuthorTable::class)->filterable();
yield CreatedAt::create()->label('Erstellt')->filterable();
```

| Feldtyp | Suchfeld |
|---|---|
| Text, Textarea, E-Mail, URL, UUID, Media, `CreatedBy`/`UpdatedBy` | Text: Teilbegriff, `*` als Platzhalter (`tee*`), `"…"` exakt, `""` leer, `!` davor verneint |
| Integer, Decimal | Bereich von–bis, Decimal auch mit Dezimalkomma |
| Date, DateTime, `CreatedAt`/`UpdatedAt` | Datumsbereich; bei DateTime zählt jeweils der ganze Tag |
| Time | Uhrzeitbereich |
| Checkbox | alle / ja / nein |
| Choice | Mehrfachauswahl; bei `multiple()` trifft jede gewählte Option |
| BelongsTo, ManyToMany | Mehrfachauswahl der Zieldatensätze, auch solcher, die `filter()` bei der Eingabe ausblendet |
| Password, Article, HasMany | keine Detailsuche |

Die Filter stehen als `filter[feld]` in der URL. Sortierung, Seitenwechsel und der Rückweg aus der Maske
behalten sie.

**Eigene Suchfelder:** `searchFilter()` überschreiben und eine Unterklasse von `Search\Filter` zurückgeben
(`parse()` normalisiert die Eingabe, `apply()` schränkt die Query ein, das Markup liegt in
`MyFilter.view.php`). Für die üblichen Fälle reichen `TextFilter`, `RangeFilter`, `ChoiceFilter` und
`BooleanFilter`:

```php
public function searchFilter(): ?Filter
{
    return new ChoiceFilter($this, ['de' => 'Deutschland', 'at' => 'Österreich']);
}
```

## Validierung

- Pflicht und Format prüft das Feld selbst (`required()`, E-Mail, Zahl, Datum, Relation existiert).
- Weitere Regeln hängen als Objekte am Feld: `->rule(new Length(max: 20), new Regex('/^[A-Z]/'))`. Mitgeliefert
  sind `Length`, `Range`, `Regex`, `SameAs`, `Unique`, `PasswordPolicy` und `Callback`.
- Feldübergreifende Prüfungen gehören in `Table::validate(Record $record, Errors $errors)` bzw.
  `FormDefinition::validate(array $values, Errors $errors)`.

## Daten

```php
$products = ProductTable::query()
    ->where('status', 'active')
    ->where('price', '>=', 10)
    ->whereAny(fn (Query $q) => $q->whereLike('name', '%Tee%')->whereJsonContains('tags', 'bio'))
    ->orderBy('name')
    ->paginate(page: 1, perPage: 20);

$product = ProductTable::create(['name' => 'Mate', 'category_id' => 3]);
$product->save();                       // wirft ValidationException mit $e->errors
$product->related('category_id');       // ?Record
$product->related('labels');            // Collection
```

- Spaltennamen werden gegen die Definition geprüft, Werte immer gebunden. Relationswerte ohne eigene Spalte
  werden erst beim Zugriff geladen.
- Hooks an der Tabelle: `beforeSave`, `afterSave`, `beforeDelete` (Exception verhindert das Löschen) und
  `afterDelete`.
- Extension Points: `YFORM_RECORD_VALIDATE`, `YFORM_RECORD_SAVE`, `YFORM_RECORD_SAVED`, `YFORM_RECORD_DELETE`
  und `YFORM_RECORD_DELETED`.
- **Klonen:** `$record->replicate()` liefert eine ungespeicherte Kopie; im Backend steht dafür „Klonen“ im
  Aktionsmenü der Liste und öffnet die Maske für einen neuen Datensatz mit den kopierten Werten. Zeitstempel,
  `CreatedBy`/`UpdatedBy`, `Uuid` und Passwörter starten leer, Inline-`HasMany`-Zeilen werden als neue Datensätze
  kopiert, `ManyToMany` übernimmt die Zuordnungen. Felder mit `cloneable(false)` starten mit ihrem Default; eigene
  Feldtypen können `cloneValue()` überschreiben. Eindeutige Felder (`unique()`) werden kopiert und müssen vor dem
  Speichern geändert werden.
- Eine eigene Model-Klasse wird mit `#[AsTable(model: Product::class)]` registriert. Sie erbt von `Record` und kann
  typisierte Property Hooks anbieten.

## Layout und Designer

Das Layout einer Maske besteht aus Tabs, Abschnitten (optional mit Überschrift) und Zeilen mit Zellen (Breite
1–12). Es kann auf zwei Wegen entstehen:

1. **Im Code** über `layout()` an der Tabelle bzw. am Formular.
2. **Im Designer** (YForm → Tabellen/Formulare → Designer), per Drag & Drop. Das Ergebnis wird als
   `ProductTable.layout.json` **neben der Klasse** gespeichert und mit ihr versioniert und deployt. Die Datei hat
   Vorrang vor dem Code-Layout. „Auf Code-Layout zurücksetzen“ löscht sie wieder.

Felder, die in keinem Layout vorkommen, werden automatisch angehängt, ein neues Feld geht also nie verloren.

## Frontend-Formulare

```php
#[AsForm('contact', label: 'Kontaktformular', module: true)]
final class ContactForm extends FormDefinition
{
    public function fields(): iterable
    {
        yield Text::new('name')->label('Name')->required();
        yield Email::new('email')->label('E-Mail')->required();
        yield Textarea::new('message')->label('Nachricht')->required();
        yield Checkbox::new('copy')->label('Kopie an mich');
    }

    public function actions(): iterable
    {
        yield new SaveRecord(ContactTable::class);
        yield new SendMail(to: 'team@example.org', subject: 'Anfrage #{id} von {name}', replyTo: '{email}');
        yield new SendMail(to: '{email}', subject: 'Danke!', template: __DIR__ . '/mail/confirmation.php')
            ->when(fn (Submission $s) => $s->value('copy'));
        yield new ShowMessage('<strong>Danke, {name}!</strong>');
    }
}
```

**Einbinden:**

- **Modul:** YForm bringt das Modul **„YForm: Formular“** mit. Dort erscheinen alle Formulare, die mit
  `module: true` angemeldet sind; der Redakteur wählt nur das Formular aus. Im Backend zeigt der Block einen
  Platzhalter, ausgeführt wird das Formular nur im Frontend.
- **Code:** In Templates und eigenen Modulen genügt `echo Forms::get('contact')->run();`.

**Aktionen** laufen nach einem gültigen Absenden in der angegebenen Reihenfolge. Jede Aktion lässt sich mehrfach
anhängen und mit `->when(fn (Submission $s) => …)` an eine Bedingung knüpfen.

| Aktion | YForm 5 | Zweck |
|---|---|---|
| `SaveRecord` | `db` | schreibt in eine Tabelle, danach `{id}` und `$submission->record` verfügbar |
| `SendMail` | `email`, `tpl2email` | Mail mit Platzhaltern, `template:`-Datei, `cc`, `bcc`, `replyTo`, Anhänge |
| `ShowMessage` | `showtext` | eigene Danke-Meldung (HTML, Werte werden escaped) |
| `Redirect` | `redirect` | Weiterleitung auf Artikel-ID oder URL |
| `Log` | `logger` | Eintrag im Systemlog |
| `Callback` | `callback` | beliebiger Code, z. B. Newsletter-Anmeldung |

- **Platzhalter:** `{feld}` wird durch den Textwert des Feldes ersetzt (Choice-Label, formatiertes Datum usw.),
  `{id}` durch die ID des gespeicherten Datensatzes.
- **Mail-Templates** sind PHP-Dateien, die eine Closure zurückgeben:
  `return static function (Submission $s): void { ?>…<?php };`. Ihre Ausgabe wird der HTML-Body, der
  Text-Teil der Mail entsteht automatisch.
- **Eigene Aktionen** erben von `Action` und implementieren `execute(Submission $submission)`.
- **Schutz:** CSRF-Schutz und ein Honeypot gegen Bots sind eingebaut.
- **Fehler:** Scheitert eine Aktion, wird das Formular mit Fehlermeldung erneut angezeigt. Fehler aus der Tabelle,
  etwa bei der Eindeutigkeit, erscheinen am passenden Feld.

## Themes

Im Backend wird immer Bootstrap 3 verwendet. Frontend-Formulare nutzen standardmäßig `Theme::bootstrap5()`. Das
lässt sich im Projekt-Boot mit `Forms::$defaultTheme = Theme::plain();` ändern oder pro Formular über `theme()`.
Für eigenes Markup überschreibst du einzelne Views, ohne die Klassen anzufassen:

```php
ViewResolver::override(FieldGroup::class, __DIR__ . '/views/field-group.php');
ViewResolver::override(Text::class, __DIR__ . '/views/text.php');
```

## Rechte

Admins dürfen alles. Für Redakteure gibt es zwei Rollenrechte mit Tabellenauswahl: **Tabellen ansehen** und
**Tabellen bearbeiten**. `ListConfig::filter()` schränkt ein, welche Datensätze eine Liste zeigt und welche
bearbeitet werden dürfen.

## Schema

- `console migrate` gleicht alle Tabellen an, ohne etwas zu löschen.
- `console yform:schema` zeigt die Unterschiede. Mit `--apply` werden sie angewendet, mit
  `--drop=rex_product.alte_spalte` wird zusätzlich eine Spalte ohne Feld gelöscht.
- Dieselben Informationen samt Schaltfläche zeigt die Backend-Seite YForm → Tabellen.

## Bewusst weggelassen

| Früher | Stattdessen |
|---|---|
| `yform_table`/`yform_field`, Table- und Field-Editor | Klassen im Projekt |
| Pipe-/PHP-Notation, `objparams` | `FormDefinition`-Klassen |
| Value `php`, Action `php`/`html` (`eval`) | eigene Klassen, `Callback`-Aktion |
| `emptyname`, `hashvalue`, `encrypt_value`, `generate_key`, `index` | `Password`, `Uuid`, Queries |
| `createdb`, `create_table`, `manage_db` | Schema-Sync |
| CSV-IDs in Relationsspalten, Relationstypen 0–5 | `BelongsTo`, `ManyToMany` (Pivot), `HasMany` |
| `google_geocode`, `signature`, `be_table` | bei Bedarf als eigene Feldklassen oder Addon |
| Tablesets (JSON) | die Klassen sind die Definition |
| jQuery-Plugins (daterangepicker, inputmask) | native Eingabefelder |
| eigenes Testframework | PHPUnit (`vendor/bin/phpunit`) |

## Noch offen

- Upload-Feld (Dateien außerhalb des Medienpools)
- Sortierfeld mit Drag & Drop in der Liste (Ersatz für `prio`)
- Filter für Redakteure (gespeicherte Filter pro Rolle/Liste)
- Historie, CSV-Import/-Export, Mehrfachbearbeitung
- Popup-Auswahl für große Relationstabellen
- Anpassung von ycom an die neue API

## Entwicklung

```bash
composer install
vendor/bin/phpunit
vendor/bin/php-cs-fixer fix
```
