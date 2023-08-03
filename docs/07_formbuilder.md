# Formbuilder

## Einführung

### Installation des Formbuilder-Moduls

Innerhalb von YForm gibt es im Menüpunkt `Übersicht` unter `Setup` den Button `Modul "YForm Formbuilder installieren"`. Damit kann man das Formbuilder-Modul erstellen.

### Verwendung

Im Eingabefeld des Formbuilder-Moduls kann man die Values, Validierungen und Aktionen direkt eintragen. Eine kurze Syntaxerklärung aller Komponenten ist im Modul zu finden.

#### [Value-Felder](#values)
Value-Felder sind die am häufigstgen verwendeten Felder, die normalerweise im Formular direkt auftauchen: einfache Textfelder, Selectfelder, Checkboxen, aber auch versteckte Felder, Geburtsdaten, Datenbank-Selectfelder, etc.

#### [Validate-Felder](#validierung)
Mit Validate-Feldern werden die Werte der Value-Felder überprüft. Das heißt, damit wird z. B. valdiert, ob ein Wert eingetragen wurde (`empty`) oder ob ein `String`, `Integer` oder sonstiger Wert eingetragen wurde. Es kann aber auch überprüft werden, ob ein Datenbankfeld mit diesem Wert schon existiert.

#### [Action-Felder](#actions)
Action-Felder sind für spätere Verwendungen wichtig: Soll z. B. eine E-Mail verschickt werden und/oder ein Eintrag in die Datenbank erfolgen?

#### [Objparams-Definitionen](#objparams)
Objektparameter sind Einstellungen, die das ganze Formular betreffen. Mann kann dort z. B. CSS-Klassen oder IDs für das Formular festlegen oder das Ziel das Formularversands definieren.

### Syntax

Im Normalfall folgt die Syntax folgendem Schema: zuerst kommt der Feldtyp, dann der Name, dann die Optionen, jeweils durch einen Trennstrich (Pipe) voneinander abgetrennt.

**Beispiel:** Hier erscheint zunächst ein Textfeld, dann wird validiert, ob ein Wert eingetragen wurde. In der Zeile darunter wird ein Selectfeld definiert, danach folgt eine Aktion, um die Daten in die Datenbank "adressen" zu speichern:

	text|name|Nachname
	validate|empty|name|Bitte einen Nachnamen eingeben
	choice|anrede|Anrede|Anrede=,Frau=w,Herr=m
	action|db|adressen|

Die vollständigen Optionen für jedes Feld kann man direkt im YForm-Modul ersehen. Beim Textfeld finden sich z. B. folgende Optionen:

	text|name|label|defaultwert|[no_db]|cssclassname

- **text:** Dies definiert den Feldtyp.
- **name:** der interne Feld-Name.
- **label:** das vor dem Feld sichtbare Label (Feldbeschriftung).
- **defaultwert:** Damit kann man einen Standardwert in das Feld setzen.
- **no_db:** Speichert eine Aktion die Felddaten in die Datenbank. So gibt es hin und wieder Felder, die man nicht gespeichert haben will, z. B. den Wert eines Submit-Buttons. Dieser Wert ist optional, symbolisiert durch die eckigen Klammern.
- **cssclassname:** Damit kann man dem Feld eine individuelle CSS-Klasse zuweisen.

Es ist auch möglich, alle oder einzelne parameter nach der letzten pipe via `#attributes:{"key":"value"}` direkt zu adressieren:

	text|phone|Telefon|#attributes:{"class":"phone-class"}
	choice|is_approved|Neu|ja,nein|1|1|#group_attributes:{"class": "custom-control custom-switch"}
	

### PHP-Schreibweise

Um ein Formular mit YForm-Methoden zu generieren, muss nicht das Formbuilder-Modul genutzt werden. Man kann alles auch direkt in PHP schreiben:

**So sieht das obige Beispiel in PHP aus:**

```php
<?php
$yform = new rex_yform();
// $yform->setDebug(TRUE);
// auskommentieren, um Probleme zu finden
$yform->setValueField('text', array("name","Nachname"));
$yform->setValidateField('empty', array("name","Bitte einen Nachnamen eingeben"));
$yform->setValueField('choice', array("anrede","Anrede","Anrede=,Frau=w,Herr=m"));
$yform->setActionField('db', array('adressen'));
echo $yform->getForm();
```

> **Tipp:** Bei einer mit dem Table Manager angelegten Tabelle kann man sich den Formular-Code in den Versionen PHP (hilfreich bei eigenen Anpassungen), PIPE (im YForm-Modul verwendet) und E-MAIL (für den Versand mit Email-Templates) generieren lassen.

### Vordefinierte Aktionen

Standardmäßig ist die vordefinierte Aktion auf `Nichts machen (actions im Formular definieren)`. Dies ist auch die beste Option, denn durch die Actions in der Moduleingabe hat man wesentliche mehr Funktionen als bei den vordefinierten Aktionen, die eher für Einsteiger gedacht sind oder wenn man nur einfache Standardfunktionen benötigt. Außerdem kann man bei der manuellen Eingabe beliebig viele Actions auslösen, bei den vordefinierten Aktionen jedoch nur eine.

Folgende vordefinierte Aktionen stehen zur Verfügung:

#### Nur in Datenbank speichern

Hier muss man lediglich die Zieltabelle auswählen, in die gespeichert werden soll. Alle Felder des Formulars müssen auch als Spalten in der Datenbank existieren. Eine Ausnahme bilden die Felder, die als `no_db` gekennzeichnet sind. Diese Aktion entspricht der Action [db](yform_modul_actions.md).

#### Nur E-Mail versenden

Bei dieser Option muss die Sende-E-Mail-Adresse, die Empfänger-E-Mail-Adresse, der Betreff (Subject) und der eigentliche Mailtext (Body) eingetragen werden. Für alle im Formular vorkommenden Felder wird der Platzhalter-Code angezeigt, den man mit Copy&Paste in das Mailbody einsetzen kann. Wenn ein Feld von ###Doppelkreuzen### umschlossen eingegeben wird, wird es beim Versand gegen den richtigen Feldwert ersetzt. Diese Aktion entspricht der Action [email](yform_modul_actions.md).

#### E-Mail versenden und in Datenbak speichern

Diese Aktion ist lediglich eine Kombination der beiden oben erklärten Aktionen.

#### Meldung bei erfolgreichem Versand

Zusätzlich zu den drei vordefinierten Aktionen kann man noch eine Erfolgsmeldung definieren, wahlweise im Format `Plaintext`, `HTML` oder `Textile`. Diese Aktion entspricht der Action [showtext](yform_modul_actions.md).

### Kurzer Blick in die Modulausgabe

> **Tipp:**
> Wenn man sich den Eingabe- und Ausgabe-Code des YForm-Moduls ansieht, hilft dies, das Prinzip von YForm besser zu verstehen. In der Modulausgabe findet sich z. B. Folgendes:

	$yform = new rex_yform;
	$form_data = 'REX_VALUE[3]';
	$form_data = trim(str_replace("<br />","",rex_yform::unhtmlentities($form_data)));
	$yform->setObjectparams('form_action', rex_getUrl(REX_ARTICLE_ID,REX_CLANG_ID));
	$yform->setFormData($form_data);

Zunächst wird ein neues rex_yform-Objekt erzeugt.
Dann werden bei den im Textarea-Feld eingetragenen Felder die `<br>`-Tags herausgefiltert, als Standard-Zieladresse nach dem Abschicken die aktuelle Seite in der aktuellen Sprache definiert und die Felddefinitionen dem Objekt zur Verarbeitung übergeben.

Ebenfalls in der Modulausgabe kann man erkennen (nicht im obigen Beispiel enthalten), wie die vordefinierten Aktionen aufgebaut sind. So wird z. B. im Modul-Output der nach dem Abschicken sichtbare Text und das Anzeige-Format definiert oder der E-Mail-Versand konfiguriert.  
Alle diese Aktionen lassen sich auch leicht in eigenem PHP-Code auslösen; das YForm-Builder-Modul erleichtert lediglich diese Arbeit.

## Objparams

### Zweck der Objparams

**Objektparameter** fungieren vor allem als Einstellungen, die das ganze Formular betreffen. Diese Paramenter können - ähnlich wie die Values oder Validates – als einzeilige Anweisung gesetzt werden.

Zusätzlich kann man bestimmen, ob der Objektparameter an genau der Stelle des Formulars verändert wird, an der er im Formular gesetzt wird (`runtime`) oder den Wert initial setzt (`init`, das ist die Standardeinstellung).

Die **allgemeine Syntax** für das Setzen eines objparams lautet so:

	// Im YForm-Formbuilder
	objparams|key|newvalue|[init/runtime]

```php
<?php
$yform->setObjectparams('key', 'newvalue', '[init/runtime]');
```

- key = die Bezeichnung des Wertes
- newvalue = der Neue Wert, der gesetzt werden soll
- Der letzte Parameter ist optional und lautet `init` (default) oder `runtime`.
- **Im Folgenden werden alle objparams mit Beispiel aufgelistet.**

### Allgemeine Objparams des Formular

#### Formular anzeigen

	// Im YForm-Formbuilder
	objparams|form_show|0

```php
<?php
$yform->setObjectparams('form_show','1');
```

Mit dem Wert `0` wird das Formular nach dem Abschicken nicht angezeigt. Dieses Ausblenden benötigt man, wenn man eine Formular-Aktion auslösen will, aber kein sichtbares Formular haben möchte. **Beispiel:** Ein User wird durch den Aufruf einer bestimmten URL freigeschaltet.  

