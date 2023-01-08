# E-Mail-Templates (PlugIn)

> **Hinweis:**
> Plugins erweitern YForm und können optional aktiviert werden.

## Zweck der E-Mail-Templates

Will man eine E-Mail aus einem YForm-Formular versenden, kann man mit Hilfe eines `E-Mail-Templates` (siehe entsprechender Menüpunkt in YForm) diese E-Mail gestalten und mit Platzhaltern aus dem Formular versehen.

Über die E-Mail-Template-Verwaltung kann ein Template angelegt werden. Dabei muss zuerst ein Key erstellt werden, der die eindeutige Zuordnung zu diesem Tempalte ermöglicht. Ebenfalls muss die Absender-E-Mail, der Absender-E-Mail-Name sowie der Betreff eingegeben werden.

Danach folgen die Eingaben für den E-Mail-Body, in Plain und HTML (optional).

## Handhabung

Über die Aktion **tpl2email** kann eine E-Mail über den angebenen **Key** eines E-Mail-Templates gesendet werden. Über das Formular können z. B. die Werte der beiden Eingabefelder des Formular über das E-Mail-Template ausgeben werden.


### Beispiel-Formular im Formbuilder

	text|vorname|Vorname|
	text|name|Name|
	text|email|E-Mail-Adresse|

	validate|email|email|Das Feld enthält keine korrekte E-Mail-Adresse!
	validate|empty|email|Das Feld enthält keine korrekte E-Mail-Adresse!
	
	action|tpl2email|testtemplate|email

### Eingaben im E-Mail-Template

Als E-Mail-Template `Key` wird eingetragen:

	testtemplate

In den E-Mail-Template `Body` kommt:
	
	Hallo,
	REX_YFORM_DATA[field="vorname"] REX_YFORM_DATA[field="name"]
	
In den E-Mail-Template `Body (Html)` kommt:
	
	Hallo,<br />
	REX_YFORM_DATA[field="vorname"] REX_YFORM_DATA[field="name"]

### PHP

Es kann auch PHP-Code integriert werden, um z. B. Formular-Eingaben zu prüfen und die Ausgabe in der E-Mail individuell zu verändern.

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
> Die Action **tpl2email** kann auch mehrfach im Formular eingesetzt werden. So könnten E-Mails mit unterschiedlichen Templates versendet werden oder auch an mehrere Empfänger, z. B. Admin und Kunde.


## Beispiele

Im Regelfall werden die E-Mail-Templates zusammen mit einer passenden Action im YForm-Formular verwendet. Dies wird im Abschnitt [Actions](yform_modul_actions.md) erläutert.

E-Mail-Templates können jedoch auch von einem YForm-Formular losgelöst verwendet werden, zum Beispiel in Cronjobs, einem eigenen Addon, etc.

## Variante 1: `tpl2email` in einem yForm-PHP-Modul simulieren

Nachfolgend ein angepasster Formular-Code, um die E-Mail separat zu versenden. Dabei wird ein eigener, zusätzlicher Platzhalter definiert, der sich nicht im Formular befindet. Bitte die Kommentare beachten.

```php
<?php
$yform = new rex_yform();
$yform->setObjectparams('form_ytemplate', 'bootstrap');
$yform->setObjectparams('form_showformafterupdate', 0); // Muss 0 sein, damit if($form) funktioniert
$yform->setObjectparams('real_field_names', true);

$yform->setValueField('text', array("name","Name"));
$yform->setValueField('text', array("email","E-Mail-Adresse"));
$yform->setValidateField('type', array('email', "email","Bitte geben Sie eine gültige Emailadresse an."));
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

```html
REX_YFORM_DATA[field="name"]
REX_YFORM_DATA[field="email"]
REX_YFORM_DATA[field="custom"]
```

## Variante 2: E-Mail-Versand zur Verwendung in Cronjobs, Addons, etc.

Dieser Code basiert auf [plugins/email/lib/yform_action_tpl2email.php](https://github.com/yakamara/redaxo_yform/blob/master/plugins/email/lib/yform_action_tpl2email.php).

```php
<?php
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

```html
REX_YFORM_DATA[field="name"]
REX_YFORM_DATA[field="phone"]
REX_YFORM_DATA[field="email"]
```
