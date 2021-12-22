# Tools (Plugin)

> **Hinweis:**
> Plugins erweitern YForm und können optional aktiviert werden.


> Dieses Plugin hilft bei bestimmten Eingabearten. Datumsfelder, DatumZeit-Felder und Textfelder die bestimmte Eingaben verlangen, die man bereits bei der Eingabe erzwingen möchte.

Dabei werden die entsprechenden Bibliotheken bei der Aktivierung des AddOns bereits installiert und initialisiert. D.h. man muss die gewünschten Funktionen nur durch Definition von CSS Attributen zuweisen.

## select2

diese Bibliothek [https://select2.github.io/](https://select2.github.io/) hilft dabei, Selectfelder zu vereinfachen und entsprechend des Typs verschiedene Varianten zu aktivieren.

Dabei muss hier das select-Feld folgendes Attribut bekommen:

	data-yform-tools-select2 = ""

Das kann man im Manager über das Attributefeld innerhalb von z. B. select oder select_sql so setzen:

	{"data-yform-tools-select2": "", "placeholder": "My Placeholder"}

Eine weitere Variante wäre der Tag-Mode

	{"data-yform-tools-select2": "tags", "placeholder": "My Placeholder"}


## inputmask

Diese Bibliothek [https://github.com/RobinHerbots/Inputmask](https://github.com/RobinHerbots/Inputmask) dient dazu, bestimmte Eingabeformate vorzugeben um somit Fehler zu vermeiden. Bestimmte EIngaben können direkt im Frontend erzwungen werden.

Sehr praktisch ist z.B. das Erzwingen des Datumformates

    {"data-inputmask-alias":"datetime", "data-yform-tools-inputmask":"", "data-inputmask-inputformat":"yyyy-mm-dd"}

## daterangepicker

Diese Bibliothek [http://www.daterangepicker.com/](http://www.daterangepicker.com/) dient für die Auswahl von Datumsfeldern oder Datumzeiträumen. Dabei kann auch eine Uhrzeit selektiert werden.

> Bitte unbedingt beachten, dass man das selbe Format bei den Date(time)pickern einträgt, wie man es im entsprechenden Feld (z. B. Date) ausgewählt hat.

Dabei muss das Textfeld folgendes Attribut bekommen:

    data-yform-tools-datepicker = ""

oder

    data-yform-tools-datetimepicker = ""

und auch mit Formaten versehen werden. z. B. beim Datepicker

    YYYY-MM-DD

oder beim Datetimepicker

    YYYY-MM-DD HH:ii:ss

kann man im Manager über das Attibutefeld innerhalb von z. B. date mit input:text so setzen:

    {"data-yform-tools-datetimepicker":"YYYY-MM-DD HH:ii:ss"}

## Ein paar Beispiele für Kombinationen aus datepicker/datetimepicker und Inputmask

datepicker und Inputmask:

    {"data-yform-tools-datepicker":"YYYY-MM-DD", "data-inputmask-alias":"datetime", "data-yform-tools-inputmask":"", "data-inputmask-inputformat":"yyyy-mm-dd"}

datetimepicker und Inputmask:

    {"data-yform-tools-datetimepicker":"YYYY-MM-DD HH:ii:ss", "data-inputmask-alias":"datetime", "data-yform-tools-inputmask":"", "data-inputmask-inputformat":"yyyy-mm-dd hh:mm:mm"}
