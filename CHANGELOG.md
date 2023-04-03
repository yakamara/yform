Changelog
=========

Version 4.1.1 - 03.04.2023
--------------------------

### Korrekturen

* Fehler beim Import von Tabellen im Manager behoben.


Version 4.1.0 - 16.03.2023
--------------------------
### Neu

* PHP >= 8.1 und REDAXO mindestens 5.15
* Date/Datetime-Feld Default-Modifikationen eingebaut
* Tools-Plugin angepasst und Dateiauswahl um Today.. erweitert
* Felder: postSave Functions, damit sich Felder direkt an die Speicherung dranhängen können
* BE Ansicht: Umbau auf Fragment und Tablelayoutoption. Man kann man die Ausgaben/Layouts der BE Tabellen/Formulare anpassen
* dataset save - add history enable/disable feature ergänzt
* BE Tabellen: Klonen von Datensätzen nun möglich
* Dokumentation, Tippfehler
* index-Feld mit salt ergänzt, hashvalue nun dadurch deprecated und ersetzbar
* neue Methode um eine Url zum Datensatz zu erhalten
* Felder: date/datetime/time/datestamp Anzeigeformat um Notice ergänzt
* CodeStyle verbessert
* Manager: in den Backendliste sind nun mehr CSS Classen verfügbar um die Darstellung gezielter beeinflussen zu können
* Relationsfelder sind nun optimiert, sodass eine Frontend verwendung einfacher wird.
* Performance verbessert. Besonders beim Import und bei der Generierung von Tabellen
* be_link search added in backend
* manager: search in backend bei texten um ! (nicht) Suchen ergänzt
* NONCE Felder ergänzt, da REDAXO 5.15 nun NONCE Felder unterstützt
* manager: Liste der Datensätz: AktionsButton umgebaut. Waren nicht sinnvoll erweiterbar

### Korrekturen

* Upload in HistoryView war fehlerhaft
* checkbox: Ausgabewerte wurden im Table Manager in der Listenansicht nicht berücksichtigt
* Relations-Felder wurden nicht immer richtig übernommen.
* fieldset-Feld: Keys der Optionen fehlten
* In Whooops Pfad zum E-Mail-Template klickbar gemacht
* YTemplate Checkbox angepasst - Leerzeichen entfernt
* rex_yform_manager_collection: Methode current existiert in PHP 8 nicht mehr
* REST API mit REDAXO im Unterordner korrigiert
* REST API mit ähnlichen Endpointbezeichnungen gehen wieder.
* Textähnliche Felder werden in der Ausgabe nun auf 100 begrenzt. Doku wurde ergänzt mit Beschreibung wie man das individuell anpassen kann
* Nicht vorhandene REX_YFORM_DATA Felder erzeugen nun keine Notices mehr
* Uploadfeld war fehlerhaft. Konfiguration verbessert.
* Fehler bei Errormeldungen wenn Objekte nicht vorhanden sind behoben
* Signiature Feld funktionierte bei Touch devices nicht richtig
* historie: Speichern von 0 Werten korrigiert


### Sicherheit

* YORM. Das Escapen von Identifieren ist nun eingebaut. Wurde vorher nicht darauf geachtet, wären SQL Injections möglich gewesen.

Danke Thomas Blum, Gregor Harlan, Christoph Boecker, Alexander Walther, Daniel Weitenauer, Michael Rainer, jganthaler, Jelle Schutter, Gerald Urbas, Robert Rupf, cukabeka, Daniel Bagel, Norbert Micheel


Version 4.0.4 - 07.10.2022
--------------------------

### Korrekturen/Ergänzungen
* PHP Versionsabfrage war falsch. package angepasst.
* PHP >= 7.4


Version 4.0.3 - 30.09.2022
--------------------------

### Korrekturen/Ergänzungen
* be_relation
  * be_relation_view ytemplate eingeführt
  * be_manager suche verbessert 
