# Einführung

YForm hat zwei Seiten: es erzeugt **Formulare** (typischerweise im Frontend) und es verwaltet
**Tabellen samt Datensätzen** im Backend. Beides greift ineinander — aus einer im Table Manager
angelegten Tabelle lässt sich der Code für das passende Formular direkt erzeugen.

## Formulare

Formulare sind selten trivial: Felder, Validierung, Fehlerausgabe, E-Mail-Versand, Speichern. YForm
beschreibt ein Formular in einer kompakten Zeilen-Notation (der *Pipe-Notation*) und übernimmt den
Rest.

```
text|vorname|Vorname|
text|name|Name|
email|email|E-Mail|
validate|empty|vorname|Bitte Vornamen angeben
submit|submit|Absenden
```

Jede Zeile ist ein Feld. Es gibt drei Sorten:

Sorte | Zweck | Beispiele
----- | ----- | ---------
**Value** | Eingabefelder und Ausgaben | `text`, `email`, `choice`, `upload`
**Validate** | prüft Values, bevor gespeichert oder gesendet wird | `empty`, `type`, `unique`
**Action** | läuft nach erfolgreicher Validierung | `db`, `email`, `redirect`

Die vollständige Referenz aller Feldtypen steht im Kapitel
[Formbuilder](?page=yform/docs&mdfile=07_formbuilder). Eine direkt aus dem Code erzeugte Kurzliste
aller installierten Typen findet sich unter *YForm › Setup* — die ist immer aktuell, auch wenn ein
Projekt eigene Feldtypen mitbringt.

## Tabellenverwaltung

Der [Table Manager](?page=yform/docs&mdfile=03_table_manager) erlaubt es, Datenbanktabellen im
Backend zusammenzustellen: Felder anlegen, Validierungen hinterlegen, Relationen zu anderen Tabellen
definieren. YForm legt die Tabelle an, pflegt das Schema und stellt eine vollständige
Datenverwaltung samt Liste, Suche, Historie sowie Import und Export bereit.

Auf die Datensätze greift man im Code über das [ORM](?page=yform/docs&mdfile=04_yorm) zu — `Dataset`,
`Collection` und `Query`.

## Ein Formular in einen Artikel bringen

In REDAXO 5 installierte YForm dafür ein fertiges Modul namens *YForm-Formbuilder* in die
Modultabelle. **In REDAXO 6 gibt es weder diese Tabelle noch eine Modulverwaltung im Backend** —
Module sind PHP-Klassen im Projekt. Das Kapitel [Modul erstellen](?page=yform/docs&mdfile=05_modul)
zeigt ein vollständiges, lauffähiges Beispiel.

## Was sich gegenüber REDAXO 5 geändert hat

Wer YForm aus REDAXO 5 kennt, sollte diese Punkte kennen:

- **Kein `REX_YFORM_DATA` / `REX_YFORM_DATASET` in Templates und Modulen.** REDAXO 6 hat das
  REX_VAR-System entfernt. In einer Modulklasse fragt man die Daten direkt über das ORM ab. Einzige
  Ausnahme: in **E-Mail-Templates** funktioniert `REX_YFORM_DATA[field="…"]` unverändert weiter, siehe
  [E-Mail-Templates](?page=yform/docs&mdfile=02_email).
- **Keine REST-API.** Das entsprechende Kapitel ist entfallen.
- **Klassennamen haben Namespaces.** Aus `rex_yform_manager_dataset` wird
  `Yakamara\YForm\Manager\Dataset`, aus `rex_yform` wird `Yakamara\YForm\YForm` und so weiter.
- **Eigene Feldtypen brauchen ein Attribut.** Statt der Namenskonvention `rex_yform_value_xyz`
  markiert man die Klasse mit `#[AsValue('xyz')]`. Die *Typnamen* bleiben unverändert, bestehende
  Tabellen und Formular-Definitionen funktionieren also weiter.
- **MySQL läuft im Strict Mode.** REDAXO 5 verband sich mit `SQL_MODE=""`, REDAXO 6 nicht. Spalten,
  die YForm für verwaltete Tabellen anlegt, sind deshalb `NULL`-fähig, und ein leeres Datum wird als
  `NULL` statt `0000-00-00` gespeichert. Daraus folgen drei Verhaltensänderungen:
    - **`number` akzeptiert das deutsche Dezimalkomma.** „2,50" wird zu `2.50` normalisiert, ebenso
      „1.234,56" und „1,234.56". Unter REDAXO 5 hat MySQL „2,50" stillschweigend zu `2.00`
      abgeschnitten; im Strict Mode würde die ganze Zeile abgelehnt. Eine Eingabe, die auch nach der
      Normalisierung keine Zahl ist, wird zu `NULL` — wer das gemeldet haben will, ergänzt
      `validate|type|feldname|numeric`.
    - **Ein leeres `prio`-Feld heißt weiter „ans Ende".** Es landet aber nicht mehr als `''` in der
      `INT`-Spalte, sondern als berechnete Position; direkt nach dem Speichern nummeriert YForm den
      Bereich ohnehin neu.
    - **Die Suchoption `(leer)` findet auch `NULL`.** Bisher verglich sie nur gegen `''`, was bei
      `NULL`-fähigen Spalten nie zutraf.

> **Hinweis**
> Diese Dokumentation wird auf GitHub gepflegt.
> [Ergänzungen oder Korrekturen](https://github.com/yakamara/yform) bitte dort als Issue oder Pull
> Request einreichen.
