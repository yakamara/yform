# Plugins

> **Hinweis:**
> Plugins erweitern YForm und können optional aktiviert werden.

## E-Mail-Plugin: Einführung

### Zweck der E-Mail-Templates

Will man eine E-Mail aus einem YForm-Formular versenden, kann man mit Hilfe eines `E-Mail-Templates` (siehe entsprechender Menüpunkt in YForm) diese E-Mail gestalten und mit Platzhaltern aus dem Formular versehen.

Über die E-Mail-Template-Verwaltung kann ein Template angelegt werden. Dabei muss zuerst ein Key erstellt werden, der die eindeutige Zuordnung zu diesem Tempalte ermöglicht. Ebenfalls muss die Absender-E-Mail, der Absender-E-Mail-Name sowie der Betreff eingegeben werden.

Danach folgen die Eingaben für den E-Mail-Body, in Plain und HTML (optional).

### Handhabung

Über die Aktion **tpl2email** kann eine E-Mail über den angebenen **Key** eines E-Mail-Templates gesendet werden. Über das Formular können zb. die Werte der beiden Eingabefelder des Formular über das E-Mail-Template ausgeben werden.

#### Beispiel-Formular im Formbuilder

	text|vorname|Vorname|
	text|name|Name|
	text|email|E-Mail-Adresse|

	validate|email|email|Das Feld enthält keine korrekte E-Mail-Adresse!
	validate|empty|email|Das Feld enthält keine korrekte E-Mail-Adresse!
	
	action|tpl2email|testtemplate|email

#### Eingaben im E-Mail-Template

Als E-Mail-Template `Key` wird eingetragen:

	testtemplate

In den E-Mail-Template `Body` kommt:
	
	Hallo,
	REX_YFORM_DATA[field="vorname"] REX_YFORM_DATA[field="name"]
	
In den E-Mail-Template `Body (Html)` kommt:
	
	Hallo,<br />
	REX_YFORM_DATA[field="vorname"] REX_YFORM_DATA[field="name"]

#### PHP

Es kann auch PHP-Code integriert werden, um z.B. Formular-Eingaben zu prüfen und die Ausgabe in der E-Mail individuell zu verändern.

```php
Hallo,<br />
<?php 
if ('REX_YFORM_DATA[field="anrede"]' == 'w') {
    echo "Frau";
} else {
    echo "Herr";
}
?> REX_YFORM_DATA[field="vorname"] REX_YFORM_DATA[field="name"]
```

> **Hinweis:**  
> Die Action **tpl2email** kann auch mehrfach im Formular eingesetzt werden. So könnten E-Mails mit unterschiedlichen Templates versendet werden oder auch an mehrere Empfänger, z.B. Admin und Kunde.


### Beispiele

Im Regelfall werden die E-Mail-Templates zusammen mit einer passenden Action im YForm-Formular verwendet. Dies wird im Abschnitt [Actions](yform_modul_actions.md) erläutert.

E-Mail-Templates können jedoch auch von einem YForm-Formular losgelöst verwendet werden, zum Beispiel in Cronjobs, einem eigenen Addon, etc.

### Variante 1: `tpl2email` in einem yForm-PHP-Modul simulieren

Nachfolgend ein angepasster Formular-Code, um die E-Mail separat zu versenden. Dabei wird ein eigener, zusätzlicher Platzhalter definiert, der sich nicht im Formular befindet. Bitte die Kommentare beachten.