* EMail Attachments werden an die E-Mail beim Versand nun angehängt (Danke Marco Hanke)
* History
  * In der Viewansicht wurden falsche Choice-Werte angezeigt.
  * Suche eingebaut nach ID, User, Datum und Aktion
* date/datetime Picker angepasst
* form hidden Feld Ausgabe flexibler
* EP `YFORM_EXECUTE_FIELDS` ergänzt
* Datenexport angepasst. Filter wurden nicht verarbeitet
* Type-Fixes, Textkorrekturen, generate_key: "no_db" ( Danke Norbert Micheel)
* google_geocode Texte verbesser (Danke Alex Walther und Thoomas Blum)
* PHP8.1 Anpassungen (Danke Christoph Boecker)
* Composer Korrekturen
* Query::findId um Alias ergänzt (Danke Christoph Boecker)
* Upload Feld erweitert um json Config und callback (für z.B. Virenscanner)
* Doku ergänzt (Danke Alex Walther und Netzproductions)
* Korrektur Formversand/Article ID (Danke Thomas Skerbis)
* Verbessertes Tracking (Danke Markus Staab)
* REST API Filter wieder zum laufen gebracht
* Codestyle und PHP 8.1 Optimierungen. Danke an Markus Staab für REXSTAN
* Datestamp wird nun im Frontend im Formular ausgeblendet
* E-Mail Validierung verbessert (Danke Wolfgang Bund)
* EP YFORM_DATA_LIST_LINKS wird nun auch bei table edit UND view ausgeführt#1262
* Choice Group in Table View Ansicht hatte Fehler geworfen


Version 4.0.2 - 09.03.2022
--------------------------

### Korrekturen/Ergänzungen
* Fehlerhafte Auswertung und Darstellung der be_relation Werte


Version 4.0.1 - 09.03.2022
--------------------------

### Korrekturen/Ergänzungen
* SQL Debugmeldungen beim Install/Update entfernt
* DOCs korrigiert. 
* PSALM ergönzt. in .tools -> `./vendor/vimeo/psalm/psalm`
* Erste phpunit Tests, CS korrigiert. in redaxo -> `phpunit  -c redaxo/src/addons/yform/.tools/phpunit.xml.dist`
  * Erster YForm Test. Anlegen, Löschen, Relations 
* be_media Fehler und multiple Nutzung korrigiert
* selectpicker funktioniere bei Choice nicht
* darkmode sortable korrigiert
* Altlasten - Schriften entfernt
* Warnung bei Massenbearbeitung ergänzt
* Relation mit Relationstabellen und Speicher, Ausgabe, YForm korrigiert
* Warning in uuid entfernt
* Falsche YForm Navigation wenn nur yform[email] ohne Admin korrigiert

Version 4.0.0 – 28.12.2021
--------------------------

### wichtige Änderungen
* Der Manager ist technisch stark umgebaut worden. Bitte unbedingt Info dazu lesen
* Viele deprecated Felder sind entfernt worden. Bitte genau durchlesen und beachten
* Wenn eigene Values und abgeleitete Methoden z.B. für z.B. getDefinitions genutzt werden, müssen jetzt die return types übergeben werden
* Die Links im Table-Manager sind jetzt mit CSRF-Schutz versehen. Weitere Infos dazu in der Doku. 


### deprecated / entfernt
* Validierung nach email entfernt. Bitte mit type und email ersetzen
* Felder captcha, captcha_calc,recaptcha(_v3) sind entfernt worden und werden auch bei der Installation direkt aus den Datenbanken entfernt, bitte stattdessen das addon yform_spam_protection verwenden
* Feld float entfernt, Bitte stattdessen das number Feld verwenden. Im Table-Manager wird es bei installation/Update automatisch zu number umgewandelt
* Felder checkbox_sql, radio, radio_sql, select_sql, select entfernt. Die Felder werden im Table-Manager bei Installation/Update zu choice umgewandelt. Bitte stattdessen das choice Feld verwenden
* Feld password entfernt. Wird automatisch mit text ersetzt und sollte mit ycom und ycom_password ersetzt werden
* Feld generate_password entfernt. Bitte stattdessen generate_key verwenden.
* classic ytemplates entfernt, da diese nicht und nie vollständig waren und fehler produzierten. wurde wohl auch nie genutzt
* E-Mail Templates *** ### Ersetzungen entfernt
* Doppelten EP YFORM_MANAGER_DATA_PAGE
* remembervalues Feld entfernt
* mediafile entfernt
* be_media_category und be_select_category entfernt
* Tools: select2 entfernt, Ersatz kommt durch REDAXO Core, ist in der YForm Dokumentation beschrieben