Der Defaultwert ist `1` (anzeigen).

#### Eindeutige Namen Für Felder

	// Im YForm-Formbuilder
	objparams|form_name|formular

```php
<?php
$yform->setObjectparams('form_name','zweites_formular');
```

Wenn man mehrere Formulare auf einer Seite verwenden möchte, muss der `form_name` für jedes Formular verschieden sein. Der hier gewählte Name wird bei jedem Feld eines Formulars dem Namen und der ID hinzugefügt, so erhält man eine Eindeutigkeit.  
Der Defaultwert ist `formular`.

#### CSS-Klasse für Formular

	// Im YForm-Formbuilder
	objparams|form_class|contact_form

```php
<?php
$yform->setObjectparams('form_class','contact_form');
```

Damit kann dem Formular eine individuelle CSS-Klasse vergeben werden.  
Default-Ausgabe:
`<form class="rex-yform">`

#### CSS-ID für den HTML-Wrapper

	// Im YForm-Formbuilder
	objparams|form_wrap_id|contact_form

```php
<?php
$yform->setObjectparams('form_wrap_id','contact_form');
```
Damit kann dem das Formular umgebenden Container eine individuelle CSS-ID vergeben werden.  
Default-Ausgabe:
`<form id="yform">`

#### CSS-Klasse für den HTML-Wrapper

	// Im YForm-Formbuilder
	objparams|form_wrap_class|contact_form

```php
<?php
$yform->setObjectparams('form_wrap_class','contact_form');
```

Damit kann dem das Formular umgebenden Container eine individuelle CSS-Klasse vergeben werden.  
Default-Ausgabe:
`<form class="yform">`

#### Ausgabe der Label

	// Im YForm-Formbuilder
	objparams|form_label_type|html

```php
<?php
$yform->setObjectparams('form_label_type','html');
```

Wenn man den Wert hier auf `plain` setzt, werden die Feld-Label nicht als HTML interpretiert, sondern mit `rex_escape` und `nl2br` maskiert.  
Default ist `html`.

#### CSRF-Schutz

	// Im YForm-Formbuilder
	objparams|csrf_protection|0

```php
<?php
$yform->setObjectparams('csrf_protection', false);
```

Der Parameter zum CSRF-Schutz (Cross-Site-Request-Forgery, auch XSRF) verhindert, dass speziell präparierte Anfragen von YForm ausgeführt werden. Angriffsszenario auf ein YForm-Formular wäre z. B. ein Nutzer, der einen präparierten Link erhält und durch einen Klick dann Daten seines REDAXO-Besuchs preisgibt oder unbemerkte/ungewollte Aktionen durch das YForm-Formular ausführt.

Vereinfacht gesagt sorgt der CSRF-Schutz dafür, dass Formulare nur dann erfolgreich abgesendet werden, wenn der Nutzer sich zum Zeitpunkt des Formular-Absendens auf der Seite befunden hat.

Der CSRF-Schutz sollte daher immer aktiviert bleiben, außer, wenn der direkte Aufruf und Versand eines Formulars explizit durch einen präparierten Link erfolgen muss - z. B. beim Account-Aktivieren-Link des Addons YCom.

---

### Objparams zur Formular-Optik

#### Themes

	// Im YForm-Formbuilder
	objparams|form_ytemplate|classic

```php
<?php
$yform->setObjectparams('form_ytemplate','classic');
```

YForm verfügt über `Templates`, in denen das HTML-Markup definiert ist, das die Felder umgibt. Im Ordner `ytemplates` gibt es Unterordner für jedes Theme, in denen dann die Templates für die einzelnen Felder zu finden sind. Auf diese Weise kann man schnell eigene Themes definieren, die auf dem Basis-Theme aufbauen: wenn es für einen Feldtyp ein eigenes Template gibt, wird dieses verwendet, ansonsten das des Basis-Themes.
Der Defaultwert lautet `bootstrap`, d.h. als Basis-Theme ist das HTML-Schema des CSS-Frameworks "Bootstrap" hinterlegt.

#### Submit-Button benennen

	// Im YForm-Formbuilder
	objparams|submit_btn_label|Formular senden

```php
<?php
$yform->setObjectparams('submit_btn_label','Formular senden');
```

Damit kann die Standard-Button-Beschriftung `Abschicken` verändert werden.

#### Submit-Button anzeigen

	// Im YForm-Formbuilder
	objparams|submit_btn_show|0

```php
<?php
$yform->setObjectparams('submit_btn_show',0);
```

Mit dem Wert `0` wird der Standard-Submit-Button versteckt. Dies ist zum Beispiel sinnvoll, wenn man eigene Buttons definiert hat.  
Default ist `1` (Anzeigen).

#### CSS-Klasse für Fehler

	// Im YForm-Formbuilder
	objparams|error_class|my_form_error

```php
<?php
$yform->setObjectparams('error_class','my_form_error');
```

Diese individuelle CSS-Klasse kommt an zwei Stellen zum Tragen:  
1. im Container mit den Fehlerhinweisen zu Beginn des Formulars:  
`<div class="alert alert-danger my_form_error">`  
2. im Container aller Felder, die bei einer Validierung fehlschlagen:  
`<div class="form-group my_form_error">`.  
So kann man sowohl Label als auch Feld als fehlerhaft formatieren.

Die Default-CSS-Klasse ist `form_warning`.

#### "Echte" Feldnamen

	// Im YForm-Formbuilder
	objparams|real_field_names|1

```php
<?php
$yform->setObjectparams('real_field_names',1);
```

Mit dem auf `1` gesetzten Wert werden exakt die Feldnamen im Formular genommen, die auch in der Formulardefinition gesetzt wurden. Der Feldname lautet dann z. B. nicht mehr `name="FORM[formular][2]"`, sondern `name=vorname`.  
Der Default-Wert ist `0`.

---

### Objparams zum Formularversand

#### Versandmethode des Formulars

	// Im YForm-Formbuilder

```php
<?php
$yform->setObjectparams('form_method','get');
```

Mit dem Wert `get` wird die Versandmethode auf `GET` geändert, d.h. alle Feldwerte sind als GET-Paramater in der URL enthalten.  

Der Defaultwert ist `post`.

#### Zieladresse des Formulars

	// Im YForm-Formbuilder
	objparams|form_action|zielseite.html

```php
<?php
<?php mit rex_getUrl() auf die Artikel-ID 5
$yform->setObjectparams('form_action',rex_getUrl(5));
```

Als Ziel nach dem Abschicken kann eine andere Adresse definiert werden, z. B. für eine ausführliche Danke-Seite. Es könnte auch die aktuelle Artikel-ID gesetzt weden, ergänzt um weitere Parameter.  
Der Defaultwert ist `index.php`, bzw. die URL der Formularseite.

#### GET-Parameter bei der Zielseite erhalten

Wenn GET-Parameter, mit denen das Formular aufgerufen wurde, auch auf der Zielseite erhalten bleiben sollen.

```text
// Im YForm-Formbuilder
objparams|form_action_query_params|key1,key2,key3
```

```php
<?php
<?php mit rex_getUrl() auf die Artikel-ID 5
$yform->setObjectparams('form_action_query_params',`key1,key2,key3`);
```

#### Sprunganker

	// Im YForm-Formbuilder
	objparams|form_anchor|my_form

```php
<?php
$yform->setObjectparams('form_anchor','my_form');
```

Wenn sich ein Formular weiter unten auf der Seite befindet, sieht man nach dem Abschicken zunächst keine Erfolgs- oder Fehlermeldung. Über den `form_anchor`lässt sich ein Sprunganker definieren, der in der  URL nach dem Abschicken angehängt wird, so dass die Seite zum Anker springt. Im Normalfall wird man als Anker die ID des Formulars nutzen.  
Der Defaultwert ist leer.

#### Formular anzeigen nach Abschicken

	// Im YForm-Formbuilder
	objparams|form_showformafterupdate|1

```php
<?php
$yform->setObjectparams('form_showformafterupdate',1);
```

Mit dem Wert `1` kann man das Formular nach dem Versand nochmal anzeigen, um zum Beispiel direkt eine neue Eingabe zu ermöglichen oder die eingegebenen Werte erneut zum Verändern anzubieten.  
Default ist `0` (nicht anzeigen).


#### Formular debuggen

	// Im YForm-Formbuilder
	objparams|debug|1

```php
<?php
$yform->setObjectparams('debug',1);
```

Mit dem Wert `1` kann man zb Aktionen Formular debuggen und Aktion prüfen.


#### Fehlermeldungen ausschalten/verstecken

	// Im YForm-Formbuilder
	objparams|hide_top_warning_messages|1

```php
<?php
$yform->setObjectparams('hide_top_warning_messages',1);
```

Mit dem Wert `1` können die Fehlermeldung die über eine Validierung ausgegeben werden versteckt werden.


#### Feldwerte aus Datenbank laden

	// Im YForm-Formbuilder
	objparams|getdata|1
	objparams|main_where|id=1
	objparams|main_table|rex_table

```php
<?php
$yform->setObjectparams('getdata',1);
$yform->setObjectparams('main_where','id='.(int)$id);
$yform->setObjectparams('main_table','rex_table');
```

Mit dem Wert `1` bei `getdata` in Verbindung mit `main_where` (hier die id auf den Datensatz) und `main_table` (hier der Tabellename) können Felder mit Werten aus eine Datenbanktabelle vorbelegt/geladen werden.

#### Weiterleitung forcieren

