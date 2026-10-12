# YForm (REDAXO 6, Neubau)

Tabellen, Felder, Layouts und Formulare sind Klassen im Projekt; in der Datenbank liegen nur Daten. Überblick
und API stehen in der `README.md`.

## Entwickeln und prüfen

- Das Repo enthält kein lauffähiges REDAXO. Getestet wird in einem REDAXO-6-Projekt, das `yakamara/yform` als
  Composer-Path-Repository einbindet. Dort `REX_MODE=dev` in `.env` setzen, sonst findet die Class Discovery neue
  `#[AsTable]`/`#[AsForm]`-Klassen erst nach `console cache:clear`.
- Nach Änderungen unter `assets/` im Projekt `console assets:sync` ausführen; Addon-Assets werden kopiert, nicht
  verlinkt.
- Vor jedem Commit: `vendor/bin/phpunit` und `vendor/bin/php-cs-fixer fix --using-cache=no`.
- Commit-Messages auf Englisch mit Conventional-Commit-Präfix (`feat:`, `fix:`, …).

## Konventionen im Code

- Feldklassen haben einen `final`-Konstruktor; konfiguriert wird über fluent Setter, Typ-Voreinstellungen gehören
  in `init()`. Eigenschaften, die Unterklassen setzen, als `public protected(set)` deklarieren —
  `private(set)` gilt nur in der deklarierenden Klasse und führt in Unterklassen zum Fatal Error.
- Views liegen als `Klasse.view.php` neben der Klasse und geben eine Closure zurück. `ViewRenderer` sucht entlang
  der Elternklassen, Projekte überschreiben per `ViewResolver::override()`.
- `HtmlAttributes` liefert keinen führenden Leerraum: `'<input ', $attributes, '>'`, nicht `'<input', …`.
- `I18n::msg()` escaped bereits: direkt ausgeben, nicht in `escape()` packen. Texte, die erst später escaped
  werden (Validierungsmeldungen, Button-Labels, `HtmlAttributes`-Werte), mit `I18n::rawMsg()` erzeugen.
- Assets in `YFormAddon::boot()` registrieren — in einer Seiten-Datei ist der Seitenkopf schon gerendert.
- Der Schema-Sync löscht nie implizit Spalten; Löschen nur über `yform:schema --drop` oder die Tabellenseite.
