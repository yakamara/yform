# Alternative DB aus config.yml nutzen

> Wenn in der `config.yml` mehrere Datenbankverbindungen eingerichtet sind, lässt sich YForm auch mit diesen
> Datenbanken nutzen.

## Verfügbarkeit der Funktion

Ist nur eine Datenbankverbindung in der `config.yml` eingerichteten, werden die Bedienelemente für multiple Datenbanken
nicht angezeigt. Erst wenn mehrere Verbindungen eingerichtet sind, aktivieren sich die Elemente automatisch.

### Im Migrator

In der Tabellen-Migration haben alle für die Migration verfügbaren Tabellen ein Prefix mit der Datenbank, in welcher sie
liegen vor ihrem Namen.

### Beim Erstellen

Beim Erstellen einer neuen Tabelle über den Table Manager lässt sich die gewünschte Datenbank auswählen. Wird eine
Tabelle im weiteren Verlauf bearbeitet, so wird die Datenbank, in welcher sie gespeichert ist, als Information
angezeigt.

## ⚠ Wichtige Hinweise

1. Relationstabellen müssen in derselben Datenbank liegen wie die verknüpfte Tabelle. Es erfolgt **keine 
   Überprüfung**, ob die Tabelle wirklich in derselben Datenbank liegt. Die Verantwortung dafür liegt beim 
   Datenbank-Designer, welcher die Relation erstellt.
2. Die Verwaltung der Daten liegt immer in Datenbank 1.
