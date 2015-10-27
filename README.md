yform for REDAXO Version 4.6
=============

AddOn to manage tables and forms for REDAXO CMS Version 4.6


Installation
-------

* Download and unzip
* Rename the unzipped folder from redaxo_yform to yform
* Move the folder to your REDAXO 4.6 System /redaxo/include/addons/
* Install and activate the addon yform and the plugins setup, manager, email in the REDAXO 4.6 backend
* Die aktuelle github Version nur als Entwicklerversion betrachten

Last Changes
-------

### Version 4.13 // 09.09.2015

#### Neu
* Neues Feld: submits - Mehrere Submitfelder nebeneinander
* Manager: Ein Datensatz kann beim Editieren und Anlegen "übernommen" werden. (Man springt nicht mehr direkt in die Übersicht)
* Neuer Extension Point: yform_MANAGER_DATA_PAGE um einzelne Detailseiten beeinflussen zu können
* redirect: Exit entfernt. Die REDAXO Seite wird nun komplett ausgeführt
* upload: Eigener Ordner möglich, Default wird gesetzt, Download im Backend wie Listenansicht eingebaut
* Manager: In Suche aufgenommen: textarea, select, select_sql
* validate.existintable: Ergänzt. Mehrere Felder sind nun möglich
* Bootstrap: Captcha-Template mit aufgenommen, Checkboxen-Template ergänzt, horizontale Elemente mittels Grid-System
* Fehlermeldungen können nun auch direkt am Feld hängen. Wird bisher nur von den Bootstraptemplates beachtet
* compare und compare_value: sind um Vergleichsoperatoren ergänzt, =, >= ...
* date und datetime um variables Endjahr ergänzt
* validate_unique: Betroffene Felder werden nun mit error_class belegt
* Alle Feldtypen (validate, value, action) haben nun Aktionsmethoden, postAction(), preAction()
* select Feldwerteingaben können nun in E-Mail-Templates verwendet werden. z.b. anrede_NAME

#### Bugs
* Manager
** Selectfeld: Werte wurden in der Übersicht nicht übersetzt, Suche ging nicht richtig
** Listen: truncate_table wurde bei den Rechten nicht beachtet
** Tabellennamen werden nun überall richtig ausgelesen
* Datestamp: Werte werden nun bei Bedarf auch initial gesetzt, "nur wenn leer setzen" funktioniert wieder
* Diverse Textkorrekturen
* be_mediapool: Fehlende ID ergänzt
* be_link: Fehlende ID ergänzt
* be_medialist: Fehlende ID ergänzt
* radio: Es wird nun immer ein Wert gesetzt. Wenn nichts passendes gefunden wird oder per Default nicht definiert wird, wird der erste Wert genommen, "0" als Wert kann nun auch gesetzt werden
* Addon Geo: Diverse Fehlerkorrekturen und Anpassungen
* be_relation: Popup-Öffnen auch für multiple korrigiert, Suche korrigiert, wenn Concat als Feldbezeichner verwendet wurde


### Version 4.12 // 15. Februar 2015

#### Bugs
* Installation geht wieder. "class class.rex_yform.inc.php not found".


### Version 4.11 // 4. Februar 2015

#### Bugs
* Manager: Relationfeld 1-n verursachte in der Suche fehler. Suche hier entfernt.


### Version 4.10 // 3. Februar 2015

#### Neu
* Neue Felder: integer, float, be_select_category
* validate_customfunction: Funktion kann in jeglichem Callback-Format übergeben werden (inkl. Closures)
* Manager: Feldabhängige Suchfelder (Nur wenige Felder im Moment vorhanden)
* Manager: Api zur Abfrage der Tabellen und Felder vereinfacht/vereinheitlicht
* Manager: Relationen vom Typ 1:n können direkt aus der rex_list per Link geöffnet werden
* Manager: Integerfelder kann man nun auch als NULL in der Datenbank speichern (Option)
* Bootstrapbasis Templates eingesetzt.
* Neuer EP: yform_MANAGER_SUBPAGES_TABLES um Tabellenlink beeinflussen zu können
* Selectfelder können nun "disabled" werden.
* Manager: Auch radio-Felder sind nun möglich

#### Bugs
* Manager: Bessere Abwärtskompatibilität zu alter Tabellenstruktur
* Datefeld: Defaulteinstellung erweitert.
* geoplugin: css aktualisiert (danke ceekay82)

### Version 4.9 // 20. Oktober 2014

#### Neu
* Submit Klasse mit eigenem Value möglich
* Umbau von Managerklassen (taböe, field) für flexiblere Verwendung, inkl. arrayaccess Rückwärtskompatibilität
* Validate Klasse Type kann nun auch Hex Werte validieren
* Manager: Tablesets eingeführt. Manager Tabellen können ex- und importiert werden (Json)
* Upload Klasse ergänzt. Datei in die Datenbank oder ins Filesystem legen
* Kleinere optische Umbauten: Kein Infolayer mehr, Tabellenansicht angepasst ..
* Fehlende Texte, oder falsche/unschöne Texte angepasst

