YForm für REDAXO 5.0
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

### Version 1.0 // 01.06.2016

* Formularbuilder um einfache bis komplexe Formulare zu bauen
* Values definieren die Felder und Typen, Validierung definieren die Überprüfungen und Actions werden bei Erfolg ausgeführt
* Erstellungen eigener Email Templates, bei welchen man Patzhalter der Einträge nutzen kann (z.B. REX_YFORM_DATA[field="label"]). Weiterhin ist auch PHP möglich um spezifische Lösungen bauen zu können
* Als Basis diente die XForm von REDAXO 4
* Tablemanager: Verwaltung von selbst erstellen Tabellen mit den verschiednen Value und Validate Typen
* Es können alle Felder ergänzt werden
* Darstellungen können über die ytemplates gesteuert werden. Basis ist im Bootstrap - aber eigene Darstellung sind auch möglich
* Das Geo-PlugIn erweitert die Felder um GoogleMap zuordnungen und Reverse Adresse Lookup Funktionen (Bitte Googlelizenz und Rechte beachten)