### Neu
* läuft nun auch auf PHP 8
* ❤️ Neue Rechtestruktur. Es gibt nun Ansichtsrechte und/oder Editierrechte für Rollen
* Viewansicht ergänzt. Automatische Viewansicht bei Relationstabellen.
* UUID Feld ergönzt
* Migration von Tabellen ohne Änderungen der Tabelle möglich
* Manager: Umbau der Suchen, Exporte etc. von eigenen SQL Einbindungen zu Yorm. EPs laufen hier nun anders.
* rex_yform_list ergänzt. Ersetzt rex_list und YORM Queries entgegennehmen
* EP: YFORM_DATA_LIST_QUERY statt YFORM_DATA_LIST_SQL (nun entfernt)
* EP: YFORM_LIST_GET statt REX_LIST_GET (aus der rex list)
* YFORM_DATA_TABLE_EXPORT nun mit anderen Parametern ($query als Subject)
* Manager: fragment layout nun relevanter für BE Ausgaben. Tiefere Änderungen möglich. 
* First View ist nun Manager Page wenn vorhanden
* Signature Feld ergänzt
* Manager: Liste von Datensätzen mit Actionbuttons angepasst, besserer Überblick und Erweiterbarkeit 
* Darkmode optimiert

### Korrekturen, Anpassungen, Bugs
* Export Tabellen und Felder optimiert. Danke @christophboecker
* Tablesetexport ist nun auf die nötigsten Felder beschränkt, kein Overhead mehr
* Dokuansicht korrigiert, ergänzt und optisch verbessert
* E-Mail Templateansicht erweitert
* E-Mail Versand, plain message Striptags entfernt
* E-Mail Template werden nun nach key validiert
* Manager: Datesuche korrigiert
* Manager: Tabellenansicht verbessert
* Fehlermeldungen bei nicht speicherbaren Formularen sind nun klarer
* Codestyling
* REST-API: include bei URL Params ergänzt und Felder einschränken zu können, die man gerne hätte
* REST-API: Eigene Header nun möglich
* Relation Typansicht/reihenfolge geändert
* be_relations verbessert. Suchen gingen nicht richtig wenn eigene Relationstabellen verwendet wurden.
* be_table Bugs behoben, date felder gingen nicht
* time-Feld: notices bei ergänzt
* number-Feld: Attributes ergänzt und nun auch als type number möglich
* date-feld umd typ HTML-date ergänzt
* empty name nun auch in der Suche
* Int Feld um BigInt erweitert
* Choice. Callback bei Labels nun möglich
* choice-Feld: Attributes ergänzt
* date/datetime/datestamp angepasst und angeglichen, flexiblere Angaben möglich, Nur noch Standard-ISO Eingaben möglich
* time angepasst. Stundenraster/Minutenraster entfern
* validate type - time Überprüfung verbesser
* datestamp hat nun auch eine Suche und Aktualierungen werden dem Redakteur deutlicher dargestellt
* upload Feld angepasst um System Fehlermeldungen und einen Reset Button
* Übersetzung der Tabellen bei den Benuterrechten geht wieder
* Suchen mit Relationen mit Relationstabellen sind korrigiert
* Tools Bibliotheken aktualisiert
* viele statische Codetests wurde durchgeführt.