Mit `form_exit` wird gesteuert, ob die Abarbeitung des weiteren Codes, bspw. in Modulen und Templates, nach erfolgreichem Versand beendet wird oder nicht. Ein vorzeitiges Beenden des Codes ist z. B. dann nötig, wenn man eine redirect-Action (Weiterleitung) verwenden möchte und diese direkt ausgeführt werden soll, oder, um in bestimmten Fällen eine doppelte Ausführung des Formulars zu verhindern.

	// Im YForm-Formbuilder
	objparams|form_exit|1

	<?php
	$yform->setObjectparams('form_exit',1);

### Objparams auslesen

Über folgende Methode können Objparams ausgelesen werden:

```php
$yform->getObjectparams('warning')
```

### Verfügbare Objparams

Folgende Objparams find verfügbar:
- `action`: Array
- `actions`: Dem Formular zugeordnete YForm-Actions.
- `actions_executed`: Gibt an, ob YForm-Actions ausgeführt wurden `true` oder `false`. Siehe auch `preactions_executed` und `postactions_executed`.
- `article_id`: Artikel-ID, dessen URL als Ziel für das Formular dient.
- `clang`: Clang-ID des Formulars (Sprache).
- `csrf_protection`: Boolean. Aktiviert den CSRF-Schutz. Hierbei wird ein Cookie gesetzt.
- `csrf_protection_error_message`: CSRF Fehlermeldung. Standard: '{{ csrf.error }}'
- `data`: Boolean.
- `debug`: Boolean. Aktiviert den Debugmodus
- `error_class`: CSS Klasse des Fehlerfelds
- `Error-occured`: Freitext oberhalb auftgetretener Fehlermeldungen.
- `Error-Code-EntryNotFound`: Standard: 'ErrorCode - EntryNotFound'
- `Error-Code-InsertQueryError`: Standard 'ErrorCode - InsertQueryError'
- `fieldsets_opened`:
- `form_action`: 
- `form_anchor`: S(optional) ID des HTML-Sprungankers, um nach Absenden zu einem HTML-Element zu scrollen.
- `form_array`:
- `form_class`: CSS Klasse des Formulars
- `form_data`: Formular in pipe Notation
- `form_elements`:
- `form_hiddenfields`: Versteckte Felder des Formulars
- `form_label_type`: Label Typ. html (Standard) oder plain.
- `form_name`: Name des Formulars
- `form_method`: Übertragungsweg des Formulars: `POST` (Standard) oder `GET`.
- `form_needs_output`: Boolean.
- `form_output`:
- `form_show`: Boolean, wenn true wird das Formular angezeigt.
- `form_showformafterupdate`: Boolean, wenn true wird das Formular nach dem Absenden erneut angezeigt.
- `form_wrap_class`: Klasse der div, die das Formular umgibt. Standard ist 'yform'.
- `form_wrap_id`: ID der div, die das Formular umgibt. Standard ist 'rex-yform'.
- `form_ytemplate`: YForm Template. Standard: 'bootstrap,classic';
- `getdata`: Boolean
- `get_field_type`: Standard 'request';
- `hide_field_warning_messages`: Boolean. Blendet Fehlermeldung die Eingabefelder betreffend aus oder ein. Standard ist ein.
- `hide_top_warning_messages`: Boolean.
- `main_id`: optional: Primär-Schlüssel / ID des Datensatz, der bearbeitet wird.
- `main_table`: Tabellenname der Haupttabelle
- `main_where`: Hauptbestandteil der SQL WHERE Abfrage, z.B. "id=12"
- `output`: Finale Ausgabe des Formulars
- `preactions_executed`: Boolean. Wenn true sind die Pre Aktionen ausgeführt. Siehe auch `actions_executed` und `postactions_executed`.
- `postactions_executed`:Boolean. Wenn true sind die Post Aktionen ausgeführt. Siehe auch `actions_executed` und `preactions_executed`.
- `real_field_names`: Boolean, wenn true erhalten die Felder im Formular im ausgegeben HTML auch den im Formbuilder angegebenen Namen.
- `sql_object`: SQL Objekt
- `submit_btn_label`: Beschriftung des Absenden Buttons
- `submit_btn_show`: Boolean, wenn false wird kein Absenden Button angezeigt
- `this`: YForm Objekt
- `unique_error`:
- `unique_field_warning`:
- `validate`: Array
- `validates`: Validierungsfelder des Formulars
- `value`: Array
- `value_pool`: Array. Schlüssel der Elemente sind `email`, `files`, `sql`, die ebenfalls Arrays beinhalten.
- `values`: Value Felder des Formulars
- `warning`: Fehlermeldung des Formulars. Hat das Formular keine Fehler, ist das Feld leer.
- `warning_messages`: Fehlermeldungen des Formulars als Array

## Values

