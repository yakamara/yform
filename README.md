YForm für REDAXO 5.2
=============


Installation
-------

* Ins Backend einloggen und mit dem Installer installieren

oder

* ZIP Paket aus https://github.com/yakamara/redaxo_yform herunterladen
* Unzippten Ordner von redaxo_yform zu yform umbenennen
* Ordner in den AddOns Ordner von REDAXO schieben
* Über das REDAXO Backenend das AddOn installieren und aktivieren


Last Changes
-------

### Version 2.0 // xx.xx.xxxx

#### Achtung !! Bitte genau anschauen und vor dem Update anpassen !!

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

### Version 1.0 // 01.06.2016

* Formularbuilder um einfache bis komplexe Formulare zu bauen
* Values definieren die Felder und Typen, Validierung definieren die Überprüfungen und Actions werden bei Erfolg ausgeführt
* Erstellungen eigener Email Templates, bei welchen man Patzhalter der Einträge nutzen kann (z.B. REX_YFORM_DATA[field="label"]). Weiterhin ist auch PHP möglich um spezifische Lösungen bauen zu können
* Als Basis diente die XForm von REDAXO 4
* Tablemanager: Verwaltung von selbst erstellen Tabellen mit den verschiednen Value und Validate Typen
* Es können alle Felder ergänzt werden
* Darstellungen können über die ytemplates gesteuert werden. Basis ist im Bootstrap - aber eigene Darstellung sind auch möglich
* Das Geo-PlugIn erweitert die Felder um GoogleMap zuordnungen und Reverse Adresse Lookup Funktionen (Bitte Googlelizenz und Rechte beachten)
