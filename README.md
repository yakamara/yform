YForm für REDAXO 5.2
=============

![Screenshot](https://raw.githubusercontent.com/yakamara/redaxo_yform/assets/manager_editdata.png)


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

### Version 2.1 // 26.01.2017

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

### Version 2.0 // 14.10.2016

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

### Version 1.0 // 01.06.2016

* Formularbuilder um einfache bis komplexe Formulare zu bauen
* Values definieren die Felder und Typen, Validierung definieren die Überprüfungen und Actions werden bei Erfolg ausgeführt
* Erstellungen eigener Email Templates, bei welchen man Patzhalter der Einträge nutzen kann (z.B. REX_YFORM_DATA[field="label"]). Weiterhin ist auch PHP möglich um spezifische Lösungen bauen zu können
* Als Basis diente die XForm von REDAXO 4
* Tablemanager: Verwaltung von selbst erstellen Tabellen mit den verschiednen Value und Validate Typen
* Es können alle Felder ergänzt werden
* Darstellungen können über die ytemplates gesteuert werden. Basis ist im Bootstrap - aber eigene Darstellung sind auch möglich
* Das Geo-PlugIn erweitert die Felder um GoogleMap zuordnungen und Reverse Adresse Lookup Funktionen (Bitte Googlelizenz und Rechte beachten)