> **Hinweis:** 
> Dieser Abschnitt der Doku ist noch nicht fertig. Du kannst dich auf [GitHub](https://github.com/yakamara/redaxo_yform_docs/) an der Fertigstellung beteiligen.

- [Zweck der Values](#zweck-der-values)
- [Value-Klassen](#value-klassen)

### Zweck der Values

Mit diesen Klassen werden alle sichtbaren und verstecken Felder definiert.

> Die Value-Klassen sind hier zu finden:  
> `src/addons/yform/lib/yform/values/`


Beispiele (Schreibweisen): **yForm Formbuilder** und **PHP**

Die PHP-Beispiele können in diesem Formular getestet/eingesetzt werden:

```php
<?php
$yform = new rex_yform();
$yform->setObjectparams('form_action', rex_getUrl(REX_ARTICLE_ID,REX_CLANG_ID));

$yform->setValueField('text', array("wert1","Wert 1"));


echo $yform->getForm();
?>
```

### Value-Klassen

	
	
	
	
#### be_link

###### Definition
	Ein Redaxo-Feld, um einen Redaxo-Artikel auszuwählen.
	
###### Beispiel PHP
	$yform->setValueField('be_link', array("link","Link zu Artikel"));
		
###### Beispiel Pipe
	be_link|link|Link zu Artikel|

###### Beispiel E-Mail
	REX_YFORM_DATA[field="link"]
		

#### be_manager_relation

###### Definition
	Ein Auswahlfeld / Popup, um ein oder mehrere Datensätze mit denen einer fremden Tabelle zu verknüpfen, z. B. über einen Fremdschlüssel (1:n) oder eine Relationstabelle (m:n).
		
###### Beispiel PHP
	$yform->setValueField('be_manager_relation', array("manager_relation","Beispiel","rex_yf_messages","","0","0","","","","rex_yf_employees"));
		
###### Beispiel Pipe
	be_manager_relation|manager_relation|Beispiel|rex_yf_messages||0|0||||rex_yf_employees|

###### Beispiel E-Mail
	REX_YFORM_DATA[field="manager_relation"]
	

	

#### be_media

###### Definition
	Ein Redaxo-Feld, um eine einzelne oder mehrere Medienpool-Datei/en auszuwählen.
	
###### Beispiel PHP
	$yform->setValueField('be_media', array("image","Bild","1","0","general","jpg,gif,png,jpeg"));
		
###### Beispiel Pipe
	be_media|image|Bild|1|0|general|jpg,gif,png,jpeg|

###### Beispiel E-Mail
	REX_YFORM_DATA[field="image"]


	
#### be_table

###### Definition
	Eine Reihe von Eingabefeldern, um tabellarische Daten einzugeben.
	
###### Beispiel PHP
	$yform->setValueField('be_table', array("table","Tabelle","Menge,Preis,Gewicht"));
		
###### Beispiel Pipe
	be_table|table|Tabelle|Menge,Preis,Gewicht|

###### Beispiel E-Mail
	REX_YFORM_DATA[field="table"]


> **Tipp:** In be_table lassen sich auch weitere YForm-Feldtypen in Pipe-Schreibweise hinterlegen, z.B. `text|title|Titel,textarea|text|Beschreibung,be_media|image|Bild`


#### article

###### Definition
    Gibt den Inhalt eines Artikels aus. Es muss die ID übergeben werden. Besser nicht auf sich selbst verweisen ;)
	

#### checkbox

###### Definition
	Eine Checkbox mit vordefinierten Werten.
	
###### Beispiel PHP

*Syntax*

```php
$yform->setValueField('checkbox', array("checkbox","Checkbox","0 or 1"));

*Beispiel*

Eine Checkbox die bereits gecheckt ist.

```php
$yform->setValueField('checkbox', array("checkbox","Checkbox","1"));
```

###### Beispiel Pipe
	checkbox|checkbox|Checkbox|1|

###### Beispiel E-Mail
	REX_YFORM_DATA[field="checkbox"]

#### choice

###### **Definition**
Erzeugt eine Selectbox, eine Radiobutton Auswahl oder ein Checkbox-Feld. Wahlweise mit Multiple Auswahl oder Gruppiert (optgroup). Das Feld choice ersetzt mit der YFORM Version 3.0 die Felder checkbox_sql, radio, radio_sql, select und select_sql.
Die Options können entweder als kommaseparierte Liste `label1=val1,label2=val2...`, als JSON `{"Europa": {"Dänemark": "DK", "Deutschland": "DE", "Österreich": "AT", "Schweiz": "CH"}, "Südamerika": {"Bolivien": "BO"}}` oder als SQL Query `SELECT id AS value, name AS label FROM country` bzw. `SELECT a.id AS value, a.name AS label, b.name AS group_label FROM country AS a LEFT JOIN continent AS b ON a.continent_id = b.id` angegeben werden. SQL muss die Felder `value` und `label` sowie `group_label` für gruppierte Felder zurückgeben. Der Alias `group_label` kann frei gewählt werden und muss beim Parameter `group_by` angegeben werden (Siehe Beispiel 6). Mit der Syntax als kommaseparierte Liste sind keine gruppierten Felder (optgroups) möglich.
Die Options können auch als Callable angegeben. Die Funktion kann ein Array oder ein JSON zurückgeben, welches dem obigen Aufbau entspricht.

*Hinweis*
Die SQL Syntax unterscheidet sich zur früheren Syntax! Es werden nun die Felder `label` und `value` statt `id` und `name` erwartet.

*Feldtyp*
Wenn beim Parameter `expanded` 1 oder true angegeben wird, so wird ein Checkboxfeld oder Radiobuttons erzeugt. Bei 0 oder false wird ein Selectfeld erzeugt. Wenn beim Parameter `multiple` 1 oder true angegeben wird, so wird ein Multiselectfeld bzw. ein Checkboxfeld erzeugt.

    Select:       expanded = 0   multiple = 0
    Multiselect:  expanded = 0   multiple = 1
    Radiobuttons: expanded = 1   multiple = 0
    Checkboxfeld: expanded = 1   multiple = 1

*Attribute*

Der Parameter `group_attributes`, `choice_attributes` und `attributes` können entweder als ausführbare Befehle (callable) (z. B. `foo::bar($attributes)` oder `foo($attributes)`) oder als JSON (z. B. `{"class": "group-item"}`) angegeben werden.
Beim Parameter `choice_attributes` sind bei einer Funktion drei Werte möglich: `foo($attributes, $value, $label)`.


###### **Beispiele PHP**

*Syntax*

```php $yform->setValueField('choice',["fieldname","Label",Options,expanded,multiple,default,group_by,preferred_choices,placeholder,group_attributes,choice_attributes,attributes,notice,[no_db]);
```

*Beispiele*

1. Select, Options als kommaseparierte Liste

```php
$yform->setValueField('choice',["selectfield","Verkehrsmittel","Auto,Bus,Fahrrad,Schiff,Rollschuhe,Zug",0,0]);
```

2. Gruppiertes Checkboxfeld, Options als JSON

```php
$yform->setValueField('choice',["mycheckboxfield","Vor- und Nachspeisen",'{"Vorspeisen": {"Gemischter Salat":"insalata_mista","Tagessuppe":"piatto_del_giorno"},"Dessert":{"Spaghettieis":"spaghetti_di_ghiaccio","Tiramisu":"tiramisu"}}',1,1]);
```

3. Options als JSON, Nutzung von choice_attributes, um z. B. an eine Option das Attribut disabled anzufügen

```php
$yform->setValueField('choice', ['choice','choice','{"Dänemark": "DK", "Deutschland": "DE", "Österreich": "AT", "Schweiz": "CH"}','0','0','','','','','','','{"class": "choicable","DE": {"disabled":"disabled"}}','','0']);
```

###### **Beispiel Pipe**
*Syntax*

    choice|name|label|choices|[expanded type: boolean; default: false]|[multiple type: boolean; default: false]|[default]|[group_by]|[preferred_choices]|[placeholder]|[group_attributes]|[choice_attributes]|[attributes]|[notice]|[no_db]

*Beispiele*

4. Select, Options als kommaseparierte Liste

        choice|colors|Farben|Blau,Rot,Grün,Gelb,Lila|0|0|
	
5. Checkboxfeld, Options als kommaseparierte Liste mit Vorauswahl

        choice|colors|Farben|Blau,Rot,Grün,Gelb,Lila|1|1|Rot,Grün
	
6. Gruppierte Radiobutton, Options als JSON

        choice|drinks|Trinken|{"Kalte Getränke": {"Apfelschorle":"01","Orangensaft":"02"},"Warme Getränke":{"Kaffee":"11","Tee":"12"}}|1|0|

7. Select aus SQL, gruppiert mit Leeroption und bevorzugter Auswahl

        choice|artikel|Artikel|SELECT name label, id value, catname FROM rex_article ORDER BY catname|0|0||catname|8,5|--- bitte auswählen ---

    Die Datensätze mit der Id 8 und 5 stehen am Anfang des Select (preferred choices).
	

###### Beispiel E-Mail

	REX_YFORM_DATA[field="choice"]
	REX_YFORM_DATA[field="choice_LABELS"]
	REX_YFORM_DATA[field="choice_LIST"]

> Tipp: Bei E-Mails werden über das Feld-Suffix `_LABELS` direkt die Beschriftungen als kommaseparierter Text bzw. `_LIST` mit Zeilenumbrüchen zurückgegeben.
 
#### date

###### Definition
	Eine Reihe von Auswahlfeldern, in der ein Datum (Tag, Monat, Jahr) ausgewählt wird.
	
###### Beispiel PHP
```php
$yform->setValueField('date', array("date","Datum","2016","+5","DD/MM/YYYY","1","","select"));
```

###### Beispiel Pipe
	date|date|Datum|2016|+5|DD/MM/YYYY|1||select|
	validate|type|Datum|date|Bitte geben Sie das Datum ein.|[1 = Feld darf auch leer sein]
	
	#Beispiel für nativen Datepicker mit voreingestelltem Datum
	date|geburtsdatum|Geburtsdatum|1900|+1|YYYY-MM-DD|0|0|input:text|{"required":"required","type":"date","value":"2022-02-02"}|

###### Beispiel E-Mail
	REX_YFORM_DATA[field="date"]

#### datestamp

###### Definition
	Ein unsichtbares Feld, in das ein Zeitstempel gespeichert wird, wenn der Datensatz hinzugefügt oder bearbeitet wird.
	
###### Beispiel PHP
```php
$yform->setValueField('datestamp', array("createdate","Zeitstempel","mysql","0","0"));
```

###### Beispiel Pipe
	datestamp|createdate|Zeitstempel|mysql|wert anzeigen 0/1|0|

###### Beispiel E-Mail
	REX_YFORM_DATA[field="createdate"]

#### datetime

###### Definition
	Eine Reihe von Auswahlfeldern, in der Datum und Uhrzeit (Tag, Monat, Jahr, Stunden, Minuten, Sekunden) ausgewählt wird.
	
###### Beispiel PHP
```php
$yform->setValueField('datetime', array("datetime","Datetime","2016","+5","00,15,30,45","DD/MM/YYYY HH:ii","0","","select"));
```

###### Beispiel Pipe
	datetime|datetime|Datetime|2016|+5|00,15,30,45|DD/MM/YYYY HH:ii|0||select|

###### Beispiel E-Mail
	REX_YFORM_DATA[field="datetime"]

	

#### email

###### Definition
	Ein einfaches Eingabefeld für E-Mail-Adressen.
	
###### Beispiel PHP
```php
$yform->setValueField('email', array("email","E-Mail-Adresse"));
```

###### Beispiel Pipe
	email|email|E-Mail-Adresse|

###### Beispiel E-Mail
	REX_YFORM_DATA[field="email"]

	

#### emptyname

###### Definition
	Ein Feld ohne Eingabemöglichkeit.
	
###### Beispiel PHP
```php
$yform->setValueField('emptyname', array("emptyname","Emptyname"));
```

###### Beispiel Pipe
	emptyname|emptyname|Emptyname|

###### Beispiel E-Mail
	REX_YFORM_DATA[field="emptyname"]
	

#### fieldset

###### Definition
	Ein Fieldset gruppiert Formularfelder.
	
###### **Beispiele PHP**

*Syntax*

```php
$yform->setValueField('fieldset', array("id","Legend","classes (space separated)","onlyclose (optional)"));
```

*Beispiele* 

```php
$yform->setValueField('fieldset', array("fieldset","Fieldset"));
```

- Mit Klassen
```php
$yform->setValueField('fieldset', array("fieldset","Fieldset","col-12 col-md-4"));
```

- Das zuletzt geöffnete Fieldset schließen, ohne ein neues zu öffnen:

```php
$yform->setValueField('fieldset', array("fieldset", "Fieldset", "", "onlyclose"));
```

###### Beispiel Pipe
	fieldset|fieldset|Fieldset|

###### Beispiel E-Mail
	REX_YFORM_DATA[field="fieldset"]

	

#### float

> **Achtung:** Dieser Feldtyp wird demnächst entfernt. Stattdessen das Feld `number` verwenden.

###### Definition
	Ein einfaches Eingabefeld für Gleitkomma-Zahlen.
	
###### Beispiel PHP
```php
$yform->setValueField('float', array("float","Float","1"));
```

###### Beispiel Pipe
	float|float|Float|1|

###### Beispiel E-Mail
	REX_YFORM_DATA[field="float"]


		
#### generate_key

###### Definition
	Generiert ein nicht sichtbares Feld mit zufälligem 32-stelligem Schlüssel, bestehend aus Zahlen und Kleinbuchstaben.
		
###### Beispiel Pipe
	generate_key|name|[no_db]
	
###### Beispiel PHP
	_


#### hashvalue

###### Definition
	Ein Feld, das aus dem Wert eines anderen Feldes einen Hashwert erzeugt.
	
###### Beispiel PHP
```php
$yform->setValueField('hashvalue', array("hashvalue","Hashvalue"));
```

###### Beispiel Pipe
	hashvalue|hashvalue|Hashvalue|

###### Beispiel E-Mail
	REX_YFORM_DATA[field="hashvalue"]

	

#### hidden
definiert ein Feld, das nur serverseitig befüllt wird und nicht ausgegeben wird.

> Hinweis: Für ein unsichtbares Eingabefeld wird nicht dieses hidden-Feld verwendet, sondern z. B. ein reguläres Eingabefeld (`text`), das zusätzlich das Attribut type="hidden" bekommt.

###### Definition
	hidden|name|(default)value||[no_db]
	 	
###### Beispiel Formbuilder
	hidden|name|(default)value||[no_db]
	hidden|name|(default)value|REQUEST|[no_db]
	
###### Beispiel PHP
```php
$yform->setValueField('hidden', array("name", "Max Muster"));

// oder

$ycom_user = rex_ycom_auth::getUser();
if($ycom_user) {
	$yform->setValueField('hidden', array("user", $ycom_user->getId()));
}
```

#### html

###### Definition
	Gibt HTML-Code an der gewünschten Stelle des Eingabe-Formulars aus.
	
###### Beispiel PHP
```php
$yform->setValueField('html', array("html","HTML","<p>Hallo Welt!</p>"));
```

###### Beispiel Pipe
	html|html|HTML|<p>Hallo Welt!</p>|

###### Beispiel E-Mail
	REX_YFORM_DATA[field="html"]

	

#### index

###### Definition
	Ein Feld, das einen Index / Schlüssel über mehrere Felder erzeugt.
	
###### Beispiel PHP
```php
$yform->setValueField('index', array("index","Index"));
```

###### Beispiel Pipe
	index|index|Index|

###### Beispiel E-Mail
	REX_YFORM_DATA[field="index"]

	

#### integer

###### Definition
	Ein einfaches Eingabefeld für ganze Zahlen.
	
###### Beispiel PHP
```php
$yform->setValueField('integer', array("int","Integer"));
```

###### Beispiel Pipe
	integer|int|Integer|

###### Beispiel E-Mail
	REX_YFORM_DATA[field="int"]

	
#### ip
übergibt die IP des Users.

###### Definition
	ip|name|[no_db]
	
###### Beispiel Formbuilder
	ip|ip	

###### Beispiel PHP
```php
$yform->setValueField('ip', array("ip"));
```

##### number 
###### Beispiel Formbuilder
    number|name|label|precision|scale|defaultwert|[no_db]|[unit]|[notice]
	number|zahl|Zahl|6|2|5||cm|Hinweis Number

precision ist die Anzahl der signifikanten Stellen. Der Bereich von precision liegt zwischen 1 und 65.

scale ist die Anzahl der Stellen nach dem Dezimalzeichen. Der Bereich von scale ist 0 und 30. MySQL erfordert, dass scale kleiner oder gleich (<=) precision ist.

In diesem Beispiel kann die Spalte 6 Stellen mit 2 Dezimalstellen speichern. Daher reicht der Bereich der Betragsspalte von 9999,99 bis -9999,99.


###### Beispiel PHP
```php

```



#### password

###### Beispiel Formbuilder
	password|name|label|default_value
	
###### Beispiel PHP
```php
$yform->setValueField('password', array("name","label", "default_value"));
```

#### php


###### Definition
	Führt PHP-Code an der gewünschten Stelle des Eingabe-Formulars aus.
	
###### Beispiel PHP
```php
$yform->setValueField('php', array("php","PHP","<? echo 'hallo welt'; ?>"));
```

###### Beispiel Pipe
	php|php|PHP|<? echo 'hallo welt'; ?>|

###### Beispiel E-Mail
	REX_YFORM_DATA[field="php"]

> **Hinweis**: Zusammen mit dem Upload-Feld lassen sich komfortabel [E-Mails mit Anhang versenden](demo_email-attachments.md).
	

#### prio

###### Definition
	Ein Auswahlfeld, um Datensätze in eine bestimmte Reihenfolge zu sortieren.
	
###### Beispiel PHP
```php
$yform->setValueField('prio', array("prio","Reihenfolge"));
```

###### Beispiel Pipe
	prio|prio|Reihenfolge|

###### Beispiel E-Mail
	REX_YFORM_DATA[field="prio"]

###### Definition
	Speichert Werte des Formulars in einem Cookie

#### resetbutton


###### Definition
	definiert einen Reset-Button, mit dem Eingaben zurückgesetzt werden können.
	
###### Beispiel Formbuilder
	resetbutton|reset|reset|Reset

###### Beispiel PHP
```php
$yform->setValueField('resetbutton', array("reset","reset","Reset"));
```
	
#### showvalue

###### Definition
	Zeigt einen Wert in der Ausgabe.
	
###### Beispiel PHP
```php
```

###### Beispiel Pipe
	showvalue|name|label|defaultwert|notice
	
	
	
#### submit

###### Definition
	Ein oder mehrere Submit-Buttons zum Absenden des Formulars.
	
###### Beispiel PHP
```php
$yform->setValueField('submit', array("submit","Submit"));
Als Standard werden die Klassen "btn" & "btn-primary" definiert. Für zusätzliche Klassen gilt:
$yform->setValueField('submit', array('submit','Anfrage senden','','','','btn-secondary'));
```

###### Beispiel Pipe
	submit|submit|Submit|

###### Beispiel E-Mail
	REX_YFORM_DATA[field="submit"]


	
	
	
#### text

###### Definition
	Input-Feld zur Eingabe eines Textes.
	
###### Beispiel PHP
```php
$yform->setValueField('text', array("text","Text"));
```

###### Beispiel Pipe
	text|text|Text|

###### Beispiel E-Mail
	REX_YFORM_DATA[field="text"]


	
	
	
	
	
#### textarea

###### Definition
	Ein mehrzeiliges Eingabefeld für Text.

###### Beispiel PHP
```php
$yform->setValueField('textarea', array("textarea","Textarea"));
```

###### Beispiel Pipe
	textarea|textarea|Textarea|

###### Beispiel E-Mail
	REX_YFORM_DATA[field="textarea"]


	
	
	
#### time

###### Definition
	Eine Reihe von Auswahlfeldern, in der die Uhrzeit (Stunden, Minuten, Sekunden) ausgewählt wird.
	
###### Beispiel PHP
```php
$yform->setValueField('time', array("time","Zeit","","00,15,30,45","HH:ii","","select"));
```

###### Beispiel Pipe
	time|time|Zeit||00,15,30,45|HH:ii||select|

###### Beispiel E-Mail
	REX_YFORM_DATA[field="time"]

#### upload

###### Definition
	Ein Upload-Feld, mit dem eine Datei in die Datenbank oder ein Verzeichnis hochgeladen wird. Die Felder Dateigröße, Allowed Extensions und Messages sind deprecated, werden aber noch genutzt. Die JSON Config hat Priorität, wenn beides eingetragen wurde.

###### Beispiel PHP
```php
$json_config = '{
    "sizes":{
        "min":0,
        "max":15360000
    },
    "allowed_extensions":[
        "jpg",
        "zip"
    ],
    "disallowed_extensions":[
        "exe"
    ],
    "check":[
        "multiple_extensions",
        "zip_archive"
    ],
    "messages":{
        "min_error":"min_error_msg",
        "max_error":"max_error_msg",
        "type_error":"type_error_msg",
        "empty_error":"empty_error_msg",
        "system_error":"system_error_msg",
        "type_multiple_error":"type_multiple-msg",
        "zip-type_error":"zip-type_error-msg {0}",
        "type_zip_error":"type_zip_error-msg",
        "delete_file":"delete_file_msg"
    }
}';

$yform->setValueField('upload', array("upload","Upload","config" => $json_config));
```

###### Beispiel Pipe
	upload|upload|Upload||.jpg,.gif,.png,.jpeg|

	upload|name | label | Maximale Größe in Kb oder Range 100,500 | endungenmitpunktmitkommasepariert | pflicht=1 | min_err,max_err,type_err,empty_err,delete_file_msg | JSON CONFIG einzeilig

###### Beispiel E-Mail
	REX_YFORM_DATA[field="upload"]

> **Hinweis für die Nutzung im Frontend**: Damit die Zuordnung von temporärem Dateinamen (Präfix ist ein temporärer Hash, z.B. `9f938fb7d400795e6fa998606a3ce126468133e57d86a48116bf6c4195cc460c_meine_datei.pdf`) zu späterem Dateinamen (mit Präfix ist die ID des Datensatzes, z.B. `121_meine_datei.jpg`) erfolgen kann, müssen die Objekt-Parameter `main_table` und `main_where` gesetzt sein. Die Umbenennung von Temp-Datei zur finalen Datei erfolgt durch eine Post-Action von YForm. Die Post-Action wird bspw. nicht ausgeführt, wenn der Datensatz "an YForm vorbei", z.B. durch die YForm-Action `db_query` erstellt oder bearbeitet wird.

> **Hinweis**: Zusammen mit dem PHP-Feld lassen sich komfortabel [E-Mails mit Anhang versenden](demo_email-attachments.md).

#### uuid

###### Definition
    erstellt eine eindeutige UUID

###### Beispiel Formbuilder
	uuid|name|


## Validierung
	
### Zweck der Validierungen

Mit diesen Klassen lassen sich Values überprüfen. Bei einer negativen Validierung wird eine entsprechende Warnung ausgegeben.

Die Validate-Feldklassen werden wie Values und Actions im Formbuilder im Feld `Felddefinitonen` eingetragen. Dabei muss immer der Name der Value-Feldklasse angegeben, der validiert werden soll.

> Die Validate-Klassen sind hier zu finden:  
> `src/addons/yform/lib/yform/validate/`

Die PHP-Beispiele können in diesem Basis-Formular getestet/eingesetzt werden:

```php
<?php
$yform = new rex_yform();
$yform->setObjectparams('form_action', rex_getUrl(REX_ARTICLE_ID,REX_CLANG_ID));

$yform->setValueField('text', array("wert1","Wert 1"));
$yform->setValidateField('empty', array("wert1","Bitte geben Sie einen Namen an!"));

echo $yform->getForm();
?>
```

### Validierungs-Klassen

#### compare

Vergleicht zwei Felder mit Hilfe von Operatoren.

	// allgemeine Definition
	validate|compare|label1|label2|[!=,<,>,==,>=,<=]|warning_message|

	// im YForm-Formbuilder
	text|wert1|Wert 1|
	text|wert2|Wert 2|
	validate|compare|wert1|wert2|!=|Die beiden Felder haben unterschiedliche Werte|

```php
<?php
$yform->setValueField('text', array("wert1","Wert 1"));
$yform->setValueField('text', array("wert2","Wert 2"));
$yform->setValidateField('compare', array("wert1","wert2","!=", "Die Felder haben unterschiedliche Werte"));
```

	
> **Hinweis:** Mögliche Vergleichs-Operatoren sind `!=`, `<`, `>`, `==`, `>=`und `<=`

#### compare_value

Vergleicht ein Feld mit einem angegebenen Wert mit Hilfe von Operatoren.

	// Definition
	validate|compare_value|label|value|[!=,<,>,==,>=,<=]|warning_message
	
	// Im YForm-Formbuilder
	text|wert1|Wert 1|
	validate|compare_value|wert1|2|<|Der Wert ist kleiner als 2!|

```php
<?php
$yform->setValueField('text', array("wert1","Wert 1"));
$yform->setValidateField('compare_value', array("wert1",2,"<", "Der Wert ist kleiner als 2!"));
```

	
> **Hinweis:** Mögliche Vergleichs-Operatoren sind `!=`, `<`, `>`, `==`, `>=` und `<=`

#### customfunction

Damit können eigene Überprüfungen via Funktion oder Klasse/Methode durchgeführt werden.

	// Definition
	validate|customfunction|label|[!]function/class::method|weitere_parameter|warning_message
	
#### empty

Überprüft, ob im Feld ein Wert eingetragen wurde und gibt ein Meldung aus.

	// Definition
	validate|empty|label|Meldung

	// Im YForm-Formbuilder
	text|name|Nachname|
	validate|empty|name|Bitte geben Sie einen Namen an!

```php
<?php
$yform->setValueField('text', array("name","Nachname"));
$yform->setValidateField('empty', array("name","Bitte geben Sie einen Namen an!"));
```

#### in_table (früher existintable, wird nicht mehr fortgeführt)

Überprüft, ob ein Feld in einer Tabelle existiert.
 
	// Definition
	validate|existintable|label,label2|tablename|feldname,feldname2|warning_message

#### intfromto

Überprüft ob der **Wert** der Eingabe größer oder kleiner als die definierten Werte sind.

	// Definition
	validate|intfromto|label|from|to|warning_message
	
	// Im YForm-Formbuilder
	text|wert|Wert
	validate|intfromto|wert|2|4|Der Wert ist kleiner als 2 und größer als 4! 

```php
<?php
$yform->setValueField('text', array("wert","Wert"));
$yform->setValidateField('intfromto', array("wert","2", "4", "Der Wert ist kleiner als 2 und größer als 4! "));
```

#### in_names (früher: labelexist)

Überprüft mit einem Minimal- und Maximalwert, ob eine bestimmte Menge an Feldern ausgefüllt wurden.

	// Definition
	validate|in_names|label,label2,label3|[minlabels]|[maximallabels]|Fehlermeldung
	
	// Im YForm-Formbuilder
	text|vorname|Vorname|
	text|name|Nachname|
	text|email|E-Mail|
	text|tel|Telefon|
	
	validate|in_names|vorname,name,tel|1|2|Fehlermeldung

```php
//In PHP
$yform->setValueField('text', array("vorname","Vorname"));
$yform->setValueField('text', array("name","Nachname"));
$yform->setValueField('text', array("email","E-Mail"));
$yform->setValueField('text', array("tel","Telefon"));

$yform->setValidateField('in_names', array("vorname, name, tel", "1", "2", "Fehlermeldung"));

// Hier in diesem Beispiel müssen von den drei Feldern mindestens 1 und maximal 2 ausgefüllt werden
```

> Hinweis:  
> * `minlabels` ist optional und hat den Defaultwert 1.  
> * `maximallabels` ist optional und den Defaultwert 1000.

#### preg_match

Überprüft die Eingabe auf die hinterlegten Regex-Regeln.

	// Definition
	validate|preg_match|label|/[a-z]/i|warning_message
	
	// Im YForm-Formbuilder
	text|eingabe|Eingabe
	validate|preg_match|eingabe|/[a-z]+/|Es dürfen nur ein oder mehrere klein geschriebene 	Buchstaben eingegeben werden!

```php
<?php
$yform->setValueField('text', array("eingabe","Eingabe"));
$yform->setValidateField('preg_match', array("eingabe","/[a-z]+/", "Es dürfen nur ein oder mehrere klein geschriebene Buchstaben eingegeben werden!"));
```

#### size

Überprüft die Eingabe eines Feldes auf genau die angegebene Zeichenlänge.

	// Definition
	validate|size|plz|[size]|warning_message
	
	// Im YForm-Formbuilder
	text|plz|PLZ
	validate|size|plz|5|Die Eingabe hat nicht die korrekte Zeichenlänge!

```php
<?php
$yform->setValueField('text', array("plz","PLZ"));
$yform->setValidateField('size', array("plz","5", "Die Eingabe hat nicht die korrekte Zeichenlänge!"));
```

> **Hinweis:** `size` ist eine Zahl und meint die Zeichenlänge.

#### size_range

Überprüft die Eingabe eines Feldes auf die angegebene **Zeichenlänge**, die zwischen dem Minimal- und Maximalwert liegt

	// Definition
	validate|size_range|label|[minsize]|[maxsize]|Fehlermeldung
	
	// Im YForm-Formbuilder
	text|summe|Summe
	validate|size_range|summe|3|10|Die Eingabe hat nicht die korrekte Zeichenlänge (mind. 3, max 10 Zeichen)!

```php
<?php
$yform->setValueField('text', array("summe","Summe"));
$yform->setValidateField('size_range', array("summe", "3", "10", "Die Eingabe hat nicht die korrekte Zeichenlänge (mind. 3, max 10 Zeichen)!"));
```

#### type

Überprüft den Typ der Eingabe.

	// Definition
	validate|type|label|[int,float,numeric,string,email,url,date,datetime]|Fehlermeldung|[1 = Feld darf auch leer sein]	
	
	// Im YForm-Formbuilder
	text|wert|Wert
	validate|type|wert|numeric|Die Eingabe ist keine Nummer!

```php
<?php
$yform->setValueField('text', array("wert","Wert"));
$yform->setValidateField('type', array("wert", "numeric", "Die Eingabe ist keine Nummer!"));
```

#### unique

Überprüft, ob ein Datensatz mit einem bestimmten Feld-Wert bereits in einer Datenbank-Tabelle vorhanden ist.

	// Definition
	validate|unique|dbfeldname[,dbfeldname2]|Dieser Name existiert schon|[table]
	
	// Im YForm-Formbuilder
	text|email|E-Mail|
	validate|unique|email|Ein User mit dieser E-Mail-Adresse existiert schon!|rex_user

```php
<?php
$yform->setValueField('text', array("email","E-Mail"));
$yform->setValidateField('unique', array("email", "Ein User mit dieser E-Mail-Adresse existiert schon!","rex_user"));
```

> **Hinweise:**  
> * `table`: Wenn kein Tabellenname angegeben ist, wird der Tabellenname verwendet, der im Formbuilder ausgewählt wurde.  
> * `dbfeldname`: Es können mehrere Feldname überprüft werden (kommagetrennt).

## Actions

### Zweck der Aktionen

Aktionen defineren, was nach dem Versand des Formulars mit den Formulardaten passieren soll, z. B. der Versand einer E-Mail über ein E-Mail-Template oder die Speicherung der Daten in einer Tabelle.


> Die Action-Klassen sind hier zu finden:  
> `src/addons/yform/lib/yform/actions/`


Beispiele (Schreibweisen) gibt es für **yForm Formbuilder** und **PHP**

Die PHP-Beispiele können in diesem Formular getestet/eingesetzt werden:

```php
<?php
$yform = new rex_yform();
$yform->setObjectparams('form_action', rex_getUrl(REX_ARTICLE_ID,REX_CLANG_ID));

$yform->setValueField('text', array("wert1","Wert 1"));
$yform->setValidateField('empty', array("wert1","Bitte geben Sie einen Namen an!"));

echo $yform->getForm();
?>
```

### Action-Klassen

#### callback

Ruf eine Funktion oder Klasse auf.

	// allgemeine Definition
	action|callback|mycallback / myclass::mycallback

	// im YForm-Formbuilder
	folgt ...

```php
<?php
folgt ...
```

#### copy_value

Kopiert Eingaben vom Feld mit dem Label `label_from` in das Feld mit dem Label `label_to`

	// allgemeine Definition
	action|copy_value|label_from|label_to
	
	// im YForm-Formbuilder
	hidden|user
	text|name|Name
	action|copy_value|name|user
	
	action|db|rex_warenkorb
	action|html|Daten gespeichert	

```php
<?php	
$yform->setValueField('hidden', array("user"));
$yform->setValueField('text', array("name","Name"));
$yform->setActionField('copy_value', array("name","user"));

$yform->setActionField('db', array("rex_warenkorb"));
$yform->setActionField('html', array("Daten gespeichert"));
```

#### create_table

Erstellt eine Datenbank-Tabelle. Formular-Label werden dabei als Feldnamen in die neue Tabelle gespeichert. Die neue Tabelle erscheint dabei **nicht** in der Redaxo-Tabellen-Struktur.
Mit %TABLE_PREFIX% im Tabellennamen kann man den Prefix der REDAXO Tabellen setzen.

	// allgemeine Definition
	action|create_table|tablename

	// Beispiel Formbuilder
	text|vorname|Vorname
	text|name|Name
	
	action|create_table|rex_order

```php
<?php
$yform->setValueField('text', array("vorname","Vorname"));
$yform->setValueField('text', array("name","Name"));

$yform->setActionField('create_table', array("rex_order"));
```

#### db

Speichert oder aktualisiert Formulardaten in einer Tabelle. Dabei werden die Label und deren Eingaben in die gleichnamigen Tabellenfelder gespeichert.
Mit %TABLE_PREFIX% im Tabellennamen kann man den Prefix der REDAXO Tabellen setzen.

```
// allgemeine Definition
action|db|tblname|[where(id=2)/main_where]

// im YForm-Formbuilder
text|vorname|Vorname
text|name|Name
text|plz|PLZ
text|ort|Ort

objparams|getdata|true
objparams|main_table|rex_warenkorb
objparams|main_where|id=1

action|db|rex_warenkorb|main_where
action|html|Daten gespeichert
```

```php
// in Beispiel PHP
$yform->setValueField('text', array("vorname","Vorname"));
$yform->setValueField('text', array("name","Name"));
$yform->setValueField('text', array("plz","PLZ"));
$yform->setValueField('text', array("ort","Ort"));

$yform->setObjectparams('getdata', TRUE);
$yform->setObjectparams('main_where', 'id='.(int) $id);
$yform->setObjectparams('main_table', 'rex_warenkorb');

$yform->setActionField('db', array("rex_warenkorb", "main_where"));
$yform->setActionField('html', array("Daten gespeichert"));
```
#### db_query

Führt eine Abfrage aus, z. B. um hier Werte aus Eingabefeldern in die Abfrage einzusetzen.

	// allgemeine Definition
	action|db_query|query|Fehlermeldung

	// im YForm-Formbuilder
	text|name|Name
	text|email|E-Mail-Adresse
	action|db_query|insert into rex_ycom_user set name = ?, email = ?|name,email

```php
<?php
$yform->setValueField('text', array("name","Name"));
$yform->setValueField('text', array("email","|E-Mail-Adresse"));
$yform->setActionField('db_query', array("insert into rex_ycom_user set name = ?, email = ?", "name,email"));
```

#### email

Sendet E-Mail mit Betreff und Body an angegebene E-Mail-Adresse. Eingaben aus dem Formular können als Platzhalter im Mailbody verwendet werden. 

	// allgemeine Definition
	action|email|from@email.de|to@email.de|Mailsubject|Mailbody###name###|<p class="alert alert-danger">Es ist ein Fehler aufgetreten. Bitte kontaktieren Sie uns telefonisch.</p>
	
	im YForm-Formbuilder
	text|name|Name
	action|email|from@mustermann|to@mustermann.de|Test|Hallo ###name###|<p class="alert alert-danger">Es ist ein Fehler aufgetreten. Bitte kontaktieren Sie uns telefonisch.</p>
	

```php
// in Beispiel PHP
$yform->setValueField('text', array("name","Name"));
$yform->setActionField('email', array("from@mustermann", "to@mustermann.de", "Test", "Hallo ###name###", '<p class="alert alert-danger">Es ist ein Fehler aufgetreten. Bitte kontaktieren Sie uns telefonisch.</p>'));
```

#### encrypt_value (wird nicht mehr fortgeführt)

Verschlüsselt eine Eingabe in Feld mit Label.

	// allgemine Definition
	action|encrypt|label[,label2,label3]|md5|[save_in_this_label]

	im YForm-Formbuilder
	text|pass|Password
	action|encrypt_value|pass|md5

	action|db|rex_warenkorb
	action|html|Daten gespeichert	

```php
// In Beispiel PHP
$yform->setValueField('text', array("pass", "Password"));
$yform->setActionField('encrypt_value', array("pass", "md5"));	
$yform->setActionField('db', array("rex_warenkorb"));
$yform->setActionField('html', array("Daten gespeichert"));
```

#### fulltext_value (wird nicht mehr fortgeführt)

Erklärung folgt.

	// allgemine Definition
	action|fulltext_value|label|fulltextlabels with ,

	// im YForm-Formbuilder
	folgt 

```php
<?php
folgt
```

#### html

Gibt HTML-Code aus.

	// allgemeine Definition
	action|html|[html]

	// im YForm-Formbuilder
	action|html|<b>fett</b>

```php
<?php
$yform->setActionField('html', array("<b>fett</b>"));
```

#### manage_db

Die Action legt bei Bedarf nicht vorhandene Felder an.

#### readtable

Liest aus der angegebenen Tabelle den Feldinhalt von `feldname` anhand der Eingabe im Formular-Feld `label` den gefundenen Datensatz. Das gesuchte Tabellen-Feld `label`muss im Formular vorhanden sein.  
Damit kann man anhand eines Eingabefeldes Daten aus einer Tabellen selektieren. Die Werte des gefundenen Datensatzes stehen dann auch zur Weiterverarbeitung z. B. im E-Mail-Versand zur Verfügung.

	// allgemeine Definition
	action|readtable|tablename|feldname|label

	// im YForm-Formbuilder
	text|name|Name
	action|readtable|shop_user|fname|name

```php
<?php
$yform->setValueField('text', array("name","Name"));
$yform->setActionField('readtable', array("shop_user", "fname", "name"));
```

#### redirect

Führt nach dem Abschicken des Formulars eine Weiterleitung aus.

```plaintext
// allgemeine Definition
action|redirect|Artikel-Id oder Externer Link|request/label|field

// im YForm-Formbuilder
// Umleitung auf internen Artikel 32
action|redirect|32
// mit Übergabe von URL-Parameter(n):
// Hinweis: Interne Links (per ID übergeben) müssen zwingend als externe URL angegeben werden, sonst lassen sich keine URL-Parameter übergeben!
action|redirect|https://www.example.org/kontakt/?mein_parameter=mein_wert
```

	<?php
```php
$yform->setActionField('redirect', array("32"));
// mit Übergabe von URL-Parameter(n):
$yform->setActionField('redirect', [rex_getUrl(32, rex_clang::getCurrentId(), ['mein_parameter' => 'mein_wert'])]);
// oder
$yform->setActionField('redirect', ["https://www.example.org/kontakt/?mein_parameter=mein_wert");
```
#### showtext

Gibt einen Antworttext zurück, der als Plaintext, HTML oder über Textile formatiert werden kann.

	// allgemeine Definition
	action|showtext|Antworttext|<p>|</p>|0/1/2 (plaintext/html/textile)

	// im YForm-Formbuilder
	action|showtext|Hallo das ist Redaxo|<p>|</p>|0
	action|showtext|Hallo das ist *Redaxo*|||2

```php
// In Beispiel PHP
$yform->setActionField('showtext', array("Hallo das ist Redaxo", "<p>", "</p>", "0"));
$yform->setActionField('showtext', array("Hallo das ist *Redaxo*", "", "", "2"));
```

	// Ausgabe nach Submit
	<p>Hallo das ist Redaxo</p>	
	<p>Hallo das ist <strong>Redaxo</strong></p>

#### tpl2email (Plugin email)

Versendet eine E-Mail über ein YForm-E-Mail-Template. Der Parameter **emailtemplate** ist der Key des E-Mail-Templates.

	// allgemeine Definition
	action|tpl2email|emailtemplate|emaillabel|[email@domain.de]

	// im YForm-Formbuilder
	text|email|E-Mail-Empfänger
	action|tpl2email|emailtemplate|email

```php
// In Beispiel PHP
$yform->setValueField('text', array("email","E-Mail-Empfänger"));  	
$yform->setActionField('tpl2email', array("emailtemplate", "email"));
```

	// In Beispiel PHP => manuelles Triggern von tpl2email OHNE Ausgabe
	$yform = new rex_yform();
	$yform->setObjectparams('csrf_protection',false);
	$yform->setValueField('hidden', ['email',$email]); // $email als Variable steht dann im Email-Template zur Verfügung (beliebig erweiterbar)
	$yform->setActionField('tpl2email', ["emailtemplate","email",'zieladresse@email.de'])
	$yform->getForm();
	$yform->setObjectparams('send',1);
	$yform->executeActions();

> **Hinweis:**
> * Wird keine E-Mail-Adresse angegeben, wird die E-Mail-Adresse verwendet, die bei `System/Einstellungen` hinterlegt ist.
> * `emaillabel` ist das E-Mail-Label, Formular-Element
> * Wird eine E-Mail-Adresse angegeben, wird die E-Mail des Labels überschrieben.

Die Action lässt sich auch mehrfach verwenden, sodass z. B. noch eine Bestätigungs-E-Mail an einen vorgegebenen Empfänger versendet werden kann.

	// im YForm-Formbuilder
	text|email|E-Mail-Empfänger
	action|tpl2email|emailtemplate|email
	action|tpl2email|emailtemplate||bestaetigung@redaxo.org

```php
// In Beispiel PHP
$yform->setValueField('text', array("email","E-Mail-Empfänger"));  	
$yform->setActionField('tpl2email', array("emailtemplate", "email"));
$yform->setActionField('tpl2email', array("emailtemplate", "", "bestaetigung@redaxo.org"));
```

	
#### wrapper_value (wird nicht mehr fortgeführt)

	// allgemeine Definition
	action|wrapper_value|label|prefix###value###suffix
	
	// im YForm-Formbuilder
	text|telefon|Telefon
	action|wrapper_value|telefon|<a href="tel:+49###value###">###value###</a>
	action|db|rex_warenkorb
	action|html|Daten gespeichert	

```php
// In Beispiel PHP
$yform->setValueField('text', array("telefon", "Telefon"));
$yform->setActionField('wrapper_value', array("telefon", "<a href=\"tel:+49###value###\">###value###</a>"));
$yform->setActionField('db', array("rex_warenkorb"));
$yform->setActionField('html', array("Daten gespeichert"));
```

## YForm-Modul: YForm erweitern

YForm lässt sich an verschiedenen Stellen erweitern - durch eigene Feldtypen, Templates und mittels Extension Points.

### eigene Values, Validates und Actions verwenden


Values, Validates und Actions werden von YForm automatisch aufgenommen. Dupliziere dazu z. B. eine Validierung aus `/redaxo/src/addons/yform/lib/yform/validate`. Den Datei- sowie den Klassennamen anpassen und unter `/redaxo/src/addons/project/lib/` ablegen.

> **Tipp:** Das Theme-Addon für REDAXO bringt bereits eine Struktur mit, in der eigene YForm-Erweiterungen abgelegt werden können.

```
lib
   yform
      action
      validate
      value
...
ytemplates
   bootstrap
   classic
```

### ein eigenes Template / Framework für Formularcode verwenden

Standardmäßig werden Formularcodes von YForm mit Bootstrap-3-Syntax ausgegeben. Mit dem Parameter `form_ytemplate` lassen sich eigene Templates laden, die das Template komplett oder auch teilweise überschreiben.

Dazu z. B. in die boot.php des `project`-Addons folgende Zeile aufnehmen:

```php
rex_yform::addTemplatePath($this->getPath('ytemplates'));
```

und anschließend die gewünschten Ausgabe-Templates von `/redaxo/src/addons/yform/ytemplates/bootstrap/` nach `/redaxo/src/addons/project/ytemplates/dein_template/` kopieren und anpassen oder eigene hinzufügen.

Zu guter Letzt über den Parameter `form_ytemplate` das zusätzliche Template für die Ausgabe wählen.

**Pipe-Schreibweise**

```text
objparam|form_ytemplate|dein_template,bootstrap
```

> **Tipp:** Es genügt, einzelne Template-Dateien abzuändern und ggf. zu überschreiben - alle Template-Dateien, die nicht in `dein_template` abgelegt sind, werden dann weiterhin vom `bootstrap`-Template verwendet.

### Extension Points in YForm

In YForm gibt es verschiedene Extension Points, wie z. B. `YFORM_DATA_LIST_SQL`, der die Darstellung der Tabelle und der Datensätze im Table-Manager beeinflusst. Hilf mit, diese Liste zu vervollständigen und werde Teil des Doku-Teams!

#### Die E-Mail-Adresse in der Table-Manager-Übersicht verlinken

Ein Trick von @steri-rex unter https://github.com/yakamara/redaxo_yform_docs/issues/117

In die boot.php vom `project`-Addon folgenden Code mit aufnehmen, um am Beispiel des `ycom`-Addons die E-Mail-Adresse zu verlinken. Es muss ein Feld `email` in der Tabelle existieren.

```php
function format_email($ep)
{
return '<a href="mailto:###email###">###email###</a>'; // email ist der Platzhalter für den VALUE von Spalte email
}

rex_extension::register('YFORM_DATA_LIST','my_rex_list_tweaks');

function my_rex_list_tweaks($ep)

{
 $list = $ep->getSubject();
//if ($ep->getParam('table')->getTableName() == rex::getTable('ycom_user')) { // optional, wenn nur eine bestimmte Tabelle verändert werden soll
 $list->setColumnFormat('email','custom','format_email'); // email ist die Spalte welche editiert werden soll
//}

}
```

### Beispiel: customfunction zum Validieren der IBAN

#### Vorgehen

1. Feld "iban" als Textfeld hinzufügen
2. Validierungsfeld "customfunction" hinzufügen
3. Als Funktion getValidIban angeben
4. Nachfolgende Funktion ins Project-Addon ablegen

#### Funktion

```php
<?php

function getValidIban($_label,$iban, $_additional_param)
{
  // normalize
  $iban    = str_replace(array(
      ' ',
      '-',
      '.',
      ','
  ), '', strtoupper($iban));
  
  $pattern = '#(?P<value>((?=[0-9A-Z]{28}$)AL\d{10}[0-9A-Z]{16}$|^(?=[0-9A-Z]{24}$)AD\d{10}[0-9A-Z]{12}$|^(?=[0-9A-Z]{20}$)AT\d{18}$|^(?=[0-9A-Z]{22}$)BH\d{2}[A-Z]{4}[0-9A-Z]{14}$|^(?=[0-9A-Z]{16}$)BE\d{14}$|^(?=[0-9A-Z]{20}$)BA\d{18}$|^(?=[0-9A-Z]{22}$)BG\d{2}[A-Z]{4}\d{6}[0-9A-Z]{8}$|^(?=[0-9A-Z]{21}$)HR\d{19}$|^(?=[0-9A-Z]{28}$)CY\d{10}[0-9A-Z]{16}$|^(?=[0-9A-Z]{24}$)CZ\d{22}$|^(?=[0-9A-Z]{18}$)DK\d{16}$|^FO\d{16}$|^GL\d{16}$|^(?=[0-9A-Z]{28}$)DO\d{2}[0-9A-Z]{4}\d{20}$|^(?=[0-9A-Z]{20}$)EE\d{18}$|^(?=[0-9A-Z]{18}$)FI\d{16}$|^(?=[0-9A-Z]{27}$)FR\d{12}[0-9A-Z]{11}\d{2}$|^(?=[0-9A-Z]{22}$)GE\d{2}[A-Z]{2}\d{16}$|^(?=[0-9A-Z]{22}$)DE\d{20}$|^(?=[0-9A-Z]{23}$)GI\d{2}[A-Z]{4}[0-9A-Z]{15}$|^(?=[0-9A-Z]{27}$)GR\d{9}[0-9A-Z]{16}$|^(?=[0-9A-Z]{28}$)HU\d{26}$|^(?=[0-9A-Z]{26}$)IS\d{24}$|^(?=[0-9A-Z]{22}$)IE\d{2}[A-Z]{4}\d{14}$|^(?=[0-9A-Z]{23}$)IL\d{21}$|^(?=[0-9A-Z]{27}$)IT\d{2}[A-Z]\d{10}[0-9A-Z]{12}$|^(?=[0-9A-Z]{20}$)[A-Z]{2}\d{5}[0-9A-Z]{13}$|^(?=[0-9A-Z]{30}$)KW\d{2}[A-Z]{4}22!$|^(?=[0-9A-Z]{21}$)LV\d{2}[A-Z]{4}[0-9A-Z]{13}$|^(?=[0-9A-Z]{,28}$)LB\d{6}[0-9A-Z]{20}$|^(?=[0-9A-Z]{21}$)LI\d{7}[0-9A-Z]{12}$|^(?=[0-9A-Z]{20}$)LT\d{18}$|^(?=[0-9A-Z]{20}$)LU\d{5}[0-9A-Z]{13}$|^(?=[0-9A-Z]{19}$)MK\d{5}[0-9A-Z]{10}\d{2}$|^(?=[0-9A-Z]{31}$)MT\d{2}[A-Z]{4}\d{5}[0-9A-Z]{18}$|^(?=[0-9A-Z]{27}$)MR13\d{23}$|^(?=[0-9A-Z]{30}$)MU\d{2}[A-Z]{4}\d{19}[A-Z]{3}$|^(?=[0-9A-Z]{27}$)MC\d{12}[0-9A-Z]{11}\d{2}$|^(?=[0-9A-Z]{22}$)ME\d{20}$|^(?=[0-9A-Z]{18}$)NL\d{2}[A-Z]{4}\d{10}$|^(?=[0-9A-Z]{15}$)NO\d{13}$|^(?=[0-9A-Z]{28}$)PL\d{10}[0-9A-Z]{,16}n$|^(?=[0-9A-Z]{25}$)PT\d{23}$|^(?=[0-9A-Z]{24}$)RO\d{2}[A-Z]{4}[0-9A-Z]{16}$|^(?=[0-9A-Z]{27}$)SM\d{2}[A-Z]\d{10}[0-9A-Z]{12}$|^(?=[0-9A-Z]{,24}$)SA\d{4}[0-9A-Z]{18}$|^(?=[0-9A-Z]{22}$)RS\d{20}$|^(?=[0-9A-Z]{24}$)SK\d{22}$|^(?=[0-9A-Z]{19}$)SI\d{17}$|^(?=[0-9A-Z]{24}$)ES\d{22}$|^(?=[0-9A-Z]{24}$)SE\d{22}$|^(?=[0-9A-Z]{21}$)CH\d{7}[0-9A-Z]{12}$|^(?=[0-9A-Z]{24}$)TN59\d{20}$|^(?=[0-9A-Z]{26}$)TR\d{7}[0-9A-Z]{17}$|^(?=[0-9A-Z]{,23}$)AE\d{21}$|^(?=[0-9A-Z]{22}$)GB\d{2}[A-Z]{4}\d{14}))#';
  // check
  if (preg_match($pattern, $iban, $matches)) {
      return false;
  } else {
      return true;
  }
}
```