#### Bugs

* datetime Klasse um currentdate erweitert
* time Klasse war falsch beschrieben, Formatierung ging nicht,
* removeRelationTableRelicts führte zu Fehlern, wenn Relationtabelle nicht existierte
* Radio Klasse funktionierte nicht richtig. Beschreibung war != zur Funktion.
* Select Klasse hatte die Anzahl der Einträge falsch gezählt
* Manager: Reservierte Feldnamen führte zu Problemen.
* Uninstall: email template tabelle wird nun entfernt
* Update über Installer beachtet die PlugIns nicht richtig. Workaround gebaut
* Sprachkeys an prefix Notation angepasst. "yform_"..


### Version 4.8 // 1. September 2014

#### Neu

* Prio-Value-Klasse für Bestimmung der Reihenfolge von Datensätzen (wird auch in der Tabellen- und Felder-Verwaltung des Managers verwendet)
* In Pipenotation kann der Schlüssel eines Elements explizit angegeben werden (z. B. "text|titel|#placeholder:Titel")
* Manager: Migrationsmanager ist über das Backend verfügbar und einsetzbar.
* Manager: Migrationsmanager unterstützt weitere Feldtypen
* Manager: Standardsortierung kann festgelegt werden
* Manager: Echte Relationstabellen können verwendet werden
* Manager: Anzeigefelder bei Relationen werden rekursiv aufgelöst (falls Anzeigefeld selbst eine Relation ist, wird dessen Anzeigefeld verwendet etc.)
* Manager: Für Relationsfelder können mehrere Anzeigefelder und Konstanten angegeben werden, die verknüpft werden (z. B. `lastname, ", ", firstname`)
* Manager: Für Relationsfelder können Filter gesetzt werden

#### Bugs

* Manager: Tabellen laufen nun über die Translatefunktion und können mehrsprachig sein.
* E-Mail-Validierung: Adressen mit UTF8-Zeichen werden akzeptiert.
* Typ-Validierung: Missverständlicher Key "required" in "not_required" umbenannt
* yform-Felder für vorhandene Spalten konnten nicht angelegt werden, falls bereits eine Validierung für das Feld vorhanden war
* Manager: Beim Editieren und Löschen von Tabellen und Feldern bleibt man auf der entsprechenden Seite
* Manager: Für Fieldsets keine Spalten in Datentabelle anlegen
* select_sql: Bei Verwendung der Leeroption kam es zu ungewollten Verhalten
* Manager: Verschachtelte Popups waren nicht möglich


### Version 4.7 // 25. Juli 2014

#### Neu

* Migrationsmanager (Basis) / rex_yform_manager_table_api::migrateTable($tablename);
* Manager Api, damit andere AddOns yform Felder automatisiert anlegen können /rex_yform_manager_table_api::setTable($tablearray, $fieldsarray)

#### Bugs

* Manager: Beim Löschen von Datensätzen bleibt man nun auf der entsprechenden Seite


### Version 4.6.3 // 22. Juli 2014

#### Neu

* date, datetime und time haben nun auch die no_db option
* aus tabelle heraus als admin direkt zum Felder bearbeiten springen
* Felder und Tabellenlisten im Manager sind umstrukturiert (Feldnamen sortiert und übersetzt)


#### Änderungen

* Default Klassenbeschreibung in base abstract entfernt.
* Benennungen geändert

#### Bugs

* Export der Daten nicht erlauben - wird nun richtig ausgewertet
* einige individuelle css klassen wurden nicht getrennt gesetzt
* Ausgabe der Klassenbeschreibungen werden nun nur mit richtig definierte Dateiname (class.*.inc.php) angezeigt
* E-Mail Validierung geht wieder und wird auch nach ".." kontrolliert.
* GeoPlugin funktionierte nicht richtig. Achtung lat/lng Reihenfolge ist nun umgedreht.


### Version 4.6.2

#### Bugs

* db2email action ging nicht.


### Version 4.6.1

#### New

* Manager: 1-n Verknüpfungen ergänzt, inkl Darstellung in der Popupansicht.
* Elemente kann man und sollte man nun mit Namen verwenden (nicht mehr ids/Zahlen). Vorhandene Klassen wurden bereits angepasst
* Templates integriert: Man kann nun eigenes Markup für die Formulare verwenden. Default skin entspricht classic yform Markup
* php 5.3 Anpassungen inkl. Formularverwendung von PHP aus verbessert.
* Codeeinrückungen/Darstellung angepasst
* Texte und Benennungen geändert und korrigiert.
* Deleted: jquery.value class weil zu kompliziert und kaum verwendet


#### Bugs

* Objectparams in runtime mode are available now
