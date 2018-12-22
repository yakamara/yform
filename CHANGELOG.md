Changelog
=========

Version 3.0 – xx.xx.2018
--------------------------

#### Warnung - Bitte nicht von 2.x auf 3.x updaten, ohne sich genau informiert zu haben. Bei Updates der Hauptversion sollte zuvor ein Datenbank-Backup durchgeführt sein.

### Neue Features

* be_relations: 1-n Verknüpfungen nun über inline Modul möglich, inkl. Sortierung und überprüfung der verknüpften Formulare
  * inline relations verschachtelbar
  * entsprechend Felder wie be_media angepasst
* type: URL-Filter
* choice Feld ergänzt: ersetzt radio, radio_sql, select, select_sql, checkbox_sql.
* Warnmeldungen ohne Inhalt werden nun um technische Infos ergänzt.
* ! Felder geben nun den Datenbankfeldtyp fest vor. Felder werden auch nachträglich an das Datenbankfeld angepasst. Nun auch optional
* integer: um Maßeinheit ergänzt
* number: Neues Feld, mit Maßeinheit und richtigem DB Feldtyp
* ! datestamp: wird nun als datetime gespeichert. format nun für Anzeige, nicht mehr als Speicherformat.
* validate: type um json erweitert
* checkbox nun auch mit Attributen
* ! utf8mb4 ist nun standard und wird erzwungen, wie auch von Varchar(255) -> varchar(191). Möglicher Datenverlust bei zu langen Feldinhalten!

### Änderungen und Korrekturen

* ! Formbuilder textile entfernt (pschuchmann)
* Umbau des Feldnamenmanagements
* sql-Injection des Prio-Felds behoben
* Unnötige "Send"-Abfragen in den Validierungsklassen entfernt
* Feldwertermittlung korrigiert. Bei "0" waren Ergebnisse fehlerhaft
* internalForms (YORM) führte zu Problemen weil CSRF mit ausgeführt wurden und Sessions erstellt wurden.
* Snapshots über Console korrigiert, User wird nun auch richtig gesetzt
* Tablesetimport verbessert
* Dataimport verbessert und bessere Fehlermeldungen, Normalisierung der Feldnamen
* E-Mail YFORM_DATA output korrigiert auf html und plain
* MIT-Lizenz ergänzt
* Popupfenster schliesst nun bei Mehrfachselect nicht mehr direkt
* Templates überarbeitet: Grid entfernt, diverse Anpassungen class und bootstrap
* Diverse technische Optimierungen
* checkbox: keine eigenen Werte mehr. Ausschliesslich 0,1. DB-Type: tinyint. Deswegen unbedingt die Formulare entsprechend anpassen
* time: korrigiert (Norbert Micheel)
* captcha: korrekturen
* date: korrekturen (RexDude)
* be_table: korrekturen (Alex Platter)
* html: Infotext angepasst
* index: Fehler bei der Speicherung behoben, null wird nun vermieden
* upload: diverse anpassungen.
* emptyname: wird nun auch an YOrm übergeben, null wird nun vermieden
* Doku wurde aktualisiert
* Übersetzungen ergänzt
* UNIX Timestamp in historie auf serverdate geändert
* yform-element in ytemplates komplett entfernt
* Tabellenübersicht optimiert
* YTemplates: Grid überall entfernt, da nicht genutzt und nicht klar verständlich
* CSV Import mit fehlenden Feldern geht wieder. Fehlende Felder werden als TEXT angelegt
* README und CHANGELOG getrennt
* value: date/datetime Standardformat auf YYYY-MM-DD und YYYY-MM-DD HH:ii:ss geändert
* Bug: Mehrere YOrm Aufrufe konnten sich beeinflussen. REQUEST/YCom Problem
* be_manager_relation nun auch mit zusätzlichen Feldtyp varchar(191) ergänzt
* create_table, db: Mit %TABLE_PREFIX% im Tabellennamen kann man den Prefix der REDAXO Tabellen setzen.

Danke auch an Fernando Averanga, christophboecker, Wolfgang Bund, Alex Platter, Yves Torres, Alexander Walther, Jürgen Weiss für Übersetzungen, kleinere Korrekturen, Doku, Anpassungen an Templates.

#### deprecated

* radio Feld
* radio_select Feld
* select Feld
* select_sql Feld
* checkbox_sql Feld
* float Feld
* captcha
* captcha_calc
* createdb

#### umbenannt und werden initial aus der YForm Definition entfernt