```php
<?
$yform = new rex_yform();
$yform->setObjectparams('form_ytemplate', 'bootstrap');
$yform->setObjectparams('form_showformafterupdate', 0); // Muss 0 sein, damit if($form) funktioniert
$yform->setObjectparams('real_field_names', true);

$yform->setValueField('text', array("name","Name"));
$yform->setValueField('text', array("email","E-Mail-Adresse"));
$yform->setValidateField('email', array("email","Bitte geben Sie eine gültige Emailadresse an."));
$yform->setValueField('textarea', array("message","Nachricht"));
$yform->setObjectparams('form_action',rex_article::getCurrent()->getUrl());

// Statt der Action wird die E-Mail separat versendet.
// $yform->setActionField('tpl2email', array('emailtemplate', 'emaillabel', 'email@domain.de'));

$form = $yform->getForm(); // HTML-Code des Formulars

if($form) { // Wenn das Formular nicht abgesendet wurde
    echo $form; // HTML-Codes des Formulars ausgeben
} else { 

	// Ab hier beginnen die Vorbereitungen zum E-Mail-Versand
	$yform_email_template_key = 'test'; // Key, wie im Backend unter YForm > E-Mail-Templates hinterlegt
	$debug = 0;

	// Array mit Platzhaltern, die im E-Mail-Template ersetzt werden.
	$values = $yform->objparams['value_pool']['email'];
	$values['custom'] = 'Eigener Platzhalter';

	if ($yform_email_template = rex_yform_email_template::getTemplate($yform_email_template_key)) {

	    if ($debug) {
	        echo '<hr /><pre>'; var_dump($yform_email_template); echo '</pre><hr />';
	    }
	    $yform_email_template = rex_yform_email_template::replaceVars($yform_email_template, $values);
	    $yform_email_template['mail_to'] = $values['email'];
	    $yform_email_template['mail_to_name'] = $values['name'];

	    if ($debug) {
	        echo '<hr /><pre>'; var_dump($yform_email_template); echo '</pre><hr />';
	    }
	    if (!rex_yform_email_template::sendMail($yform_email_template, $yform_email_template_key)) {
	        if ($debug) { echo 'E-Mail konnte nicht gesendet werden.'; }
	        return false;
	    } else {
	        if ($debug) { echo 'E-Mail erfolgreich gesendet.'; }
	        return true;
	    }
	} else {
	    if ($debug) {echo '<p>YForm E-Mail-Template "'.htmlspecialchars($yform_email_template_key).'" wurde nicht gefunden.'; }
	}
}
?>
```

Wenn die Validierung des Formulars erfolgreich ist, wird die E-Mail versendet und der selbst definierte Platzhalter steht nun ebenfalls im Template zur Verfügung.

```
REX_YFORM_DATA[field="name"]
REX_YFORM_DATA[field="email"]
REX_YFORM_DATA[field="custom"]
```

### Variante 2: E-Mail-Versand zur Verwendung in Cronjobs, Addons, etc.

