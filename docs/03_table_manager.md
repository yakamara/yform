# Table Manager (PlugIn)

> **Hinweis:**
> Plugins erweitern YForm und können optional aktiviert werden.

## Einführung

Der Table Manager in YForm dient zum Erstellen und Bearbeiten von Datenbanktabellen sowie zum Verwalten von tabellarischen Daten innerhalb von Redaxo.

> Hinweis: Der Table Manager ist nicht für den Zugriff aller Redaxo-Datenbanktabellen, z. B. `rex_article` gedacht. Um direkt auf die Tabellen einer Redaxo-Installation zuzugreifen, gibt es das [Adminer-Addon von Friends Of Redaxo](https://github.com/FriendsOfREDAXO/adminer). Adminer ist wie PHPMyAdmin eine Webanwendung zur Administration von Datenbanken.

### Erste Schritte

Im Wesentlichen sind folgende Schritte notwendig, um mit dem Table Manager zu starten:

1. [Tabelle im Table Manager erstellen](#tabellen-und-optionen).
2. [Felder der neuen Tabelle hinzufügen](#feldtypen). 
3. Datensätze in die Felder der neuen Tabelle eintragen.

> Tipp für Neulinge: Alternativ zu Schritt 1 und 2 kann auch ein vorkonfiguriertes Tableset importiert werden, das bereits Tabelle und Felder enthält. Eine Anleitung mit Muster-Tablesets für Kontaktformular, Team und Projekte gibt es im Abschnitt [Tableset importieren](#tableset-importieren-exportieren)

### Aufbau des Table Manager

Der Table Manager wird im Menü über `Addons > YForm > Table Manager` aufgerufen. Dort lassen sich Tabellen und deren Eingabe-Felder hinzufügen. Alle Tabellen, die auf `aktiv` gestellt sind, werden im Menü über `Tabellen > Name_Der_Tabelle` erreicht. Dort lassen sich die Datensätze der jeweiligen Tabelle bearbeiten.

### Zweck des Table Manager

Der Table Manager wird üblicherweise dann eingesetzt, wenn in Redaxo tabellarische Daten erzeugt, verwaltet oder aufgezeichnet werden müssen, zum Beispiel:

* Archivierung aller Anfragen eines Kontaktformulars
* Archivierung aller Bestellungen eines Bestellformulars
* Verwaltung von Kursen, Terminen und Verstaltungen
* Verwaltung von News einschließlich zugehöriger Eigenschaften (Kategorien, Tags, ...)
* Verwaltung von Produkten einschließlich zugehöriger Eigenschaften (Größen, Preis, ...) 

Außerdem kann der Table Manager anhand einer Table Manager-Tabelle den Formular-Code 
- für das YForm Formbuilder-Modul erzeugen, 
- für die PHP-Variante von YForm vorbereiten und
- die Feld-Platzhalter für ein E-Mail-Template auflisten.

Die Daten können dann in Modulen und Addons im Frontend und Backend verwendet werden.

### Direkte Ausgabe des Formulars

Das Formular lässt sich ohne Umwege mit den hinterlegten Feldern des Table Managers ausgeben. Alternativ lässt sich in der Felddefinition jeder Table Manager-Tabelle direkt auslesen und um weitere Felder und Actions ergänzen:

```php
$dataset = rex_yform_manager_dataset::create('rex_my_yform_table');
$yform = $dataset->getForm();
$yform->setObjectparams('form_action', rex_getUrl(rex_article::getCurrentId()));
$yform->setActionField('showtext', array('',"Thank you."));
echo $dataset->executeForm($yform);
```

### Ausgabe der Table Manager-Daten im Frontend

Daten im Table Manager werden in der SQL-Datenbank abgelegt, die bei der Redaxo-Installation angegeben wurde. Die einfachste Möglichkeit ist daher, über das [rex_sql-Objekt](https://github.com/redaxo/redaxo/wiki/Aenderungen-in-REDAXO-5#rex_sql) die Daten auszulesen.

Über YOrm lassen sich die YForm-Daten auslesen, abfragen, filtern und verarbeiten.

> Tipp: Um z. B. jede News oder jedes Produkt statt via GET-Parameter über eine eigene URL aufzurufen, obwohl kein eigener Artikel existiert, kann das [URL-Addon von Thomas Blum](https://github.com/tbaddade/redaxo_url) verwendet werden.

### Widget Ausgabe im Backend

Mit folgenden Widgets kann man eine Auswahl für den Redakteur im Backend (z.B. in einem Modul) zur Verfügung stellen:

Modul Eingabe:

```
REX_YFORM_TABLE_DATA[id=1 table="tablename"]
REX_YFORM_TABLE_DATA[id=1 table="tablename" widget=1]
REX_YFORM_TABLE_DATA[id=1 table="tablename" field=name widget=1]
REX_YFORM_TABLE_DATA[id=1 table="tablename" field=name widget=1 multiple=1]
 ```
 Modul Ausgabe:
 
 ```
REX_YFORM_TABLE_DATA[1]
 ```
Als Werte werden die IDs der Datensätze ausgegeben. Diese können dann beliebig weiter verarbeitet werden.

### Backups der Table Manager-Tabellen

Datensätze können manuell exportiert werden, sofern die Tabelle im Table Manager konfiguriert ist. Außerdem lässt sich über das Cronjob-Addon ein regelmäßiger Datenbank-Export einrichten.

Und es gibt ein neues History-Plugin, mit dem Änderungen an Datensätzen nachverfolgt werden können.

### Für Entwickler: Table Manager erweitern

Es ist möglich, eigene Feldtypen zu definieren und dem Table Manager hinzuzufügen. Das Geo-Plugin für YForm z. B. ist eine Möglichkeit, sich mit der Erweiterung von YForm und dem Table Manager vertraut zu machen.


## Tabellen und Optionen
 
Um Tabellen im Table Manager zu bearbeiten, gibt es drei verschiedene Möglichkeiten:

* eine neue [Datenbank-Tabelle erstellen](#tabelle-erstellen).
* eine vorhandene Datenbank-Tabelle in den [Table Manager migrieren](#tabelle-migrieren)
* eine neue Datenbank-Tabelle einschließlich aller benötigten Felder anhand eines [Tablesets imporiteren](#tableset-importieren-exportieren).

### Tabelle erstellen

So fügt man dem Table Manager eine neue Tabelle hinzu:

* Im Menü auf YForm klicken,
* Table Manager öffnen,
* über das +-Symbol eine neue Tabelle hinzufügen.

Anschließend können der Tabelle folgende Optionen zugewiesen werden:

Option | Erläuterungen
------ | ------
Priorität | Legt fest, an welcher Position sich die neue Tabelle zwischen bestehenden Tabellen einreiht, z. B. im Menü.
Name | Der Name der Datenbank-Tabelle, wie sie in MySQL heißt und über SQL-Querys aufgerufen wird.
Bezeichnung | Der Name der Tabelle, wie sie im Menü aufgelistet wird.
Beschreibung | Informationen zur Tabelle, zum Beispiel eine Kurzanleitung für den Kunden oder Informationen über den Aufbau der Tabelle als Merkhilfe. Die Beschreibung wird angezeigt beim direkten Aufruf einer Tabelle.
aktiv | Legt fest, ob die Tabelle im Hauptmenu angezeigt wird oder nicht.
Datensätze pro Seite | Legt fest, ab wie vielen Datensätzen die Tabellen-Übersicht in Seiten unterteilt wird.
Standardsortierung Feld | Legt fest, nach welchem Feld die Tabellen-Übersicht zu Beginn sortiert wird.
Standardsortierung Richtung |  Legt fest, ob das gewählte Feld auf- oder absteigend sortiert wird.
Suche aktiv | Zeigt die Schaltfläche `Datensatz suchen` in der Tabellen-Übersicht an.
In der Navigation versteckt | Legt fest, ob die Tabelle auch im Menü angezeigt wird, oder nur im Table Manager. (Hilfreich, um z. B. relationale Datenbank-Tabellen auszublenden.)
Export der Daten erlauben | Zeigt die Schaltfläche `Datensätze exportieren` in der Tabellen-Übersicht an.
Import von Daten erlauben | [Zeigt die Schaltfläche `Datensätze importieren` in der Tabellen-Übersicht an.

> **Hinweis:**
> Solange die Tabelle über keine Felder verfügt, kann hier nur `id` ausgewählt werden. Man kann zunächst die Standard-Sortierung nach id-Feld belassen, dann neue Felder hinzufügen und anschließend die Sortierung der Tabelle neu festlegen. Zum Beispiel nach Name, Datum oder den selbst festgelegten Feldern.

> **Hinweis:** 
> Alternativ kann auch ein vorhandenes Tableset importiert werden.
In diesem Fall wird durch den abschließenden Klick auf `hinzufügen` die Datenbank-Tabelle erstellt.

> **Tipp:** 
> Wenn die Datenbank über Import/Export oder über einen Backup-Crobjob gesichert werden soll, sollte die Tabelle den Präfix `rex_` behalten. Zur besseren Übersicht empfiehlt es sich, der Tabelle einen eigenen Projekt-Präfix zu geben, z. B. `rex_kunde_projekte` oder `rex_kunde_mitarbeiter`.


### Tabelle migrieren

Der Migrationsmanager erstellt aus einer vorhandenen Tabelle eine, die über den Table Manager verwaltet werden kann. Dazu ist in der Tabelle ein Autoinkrement-Feld mit dem Namen `id` nötig. Ohne dieses Feld funktioniert der Tablemanager nicht.

So migrieren Sie eine vorhandene Tabelle in den Table Manager:

* Im Menü auf YForm klicken,
* Table Manager öffnen,
* den Button `Tabelle migrieren` anklicken,
* vorhandene Datenbank-Tabelle auswählen und
* mit `Abschicken` bestätigen.

> **Hinweis:** Falls die Datenbank-Tabelle über kein id-Feld verfügt, kann man die Option `id-Feld konvertieren falls nicht vorhanden` benutzen. Dabei wird jenes Feld in `id` umbenannt, das als `PRIMARY` und `AUTO_INCREMENT` eingetragen ist.

### Tableset importieren / exportieren

Seit Redaxo 5 gibt es eine neue Möglichkeit, eine Tabelle im Table Manager zu erstellen: den Import via Tableset (JSON).

1. Im Menü auf YForm klicken,
2. Table Manager öffnen,
3. die Schaltfläche `Tableset importieren` anklicken,
4. JSON-Datei auswählen und 
5. mit Abschicken bestätigen.

Anschließend wird die Tabelle einschließlich aller in der JSON-Datei hinterlegten Felder, Parameter und Standardwerten importiert.

Mit diesen Demo-Tablesets (JSON) kann man direkt starten:
- [Kontaktformular](demo_tableset-rex_yf_messages.json)
- [Mitarbeiter / Team](demo_tableset-rex_yf_staff.json)
- [Projekte / Referenzen](demo_tableset-rex_yf_projects.json)

### Tabelle importieren

Um eine fremde Datenbank-Tabelle zu importieren oder Datensätze einer Tabelle zu importieren, muss zunächst
* eine Tabelle erstellt oder migriert werden.  
* Anschließend im Redaxo-Hauptmenü die entsprechende Tabelle öffnen. 
* Dort neben dem Label `Tabelle` die Schaltfläche `importieren` anklicken und den Instruktionen folgen.

> Der Button `importieren` ist nur dann sichtbar, wenn die Tabelle beim Erstellen oder migrieren die Option `Import von Daten erlauben` aktiviert ist.

> **Hinweis**: Seit YForm 2.0 wird beim Import jeder Datensatz beim Importieren validiert. Ungültige Datensätze werden nicht in die Datenbank importiert.

## Feldtypen

### be_link

Ein Redaxo-Feld, um einen <b>Redaxo-Artikel</b> auszuwählen.

> **Wert in der Datenbank**
> id des Redaxo-Artikels, z. B. `1`, `5`, `20`

Option | Erläuterung
------ | ------
Priorität | Ordnet das Feld zwischen anderen Feldern in der Tabelle ein.
Name | Name des Felds in der Datenbank, z. B. `article_id`, `page_id`, `link_id`
Bezeichnung | Name des Felds, wie er im Frontend oder Backend angezeigt wird, z. B. `Redaxo-Artikel`, `Seite`, `Link`
Notiz | Hinweis unterhalb des Feldes, um dem Nutzer zusätzliche Instruktionen zur Eingabe mitzugeben. 
In der Liste verstecken | Versteckt das Feld in der Tabellen-Übersicht.
Als Suchfeld aufnehmen | Zeigt das Feld in den Suchoptionen an, sofern die Option "Suche aktiv" in den Tabellen-Optionen aktiviert wurde.

### be_manager_relation

Ein Auswahlfeld / Popup, um ein oder mehrere <b>Datensätze</b> mit denen einer fremden Tabelle zu <b>verknüpfen</b>, z. B. über einen Fremdschlüssel (1:n) oder eine Relationstabelle (m:n).

> **Wert in der Datenbank**
> - id des verknüpften Datensatzes, z. B. `1`, `5`, `20` oder
> - ids der verknüpften Datensätze als `SET`, z. B. `1,5,20`, `4,8,12`, `7,10,42` oder
> - leer, wenn eine Relationstabelle angegeben wurde.

Option | Erläuterung
------ | ------
Priorität | Ordnet das Feld zwischen anderen Feldern im Formular ein.
Name | Name des Felds in der Datenbank, z. B. `article_id`, `person_id`, `link_id`
Bezeichnung | Name des Felds, wie er im Frontend oder Backend angezeigt wird, z. B. die Bezeichnung der Ziel-Tabelle
Ziel-Tabelle | Name der Tabelle, deren Datensätze referenziert werden.
Ziel Tabellenfeld(er) zur Anzeige oder Zielfeld | Feldname der Tabelle, dessen Werte als Select-Box oder im Popup angezeigt werden, z. B. `name`, `prename, ' ', name` oder `name, '(', id, ')'`
Mehrfachauswahl | Gibt an, ob ein (1:n) oder mehrere (m:n) Datensätze ausgewählt werden können, entweder in einem select-Feld oder als Popup-Fenster 
Mit "Leer-Option" | Gibt an, ob "keine Auswahl" erlaubt ist.
Fehlermeldung, wenn "Leer-Option" nicht aktiviert ist | Fehlermeldung, die dem Nutzer mitteilt, dass eine Auswahl getroffen werden muss. 
Höhe der Auswahlbox | Höhe der Auswahlbox, wenn `Mehrfachauswahl` als select-Feld aktiviert wurde. 
Zusätzliche Angaben in einer speziellen Syntax, die in [be_relation-Tutorial](#anhang) erläutert werden.  
Vorab: [Diskussion auf GitHub](https://github.com/yakamara/redaxo_yform_docs/issues/12)  
Relationstabelle | [optional] Name der Tabelle, in der die m:n-Beziehungen abgelegt sind, z. B. `rex_project_news_tags`
Notiz | Hinweis unterhalb des Feldes, um dem Nutzer zusätzliche Instruktionen zur Eingabe mitzugeben. 
In der Liste verstecken |  Versteckt das Feld in der Tabellen-Übersicht.
Als Suchfeld aufnehmen |  Zeigt das Feld in den Suchoptionen an, sofern die Option "Suche aktiv" in den Tabellen-Optionen aktiviert wurde.

> **Hinweise:**
> **Tipp für Anfänger:** Um die verknüpften Datensätze im Frontend auszugeben, wird eine SELECT-Abfrage mit einem `JOIN` benötigt.
> **Tipp:** Details zu den umfangreichen Einstellungsmöglichkeiten gibt's im [be_relation-Tutorial](#anhang).

> Achtung! Feld Typ Auswahl `int` nur bei `Mehrfachauswahl: Single` verwenden

### be_media

Ein Redaxo-Feld, um eine einzelne oder mehrere <b>Medienpool-Datei/en</b> auszuwählen.

> **Wert in der Datenbank**
> Dateiname der Medienpool-Datei, z. B. `mueller.jpg`, `preisliste.pdf`

Option | Erläuterung
------ | ------
Priorität | Ordnet das Feld zwischen anderen Feldern in der Tabelle ein.
Name | Name des Felds in der Datenbank, z. B. `image`, `attachment`, `file`
Bezeichnung | Name des Felds, wie er im Frontend oder Backend angezeigt wird, z. B. `Bild`, `Anhang`, `Datei`
Preview (0/1) (opt) | Zeigt eine Bildvorschau an, wenn die Datei markiert wird.
Mehrfachauswahl (0/1) | Wenn mehrere Medien ausgwählt werden sollen.
Medienpool Kategorie (opt) | id der Medienpool-Kategorie, die bei der Auswahl der Dateien voreingestellt ist.
Types (opt) | Filtert die Dateiauswahl im Medienpool anhand der Dateiendung, z. B. `.jpg,.jpeg,.png,.gif` oder `.pdf,.docx,.doc`
Notiz | Hinweis unterhalb des Feldes, um dem Nutzer zusätzliche Instruktionen zur Eingabe mitzugeben.
In der Liste verstecken |  Versteckt das Feld in der Tabellen-Übersicht.
Als Suchfeld aufnehmen |  Zeigt das Feld in den Suchoptionen an, sofern die Option "Suche aktiv" in den Tabellen-Optionen aktiviert wurde.


### be_select_category

Ein Redaxo-Feld, um ein oder mehrere <b>Kategorien</b> aus der Struktur auszuwählen.

> **Wert in der Datenbank**
> ids der gewählten Kategorien (kommagetrennt), z. B. `1,5,20`

Option | Erläuterung
------ | ------
Priorität | Ordnet das Feld zwischen anderen Feldern in der Tabelle ein.
Name | Name des Felds in der Datenbank, z. B. `category_id`, oder `category_ids`
Bezeichnung | Name des Felds, wie er im Frontend oder Backend angezeigt wird, z. B. `Kategorie`, `Kategorien`, `Navigationspunkt`
Ignoriere Offline-Kategorien  | Gibt an, ob Offline-Kategorien aus dem Auswahl-Dialogfeld ausgeschlossen werden. 
Prüfe Rechte  | Prüft, ob der Nutzer berechtigt ist, auf die jeweiligen Kategorien zuzugreifen.
Füge "Homepage"-Eintrag (Root) hinzu  | Gibt an, ob im Auswahl-Dialogfeld die oberste Ebene auswählbar ist.
Root-id | Startpunkt der Auswahl-Dialogfelds, z. B. die id einer Unterkategorie.
Sprache |Clang-id der Sprache, aus der die Kategorienamen und der Offline-Status gelesen werden, z.B: `1`
Mehrere Felder möglich | Gibt an, ob ein oder mehrere Kategorien ausgewählt werden können.
Höhe der Auswahlbox | Höhe der Auswahlbox, wenn `Mehrere Felder möglich` aktiviert wurde. 
Nicht in Datenbank speichern | Gibt an, ob das Feld nur angezeigt werden soll oder der Wert auch in der Datenbank gespeichert werden soll.
Notiz | Hinweis unterhalb des Feldes, um dem Nutzer zusätzliche Instruktionen zur Eingabe mitzugeben.
In der Liste verstecken |  Versteckt das Feld in der Tabellen-Übersicht.
Als Suchfeld aufnehmen |  Zeigt das Feld in den Suchoptionen an, sofern die Option "Suche aktiv" in den Tabellen-Optionen aktiviert wurde.

### be_table

Eine Reihe von Eingabefeldern, um <b>tabellarische Daten</b> einzugeben.

> **Wert in der Datenbank**
> JSON-Format (seit YForm 1.1)

Option | Erläuterung
------ | ------
Priorität | Ordnet das Feld zwischen anderen Feldern in der Tabelle ein.
Name | Name des Felds in der Datenbank, z. B. `table`, `features`
Bezeichnung | Name des Felds, wie er im Frontend oder Backend angezeigt wird, z. B. `Info-Tabelle`, `Eigenschaften`
Anzahl Spalten |  Anzahl der Spalten, die bei der Eingabe zur Verfügung stehen.
Bezeichnung der Spalten (Menge,Preis,Irgendwas)  | Kopfzeile der Tabelle, z. B., `Leistung,Aufpreis`, `Vorname,Name`, `Artikelnummer,Größe,Preis` 
Notiz | Hinweis unterhalb des Feldes, um dem Nutzer zusätzliche Instruktionen zur Eingabe mitzugeben.
In der Liste verstecken |  Versteckt das Feld in der Tabellen-Übersicht.
Als Suchfeld aufnehmen |  Zeigt das Feld in den Suchoptionen an, sofern die Option "Suche aktiv" in den Tabellen-Optionen aktiviert wurde.


> **Hinweis:**  
> Um die Werte wieder aufzutrennen, kann z. B. `$array = json_decode($json, true);` verwendet werden. `json_decode()` liefert ein mehrdimensionales Array, das per `foreach()` durchlaufen werden kann.

### checkbox

Eine <b>Checkbox</b> mit vordefinierten Werten.

> **Wert in der Datenbank**
> Status der Checkbox als Zahl, z. B. `0` oder `1`

Option | Erläuterung
------ | ------
Priorität | Ordnet das Feld zwischen anderen Feldern in der Tabelle ein.
Name | Name des Felds in der Datenbank, z. B. `active`, `online`, `visible`, `hidden`
Bezeichnung | Name des Felds, wie er im Frontend oder Backend angezeigt wird, z. B. `aktiviert`, `online`, `sichtbar?`, `ausgeblendet?`
Defaultstatus | Gibt an, ob die Checkbox vorausgewählt ist oder nicht.
Nicht in Datenbank speichern | Gibt an, ob das Feld nur angezeigt werden soll oder der Wert auch in der Datenbank gespeichert werden soll.
Notiz | Hinweis unterhalb des Feldes, um dem Nutzer zusätzliche Instruktionen zur Eingabe mitzugeben.
In der Liste verstecken |  Versteckt das Feld in der Tabellen-Übersicht.
Als Suchfeld aufnehmen |  Zeigt das Feld in den Suchoptionen an, sofern die Option "Suche aktiv" in den Tabellen-Optionen aktiviert wurde.

### date

Eine Reihe von Auswahlfeldern, in der ein <b>Datum</b> (Tag, Monat, Jahr) ausgewählt wird.

> **Wert in der Datenbank**
> MYSQL-Date-Format, z. B. `2016-07-12`

Option | Erläuterung
------ | ------
Priorität | Ordnet das Feld zwischen anderen Feldern in der Tabelle ein.
Name | Name des Felds in der Datenbank, z. B. `date`, `date_begin`
Bezeichnung | Name des Felds, wie er im Frontend oder Backend angezeigt wird, z. B. `Datum`, `Beginn der Veranstaltung`
[Startjahr] oder [-X] | Gibt an, mit welchem Jahr das Auswahlfeld beginnt, z. B. `1980`, `2014` oder '-1', um immer 1 Jahr unter der aktuellen Jahreszahl zu beginnen 
[Endjahr] oder [+X] | Gibt an, mit welchem Jahr das Auswahlfeld endet, z. B. `2020` oder `+3`, um immer 3 Jahre über der aktuellen Jahreszahl zu enden.
[Anzeigeformat###Y###-###M###-###D###]] | Reihenfolge der Auswahlfelder für Tag, Monat und Jahr beim bearbeiteten eines Datensatzes, z. B. `am ###D###.###M###.###Y###`
Aktuelles Datum voreingestellt | Gibt an, ob das aktuelle Datum vorausgewählt ist.
Nicht in Datenbank speichern | Gibt an, ob das Feld nur angezeigt werden soll oder der Wert auch in der Datenbank gespeichert werden soll.
Notiz | Hinweis unterhalb des Feldes, um dem Nutzer zusätzliche Instruktionen zur Eingabe mitzugeben.
In der Liste verstecken |  Versteckt das Feld in der Tabellen-Übersicht.
Als Suchfeld aufnehmen |  Zeigt das Feld in den Suchoptionen an, sofern die Option "Suche aktiv" in den Tabellen-Optionen aktiviert wurde.

> Tipp: Zum konvertieren des MySQL-Date-Formats in PHP `date(rex_sql::FORMAT_DATETIME, strtotime($datetime))` verwenden

### datestamp

Ein unsichtbares Feld, in das ein **Zeitstempel** gespeichert wird, wenn der Datensatz hinzugefügt oder bearbeitet wird.

> **Wert in der Datenbank**

> - MYSQL-Date-Format, z. B. `2016-07-12`, oder
> - andere in "Format" angegebene Datumsformate.

Option | Erläuterung
------ | ------
Priorität | Ordnet das Feld zwischen anderen Feldern in der Tabelle ein.
Name | Name des Felds in der Datenbank, z. B. `datestamp`, `date_created`
Format | Format, in dem der Zeitstempel abgespeichert wird, z. B. `YmdHis`, `U`, `dmy`, - leer lassen für `mysql`
Nicht in Datenbank speichern | Gibt an, ob das Feld nur angezeigt werden soll oder der Wert auch in der Datenbank gespeichert werden soll.
Wann soll Wert gesetzt werden | Gibt an, ob der Zeitstempel initial beim Erstellen des Datenbankeintrags angelegt wird (`nur, wenn leer`), oder auch bei Änderungen (`immer`).
In der Liste verstecken |  Versteckt das Feld in der Tabellen-Übersicht.
Als Suchfeld aufnehmen |  Zeigt das Feld in den Suchoptionen an, sofern die Option "Suche aktiv" in den Tabellen-Optionen aktiviert wurde.

### datetime

Eine Reihe von Auswahlfeldern, in der **Datum und Uhrzeit** (Tag, Monat, Jahr, Stunden, Minuten, Sekunden) ausgewählt wird.

> **Wert in der Datenbank**
> MYSQL-Date-Format, z. B. `2016-07-12 10:00:00`

Option | Erläuterung
------ | ------
Priorität | Ordnet das Feld zwischen anderen Feldern in der Tabelle ein.
Name | Name des Felds in der Datenbank, z. B. `date`, `date_begin`
Bezeichnung | Name des Felds, wie er im Frontend oder Backend angezeigt wird, z. B. `Datum`, `Beginn der Veranstaltung`
[Startjahr] oder [-X] | Gibt an, mit welchem Jahr das Auswahlfeld beginnt, z. B. `1980`, `2014` oder '-1', um immer 1 Jahr unter der aktuellen Jahreszahl zu beginnen 
[Endjahr] oder [+X] | Gibt an, mit welchem Jahr das Auswahlfeld endet, z. B. `2020` oder `+3`, um immer 3 Jahre über der aktuellen Jahreszahl zu enden.
Anzeigeformat | Reihenfolge der Auswahlfelder für Tag, Monat und Jahr beim bearbeiteten eines Datensatzes, z. B. `am ###D###.###M###.###Y###`
Aktuelles Datum voreingestellt | Gibt an, ob das aktuelle Datum vorausgewählt ist.
Nicht in Datenbank speichern | Gibt an, ob das Feld nur angezeigt werden soll oder der Wert auch in der Datenbank gespeichert werden soll.
Notiz | Hinweis unterhalb des Feldes, um dem Nutzer zusätzliche Instruktionen zur Eingabe mitzugeben.
In der Liste verstecken |  Versteckt das Feld in der Tabellen-Übersicht.
Als Suchfeld aufnehmen |  Zeigt das Feld in den Suchoptionen an, sofern die Option "Suche aktiv" in den Tabellen-Optionen aktiviert wurde.

### email

Ein einfaches Eingabefeld für **E-Mail-Adressen.**

> **Wert in der Datenbank**
> `String`, z. B. `max@mustermann.de`

Option | Erläuterung
------ | ------
Priorität | Ordnet das Feld zwischen anderen Feldern in der Tabelle ein.
Name | Name des Felds in der Datenbank, z. B. `email`, `contact`
Bezeichnung | Name des Felds, wie er im Frontend oder Backend angezeigt wird, z. B. `E-Mail`, `Kontakt-Mail-Adresse`
Defaultwert | E-Mail-Adresse, mit der das Eingabfeld vorausgefüllt wird, z. B. `max@mustermann.de`, `jane@smith.com` 
Nicht in Datenbank speichern | Gibt an, ob das Feld nur angezeigt werden soll oder der Wert auch in der Datenbank gespeichert werden soll.
cssclassname | CSS-Klasse(n), die dem Input-Element zugewiesen werden.
Notiz | Hinweis unterhalb des Feldes, um dem Nutzer zusätzliche Instruktionen zur Eingabe mitzugeben.
In der Liste verstecken |  Versteckt das Feld in der Tabellen-Übersicht.
Als Suchfeld aufnehmen |  Zeigt das Feld in den Suchoptionen an, sofern die Option "Suche aktiv" in den Tabellen-Optionen aktiviert wurde.


### emptyname

Ein Feld **ohne** Eingabemöglichkeit.
> **Wert in der Datenbank**

> leer

Option | Erläuterung
------ | ------
Priorität | Ordnet das Feld zwischen anderen Feldern in der Tabelle ein.
Name | Name des Felds in der Datenbank
Bezeichnung | 
Notiz | Hinweis unterhalb des Feldes, um dem Nutzer zusätzliche Instruktionen zur Eingabe mitzugeben.
In der Liste verstecken |  Versteckt das Feld in der Tabellen-Übersicht.
Als Suchfeld aufnehmen |  Zeigt das Feld in den Suchoptionen an, sofern die Option "Suche aktiv" in den Tabellen-Optionen aktiviert wurde.

### fieldset

Ein Fieldset gruppiert Formularfelder.

> **Wert in der Datenbank**
> leer

Option | Erläuterung
------ | ------
Priorität | Ordnet das Feld zwischen anderen Feldern in der Tabelle ein.
Name | Name des Felds in der Datenbank, z. B. `fieldset_product`, `fieldset_details`,
Bezeichnung | Titel des Fieldsets, das im `<legend />`-Tag notiert wird. z. B. `Produktinfos`, `Details`
Notiz | Hinweis unterhalb des Feldes, um dem Nutzer zusätzliche Instruktionen zur Eingabe mitzugeben.
In der Liste verstecken |  Versteckt das Feld in der Tabellen-Übersicht.
Als Suchfeld aufnehmen |  Zeigt das Feld in den Suchoptionen an, sofern die Option "Suche aktiv" in den Tabellen-Optionen aktiviert wurde.

> **Hinweis:**  
> Das Feld wird nur aus technischen Gründen angelegt. Das Feld wird nicht mit einem Wert gefüllt.

### generate_key

### google_geocode

### hashvalue

Ein Feld, das aus dem Wert eines anderen Feldes einen <b>Hashwert</b> erzeugt.

> **Wert in der Datenbank**
> String, z. B. `f53c8008cb4b3b3c1f51c9922d9dddd0`

Option | Erläuterung
------ | ------
Priorität | Ordnet das Feld zwischen anderen Feldern in der Tabelle ein.
Name | Name des Felds in der Datenbank, z. B. `password_hash`, `password`.
Bezeichnung | Name des Felds, wie er im Backend angezeigt wird, z. B. `Passwort-Hash`, `Passwort`
Input-Feld | Name des Felds, aus dem der Hash generiert werden soll.
Algorithmus | Name des Algorithmus, der beim Generieren verwendet wird, z. B. `md5`, `sha1`, `sha512`
Salt | Salz, das zusätzlich zum Input-Feld gestreut wird.
Nicht in Datenbank speichern | Gibt an, ob das Feld nur angezeigt werden soll oder der Wert auch in der Datenbank gespeichert werden soll.
Notiz | Hinweis unterhalb des Feldes, um dem Nutzer zusätzliche Instruktionen zur Eingabe mitzugeben.
In der Liste verstecken |  Versteckt das Feld in der Tabellen-Übersicht.
Als Suchfeld aufnehmen |  Zeigt das Feld in den Suchoptionen an, sofern die Option "Suche aktiv" in den Tabellen-Optionen aktiviert wurde.

> **Tipp**
> Salt sollte immer individuell von Feld zu Feld und Redaxo-Installation zu Redaxo-Installation festegelgt werden, um die Sicherheit zu erhöhen. Als Generator für Salt kann z. B. ein [Passwort-Generator](https://www.passwort-generator.com/) zum Einsazt kommen.

### hidden

### html

Gibt <b>HTML-Code</b> an der gewünschten Stelle des Eingabe-Formulars aus.

> Wert in der Datebank
> String, z. B. `<p>Hallo Welt</p>`

Option | Erläuterung
------ | ------
Priorität | Ordnet das Feld zwischen anderen Feldern im Formular ein.
Name | Name des Felds in der Datenbank, z. B. `info`, `code`.
HTML | HTML-Code, der vor, zwischen oder nach anderen Feldern im Frontend oder Backend eingefügt wird.
Notiz | Hinweis unterhalb des Feldes, um dem Nutzer zusätzliche Instruktionen zur Eingabe mitzugeben.
In der Liste verstecken |  Versteckt das Feld in der Tabellen-Übersicht.
Als Suchfeld aufnehmen |  Zeigt das Feld in den Suchoptionen an, sofern die Option "Suche aktiv" in den Tabellen-Optionen aktiviert wurde.

### index

Ein Feld, das einen <b>Index</b> / Schlüssel über mehrere Felder erzeugt.

> **Wert in der Datenbank**
> Alle Werte zusammengefasst als String, z. B. `max@mustermann.de4ja` bei Feldern `email,article_id,checkbox_agb`, sofern kein Algorithmus gewählt wurde.

Option | Erläuterung
------ | ------
Priorität | Ordnet das Feld zwischen anderen Feldern in der Tabelle ein.
Name | Name des Felds in der Datenbank, z. B. `password_hash`, `password`.
Felder | Auswahl an Feldern, die für das Erstellen des Index verwendet werden sollen.
Nicht in Datenbank speichern | Gibt an, ob das Feld nur angezeigt werden soll oder der Wert auch in der Datenbank gespeichert werden soll.
Opt. Codierfunktion |  Name des Algorithmus, der beim Generieren verwendet wird, z. B. `md5`, `sha1`
Notiz | Hinweis unterhalb des Feldes, um dem Nutzer zusätzliche Instruktionen zur Eingabe mitzugeben.
In der Liste verstecken |  Versteckt das Feld in der Tabellen-Übersicht.
Als Suchfeld aufnehmen |  Zeigt das Feld in den Suchoptionen an, sofern die Option "Suche aktiv" in den Tabellen-Optionen aktiviert wurde.

> Tipp:
> Die Generierung eines Index ist nützlich, um aus mehreren Feldern, die sich nicht für `unique` qualifizieren, einen eindeutigen Key zu erstellen.

### integer

Ein einfaches Eingabefeld für <b>ganze Zahlen.</b>

> **Wert in der Datenbank**
> Zahl, z. B. `5`

Option | Erläuterung
------ | ------
Priorität | Ordnet das Feld zwischen anderen Feldern in der Tabelle ein.
Name | Name des Felds in der Datenbank, z. B. `price`, `quantity`.
Bezeichnung | Name des Felds, wie er im Frontend oder Backend angezeigt wird, z. B. `Preis in EUR`, `Anzahl`
Defaultwert | Zahl, mit der das Eingabfeld vorausgefüllt wird, z. B. `1`, `5`, `42` 
Nicht in Datenbank speichern | Gibt an, ob das Feld nur angezeigt werden soll oder der Wert auch in der Datenbank gespeichert werden soll.
Notiz | Hinweis unterhalb des Feldes, um dem Nutzer zusätzliche Instruktionen zur Eingabe mitzugeben.
In der Liste verstecken |  Versteckt das Feld in der Tabellen-Übersicht.
Als Suchfeld aufnehmen |  Zeigt das Feld in den Suchoptionen an, sofern die Option "Suche aktiv" in den Tabellen-Optionen aktiviert wurde.

### ip

### mediafile

Ein <b>Upload-Feld</b>, mit dem eine Datei in den Medienpool hochgeladen wird.

> Wert in der Datebank
> Dateiname, z. B. `default.jpg`

Option | Erläuterung
------ | ------
Priorität | Ordnet das Feld zwischen anderen Feldern in der Tabelle ein.
Name | Name des Felds in der Datenbank, z. B. `image`, `attachment`.
Bezeichnung | Name des Felds, wie er im Frontend oder Backend angezeigt wird, z. B. `Profilbild`, `Anhang`
Maximale Größe in Kb oder Range 100,500 | |
Welche Dateien sollen erlaubt sein, kommaseparierte Liste. ".gif,.png" | |
Pflichtfeld  | |
min_err,max_err,type_err,empty_err | |
Nicht in Datenbank speichern | Gibt an, ob das Feld nur angezeigt werden soll oder der Wert auch in der Datenbank gespeichert werden soll.
Mediakategorie id | |
Mediapool User (createuser/updateuser) | |
Notiz | Hinweis unterhalb des Feldes, um dem Nutzer zusätzliche Instruktionen zur Eingabe mitzugeben.
In der Liste verstecken |  Versteckt das Feld in der Tabellen-Übersicht.
Als Suchfeld aufnehmen |  Zeigt das Feld in den Suchoptionen an, sofern die Option "Suche aktiv" in den Tabellen-Optionen aktiviert wurde.

### number

Option | Erläuterung
------ | ------
`precision` | ist die Anzahl der signifikanten Stellen. Der Bereich von `precision` liegt zwischen 1 und 65.

`scale` | ist die Anzahl der Stellen nach dem Dezimalzeichen. Der Bereich für `scale` ist von `0` bis `30`. MySQL erfordert, dass `scale` kleiner gleich (`<=`) `precision` ist.

### objparams

### password

### php

Führt <b>PHP-Code</b> an der gewünschten Stelle des Eingabe-Formulars aus.

Option | Erläuterung
------ | ------
Priorität | Ordnet das Feld zwischen anderen Feldern in der Tabelle ein.
Name | Name des Felds in der Datenbank, z. B. `php`, `code`.
PHP Code | PHP-Code, der vor, zwischen oder nach anderen Feldern im Frontend oder Backend eingefügt wird.
In der Liste verstecken |  Versteckt das Feld in der Tabellen-Übersicht.
Als Suchfeld aufnehmen |  Zeigt das Feld in den Suchoptionen an, sofern die Option "Suche aktiv" in den Tabellen-Optionen aktiviert wurde.

> **Hinweis**: Zusammen mit dem Upload-Feld lassen sich komfortabel [E-Mails mit Anhang versenden](demo_email-attachments.md).


### prio

Ein <b>Auswahlfeld</b>, um Datensätze in eine <b>bestimmte Reihenfolge</b> zu sortieren.

> **Wert in der Datenbank**
> Zahl, z. B. `5`, `20`

Option | Erläuterung
------ | ------
Priorität | Ordnet das Feld zwischen anderen Feldern in der Tabelle ein.
Name | Name des Felds in der Datenbank, z. B. `prio`, `ranking`, `order`
Bezeichnung | Name des Felds, wie er im Frontend oder Backend angezeigt wird, z. B. `Priorität`, `Rang`, `Reihenfolge`
Anzeige | Feld(er), die in der Auswahl-Box angezeigt werden, z. B. `name`, `title`, `isbn`
Beschränkung | Feld(er), die die Auswahlmöglichkeiten in der Auswahl-Box beschränken, z. B. `category_id` 
Am Anfang | Vorauswahl, ob ein neuer Datensatz am Anfang oder am Ende angelegt wird. 
Notiz | Hinweis unterhalb des Feldes, um dem Nutzer zusätzliche Instruktionen zur Eingabe mitzugeben.
In der Liste verstecken |  Versteckt das Feld in der Tabellen-Übersicht.
Als Suchfeld aufnehmen |  Zeigt das Feld in den Suchoptionen an, sofern die Option "Suche aktiv" in den Tabellen-Optionen aktiviert wurde.

> **Hinweis:**  
> Wenn eine Beschränkung ausgewählt wurde, kann es vorkommen, dass ein Datensatz zunächst gespeichert werden muss, damit die Beschränkung greifen kann. Dadurch lässt sich z. B. eine Reihenfolge pro Kategorie festlegen.

### showvalue

Zeigt einen Wert in der Ausgabe.

### signature

### submit

Ein oder mehrere <b>Submit-Buttons</b> zum Absenden des Formulars.

> **Wert in der Datenbank**
> Wert des abgesendeten Buttons, z. B. `Anfrage`

Option | Erläuterung
------ | ------
Priorität | Ordnet das Feld zwischen anderen Feldern in der Tabelle ein.
Name | Name des Felds in der Datenbank, z. B. `submit`
Bezeichnungen (kommasepariert) | Liste an Beschriftungen für die jeweiligen Buttons, z. B. `Anfragen,Kaufen` oder `Rückruf,E-Mail`
Werte (optional, kommasepariert) | `Anfragen,Kaufen`,`email,phone`
Nicht in Datenbank speichern | Gibt an, ob das Feld nur angezeigt werden soll oder der Wert auch in der Datenbank gespeichert werden soll.
Defaultwert |  Wert, mit der das Eingabefeld vorausgefüllt wird, z. B. `Anfragen`, `phone` 
CSS Klassen (kommasepariert) | CSS-Klassen für die jeweiligen Submit-Buttons, z. B. `submit--email,submit--phone`
Notiz | Hinweis unterhalb des Feldes, um dem Nutzer zusätzliche Instruktionen zur Eingabe mitzugeben.
In der Liste verstecken |  Versteckt das Feld in der Tabellen-Übersicht.
Als Suchfeld aufnehmen |  Zeigt das Feld in den Suchoptionen an, sofern die Option "Suche aktiv" in den Tabellen-Optionen aktiviert wurde.

> **Tipp:**  
> Mit mehreren Submits kann man auf unterschiedliche Absichten mit dem gleichen Formular reagieren, z. B. zwei Buttons namens `Bestellen` und `Angebot anfragen`.

### text

Input-Feld zur Eingabe eines Textes.

> **Wert in der Datenbank**
> String, z. B. `Musterfirma`, `Paul`, `Musterprojekt`

Option | Erläuterung
------ | ------ 
Priorität | Ordnet das Feld zwischen anderen Feldern in der Tabelle ein.
Name | Name des Felds in der Datenbank, z. B. `name`, `prename`, `title`
Bezeichnung | Name des Felds, wie er im Frontend oder Backend angezeigt wird, z. B. `Name`, `Vorname`, `Titel`
Defaultwert | Wert, der beim Aufruf des Formulars eingetragen ist, z. B. `Musterfirma`, `Paul`, `Musterprojekt`
Nicht in Datenbank speichern | Gibt an, ob das Feld nur angezeigt werden soll oder der Wert auch in der Datenbank gespeichert werden soll.
individuelle Attribute | Zusätzliche Feld-Attribute im JSON-Format, z. B. `{"placeholder":"+491234567890","type":"phone"}`
Notiz | Hinweis unterhalb des Feldes, um dem Nutzer zusätzliche Instruktionen zur Eingabe mitzugeben.
In der Liste verstecken | Versteckt das Feld in der Tabellen-Übersicht.
Als Suchfeld aufnehmen | Zeigt das Feld in den Suchoptionen an, sofern die Option "Suche aktiv" in den Tabellen-Optionen aktiviert wurde.

> Tipp:
> Mit der neuen Möglichkeit, individuelle Attribute zu vergeben, lassen sich alle [Input-Feldtypen aus HTML5](http://www.w3schools.com/html/html_form_input_types.asp) nutzen und clientseitig validieren. 

### textarea

Ein <b>mehrzeiliges Eingabefeld</b> für Text.

> **Wert in der Datenbank**
> Inhalt des Textarea-Feldes

Option | Erläuterung
------ | ------
Priorität | Ordnet das Feld zwischen anderen Feldern in der Tabelle ein.
Name | Name des Felds in der Datenbank, z. B. `text`, `description`, `message`
Bezeichnung |  Name des Felds, wie er im Frontend oder Backend angezeigt wird, z. B. "Text", "Beschreibung", "Nachricht"
Defaultwert | Wert, der beim Aufruf des Formulars eingetragen ist, z. B. `Geben Sie hier Ihre Nachricht ein`
Nicht in Datenbank speichern | Gibt an, ob das Feld nur angezeigt werden soll oder der Wert auch in der Datenbank gespeichert werden soll.
individuelle Attribute | Zusätzliche Feld-Attribute im JSON-Format, z. B. `{"class":"form-control redactorEditor2-full","id":"textarea1"}`
Notiz | Hinweis unterhalb des Feldes, um dem Nutzer zusätzliche Instruktionen zur Eingabe mitzugeben.
In der Liste verstecken |  Versteckt das Feld in der Tabellen-Übersicht.
Als Suchfeld aufnehmen |  Zeigt das Feld in den Suchoptionen an, sofern die Option "Suche aktiv" in den Tabellen-Optionen aktiviert wurde.

> Hinweis: Wenn das `class`-Attribut im Backend überschrieben wird, z. B. für einen Editor, dann muss die Klasse `form-control` ebenfalls wieder hinzugefügt werden. 
> Tipp: Mit dem Redactor 2 Addon kann das `textarea`-Feld zu einem WYSIWYG-Editor umgewandelt werden, mit dem Markdown-Addon zu einem WYSIWYM-Editor.

### time

Eine Reihe von Auswahlfeldern, in der die <b>Uhrzeit</b> (Stunden, Minuten, Sekunden) ausgewählt wird.

> **Wert in der Datenbank**
> - MYSQL-Date-Format, z. B. `10:00:00`

Option | Erläuterung
------ | ------
Priorität | Ordnet das Feld zwischen anderen Feldern in der Tabelle ein.
Name | Name des Felds in der Datenbank, z. B. `time`, `time_begin`
Bezeichnung | Name des Felds, wie er im Frontend oder Backend angezeigt wird, z. B. `Uhrzeit`, `Beginn der Veranstaltung`
[Anzeigeformat ###H###h ###I###m] |  Reihenfolge der Auswahlfelder für Stunde und Minute beim bearbeiteten eines Datensatzes, z. B. `um ###H###:###I### Uhr`
Nicht in Datenbank speichern | Gibt an, ob das Feld nur angezeigt werden soll oder der Wert auch in der Datenbank gespeichert werden soll.
Notiz | Hinweis unterhalb des Feldes, um dem Nutzer zusätzliche Instruktionen zur Eingabe mitzugeben.
In der Liste verstecken |  Versteckt das Feld in der Tabellen-Übersicht.
Als Suchfeld aufnehmen |  Zeigt das Feld in den Suchoptionen an, sofern die Option "Suche aktiv" in den Tabellen-Optionen aktiviert wurde.

### upload

Ein **Upload-Feld**, mit dem eine Datei in **die Datenbank oder ein Verzeichnis** hochgeladen wird.


Option | Erläuterung
------ | ------
Priorität | Ordnet das Feld zwischen anderen Feldern in der Tabelle ein.
Label | |
Bezeichnung | |
Maximale Größe in Kb oder Range 100,500 | |
Welche Dateien sollen erlaubt sein, kommaseparierte Liste. ".gif,.png" | |
Pflichtfeld | |
min_err,max_err,type_err,empty_err,delete_file_msg | |
Speichermodus | |
`database`: Dateiname wird gespeichert in Feldnamen | |
Eigener Uploadordner [optional] | |
Dateiprefix [optional] | |
Defaultfile | |
Notiz | Hinweis unterhalb des Feldes, um dem Nutzer zusätzliche Instruktionen zur Eingabe mitzugeben.
In der Liste verstecken |  Versteckt das Feld in der Tabellen-Übersicht.
Als Suchfeld aufnehmen |  Zeigt das Feld in den Suchoptionen an, sofern die Option "Suche aktiv" in den Tabellen-Optionen aktiviert wurde.

> **Hinweis**: Zusammen mit dem PHP-Feld lassen sich komfortabel [E-Mails mit Anhang versenden](demo_email-attachments.md).

### uuid


## Validierung

### compare

Vergleicht zwei Eingabe-Werte <b>miteinander</b>.

Option | Erläuterung
------ | ------
Priorität | Reihenfolge des Feldes in der Feldübersicht und beim Abarbeiten der Validierungen.
1. Feldname | Name des Tabellenfeldes, das für die Überprüfung herangezogen wird, z. B. `password`, `email`
2. Feldname | Name des Tabellenfeldes, das für die Überprüfung herangezogen wird, z. B. `password2`, `email_verified`
Vergleichsart | Operator, wie `Feld 1` und `Feld 2` verglichen werden sollen, z. B. `!=`, `!=`, `>`, `<` 
Fehlermeldung | Hinweis, der erscheint, wenn der Vergleich beider Felder `false` ergibt.

> Tipp: Diese Validierung kann z. B. bei Online-Tarifrechnern oder Ähnlichem eingesetzt werden, um serverseitig unzulässige Konfigurationen durch den Nutzer auszuschließen.

### compare_value

Vergleicht einen Eingabe-Wert mit einem <b>fest definierten Wert</b>.

Option | Erläuterung
------ | ------
Priorität | Reihenfolge des Feldes in der Feldübersicht und beim Abarbeiten der Validierungen.
\1. Feldname | Name des Tabellenfeldes, das für die Überprüfung herangezogen wird, z. B. `checkbox_agb`, `newsletter_consent`
Vergleichswert | Fest definierter Wert, der für den Vergleich herangezogen wird, z. B. `1` (bei Checkboxen) 
Vergleichsart |  Operator, wie `Feld 1` und `Vergleichswert` vergleichen werden sollen, z. B. `==`, `!=`, `>`, `<` 
Fehlermeldung | Hinweis, der erscheint, wenn die Bedingung des Vergleichs erfüllt ist.

> Merkhilfe: Wenn die Bedingung erfüllt ist, dann wird eine Fehlermeldung ausgegeben.

### customfunction

Ruft eine eigene <b>PHP-Funktion</b> für einen Vergleich auf.

Option | Erläuterung
------ | ------
Priorität | Reihenfolge des Feldes in der Feldübersicht und beim Abarbeiten der Validierungen.
Name | Name des Tabellenfeldes, das für die Überprüfung herangezogen wird, z. B. `name`, `email`, `phone`, `zip`
Name der Funktion | Funktion, die den Wert überprüfen soll, z. B. `yform_validate_custom`
Weitere Parameter | Eingabe-Wert, gegen den geprüft werden soll, z. B. `20`
Fehlermeldung | Hinweis, der erscheint, wenn die Bedingung des Vergleichs erfüllt ist.

> Merkhilfe: Wenn die Bedingung erfüllt ist (`return true;`), dann wird eine Fehlermeldung ausgegeben.

#### Beispiel für `customfunction`

Diese Funktion z. B. im `project`-Addon in der boot.php hinterlegen:

```php
<?php
function yform_validate_custom($label, $value, $param)
{
	if($value > $param) { // eigene Validierung. Hier: Prüft, ob der Formular-Eingabewert größer ist als der Parameter
        return false; // Achtung: false = gültig
    } else {
    	return true; // Achtung: true = Fehlermeldung ausgeben
    }
}
```

### empty

Überprüft, ob ein Eingabe-Wert <b>vorhanden</b> ist.

Option | Erläuterung
------ | ------
Priorität | Reihenfolge des Feldes in der Feldübersicht und beim Abarbeiten der Validierungen.
Name |  Name des Tabellenfeldes, das für die Überprüfung herangezogen wird, z. B. `email`, `name`
Fehlermeldung | Hinweis, der erscheint, wenn die Eingabe leer ist.

### in_names

### in_table

Überprüft, ob ein Eingabe-Wert <b>in der DB vorhanden</b> ist.

Option | Erläuterung
------ | ------
Priorität | Reihenfolge des Feldes in der Feldübersicht und beim Abarbeiten der Validierungen.
Name |  Name des Tabellenfeldes, das für die Überprüfung herangezogen wird, z. B. `email`, `name`
Tabellenname |  Name der Tabelle, das für die Überprüfung herangezogen wird, z. B. `rex_ycom_user`
Feldname |  Name des Feldes in der Tabelle, das für die Überprüfung herangezogen wird, z. B. `email`
Fehlermeldung | Hinweis, der erscheint, wenn die Eingabe leer ist.

### intfromto

Überprüft, ob der Eingabe-Wert <b>zwischen zwei Zahlen</b> liegt.

Option | Erläuterung
------ | ------
Priorität | Reihenfolge des Feldes in der Feldübersicht und beim Abarbeiten der Validierungen.
Name |  Name des Tabellenfeldes, das für die Überprüfung herangezogen wird, z. B. `email`, `name`
Von | Wert, der mindestens eingegeben werden muss, z. B. `0`, `5`, `1000`
Bis | Wert, der höchstens eingegeben werden darf, z. B. `5`,`10`,`2030`
Fehlermeldung | Hinweis, der erscheint, wenn die Eingabe nicht im erlaubten Bereich liegt.

### password_policy

### preg_match

### size

Überprüft, ob der Eingabe-Wert eine <b>exakte Anzahl von Zeichen</b> hat.

Option | Erläuterung
------ | ------
Priorität | Reihenfolge des Feldes in der Feldübersicht und beim Abarbeiten der Validierungen.
Name |  Name des Tabellenfeldes, das für die Überprüfung herangezogen wird, z. B. `customer_id`, `pin`
Anzahl der Zeichen | exakte Anzahl der Zeichen, die eingegeben werden sollen, z. B. `5`,`10`,`42`
Fehlermeldung | Hinweis, der erscheint, wenn die Eingabe die festgelegte Anzahl von Zeichen unter- oder überschreitet.

### size_range

Überprüft, ob die <b>Länge</b> des Eingabe-Werts <b>zwischen zwei Zahlen</b> liegt.

Option | Erläuterung
------ | ------
Priorität | Reihenfolge des Feldes in der Feldübersicht und beim Abarbeiten der Validierungen.
Name |  Name des Tabellenfeldes, das für die Überprüfung herangezogen wird, z. B. `customer_id`, `password`
Minimale Anzahl der Zeichen (opt) | Anzahl der Zeichen, die mindestens werden sollen, z. B. `0`, `5`, `7`
Maximale Anzahl der Zeichen (opt) | Anzahl der Zeichen, die höchstens werden sollen, z. B. `5`,`10`,`15`
Fehlermeldung | Hinweis, der erscheint, wenn die Eingabe den festgelegten Bereich an Zeichen unter- oder überschreitet.

### type

Überprüft, ob <b>der Typ</b> des Eingabe-Werts korrekt ist.

Option | Erläuterung
------ | ------
Priorität | Reihenfolge des Feldes in der Feldübersicht und beim Abarbeiten der Validierungen.
Name |  Name des Tabellenfeldes, das für die Überprüfung herangezogen wird, z. B. `zip`, `phone`, `name`, `email`, `website`
Prüfung nach | Typ, der überprüft werden soll, z. B. `int`, `float`, `numeric`, `string`, `email`, `url`, `date`, `hex`]
Fehlermeldung | Hinweis, der erscheint, wenn die Eingabe nicht dem festgelegten Typ entspricht.
Feld muss nicht ausgefüllt werden | Gibt an, ob die Validierung erfolgreich ist, wenn keine Eingabe stattfindet. 

### unique

Überprüft ob der Eingabe-Wert <b>noch nicht in anderen Datensätzen vorhanden</b> ist.

Option | Erläuterung
------ | ------
Priorität | Reihenfolge des Feldes in der Feldübersicht und beim Abarbeiten der Validierungen.
Names |  Namen der Tabellenfelder, die für die Überprüfung herangezogen werden, z. B. `id`, `customer_id`, `email,email_verified`
Fehlermeldung | Hinweis, der erscheint, wenn die Eingabe bereits in einem anderen Datensatz existiert.
Tabelle [opt] | Name der Tabelle, in der die Felder durchsucht werden.

## Table Manager Snippets

- [Table Manager: Spalte ausblenden](#table-manager-spalte-ausblenden)
- [Table Manager: Spalteninhalt vor Anzeige in Übersicht ändern](#table-manager-spalteninhalt-vor-anzeige-in-uebersicht-aendern)
- [Table Manager: Bilderspalte in Tabellenansicht (Bild statt Dateiname)](#table-manager-bilderspalte-in-tabellenansicht-bild-statt-dateiname)
- [Table Manager: Extensionpoint / Listensortierung beeinflussen)](#table-manager-extensionpoint-listensortierung-beeinflussen)

### Table Manager: Spalte ausblenden

Beim Einsatz einer YForm-Tabelle im eigenen AddOn können beliebige Spalten über den Einsatz des folgenden Extension points ausgeblendet werden (hier als Beispiel die Spalte ID):

```php
<?php
if (rex::isBackend())
{
	rex_extension::register("YFORM_DATA_LIST", function( $ep ) {  

	if ($ep->getParam("table")->getTableName()=="gewuenschte_tabelle"){
		$list = $ep->getSubject();

		$list->removeColumn("id");
	});
});

``` 

### Table Manager: Spalteninhalt vor Anzeige in Übersicht ändern

Beim Einsatz einer YForm-Tabelle im eigenen AddOn kann für beliebige Spalten vor der Anzeige in der Übersicht der Wert manipuliert und ggf. mit Werten aus derselben Tabellenzeile kombiniert werden. Konkret wird hier in der Anzeige der Spalte "title" der Wert der Spalte "name" angehängt.

```php
<?php
if (rex::isBackend())
{
	rex_extension::register('YFORM_DATA_LIST', function( $ep ) {  

		if ($ep->getParam('table')->getTableName()=="gewuenschte_tabelle"){
			$list = $ep->getSubject();

			$list->setColumnFormat(
				'title', // Spalte, für die eine custom function aktiviert wird
				'custom', // festes Keyword
				function($a){ 

					// Generierung des auszugebenden Werts unter Einbeziehung beliebiger anderer Spalten
					// $a['value'] enthält den tatsächlichen Wert der Spalte
					// $a['list']->getValue('xyz') gibt den Wert einer anderen Spalte ("xyz) zurück.

					$neuer_wert=$a['value']." ".$a['list']->getValue('xyz');

					return $neuer_wert;
				}
			);
		}
	});
}
```

Das Snippet kommt am besten in die boot.php des project-AddOns.

### Table Manager: Bilderspalte in Tabellenansicht (Bild statt Dateiname)

Der Code kommt entweder in die boot Datei des Projekt AddOns oder in die Boot Datei des Theme Addons (wer damit arbeitet) oder in eine anderweitige Boot Datei.

```php 
<?php
// Es soll nur im Backend passieren und nur, wenn der table_name rex_test requestet wird (ggf. eigenen table_name verwenden)
if (rex::isBackend() && rex_request('table_name') == 'rex_test') {
    // am Extensionpoint YFORM_DATA_LIST einklinken
    rex_extension::register('YFORM_DATA_LIST', function( $ep ) {
        // die Liste holen
        $list = $ep->getSubject();
        // die Spalte bild (ggf. eigenen Spaltennamen verwenden) soll mit einer custom Funktion umformatiert werden
        $list->setColumnFormat('bild', 'custom', function ($params ) {
            // das passiert hier. Ggf. eigenen Medientyp setzen.
            return '<img src="/images/rex_medialistbutton_preview/'.$params['list']->getValue('bild').'">';                
        });            
    });        
}
```

### Table Manager: Extensionpoint | Listensortierung beeinflussen

Im Table Manager lässt sich _ein_ DB-Feld für die Sortierung der Backendausgabe festlegen. 
Manchmal ist eine komplexere Sortierung sinnvoll: `ORDER BY column1, column2`

>Hinweis: Das geht nur, solange keine andere Spalte zum Sortieren ausgewählt wird. Will man eine andere Spalte zum sortieren auswählen wirft der EP nicht das passende Query aus.

Folgendes Snippet kann im Projekt Addon oder Theme Addon platziert werden und ermöglicht es die Sortierung zu erweitern:

#### Ausführliches Beispiel

```php
<?php
if(rex::isBackend() && rex_addon::get('yform')->isAvailable() && rex_plugin::get('yform', 'manager')->isAvailable() &&
   rex_be_controller::getCurrentPage() == 'yform/manager/data_edit' && rex_request('table_name') == '<TABLE_NAME>') {
	rex_extension::register('YFORM_DATA_LIST_SQL', function(rex_extension_point $ep){
		$sortField = $ep->getParam('table')->getSortFieldName();
		$sortOrder = $ep->getParam('table')->getSortOrderName();
		$fields = $ep->getParam('table')->getFields();

		// dont prevent sorting of other columns
		if(rex_request("sort") != "" && rex_request("sort") != $sortField) {
			return;
		}

		$subject = preg_replace(
			"@ORDER BY `id` ASC$@i", "ORDER BY <SOMETHING ELSE>",
			$ep->getSubject()
		);

		$ep->setSubject($subject);
	}, rex_extension::LATE);
}
```
`<TABLE_NAME>` und `<SOMETHING ELSE>` austauschen und  darauf achten das in der Tabellen Konfiguration die Standardsortierung auf `id` und die Richtung auf  `aufsteigend` steht.


#### Einfaches Beispiel zur Verwendung des EP

```php
<?php
rex_extension::register('YFORM_DATA_LIST_SQL', function ($ep) {
  $params  = $ep->getParams(); // EP Params holen
  $subject = $ep->getSubject(); // EP Subject (SQL) holen
  // dump($subject); //SQL der rex_list ausgeben lassen

  if ($params['table'] == rex::getTable('my_table')) {

    $sql = 'my_sql_query'; // SQL neu sortieren

    $subject = $ep->setSubject($sql); // neue Liste setzen

  }

  return $subject; // neue Liste zurück geben

});
```

## Anhang

### be_manager_relation

> **Hinweis:**  
> Dieser Abschnitt der Doku ist noch nicht fertig. Du kannst dich auf [GitHub](https://github.com/yakamara/redaxo_yform/) an der Fertigstellung beteiligen.

In diesem Tutorial wird die Anwendung des be_manager_relation Feld-Typs erklärt.

Als Beispiel für das Tutorial dient dazu eine Tabelle mit News-Einträgen und einer Tag-Tabelle, für beide Tabellen werden per yForm Table-Manager die gewünschten Felder erstellt. 

Das Ziel des ersten Teils des Tutorials ist, den News-Beiträgen jeweils ein Tag zuweisen zukönnen (One to Many, 1:n).

Im zweiten Teil soll man einem News-Beitrag mehrere Tags zuweisen können (Many to Many, m:n). Dies lässt sich zwar auch mit One to Many bewerkstelligen, birgt aber diverse Nachteile. 

> Ergänzen mit Vor- und Nachteilen

Eine Many to Many Relation hingegen ist in der Wartung flexibler und zukunftssicherer. Obwohl im folgendem Beispiel nur abgehandelt wird, wie einem Beitrag mehrere Tags zugewiesen werden können, gäbe es auch die Möglichkeit, den Tags ebenfalls mehrere Beiträge zuzuweisen (also quasi der Umgekehrte Weg). Das Prinzip ist aber dasselbe und wer Lust hat, kann das selber als eine Art Hausaufgabe angehen. 

#### One to Many (1:n)

##### Schritt 1 - Tabellen erstellen

Als erstes werden per Table-Manager folgende Tabellen erstellt: 

> **Wichtig**
> Diese Tabellen dienen ausschliesslich der Veranschaulichung des Vorgangs (eine echte News-Tabelle würde voraussichtlich weitere Felder bzw. Spalten beinhalten).

> Es wird ausschliesslich das Feld mit dem Typ be_manager_relation genauer erklärt. Die Konfiguration anderer Feld-Typen wird hier genauer erläutert: [Table Manager: Feldtypen](table_manager_feldtypen.md)

###### Tabelle News-Tags `news_tag`

Feld-Name | Feld-Typ
------ | ------
`name` | `text`

###### Tabelle News-Beiträge `news_beitrag`

Feld-Name | Feld-Typ
------ | ------
`titel` | `text`
`text` | `textarea`
`id_tag` | `be_manager_relation`

Das Feld id_tag wird wie folgt konfiguriert (nicht erwähnte Optionen bleiben unverändert):

Option | Erläuterung
------ | ------
Priorität | Kann beliebig gesetzt werden. Der Default-Wert ist in diesem Fall passend.
Name | `id_tag`
Bezeichnung | `Tag`
Ziel Tabelle | `[rex_news_tag]` bzw. die Tabelle, die man mit der aktuellen verknüpfen möchte
Ziel Tabellenfeld(er) zur Anzeige oder Zielfeld | `name` bzw. den Inhalt den man bei erfassen sehen möchte. Da man beim Erfassen eines News-Eintrags die Namen der vorhandenen Tags sehen und auswählen können möchte, gibt man hier den Namen des entsprechendes Feldes aus der Tag-Tabelle an. 
Mehrfachauswahl | `select (single)` optional auch `popup (single)` möglich
Als Suchfeld aufnehmen | deaktivieren


##### Schritt 2 - Frontend-Ausgabe

Wenn man nun einen News-Beitrag erfasst oder editiert, findet man zuunterst das Feld "Tag", ein  SELECT-Feld welches die zuvor erfassten Tags beinhaltet. Speichert man den Datensatz ab, wird in das Feld `id_tag` (in der Tabelle `news_beitrag`) die gewählte Tag-ID geschrieben und kann so im Frontend schliesslich ausgelesen werden.

Zum Beispiel so:

```php
<?php
$sql = rex_sql::factory();
$query = 'SELECT * FROM ' . rex::getTable('news_beitrag') . ' JOIN ' . rex::getTable('news_tag') . ' ON ' . rex::getTable('news_beitrag') . '.id_tag = ' . rex::getTable('news_tag') . '.id ';
$rows = $sql->getArray($query);
foreach($rows as $row) {
    echo '<h1>' . $row['titel'] . '</h1>';
    echo '<p>' . $row['text'] . '</p>';
    echo '<p>Tag: ' . $row['name'] . '</p>';
}
?>
```


#### Many to Many (m:n) 


##### Schritt 1 - Tabellen erstellen

Als erstes werden per Table-Manager folgende Tabellen erstellt. 

> **Wichtig** 
> Diese Tabellen dienen ausschliesslich der Veranschaulichung des Vorgangs (eine echte News-Tabelle würde voraussichtlich noch weitere Felder bzw. Spalten beinhalten).
> Es wird ausschliesslich das Feld mit dem Typ be_manager_relation genauer erklärt. Die Konfiguration anderer Feld-Typen wird hier genauer erläutert: [Table Manager: Feldtypen](table_manager_feldtypen.md)

###### Tabelle News-Tags `news_tag`

Feld-Name | Feld-Typ
------ | ------
`name` | `text`

###### Tabelle News-Beiträge `news_beitrag`

Feld-Name | Feld-Typ
------ | ------
`titel` | `text`
`text` | `textarea`

###### Tabelle News-Tags-zu-Beitrag `news_tag_beitrag`

Feld-Name | Feld-Typ
------ | ------
`id_tag` | `be_manager_relation`
`id_beitrag` | `be_manager_relation`

Das Feld `id_tag` wird wie folgt konfiguriert (nicht erwähnte Optionen bleiben unverändert):

Option | Erläuterung
------ | ------
Priorität | Kann beliebig gesetzt werden. Der Default-Wert ist in diesem Fall passend.
Name | `id_tag`
Bezeichnung | `Tag ID`
Ziel Tabelle | `[rex_news_tag]`
Ziel Tabellenfeld(er) zur Anzeige oder Zielfeld | `id`
Mehrfachauswahl | `select (single)`
Als Suchfeld aufnehmen | deaktivieren

Das Feld `id_beitrag` wird wie folgt konfiguriert (nicht erwähnte Optionen bleiben unverändert):

Option | Erläuterung
------ | ------
Priorität | Kann beliebig gesetzt werden. Der Default-Wert ist in diesem Fall passend.
Name | `id_beitrag`
Bezeichnung | `Beitrag ID`
Ziel Tabelle | `[news_beitrag]`
Ziel Tabellenfeld(er) zur Anzeige oder Zielfeld | `id`
Mehrfachauswahl | `select (single)`
Als Suchfeld aufnehmen | deaktivieren


##### Schritt 2 - Hilfstabelle verknüpfen

Um die Verknüpfung abzuschliessen, geht's zurück zur Tabelle News-Beiträge `news_beitrag` wo am Ende der Tabelle ein weiteres be_manager_relation Feld angelegt wird und zwar mit folgenden Einstellungen (nicht erwähnte Optionen bleiben unverändert):

Option | Erläuterung
------ | ------
Priorität | Kann beliebig gesetzt werden. Der Default-Wert ist in diesem Fall passend.
Name | `tags`
Bezeichnung | `Tags`
Ziel Tabelle | `[rex_news_tag]`
Ziel Tabellenfeld(er) zur Anzeige oder Zielfeld | `name`
Mehrfachauswahl | `select (multiple)` oder `popup (multilple)`, für Einzelauswahl auch `select` oder `popup` möglich.
Relationstabelle | `news_tag_beitrag`
Leeroption | Falls ausgefüllt werden muss, deaktivieren
Als Suchfeld aufnehmen | deaktivieren


##### Schritt 3 - Frontend-Ausgabe

Wenn man nun einen News-Beitrag erfasst oder editiert, findet man ganz am Ende der Eingabemaske ein Feld mit Namen "Tags", entweder mit einem Multi-SELECT-Feld oder einem Rex-Widget mit Popup. Speichert man den Datensatz ab, wird in der News-Beitrag-Tabelle `news_tag_beitrag` die Zuordnung vom jeweiligen Beitrag zu den entsprechenden Tags gespeichert.

Im Frontend kann das ganze dann z. B. so abgefragt werden:

```php
<?php
$sql = rex_sql::factory()
$query = 'SELECT ' . rex::getTable('news_beitrag') . '.*, GROUP_CONCAT(' . rex::getTable('news_tag') . '.name SEPARATOR ",") AS tags ';
$query.= 'FROM ' . rex::getTable('news_beitrag') . ' ';
$query.= 'JOIN ' . rex::getTable('news_tag_beitrag') . ' ON ' . rex::getTable('news_beitrag') . '.id = ' . rex::getTable('news_tag_beitrag') . '.id_beitrag ';
$query.= 'JOIN ' . rex::getTable('news_tag') . ' ON ' . rex::getTable('news_tag') . '.id = ' . rex::getTable('news_tag_beitrag') . '.id_tag ';
$query.= 'GROUP BY ' . rex::getTable('news_beitrag') . '.id';
## $sql->setQuery($query);
$rows = $sql->getArray($query);
foreach($rows as $row) {
    echo '<h1>' . $row['titel'] . '</h1>';
    echo '<p>' . $row['text'] . '</p>';
    echo '<p>Tags: ' . $row['tags'] . '</p>';
}
?>
```

> die Query ist hier im Dienste der Lesbarkeit auf mehrere Zeilen aufgeteilt. Dass muss natürlich nicht zwingend so sein

[GitHub-Diskussion zum Thema Filter](https://github.com/yakamara/redaxo_yform_docs/issues/3)