* action: createdb -> create_table
* labelexist -> in_names
* existintable -> in_table
* REX_YFORM_TABLE_DATA[table="tablename" output="widget/widgetlist"] -> REX_YFORM_TABLE_DATA[table="tablename" widget="1" multiple="1"]

Version 2.3 – 26.01.2018
--------------------------

### Änderungen

* Manager Upload: Feld speicherte bei YORM nicht richtig
* Manager Upload: Fehlermeldung bei Typefehler erschien nicht
* YFORM: Nested Wheres korrigiert
* Tools: Timepicker angepasst
* geo locations: fixed
* Abhängigkeit ist nun REDAXO 5.5
* Benennungen angepasst name, label .. 
* uniqueform entfernt
* Umbau auf rex_sql_table
* GEO Plugin gelöscht, google_geo feld verschoben und bleibt

#### Neu

* YFORM: populateRelation() für weniger Queries bei Relationen
* be_media_category Feld ergänzt
* email Feld ist nun durchsuchbar
* notices können als HTML notiert werden
* Falsche Queries werden bei den _sql Feldern abgefangen
* Form Notation verfeinert
* Geo Plugin: Map Zoom nun einstellbar (danke alexplusde)
* Manager: Massenbearbeitung nun pro Tabelle setzbar
* Manager: Fieldpage optimiert. 100 Einträge pro Seite, Label aufgenommen in Übersicht
* Manager: Datensatz ID nun angezeigt
* Manager: Historie nun für alle User verfügbar (die Rechte auf die Tabelle haben)
* Docs aktualisiert
* Schwedische Sprache ergänzt
* Passwort Policy Field ergänzt. Durch REDAXO 5.4 Passwort Policy
* Unique: Leerfeldoption ergänzt
* be_table: kann nun auch REX Felder nutzen wie REX_MEDIA_WIDGET etc. (Danke Alex Platter)
* Action: tpl2email kann nun auch Fehlermeldungen ausgeben, wenn Versand schief lief.
* Manager: Suchmaske. Für die wichtigsten Felder wurden Infotexte ergänzt
* CSRF Schutz durch Nonce Feld gesetzt. Ist nun default aktiv. Über objparams['csrf_protection'] deaktivierbar
* select/_sql: Leerstrings werden bei der Suche ignoriert.

#### Fehler

* YORM: getRelatedDataset fixed. Verursachte Problem mit z.B. YCom Verknüpfungen
* Bei Update aus älteren YForms wird nun auch das be_medialist und submits entfernt
* select Feld: Falsche selected korrigiert, Defaultwert wird nun richtig übernommen
* radio feld hat keinen Fehler ausgegeben.
* Manager: CSV Import/Export optimiert. BOM gesetzt und entfernt. Unnötige Felder werden ignoriert, EnsureColumne bei AlterTable .. 
* redirect geht nun richtig mit REDAXO 5.4 Version
* Manager: Historieeinträge konnten bei bestimmten Relationen nicht wieder zurückgesetzt werden
* YOrm: Durch Nutzung von YORM, wurde immer der Send Status für alle Formulare gesetzt.
* EMail-Versand: Es konnte passieren, dass der AltBody nicht richtig gesetzt wurde (Danke Andreas Eberhard)
* Geokodierung war fehlerhaft (Danke Wolfgang)
* Darstellung war im Popup zum Teil noch miz Hauptnavi und REX-Header
* Upload Feld: min_error und max_error waren vertauscht
* datestamp feld. Werte anzeigen, es wurde nicht die aktuellen Felder, sondern nur die neuen angezeigt
* Manager: Exportdownload korrigiert
* ytemplates, bootstrap: translate meldungen nun auch mit html
* manager: popup bei relationen korrigiert
* checkbox field überarbeitet und korrigiert

Version 2.2 – 21.04.2017
--------------------------

### Änderungen

* DE/EN Inhalte vervollständigt
* Extension Point YFORM_EMAIL_SEND ergänzt
* bug bei submit und BC korrigiert
* datestamp-Feld um Text-Ansicht ergänzt
* generate_key um Text-Ansicht ergänzt
* generate_key um Definitionen ergänzt
* showvalue auch im Backend nutzbar
* BUG: value.be_manager_relation.tpl.php l78 warning cause of array passed into htmlspecialchars()
* preg_match auch im Backend nutzbar
* checkbox: erlaubte attribute ergänzt
* BUG: YForm: Exception vermeiden bei getRelatedDataset mit optionalem Relationsfeld
* YOrm: Neue Methode getRelatedQuery()
* YOrm: rex_yform_manager_table->table ergänzt
* be_relation: bei popup single, war die ausgabe beim suchfeld falsch
* Dataset-Cache: Klassen korrekt beachten
* BUG: getRelatedQuery: Verwechslung bei Relationstypen
* select_sql: Text Leeroption auf string casten
* YORM: Bei Abfrage einer einzelnen ID, orderBy nicht setzen
* YORM: weitere where-Methoden (whereNot, whereNull etc.)
* YORM: Methoden zum direkten Abfragen einer einzelnen Column
* notice: an verschiedenen Feldern sind notices übersetzbar
* index-Feld: ID kann nun mit aufgenommen werden
* be_manager_relation popup (single) warning entfernt

