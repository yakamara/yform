# Tutorials

* E-Mail mit Dateianhang versenden
* Glossar / oder FAQ
* Versteckte Werte übergeben (Hidden-Fields)
* Schutz vor Spam
* Mitgliedsantrag

## E-Mail mit Dateianhang versenden

Gelegentlich besteht die Anforderung, ein Formular im Frontend zu programmieren, das ein oder mehrere Dateien entgegen nimmt und diese per E-Mail an ein oder mehrere Empfänger zustellt.

YForm bietet dieses Möglichkeit über einen Trick.

> **Hinweis**: Wir empfehlen, E-Mails möglichst ohne große Dateianhänge zu versenden. In manchen Szenarien ist es jedoch erforderlich, diese direkt als Anhang beizufügen. Für mehrere und größere Dateien gibt es [Alternativen](#alternativen).

### Voraussetzungen

1. Ein YForm-Formular (PHP-Schreibweise oder Formbuilder)
2. Ein E-Mail-Template (in REDAXO > YForm > E-Mail-Tempaltes)
3. Eine funktionierende PHPMailer-Konfiguration (in REDAXO > PHPMailer)

> Wichtig: Diese Anleitung funktioniert nur wenn man ein E-Mail-Template zusammen mit der Action `tpl2email` nutzt und nicht die im YForm Formbuilder-Modul angebotene Eingabe für eine E-Mail.



### Umsetzung

Dem bestehenden Formular wird ein `upload`-Valuefeld hinzugefügt, das in diesem Beispiel auf max. 10 MB begrenzt ist und nur bestimmte Dateiendungen zulässt.

Die empfohlene Dateigrößen-Begrenzung hängt von der gewählten PHPMailer-Konfiguration, der Konfiguration des Webservers und PHP sowie von weiteren Faktoren ab - z. B. Limits und Speicherplatz des Empfänger-Postfachs.

#### PHP-Schreibweise
```php
$yform->setValueField('upload', array('upload','Dateianhang','100,10000','.pdf,.odt,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.zip'));

$yform->setValueField('php', array('php_attach','Datei anhängen','<?php if (isset($this->params[\'value_pool\'][\'files\'])) { $this->params[\'value_pool\'][\'email_attachments\'] = $this->params[\'value_pool\'][\'files\']; } ?>'));
```

#### Pipe-Schreibweise
```
upload|upload|Dateianhang|100,10000|.pdf,.odt,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.zip|

php|php_attach|Datei anhängen|<?php if (isset($this->params['value_pool']['files'])) { $this->params['value_pool']['email_attachments'] = $this->params['value_pool']['files']; } ?>
```

Beim erfolgreichen Upload wird die Datei aus dem "value pool" des Dateiuploads (`files`) übertragen in den "value pool" der E-Mail-Anhänge (`email_attachments`).

### Datei(en) aus dem Medienpool als Anhang

Es können auch bestehende Dateien direkt aus dem Medienpool als E-Mail-Anhang versendet werden (z.B. abhängig von den Formulareingaben, Artikel-Bilder usw.).

#### PHP-Schreibweise
```php
$yform->setValueField('php', array('php_attach', 'Datei anhängen', '<?php $this->params[\'value_pool\'][\'email_attachments\'][] = [\'agb.pdf\', rex_path::media(\'agb.pdf\')]; ?>'));
```

#### Pipe-Schreibweise
```
php|php_attach|Datei anhängen|<?php $this->params[\'value_pool\'][\'email_attachments\'][] = [\'agb.pdf\', rex_path::media(\'agb.pdf\')]; ?>
```

#### Beispiel Medialiste

z.B. Bilder eines Artikels (Medialist) als E-Mail-Anhang

```php
$dateiliste = explode(',', $sql->getValue('objekt_bilderliste'));
foreach ($dateiliste as $file) {
    $yform->setValueField('php', array('php_attach', 'Datei anhängen', '<?php $this->params[\'value_pool\'][\'email_attachments\'][] = [\''.$file.'\', rex_path::media(\''.$file.'\')]; ?>'));
}
```

### Alternativen

1. Datei ins Dateisystem hochladen, Link zum Download per Mail versenden und nach einer Frist von z. B. 7 Tagen wieder vom Dateisystem löschen.
2. Externe Anbieter wie z. B. [wetransfer.com](https://wetransfer.com) (Datenschutz beachten!)
3. Cloud-Lösungen, wie z. B. eine Dropbox, OwnCloud oder NextCloud-Freigabe (Datenschutz beachten!)

### Credits

Thomas Blum, @tbaddade<br />
Alexander Walther, @alexplusde


## Glossar / oder FAQ

> Mit dieser Kombination aus Tableset, Modul-Eingabe und Modul-Ausgabe lässt sich ein Glossar pflegen.

### Installation

1. Tableset importieren [demo_tableset-rex_glossar.json](demo_tableset-rex_glossar.json)
2. Modul einrichten
3. Artikel anlegen und Modul hinzufügen
4. Glossar-Tabelle befüllen

> Hinweis: Es wird ein Redactor2-Profil `simple` benötigt, sonst siehe Modulausgabe. Das Modul nutzt Bootstrap für das Ein- und Ausblenden der Einträge.

### Modul-Eingabe

```
<div class="alert alert-info">
  Dieses Modul gibt das Glossar aus. Keine weiteren Einstellungsmöglichkeiten. 
</div>

```

### Modul-Ausgabe

```php
<?php
// REDAXO Glossary or FAQ for yform
// Modulausgabe

$db_table = "rex_glossar";
$sql = rex_sql::factory();
$sql->setDebug(false); //Ausgabe Query true oder false
$query = "SELECT * FROM $db_table  ORDER BY Begriff ";
$rows = $sql->getArray($query);
$counter = $bcounter = 1;
if ($sql->getRows()) > 0) {
// Wenn Datensätze im $sql vorliegen 
foreach($rows as $row)
{
 $id = $row["id"];
 $begriff = $row["Begriff"];
 $char = strtoupper(substr($begriff,0,1)); // Erster Buchstabe
 $beschreibung = $row["beschreibung"];
 # $beschreibung = nl2br($beschreibung); // wenn nur eine Textarea ohne WYSIWYG verwendet wird
 $counter++;
 // Ausgabe des Buchstabens, wenn in $dummy nicht bereits vorhanden. 
 if ($char != $dummy) { 
    $bcounter++;   
    $buchstabe ='<h2 id="buchstabe'.$char.'">'.$char. '</h2>'; 
    $index .= '<a type="button" class="btn btn-default" href="#buchstabe'.$char.'">'.$char. '</a>';
    // Erstellt Links für das Alphabet am Anfang 
 }  else {
    $buchstabe = "";
}
// Ausgabe als Bootstrap Panel
$out .= $buchstabe.' 
<div class="panel panel-default">
            <div class="panel-heading">
              <a data-toggle="collapse" data-parent="#accordionREX_SLICE_ID" href="#collapse'.$counter.'">'.$begriff.'</a>
            </div>
            <div id="collapse'.$counter.'" class="panel-collapse collapse">
                <div class="panel-body">'.$beschreibung.'
                </div>
            </div>
        </div>';
//dummy nimmt den aktuellen Buchstaben auf. 
$dummy = $char;

 } 
echo $index; // gibt Schnellinks als Alphabet aus
echo $out; // Ausgabe der Panels und Überschriften
}
?>

```

## Versteckte Werte übergeben (Hidden-Fields)

Gelegentlich besteht die Anforderung, einem Formular Parameter mitzugeben, die der Nutzer nicht sehen soll oder verändern darf.

YForm bietet dieses Möglichkeit auf zwei Wege: Serverseitig über das YForm-Value "hidden" - oder clientseitig über ein YForm Text-Eingabefeld `<input type="hidden" />`.

> **Hinweis**: Wir empfehlen, im Zweifel immer die serverseitige Variante zu wählen, damit der Benutzer keine Manipulation vornehmen kann. 

Sensible Daten sollten niemals an den Besucher übergeben werden. Daten wie die Summe eines Warenkorbs sollten bei einem Bestellformular immer serverseitig berechnet und überprüft werden und nicht über ein ausgeblendetes Eingabefeld eines Formulars. 

Andere Daten wie z. B. die Anzahl der Produkte in einem Warenkorb können auch über ein clientseitiges hidden-Feld übertragen werden.

### Voraussetzung

Wenn die Formulardaten in einer Datenbanktabelle gespeichert werden sollen, kann im Table Manager ein beliebiges Feld angelegt werden, z.B. ein Textfeld. Je nachdem, was für ein Wert gespeichert werden soll, kann aber auch ein anderes Feld sinnvoll sein. In diesem Beispiel wird ein Feld mit dem Namen `summe` benötigt.

### serverseitige Lösung

#### Fest definierter Wert in Feld `summe` schreiben

**PHP-Schreibweise** 

```php
$yform->setHiddenField("summe", 150);
```

**Formbuilder Pipe-Schreibweise**

    hidden|summe|150|

#### Wert aus Variable in Feld `summe` schreiben

**PHP-Schreibweise** 

```php
$yform->setHiddenField("summe", $bestellung_summe);
```

**Formbuilder Pipe-Schreibweise**

    // nicht möglich

#### Wert aus dem GET-Parameter lesen

```php
// www.domain.de/meinformular/?q=Foo
$yform->setValueField('hidden', array("suche", "q", REQUEST));
```

**Formbuilder Pipe-Schreibweise**

    // www.domain.de/meinformular/?q=Foo
    hidden|suche|q|REQUEST

Schreibt den Wert `Foo` des GET-Parameters `q` in das Feld `suche`.

> **Wichtig** Der GET-Parameter wird immer aus dem *abgesendeten* Formular geholt und nicht aus der URL des Formulars. Ggf. muss der Objekt-Parameter von YForm `form_action` den GET-Parameter enthalten.

### clientseitige Lösung

#### Wert im Formular mit ausgeben

**PHP-Schreibweise** 

```php
$yform->setValueField('text', array('anzahl','Anzahl','0','0','{"type":"hidden"}'));
```

**Formbuilder Pipe-Schreibweise**

    text|anzahl|Anzahl|0|0|{"type":"hidden"}|

Erzeugt im Formular Eingabefeld, das z. B. per Javascript verändert werden kann:

    `<input class="form-control" name="FORM[...]" type="hidden" id="yform-data_edit-..." value="0">`

### Weitere Möglichkeiten

Das YForm-Objekt verfügt über zwei weitere Objekt-Methoden: `setHiddenField()` und `setHiddenFields()`. Diese legen jedoch ihre Werte nicht in den sog. `value_pool` ab, sondern als Objektparameter unter dem key `form_hiddenfields` - d.h. diese Werte sind nicht für Aktionen wie `tpl2email` oder `db` sichtbar und werden demzufolge nicht an ein E-Mail-Template oder die Datenbank übergeben.

```php
$yform->setHiddenField('versteckt', 1);
$yform->setHiddenFields(["a" => "b","c" => "d"]);
```

Auslesen über die Methode `getObjectparams()`

```php
$yform->getObjectparams('form_hiddenfields')['versteckt'];
$yform->getObjectparams('form_hiddenfields')['a'];
$yform->getObjectparams('form_hiddenfields')['c'];
```

### Credits

Alexander Walther, @alexplusde

## Schutz vor Spam

Von Haus aus liefert `YForm` für den Formbuilder zwei Feldtypen, um sicherzustellen, dass das Formular von einem echten Website-Besucher ausgegeben wurde und nicht von einem Spam-Bot. Es gibt jedoch noch weitere Tricks, um Bots zu erkennen und Spam zu vermeiden.

> Hinweis: Ein Captcha ersetzt nicht eine sichere Übermittlung via HTTPS, daher ist ein SSL-Zertifikat verpflichtend. Wenn die eingegebenen Daten per E-Mail versendet werden, werden diese unter Umständen trotz SSL-Zertifikat unverschlüsselt übertragen.

### via Addon

Siehe [YForm Spamschutz](https://github.com/alexplusde/yform_spam_protection/);

### Captcha 

Siehe [YForm Formbuilder Values](yform_modul_values.md#captcha)

**Nachteile**

* Nicht barrierefrei
* Zusätzlicher Aufwand für den Nutzer

### Captcha Calc

Siehe [YForm Formbuilder Values](yform_modul_values.md#captcha_calc)

**Nachteile**

* Zusätzlicher Aufwand für den Nutzer

### via Zeitstempel 

**Vorgehensweise**

1. Feld vom Typ PHP anlegen: `php|validate_timer|Spamschutz|<?php echo '<input name="validate_timer" type="hidden" value="'.microtime(true).'" />' ?>|`
2. Validierung vom Typ custom_function anlegen: `validate|customfunction|validate_timer|yform_validate_timer|5|Spambots haben keine Chance|`
3. Nachfolgende Funktion hinterlegen, die via `custom_function` aufgerufen wird.

```php
function yform_validate_timer($label,$microtime,$seconds)
    {
        if (($microtime + $seconds) > microtime(true)) {
            return true;
        } else {
            return false;
        }
    }
```

**WICHTIG: damit das funktioniert, muss `real_field_names` auf `true` stehen: [Anleitung](yform_modul_objparams.md#echte-feldnamen)**
    
> Tipp: Die Funktion kann z. B. im projects-Addon innerhalb der boot.php hinterlegt werden.
    
**Funktionsweise**

Spambots sind kleine ungeduldige Biester. Sie füllen das Formular in der Regel im Bruchteil einer Sekunde aus und haben schließlich keine Zeit zu verlieren, um den nächsten Website-Betreiber in den Wahnsinn zu treiben.

Über PHP wird ein zusätzliches, verstecktes Feld namens `validate_timer` erstellt und bei der Ausgabe des Formulars mit dem Zeitstempel ausgefüllt. Ein Nutzer wird i.d.R. einige Sekunden brauchen, um das Formular auszufüllen. 

Dieser Zeitstempel wird beim Absenden des Formulars in der Funktion `yform_validate_timer` verglichen. Wenn der vorgegebene Zeitwert unterschritten wird (in diesem Beispiel sind es `5` Sekunden), dann ist davon auszugehen, dass das Formular von einem Spambot ausgefüllt wurde, weshalb die Validierung fehlschlägt und das Absenden unterbunden wird.

> Tipp: Zum Testen kann der Wert auf einen wesentlich höheren Wert gestellt werden, z. B. 30 Sekunden. Anschließend beide Fälle testen (vor 30 Sekunden -> Fehler, nach 30 Sekunden -> Erfolg) und zuletzt wieder auf einen niedrigeren Wert stellen.

**Nachteile**

* keinen

### via hidden E-Mail-Field

**Vorgehensweise**

1. Feld vom Typ `email` anlegen, als Label `email` verwenden
2. weiteres Feld vom Typ `email` anlegen, als Label z. B. `xmail` verwenden
3. Validierung vom Typ `compare` anlegen, das Feld darf nicht `>0` sein.
4. Eingabefeld `email` via CSS verstecken.

**Funktionsweise**

Sobald Spambots das input-Feld namens `email` als Eingabe-Feld erkennen, werden sie es ausfüllen. Nur ein echter Nutzer wird das Feld nicht sehen und damit auch nicht ausfüllen. Dadurch kann man feststellen, ob ein Bot oder ein Mensch das Formular ausgefüllt hat. Für den Bot wird damit das Absenden blockiert.

**Nachteil**

* Unter Umständen wird das versteckte Feld von Browsern vorausgefüllt. Lösung: Autocomplete abschalten, indem das Eingabe-Feld via JSON das Attribut `{"autocomplete":"off"}` bekommt.
* Das korrekte E-Mail-Feld wird nicht mehr vom Browser vorausgefüllt.
* Die Lösung ist nur bedingt barrierefrei.


### Google reCaptcha

Siehe [YForm Formbuilder Values](yform_modul_values.md#recaptcha)

**Nachteile**

* Übermittlung von personenbezogenen Daten an Google

## Mitgliedsantrag

In diesem Tutorial geht es darum, ein Formular zu entwerfen, mit dem Besucher einer Website verbindlich und sicher einen Mitgliedsantrag ausfüllen können.

### Voraussetzungen und Anforderungen

*Double-Opt-In*
Damit nicht wildfremde Menschen das Formular ausfüllen, soll eine E-Mail an den Antragssteller gesendet werden, in der sich ein Link zur Bestätigung befindet. Hierzu muss ein zufälliger Validierungs-Code erstellt werden, sodass nicht Fremde die Bestätigung über einen Link vornehmen können.

*Datenschutz*
E-Mails werden noch immer oft unverschlüsselt gesendet. Deshalb ist es notwendig, sensible Daten wie z. B. IBAN, vor dem Versand der Bestätinungs-E-Mail an den Antragssteller zu maskieren. Generell ist empfohlen, dass das Formular nur verschlüsselt (https) übertragen wird.

*Einfache Handhabung*
Das Formular soll, sofern möglich, auf die Autofill-Funktion des Browsers zugreifen können. Hierzu müssen die Feldnamen richtig benannt werden.

*Schutz vor unvollständigen Eingaben*
Das Formular soll erst abgesendet werden können, wenn die Pflichtfelder ordnungsgemäß ausgefüllt wurden. Dazu werden die Formular-Eingaben clientseitig (im Browser, via HTML5) validiert - und zusätzlich serverseitig (via YForm).

Für das Tutorial werden demzufolge benötigt:

* Der Table Manager zum Erstellen der Datenbanktabelle und der Formularfelder 
* Das YForm Formbuilder-Modul, das im Frontend das Formular ausgibt
* Die E-Mail-Templates, um Bestätigungs-Mails an Antragssteller und Betreiber informiert
* Ein Modul, das die Bestätigung des Nutzers entgegen nimmt und den Antrag freischaltet

### Schritt 1: Tabelle anlegen

1. Im Menü auf `YForm > Table Manager` klicken
2. Auf das `+`-Zeichen klicken, um eine neue Tabelle hinzuzufügen. 
   * Im Tutorial wird `rex_yf_member` verwendet.

### Schritt 2: Formular-Felder anlegen

1. Im Menü auf `YForm > Table Manager` klicken
2. An der Tabelle `rex_yf_member` auf `Felder hinzufügen` klicken
3. Die gewünschten Felder hinzufügen:

| Feldtyp | Feldname | Label | Weitere Optionen |
| ------- | -------- |:-----:| ----------------:|
radio|salutation|Anrede|Herr,Frau<br>{"required":"required"}|
text|firstname|Vorname|{"required":"required"}|
text|lastname|Nachname|{"required":"required"}|
text|street|Straße|{"required":"required"}|
text|zip|PLZ|{"required":"required"}|
text|city|Ort|{"required":"required"}|
text|bankname|Kreditinstitut|
text|iban|IBAN|{"required":"required"}|
text|bic|BIC|
text|email|E-Mail-Adresse|{"required":"required","type":"email"}|
index|key|key|email|md5|
hashvalue|token|Token|iban|sha512|/Ru7Vz(%tQ5VV%vkV#VYB=KhaQYAavf/?APtYpe}59ynUTybL\|
datestamp|createdate|Angemeldet am...|mysql||1|
datestamp|updatedate|Aktualisiert am|mysql||0|

** Erläuterungen **

Warum index
Warum hashvalue
Warum token
Warum datestamp
Warum required

> Hinweis: Die meisten Browser erkennen Eingabefelder anhand der Feldnamen und können so einiger Felder vorausfüllen. Beispiel hierfür sind Konventionen wie `firstname` für Vorname, `city` oder `location` für den Ortsnamen, usw.

#### Validierungen

Alle required-Felder auch serverseitig validieren.

### Schritt 3: Formular im Frontend einfügen

Der Formular-Code sollte in etwa so aussehen:
```
objparams|form_ytemplate|bootstrap
objparams|form_showformafterupdate|0
objparams|real_field_names|true

radio|salutation|Anrede|Herr,Frau||{"required":"required"}|
text|firstname|Vorname|||{"required":"required"}|
text|lastname|Nachname|||{"required":"required"}|
text|street|Straße|||{"required":"required"}|
text|zip|PLZ|||{"required":"required"}|
text|city|Ort|||{"required":"required"}|
text|bankname|Kreditinstitut|
text|iban|IBAN|||{"required":"required"}|
text|bic|BIC|
text|email|E-Mail-Adresse|||{"required":"required","type":"email"}|
index|key|key|email||md5|
hashvalue|token|Token|iban|sha512|/Ru7Vz(%tQ5VV%vkV#VYB=KhaQYAavf/?APtYpe}59ynUTybL\|
datestamp|createdate|Angemeldet am...|mysql||1|
datestamp|updatedate|Aktualisiert am|mysql||0|

action|db|rex_yf_member|
action|tpl2email|member_confirm|email|
action|tpl2email|member_confirm||betreiber@domain.de
```

*Meldung bei erfolgreichem Versand* 
```
<p>Vielen Dank für Ihre Unterstützung!</p>
<p>Bitte beachten Sie: In Kürze erhalten Sie eine Mail mit einem Bestätigungs-Link. Erst dann, wenn Sie diesen Link anklicken, wird Ihre Mitgliedschaft verbindlich. Sie erhalten anschließend eine schriftliche Bestätigung.</p>

```

### Schritt 4: E-Mail-Templates anlegen

*E-Mail an den Antragssteller*

```
Hallo REX_YFORM_DATA[field="salutation"] REX_YFORM_DATA[field="firstname"] REX_YFORM_DATA[field="lastname"],

Sie haben auf unserer Website einen Antrag auf Mitgliedschaft ausgefüllt. Bitte bestätigten Sie Ihre Mitgliedschaft mit einem Klick auf folgenden Link:

https://www.domain.de/mitgliedschaft-aktivieren/?key=REX_YFORM_DATA[field="key"]&token=REX_YFORM_DATA[field="token"]

Sie erhalten anschließend eine schriftliche Bestätigung per Post an die von Ihnen angegebene Adresse.

Ihr Vorteil als Mitglied: Sie erhalten ermäßigten Eintritt zu unseren Veranstaltungen. Bringen Sie dazu Ihren Mitgliedsausweis mit.

Wir bedanken uns sehr für Ihre Unterstützung und freuen uns über Ihren nächsten Besuch!

Anbei eine Kopie Ihrer Daten:
REX_YFORM_DATA[field="salutation"] REX_YFORM_DATA[field="firstname"] REX_YFORM_DATA[field="lastname"]
REX_YFORM_DATA[field="street"]
REX_YFORM_DATA[field="zip"] REX_YFORM_DATA[field="city"]

Ihre E-Mail-Adresse:
REX_YFORM_DATA[field="email"]

Bankdaten:
Name der Bank: REX_YFORM_DATA[field="bankname"]
IBAN [aus Datenschutzgründen ausgeblendet]
BIC: REX_YFORM_DATA[field="bic"]

Die Zahlungsweise erfolgt per Abbuchung, der Antrag gilt gleichzeitig als Einzugsermächtigung.
```

*E-Mail an den Betreiber*

```
Hallo Team des %Name des Betreibers%,

ein neuer Antrag auf Mitgliedschaft wurde ausgefüllt:

REX_YFORM_DATA[field="salutation"]
REX_YFORM_DATA[field="firstname"]
REX_YFORM_DATA[field="lastname"]
REX_YFORM_DATA[field="street"]
REX_YFORM_DATA[field="zip"]
REX_YFORM_DATA[field="city"]
REX_YFORM_DATA[field="email"]
REX_YFORM_DATA[field="bankname"]
REX_YFORM_DATA[field="iban"]
REX_YFORM_DATA[field="bic"]

Der Status der bestätigten Aktivierung kann eingesehen werden unter:
https://www.domain.de/redaxo/ > Tabellen > Mitglieder
```
### Schritt 5: Aktivierungs-Modul einrichten

```php
    <div class="modul-wrapper">       
        <?php
$key = rex_get('key', 'string', 0);
$token = rex_get('token', 'string', 0);

if($key && $token) {
    $member = current(rex_sql::factory()->setDebug(0)->getArray('SELECT * FROM rex_yf_member WHERE key = ? AND token = ? AND status = 0 LIMIT 1', array($key, $token)));

    if(count($member)) {
        if($member['token'] == $token) {
            rex_sql::factory()->setDebug(0)->setQuery('UPDATE rex_yf_member SET token = NULL, key = NULL, status = 1, updatedate = NOW() WHERE key = ?', array($key));

        ?>
        <div class="header-message"><p>Ihre Mitgliedschaft wurde bestätigt. Vielen Dank!</p></div>
        <? 
        } else {
        ?>
        <div class="header-message warning"><p>Ihre Mitgliedschaft wurde bereits bestätigt.</p></div>
        <?
        }
    } else {
        ?>
        <div class="header-message warning"><p>Ein Antrag zur Mitgliedschaft liegt uns nicht vor.</p></div>
        <?
    }
} else {
        ?>
        <div class="header-message warning"><p>Ihre Mitgliedschaft wurde nicht beantragt oder wurde bereits aktiviert.</p></div>
        <?
}
        ?>
    </div>
```