Danke Christoph Boecker, Alexander Walther, Norbert Micheel, Thomas Blum, Dirk Schürjohann, Robert Rupf, Markus Staab, Thomas Skerbis, Tobias Krais, Wolfgang Bund, Jelle Schutter, Marco Hanke

Version 3.4.2 – 21.06.2021
--------------------------

### Korrekturen, Anpassungen, Bugs

* Media-Widgets: Kompatibilität zu REDAXO 5.12.1

Version 3.4.1 – 03.08.2020
--------------------------

### Korrekturen, Anpassungen, Bugs

* Sprachersetzungen ergänzt
* Doku angepasst
* Inline relations: Orginal verlor die eigenen Relation-Ids, Fehler beseitigt : Inline relation in inline relation
* Copy Objekt erstellt
* be_relation: Cache löschen nach Anlegen neuer Datensätze
* query: neue Methode whereListContains()
* manager: field identifier in manager rex_list had conflicts with Label
* be_select_category - permission check was wrong
* validate: compare_value type select wasnt working
* multiple related popups wasnt working. id management.

Danke an: Fernando Averanga, Jürgen Weiss, Yves Torres

Version 3.4 – 12.06.2020
--------------------------

### Korrekturen, Anpassungen, Bugs

* fields: be_media, mediafile, be_link: Medien und Artikel sind nun nicht mehr löschbar wenn in YForm vorhanden
* EP YFORM_MANAGER_DATA_PAGE_HEADER ergänzt, um sich in den Titel einer Tabelle zu hängen
* action: Email2tpl umgebau: E-Mail oder Label nun als 3. Feldparameter erlaubt
* date Field: Suche im Manager nun komfortabler und toleranter
* field: upload: diverse Korrekturen in speziellen konstellationen, session handling verbessert
* showvalue nun auch durchsuchbar im Manager
* manager: dataliste - rex_list cursor angepasst. seite bleibt nun erhalten bei edit, delete, add und übernehmen
* manager: be_relation with relation table - Werte wurden in der Liste falsch ausgegeben
* manager: be_media/list inline relations funktionierten in bestimmten konstellationen nicht richtig
* manager: suchergebnisse - tablenamen mit in Tabelle als class aufnehmen
* field: html und php werden nun mit codemirror gefütter wenn aktiviert
* field:mediafile width und height wird nun gespeichert wenn vorhanden
* field: prio: Attribute - Ermöglicht u.a. die Aktivierung der Live-Suche mittels
* Action: PHP Action ergänzt
* Diverse Übersetzungen ergänzt und korrigiert.
* validate: type: iban validierung ergänzt
* email template um updatedate ergänzt
* Page Parameter auf aktuelle Seite (#836) Die Verwendung von `rex_be_controller::getCurrentPage()` erlaubt das direkte Einbinden der data_edit Ansicht in einem eigenen Addon, bzw. weiterhin wie gewohnt in YForm.
* name-Attribut der Felder von Feld-ID zu Feld-Name geändert, um Yform-Inline-Tabellen zu unterstützen.
* Umbau der docs auf neuen REDAXO Standard (ist zwar im Moment noch hässlicher, aber das wird noch)
* Bei Popup Fenstern keine Beschreibung mehr. NImmt zuviel Platz ein.
* Optimierung hidden Field.
* datetime: negatives year offset nun möglich
* Massenlöschung in be_manager_relation Popup nun möglich
* Manager: Relationtabellen können nun im Nachhinein geändert werden.
* checkbox Ausgabewert für List nun einstellbar
* field:number -> decimal default werte werden gesetzt
* field: checkbox - suche - Texte nun sprachabhängig
* be_table: Diverse Anpassungen. Importproblem behoben, REX_LINK ermöglicht
* Diverse dumps, notices entfernt, Fehlermeldung optimiert ..
* action: redirect - wenn Fehler - dann wird kein redirect ausgeführt
* Bug: plugin: tools: in neueeren REDAXO Version gingen die Tools wegen eines Fehlers nicht mehr. rex:ready
* Bug: Transaction bei YForm DataImport korrigiert.
* Bug: Negativer Uniquekey für unter anderen bei InlineRelationen zu Fehlern
* Bug: manager: Fehler beim Umgang mit Zwischentabellen behoben

Danke an: xong, jelleschutter, NGWNGW, alexplusde, nandes2062, interweave-media, pschuchmann, DanielWeitenauer, dpf-dd, christophboecker, Geri2017, crydotsnake, engel4u, staabm, tskerbis


Version 3.3.1 – 30.10.2019
--------------------------

* Bug mit Relationsansichten korrigiert. z.B. Selectpicker ging nicht.


Version 3.3 – 25.10.2019
--------------------------

### Korrekturen, Anpassungen

* Be-Relation-Feld: Fehler bei inline Relationen korrigiert
* Diverse Debugausgaben entfernt
* time, date, datetime vereinheitlicht
* Rest Token wird nun angeboten
* Uploadfeld optimiert. Downloadfähig im Datensatz selber
* Be-Relation-Feld: Multiple Wert Ausgaben in den Listen korrigiert
* Datestamp Multiedit korrigiert. Wert wurde fälschlicherweise neu gesetzt
* Action redirect optimiert. Exit wird nach dem Formular (postactions) durchgeführt


Version 3.2 – 19.09.2019
--------------------------

### Korrekturen, Anpassungen

* Diverse Beschreibungen und fehlende Texte ergänzt, Übersetzungen nachgezogen
* YForm: EP YFORM_INIT ergänzt, um allgemeine Einstellungen einbinden zu können
* YForm: als factory trait nun möglich
* Docs Update
* Anpassungen Javascript um besser auf Objekte zugreifen zu können
* pipe Notation verbessert: Tabs werden am Anfang ignoriert. Zeilen die keine validate,action oder value Feld sind werden als html interpretiert (bessere Übersicht)
* Choice-Feld: Platzhalter wurden fälschlicherweise als Json erkannt, IDs werden nun angezeigt, Placeholder wird nun übersetzt
* Datetime-Feld: Manager - Nun auch durchsuchbar
* Validate-empty: Mehrfach Validierung als ODER nun möglich
* index-Feld: Funktionsaufruf optimiert. Parameter nun optional
* hidden-Feld: um SESSION, GET, und POST erweitert
* textarea-Feld: nun auch Defaultwert möglich
* time-Feld: Format nur noch für die Ausgabe und mit voreingestellten Varianten
* Tools: daterangepicker aktualisiert. v3.0.5
* Tools: Daterangepickeraufrufe optimiert
* Manager: Nach Datensatzlöschung wird Instanz nun direkt auch gelöscht
* Manager: Grid in Fragmente gelegt und somit überschreibbar gemacht, für eigene Layoutlösungen
* Manager: Collection-Edit nun als Transaktion
* Be-Relation-Feld: m:n Werte werden nun in der Listenübersicht angezeigt.
* Be-Relation-Feld: Popup wurde z.T. fehlerhaft angezeigt
* E-Mail: Template anlegen, nun ohne übernehmen, da dies nicht funktionierte
* REST: Pathangaben in der JSON-Antwort waren z.T. falsch
* REST: Tokenfreigabe können nun auf Endpoints beschränkt werden
* REST: Zugriffsübersicht zeigte falschen Tokennamen an.

Dank geht an: godsdog, Alexander Walther, Yves Torres, Pascal Schuchmann, Fernando Averanga, Jürgen Weiss, Marco Hanke




Version 3.1.1 – 24.04.2019
--------------------------

### Korrekturen

* mediafile Template war fehlerhaft und nicht nutzbar
* Email: mail_reply_to/mail_reply_to_name aus Liste entfernt
* Tools: Datepickerdefault Einstellungen geändert. Damit die Suche im manager wieder geht
* REST API: Feldererkennung war fehlerhaft.
* REST API: Notice entfernt bei Relations
* REST API: Selfrelations gehen nun
* REST API: Path geht nun auch mit oder ohne / in URL



Version 3.1 – 04.04.2019
--------------------------

### Neue Features, bitte beachten

* REST Plugin: Authentifizierung kann nun eingeschränkt und getrackt werden
* REST Plugin: POST und DELETE ergänzt
* YOrm: collection. Methoden ergänzt: first, last, filter, slice, shuffle, sort, map, split, chunk
* Feld: upload: Upload-Dialogfeld per Filter auf gewählte Filetypes begrenzen
* validate: password_policy: Wenn keine Fehlermeldung dann redaxo default
* YForm: objparams: Answertext entfernt
* manager: Filter und Sets umgebaut und vereinheitlicht
* manager: fixdata. Nicht veränderbare Daten sind setz- und erzwingbar. Attribute disabled wird erzwungen.
* EPs: YFORM_MANAGER_DATA_EDIT_FILTER und YFORM_MANAGER_DATA_EDIT_SET um im manager Aufrufe einschränken zu können.
* manager: Import: Wird nun als Transaktion durchgeführt. Bei Fehlern wird ein Rollback ausgeführt
* manager: Verlinkung bei table edit, Feld Edit, und Datensätze jeweils ergänzt.

### Änderungen und Korrekturen

* Feld: index um Typ `TEXT`
* Feld: emptyname um Typ `MEDIUMTEXT` ergänzt
* Feld: be_manager_relation um Typ `INT` ergänzt, be_manager_relation inline werte in email value pool übergeben
* Feld: validate: custom function liefert nun an die passenden Felder die Fehlermeldungen, func aufruf flexibler und yform_value klasse
* Feld: Datetime seconds hinzugefügt da notice. str_pad left für Anzeige bei select
* Feld: action: callback angepasst
* Feld: date/datetime: Deutsches Datums-Format hinzugefügt, Notices entfernt, yearEnd angepasst
* Vendor update inputmask
* Vendor update daterangepicker
* Update tools.js
* E-Mail-PlugIn: template Tabelle in utf8mb4_unicode_ci konvertiert
* Template: checkbox classic template korrigiert
* Template: value.choice.select.tpl.php für classic-Template erstellt
* Diverse Übersetzungen und DOCs
* YForm-Table-Cache löschen nach Installation oder Update
* manager: dataset: isValid()
* Massenbearbeitung korrigiert.
* send parameter dynamisch setzen geht wieder
* Email: reply_to angepasst und richtig eingesetzt
* manager:migrate Table mit tinyint geht wieder
* Icons der Subpages entfernt
* Feld: be_table geht nun wieder.
* Email: update funktionierte nicht richtig.

Danke: Yves Torres, Tobias Krais, Gregor Harlan, 8i11y, Alexander Walther




Version 3.0 – 01.02.2019
--------------------------

#### Warnung - Bitte nicht von 2.x auf 3.x updaten, ohne sich genau informiert zu haben. Bei Updates der Hauptversion sollte zuvor ein Datenbank-Backup durchgeführt werden. Bitte auch die Bemerkungen bei deprecated und bei den Umbenennungen/Löschungen beachten


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
* REST API als Plugin

### Änderungen und Korrekturen

* ReCaptcha umgebaut. Nur noch ValueObjekt nötig.
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

* values
    * radio Feld
    * radio_select Feld
    * select Feld
    * select_sql Feld
    * checkbox_sql Feld
    * float Feld
    * captcha
    * captcha_calc
    * generate_password
    * password
* actions
    * createdb
* validates
    * email

#### umbenannt und werden initial aus der YForm Definition entfernt

* action: createdb -> create_table
* labelexist -> in_names
* existintable -> in_table
* REX_YFORM_TABLE_DATA[table="tablename" output="widget/widgetlist"] -> REX_YFORM_TABLE_DATA[id=1 table="tablename" widget="1" multiple="1"]

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