Version 2.1 – 26.01.2017
--------------------------

#### Achtung !! Bitte genau anschauen und vor dem Update anpassen !! BItte auch Hinweise der Version 2.0 beachten wenn ein Updaten von 1.x erfolgen soll

#### Neu

* classic view: Umgang mit Fehlern verbessert
* manager/tools:daterangepicker nun ohne autoupdate
* performance yorm verbessert. Nur Ausgabe wenn nötig.
* html und php Felder erzeugen nun keine eigene Spalte mehr in der Tabelle
* hiddenfields kann nun auch arrays
* manager: relationsfelder werden nun gecacht.
* massenoperatione: Performance stark verbessert
* debugstatus wird an diversen stellen nun weitergegeben
* Historiedarstellung: Bezeichnung wird geraten. Einzelinfoansicht eines Artikels. Einzelhistorie einseh- und löschbar.
* index Feld kann nun auch Unterfelder aus Relationen mit aufnehmen.
* diverse Textkorreturen
* Erweiterung der Dokumentation
* Email: Versand email und Versand-Email Name können nun auch ersetzt werden.
* select: Werteüberprüfung verbessert. Initial und Defaults korrigiert.
* upload: Diverse Fehler. Typabfrage, übernahme feld, textkorrekturen
* radiofelder überarbeitet. Fehler behoben
* datetime: formartierung angeglichen / listenansicht korrigiert
* be_link um mehrfachauswahl ergänzt
* Email: Templates: BC zu v1.0 .. Ersetzung wie ###key### gehen wieder ..
* Formbuilder: CS und Showtext wird nun immer nur verwendet wenn Text eingegeben wurde
* Captcha: Darstellung angepasst. Icon entfernt.
* YFORM Data Widget ergänzt. Context: module. Bsp: REX_YFORM_TABLE_DATA[id="12" table="address" output="widget" field="firstnam,' ',surname"]
* be_relation kann nun die gleiche ID mehrfach verwenden.
* Famous Felder wieder aufgenommen.

#### Bugs

* import: Tableset Fehler behoben
* validate type url: nun auch https
* action: createdb funktionierte nicht richtig
* massenoperationen: Problem mit date und datetime korrigiert
* select template: grid classes korrigiert
* manager dataset: mysql error getFields behoben
* be_manager_relation: check for empty values Fehler behoben

Version 2.0 – 14.10.2016
--------------------------

#### Achtung !! Bitte genau anschauen und vor dem Update anpassen !!

* Beim Update aus einer 1er Version werden veränderte Felder aus der Feldtabellendefinition gelöscht und müssen neu angelegt werden. Es werden keine echten Daten gelöscht.

* submits entfernt, mit submit kann man nun auch mehrere felder anlegen
* be_medialist entfernt und in be_media eingebaut.
* Klasse datetime hat sich verändert. Stichwort: Layout/Format
* Klasse date hat sich verändert. Stichwort: Layout/Format
* Klasse time hat sich verändert. Stichwort: Layout/Format
* REDAXO 5.2 ist mindestens nötig.
* fulltext_value.php entfernt da über index_value.php möglich
* action db_query.php umgebaut. Keine Fehlermeldung mehr und mehrere Labels zuweisbar und über ? im query setzbar
* value readttable entfernt, da nie funktionierte und nicht verwendet wurde und unnötig, da readtable als action existiert
* be_table wurde geändert. Speicherformat anders -> JSON, Kompatibilät zu R4 wird beachtet
* text/textarea wurde verändert. css_class wurde entfernt.
* uploadfeld: kein modus mehr - immer upload. Default Ordner data/addons/yform/plugins/manager/upload/[table]/[field]
* datestamp hat nun ein Label
* geocode verändert. geokoordinaten werden nun direkt kommasepariert im Feld gespeichert. Google-Api-Key kann nun gesetzt werden.
* objparam: form_id entfernt. es wird nun ausschliesslich form_name verwendet
* html/php Felder haben nun ein Label.
* objparams form_skin -> form_ytemplate