Dieser Code basiert auf [plugins/email/lib/yform_action_tpl2email.php](https://github.com/yakamara/redaxo_yform/blob/master/plugins/email/lib/yform_action_tpl2email.php).

```php
<?
$yform_email_template_key = 'test'; // Key, wie im Backend unter YForm > E-Mail-Templates hinterlegt
$debug = 0;

// Platzhalter, die im E-Mail-Template ersetzt werden. Dieses Array könnte bspw. auch von der Datenbank befüllt werden.
$values['anrede'] = 'Herr'; 
$values['name'] = 'Max Mustermann'; 
$values['email'] = 'max@mustermann.de'; 

if ($yform_email_template = rex_yform_email_template::getTemplate($yform_email_template_key)) {

    if ($debug) {
        echo '<hr /><pre>'; var_dump($yform_email_template); echo '</pre><hr />';
    }
    $yform_email_template = rex_yform_email_template::replaceVars($yform_email_template, $values);
    $yform_email_template['mail_to'] = $values['email'];
    $yform_email_template['mail_to_name'] = $values['name'];

    if ($debug) {
        echo '<hr /><pre>'; var_dump($yform_email_template); echo '</pre><hr />';
    }
    if (!rex_yform_email_template::sendMail($yform_email_template, $yform_email_template_key)) {
        if ($debug) { echo 'E-Mail konnte nicht gesendet werden.'; }
        return false;
    } else {
        if ($debug) { echo 'E-Mail erfolgreich gesendet.'; }
        return true;
    }
} else {
    if ($debug) {echo '<p>YForm E-Mail-Template "'.htmlspecialchars($yform_email_template_key).'" wurde nicht gefunden.'; }
}
?>
```

Absender, Betreff usw. werden automatisch ausgefüllt, indem die üblichen Platzhalter im E-Mail-Template verwendet werden:

```
REX_YFORM_DATA[field="name"]
REX_YFORM_DATA[field="phone"]
REX_YFORM_DATA[field="email"]
```

## RESTful API: Einführung

### Erste Schritte

Mit dem REST-Plugin lässt sich eine Schnittstelle aktivieren, mit der man YForm Tabellen von außen abrufen, verändern und löschen kann. Dabei wird auf die REST-API gesetzt.
Die Klasse muss zunächst registriert werden siehe [YOrm](yorm.md), damit auf diese mit REST zugegriffen werden kann. Alle Übergaben und Rückgabewerte werden als JSON übertragen.

> Hinweis: Die Tabellem müssen mit YForm verwaltbar sein, da diese Felder automatisch genutzt werden.

Die Schnittstelle orientiert sich an https://jsonapi.org/format/. Aufrufe und JSON Formate sind ähnlich bis exakt so aufgebaut.

### Konfiguration / Endpoints

Die Zugriff über REST muss für jeden Endpoint einzeln definiert werden. D.h. man muss für jede Tabelle und für unterschiedliche Nutzungszenarien diese fest definieren.

Die Standardroute der REST-API ist auf "/rest" gesetzt, d.h. hierunter können eigene Routen definiert werden. 

Die Konfiguration wird über PHP festgelegt und sollte im project-AddOn in der boot.php abgelegt werden. Kann aber auch an andere Stelle abgelegt werden, solange diese während der Initialisierung aufgerufen wird.

Hier ein Beispiel, um YCom-User über die REST-API zu verwalten:


```php

// diese Zeile ist normalerweise nötig. Dieses Bespiel nutzt aber eine YCom-Tabelle, die bereits über das AddOn registriert ist.
##rex_yform_manager_dataset::setModelClass('rex_ycom_user', rex_ycom_user::class);


// Konfiguration des REST Endpoints.
$route = new \rex_yform_rest_route(
    [
        'path' => '/v1/user/',
        'auth' => '\rex_yform_rest_auth_token::checkToken',
        'type' => \rex_ycom_user::class,
        'query' => \rex_ycom_user::query(),
        'get' => [
            'fields' => [
                'rex_ycom_user' => [
                    'id',
                    'login',
                    'email',
                    'name'
                 ],
                 'rex_ycom_group' => [
                    'id',
                    'name'
                 ]
            ]
        ],
        'post' => [
            'fields' => [
                'rex_ycom_user' => [
                    'login',
                    'email',
                    'ycom_groups'
                ]
            ]
        ],
        'delete' => [
            'fields' => [
                'rex_ycom_user' => [
                    'id',
                    'login'
                ]
            ]
        ]
    ]
);

// Einbinden der Konfiguration
\rex_yform_rest::addRoute($route);
```

Dieses Beispiel führt dazu, dass man User über das PlugIn auslesen kann, wie auch User einspielen kann, aber nur mit den Feldern: login,email,ycom_groups. 
Löschen kann man jeden User. Über Filter bei id oder login, lassen sich bestimmte User filtern und als Ganzes löschen.



#### Route-Konfiguration

`path`
muss angegeben werden und bestimmt mit dem $prePath den Endpoint. In diesem Fall wird dann daraus: `/rest/v1/user`

`auth`
ist optional und kann komplett weggelassen werden, wenn man keine Authentifizerung für einen Endpoint haben möchte. Erlaubt sind callbacks und Funktionsnamen.
Die Funktion darf nicht direkt aufgerufen werden, sondern muss als Callback wie im Beispiel eingetragen werden, damit sie erst im Bedarfsfall verwendet wird.

Beispiele

* **'\rex_yform_rest_auth_token::checkToken'** für die interne Authentifizierung mit einfachem Token
* **'MeineFunktion'**

Wenn man keine Authentifizierung einträgt kann jeder diese Daten entsprechend der weiteren Konfiguration nutzen. Sollte man nur bei Tabellen wie PLZ oder ähnlich offensichtlich freien Daten machen.


`table`
Hier wird die entsprechend Tabelle übergeben, die in YOrm definiert ist.

Beispiel

* **\rex_ycom_user::table()**

`get`

`post`

`delete`

### Nutzung eines Endpoints

URL (z.B. https://domain/rest/v1/user)
In den Beispielen wird davon ausgegangen, dass es keine eigene Authentifizierung gibt. Um zu sehen wie die Aufrufe funktionieren bitte hier https://jsonapi.org/format/ nachschlagen. 

#### GET

* Filter
* Felder

#### POST

* Anlegen von Datensätzen
* Update von Datensätzen
* Validierung

#### DELETE

* Filter
* Nach ID

### Authentifizierung

#### Standardauthentifizierung

Wenn im Model folgende Authentifizerung angegeben wurde: `'\rex_yform_rest_auth_token::checkToken()'` ist das die Standardauthentifizierung mit Tokens aus der YForm:Rest:Tokenverwaltung.

Die hier erstellen Token werden entsprechend überprüft und müssen im Header übergeben werden. `token=###meintoken###` Nur aktive Tokens funktionieren. 
Über das REST PlugIn kann man im Backend diese Zugriffe einschränken und tracken. D.h. Es können Einschränkungen wir Zugriffe / Stunde oder ähnliches eingestellt werden. 
Jeder Zugriff auf die REST-API wird erfasst. 

## Tools-Plugin

> Dieses Plugin hilft bei bestimmten Eingabearten. Datumsfelder, DatumZeit-Felder und Textfelder die bestimmte Eingaben verlangen, die man bereits bei der Eingabe erzwingen möchte.

Dabei werden die entsprechenden Bibliotheken bei der Aktivierung des AddOns bereits installiert und initialisiert. D.h. man muss die gewünschten Funktionen nur durch Definition von CSS Attributen zuweisen.

### select2

diese Bibliothek [https://select2.github.io/](https://select2.github.io/) hilft dabei, Selectfelder zu vereinfachen und entsprechend des Typs verschiedene Varianten zu aktivieren.

Dabei muss hier das select-Feld folgendes Attribut bekommen:

	data-yform-tools-select2 = ""

Das kann man im Manager über das Feld „Attribute“ innerhalb von z. B. `select` oder `select_sql` so setzen:


	{"data-yform-tools-select2": "", "placeholder": "My Placeholder"}

Eine weitere Variante wäre der Tag-Mode

	{"data-yform-tools-select2": "tags", "placeholder": "My Placeholder"}


### inputmask

Diese Bibliothek [https://github.com/RobinHerbots/Inputmask](https://github.com/RobinHerbots/Inputmask) dient dazu, bestimmte Eingabeformate vorzugeben um somit Fehler zu vermeiden. Z.B. kann ein bestimmtes Datumsformat erzwungen werden.

Dabei wird auch hier ein Attribute im Textfeld gesetzt:

    data-yform-tools-inputmask = ""

Man muss einen Wert angeben, damit dem Textfeld klar ist, wie die Überprüfung auszusehen hat. z.B.

    dd/mm/yyyy

oder

    99-99-9999

oder

    9-a{1,3}9{1,3}


Das kann man im Manager über das Attributefeld innerhalb von z.B. text so setzen:

    {"data-yform-tools-inputmask":"dd/mm/yyyy"}

### daterangepicker

Diese Bibliothek [http://www.daterangepicker.com/](http://www.daterangepicker.com/) dient für die Auswahl von Datumsfeldern oder Datumzeiträumen. Dabei kann auch eine Uhrzeit selektiert werden.

> Bitte unbedingt beachten, dass man das selbe Format bei den Date(time)pickern einträgt, wie man es im entsprechenden Feld (z.B. Date) ausgewählt hat.


Dabei muss das Textfeld folgendes Attribut bekommen:

    data-yform-tools-datepicker = ""

oder

    data-yform-tools-datetimepicker = ""

und auch mit Formaten versehen werden. Zum Beispiel beim Datepicker


    DD-MM-YYYY

oder beim Datetimepicker

    YYYY-MM-DD HH:ii

kann man im Manager über das Attibutefeld innerhalb von z.B. date mit input:text so setzen:

    {"data-yform-tools-datetimepicker":"YYYY-MM-DD HH:ii"}


### Ein paar Beispiele für Kombinationen aus datepicker/datetimepicker und Inputmask

datepicker und Inputmask:

    {"data-yform-tools-datepicker":"DD.MM.YYYY", "data-yform-tools-inputmask":"dd.mm.yyyy"}

datetimepicker und Inputmask:

    {"data-yform-tools-datetimepicker":"DD.MM.YYYY HH:ii:ss", "data-yform-tools-inputmask":"datetime", "data-inputmask-mask":"1.2.y h:s:s", "data-inputmask-alias":"dd.mm.yyyy", "data-inputmask-placeholder":"dd.mm.yyyy hh:mm:ss", "data-inputmask-separator":"."} 