#### Neu

* Massenbearbeitung eingeführt
* History eingeführt, und die damit verbundenen Vereinheitlichungen. Zentrales anlegen,editieren und löschen. Kann über die Tabellenverwaltung aktiviert werden
* Dokumentation eingeführt, Darstellung und URL Parsing gesetzt, Submodul eingebunden. (Danke Alex Walther, Peter Bickel und Peter Wolfrum)
* ORM eingeführt. Erklärung - siehe Doku.
* PlugIn Tools ergänzt für Datepicker und inputmask und select2 / Beispiel in attributes setzen: {"data-yform-tools-datepicker2":"DD-MM-YYYY", "data-yform-tools-inputmask":"99-99-9999"} oder {"data-yform-tools-select2":""}
* attributes Element ergänzt. Dadurch lassen sich z.b. in den input Feldern bei text/textarea die attribute setzen/ersetzt werden. Somit lassen sich nun endlich redactor und codemirror sinnvoll einsetzen
* An vielen Stellen notices ergänzt. Z.B. bei Text, Textarea, Select
* Manager: Fieldmanager / getDefinitions erweitert. Ist nicht mehr beschränkt auf bestimmte Felder
* Manager: Darstellung der Suche nun neben der Datensätze und bleibt bei Editieransicht erhalten
* Import wird nun validiert. D.h. Es können nur Datensätze importiert werden, welche erfolgreich durch die Validierung geht. -> Mehr Konsistens in den Tabellen
* Massenlöschung kann nun in der Tabellenverwaltung deaktiviert werden.
* Sprachunabhänigkeit bei den meisten Klassen eingebaut
* radio_sql nun auch im Manager verfügbar
* Diverse Codeverbesserungen und Vereinheitlichungen
* Diverse Textanpassungen und Übersetzungnethoden gesetzt.
* yform[] Recht entfernt. Nur für Admins freigegeben.
* E-Mail Validierung nach FrontendBrowserValidierungsStandard gesetzt:
* E-Mail Templates werden nun über Codemirror dargestellt, wenn REDAXO Core Customizer Plugin aktiviert ist.
* Default error class auf "has-error" gesetzt.
* Permissions bei Tabellen angepasst. Versteckte Tabellen können nun auch genutzt werden.
* email: E-Mail Subjects können nun auch Ersetzungen verwenden
* yform value pool um files ergänzt. Uploadfelder können nun per E-Mail verschickt werden.
* identische Feldermeldung werden bei Ansicht über dem Formular nun zusammengefasst und dadurch nur einmal ausgegeben


#### Bugs

* be_select_category beachten nun die clangs richtig
* redirect - urlparameter wurden falsch gesetzt
* notationdarstellung - default auf bootstrap gesetzt. action von email auf tpl2email gesetzt.
* be_relations - Diverse Fehler behoben
* Extension Point YFORM_DATASET_IMPORT Benennung angepasst
* Extension Point REX_YFORM_SAVED repariert
* Wenn man als Nichtadmin Tabellenrechte bekommt, erscheint nun nicht mehr YFORM als Navigationspunkt, sondern nur die Tabelle
* Googlemap aufruf bei Geo nun unabhängig vom Protokoll http:// -> //
* Korrekturen an der mobilen Darstellung
* action readtable wurde doppelt ausgeführt.
* Value.tpl.php reagierte bei values nicht immer richtig. YCom Password Problem dadurch gelöst
* geocode Höhen/Breite können nun vernünftig gesetzt werden.
* problem mit captch behoben
* Fehler bei hide_field_warning_messages behoben

Version 1.0 – 01.06.2016
--------------------------

* Formularbuilder um einfache bis komplexe Formulare zu bauen
* Values definieren die Felder und Typen, Validierung definieren die Überprüfungen und Actions werden bei Erfolg ausgeführt
* Erstellungen eigener Email Templates, bei welchen man Patzhalter der Einträge nutzen kann (z.B. REX_YFORM_DATA[field="label"]). Weiterhin ist auch PHP möglich um spezifische Lösungen bauen zu können
* Als Basis diente die XForm von REDAXO 4
* Tablemanager: Verwaltung von selbst erstellen Tabellen mit den verschiednen Value und Validate Typen
* Es können alle Felder ergänzt werden
* Darstellungen können über die ytemplates gesteuert werden. Basis ist im Bootstrap - aber eigene Darstellung sind auch möglich
* Das Geo-PlugIn erweitert die Felder um GoogleMap zuordnungen und Reverse Adresse Lookup Funktionen (Bitte Googlelizenz und Rechte beachten)
