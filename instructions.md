# YForm REDAXO - Vollständige Dokumentation

## Quellen:

### Allgemein

* https://raw.githubusercontent.com/yakamara/yform/master/docs/01_intro.md
* https://raw.githubusercontent.com/yakamara/yform/master/docs/02_email.md
* https://raw.githubusercontent.com/yakamara/yform/master/docs/03_table_manager.md
* https://raw.githubusercontent.com/yakamara/yform/master/docs/04_yorm.md
* https://raw.githubusercontent.com/yakamara/yform/master/docs/05_rest.md
* https://raw.githubusercontent.com/yakamara/yform/master/docs/06_demos.md
* https://raw.githubusercontent.com/yakamara/yform/master/docs/07_formbuilder.md
* https://raw.githubusercontent.com/yakamara/yform/master/docs/08_tools.md

### Export/Import

Aus eigenem ChatGPT Verlauf erstellt

## 1. Einführung

### Formulare im Frontend

Das AddOn YForm dient vor allem zur Generierung von Formularen im Frontend. Formulare sind oft komplex und ziehen meist umfangreiche Nacharbeit mit sich. YForm versucht, durch flexible Verzahnung verschiedener Komponenten möglichst viele dieser Aufgaben zu übernehmen.

YForm enthält nicht nur alle gängigen Formular-Feldtypen, sondern stellt auch vielfältige Validierungen bereit, Funktionen zum Versand von E-Mails sowie Aktionen, die zum Beispiel Daten in eine Datenbank schreiben oder Weiterleitungen ausführen.

### Tabellenverwaltung im Backend

YForm kann aber nicht nur Formulare für das Frontend generieren sowie Formulareingaben per E-Mail versenden oder in eine Datenbank speichern.

Der Admin kann mit Hilfe des Table Managers auch Datenbank-Tabellen "zusammenklicken" und diese - ergänzt z. B. durch Validierungen - im Backend samt Eingabemaske zur Verfügung stellen. Diese automatisch erzeugten Daten-Verwaltungen können dann wiederum den Code für ein dazu passendes Frontend-Formular generieren.

## 2. E-Mail-Templates

### Zweck der E-Mail-Templates

Will man eine E-Mail aus einem YForm-Formular versenden, kann man mit Hilfe eines E-Mail-Templates diese E-Mail gestalten und mit Platzhaltern aus dem Formular versehen.

Über die E-Mail-Template-Verwaltung kann ein Template angelegt werden. Dabei muss zuerst ein Key erstellt werden, der die eindeutige Zuordnung zu diesem Template ermöglicht. Ebenfalls muss die Absender-E-Mail, der Absender-E-Mail-Name sowie der Betreff eingegeben werden.

### Beispiel-Formular im Formbuilder

```
text|vorname|Vorname|
text|name|Name|
text|emailadresse|E-Mail-Adresse|

validate|type|emailadresse|email|Das Feld enthält keine korrekte E-Mail-Adresse!
validate|empty|email|Das Feld enthält keine korrekte E-Mail-Adresse!

action|tpl2email|testtemplate|email
```

### Eingaben im E-Mail-Template

Als E-Mail-Template Key wird eingetragen:
```
testtemplate
```

In den E-Mail-Template Body kommt:
```
Hallo,
REX_YFORM_DATA[field="vorname"] REX_YFORM_DATA[field="name"]
```

### PHP-Code in Templates

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

### Variante 1: tpl2email in einem yForm-PHP-Modul simulieren

```php
<?php
$yform = new rex_yform();
$yform->setObjectparams('form_ytemplate', 'bootstrap');
$yform->setObjectparams('form_showformafterupdate', 0);
$yform->setObjectparams('real_field_names', true);

$yform->setValueField('text', array("name","Name"));
$yform->setValueField('text', array("emailadresse","E-Mail-Adresse"));
$yform->setValidateField('type', array('emailadresse', "email","Bitte geben Sie eine gültige Emailadresse an."));
$yform->setValueField('textarea', array("message","Nachricht"));
$yform->setObjectparams('form_action',rex_article::getCurrent()->getUrl());

$form = $yform->getForm();

if($form) {
    echo $form;
} else { 
    $yform_email_template_key = 'test';
    $debug = 0;

    $values = $yform->objparams['value_pool']['email'];
    $values['custom'] = 'Eigener Platzhalter';

    if ($yform_email_template = rex_yform_email_template::getTemplate($yform_email_template_key)) {
        if ($debug) {
            echo '<hr /><pre>'; var_dump($yform_email_template); echo '</pre><hr />';
        }
        $yform_email_template = rex_yform_email_template::replaceVars($yform_email_template, $values);
        $yform_email_template['mail_to'] = $values['emailadresse'];
        $yform_email_template['mail_to_name'] = $values['name'];

        if (!rex_yform_email_template::sendMail($yform_email_template, $yform_email_template_key)) {
            if ($debug) { echo 'E-Mail konnte nicht gesendet werden.'; }
            return false;
        } else {
            if ($debug) { echo 'E-Mail erfolgreich gesendet.'; }
            return true;
        }
    }
}
?>
```

### Variante 2: E-Mail-Versand zur Verwendung in Cronjobs, Addons, etc.

```php
<?php
$yform_email_template_key = 'test';
$debug = 0;

$values['anrede'] = 'Herr'; 
$values['name'] = 'Max Mustermann'; 
$values['emailadresse'] = 'max@mustermann.de'; 

if ($yform_email_template = rex_yform_email_template::getTemplate($yform_email_template_key)) {
    $yform_email_template = rex_yform_email_template::replaceVars($yform_email_template, $values);
    $yform_email_template['mail_to'] = $values['emailadresse'];
    $yform_email_template['mail_to_name'] = $values['name'];

    if (!rex_yform_email_template::sendMail($yform_email_template, $yform_email_template_key)) {
        if ($debug) { echo 'E-Mail konnte nicht gesendet werden.'; }
        return false;
    } else {
        if ($debug) { echo 'E-Mail erfolgreich gesendet.'; }
        return true;
    }
}
?>
```

## 3. Table Manager

### Einführung

Der Table Manager in YForm dient zum Erstellen und Bearbeiten von Datenbanktabellen sowie zum Verwalten von tabellarischen Daten innerhalb von Redaxo.

### Erste Schritte

1. Tabelle im Table Manager erstellen
2. Felder der neuen Tabelle hinzufügen
3. Datensätze in die Felder der neuen Tabelle eintragen

### Direkte Ausgabe des Formulars

```php
$dataset = rex_yform_manager_dataset::create('rex_my_yform_table');
$yform = $dataset->getForm();
$yform->setObjectparams('form_action', rex_getUrl(rex_article::getCurrentId()));
$yform->setActionField('showtext', array('',"Thank you."));
echo $dataset->executeForm($yform);
```

### Widget Ausgabe im Backend

Modul Eingabe:
```
REX_YFORM_TABLE_DATA[id=1 table="tablename"]
REX_YFORM_TABLE_DATA[id=1 table="tablename" widget=1]
REX_YFORM_TABLE_DATA[id=1 table="tablename" field=name widget=1]
REX_YFORM_TABLE_DATA[id=1 table="tablename" field=name widget=1 multiple=1]
```

Modul Ausgabe:
```
REX_YFORM_TABLE_DATA[1]
```

### Tabellen-Optionen

| Option | Erläuterungen |
|--------|---------------|
| Priorität | Legt fest, an welcher Position sich die neue Tabelle zwischen bestehenden Tabellen einreiht |
| Name | Der Name der Datenbank-Tabelle, wie sie in MySQL heißt |
| Bezeichnung | Der Name der Tabelle, wie sie im Menü aufgelistet wird |
| Beschreibung | Informationen zur Tabelle |
| aktiv | Legt fest, ob die Tabelle im Hauptmenu angezeigt wird |
| Datensätze pro Seite | Legt fest, ab wie vielen Datensätzen die Tabellen-Übersicht in Seiten unterteilt wird |
| Standardsortierung Feld | Legt fest, nach welchem Feld die Tabellen-Übersicht sortiert wird |
| Standardsortierung Richtung | Legt fest, ob auf- oder absteigend sortiert wird |
| Suche aktiv | Zeigt die Schaltfläche "Datensatz suchen" an |
| In der Navigation versteckt | Legt fest, ob die Tabelle auch im Menü angezeigt wird |
| Export der Daten erlauben | Zeigt die Schaltfläche "Datensätze exportieren" an |
| Import von Daten erlauben | Zeigt die Schaltfläche "Datensätze importieren" an |

### Feldtypen

#### be_link
Ein Redaxo-Feld, um einen Redaxo-Artikel auszuwählen.

#### be_manager_relation
Ein Auswahlfeld/Popup, um Datensätze mit denen einer fremden Tabelle zu verknüpfen.

#### be_media
Ein Redaxo-Feld, um eine einzelne oder mehrere Medienpool-Datei/en auszuwählen.

#### be_table
Eine Reihe von Eingabefeldern, um tabellarische Daten einzugeben (JSON-Format).

#### checkbox
Eine Checkbox mit vordefinierten Werten.

#### date
Eine Reihe von Auswahlfeldern für ein Datum (Tag, Monat, Jahr).

#### datestamp
Ein unsichtbares Feld für einen Zeitstempel.

#### datetime
Eine Reihe von Auswahlfeldern für Datum und Uhrzeit.

#### email
Ein einfaches Eingabefeld für E-Mail-Adressen.

#### emptyname
Ein Feld ohne Eingabemöglichkeit.

#### fieldset
Ein Fieldset gruppiert Formularfelder.

#### hashvalue
Ein Feld, das aus dem Wert eines anderen Feldes einen Hashwert erzeugt.

#### html
Gibt HTML-Code an der gewünschten Stelle aus.

#### index
Ein Feld, das einen Index/Schlüssel über mehrere Felder erzeugt.

#### integer
Ein einfaches Eingabefeld für ganze Zahlen.

#### number
Für Dezimalzahlen mit precision und scale.

#### password
Passwort-Eingabefeld.

#### php
Führt PHP-Code an der gewünschten Stelle aus.

#### prio
Ein Auswahlfeld, um Datensätze in eine bestimmte Reihenfolge zu sortieren.

#### showvalue
Zeigt einen Wert in der Ausgabe.

#### submit
Ein oder mehrere Submit-Buttons zum Absenden des Formulars.

#### text
Input-Feld zur Eingabe eines Textes.

#### textarea
Ein mehrzeiliges Eingabefeld für Text.

#### time
Eine Reihe von Auswahlfeldern für die Uhrzeit.

#### upload
Ein Upload-Feld für Dateien.

#### uuid
Erstellt eine eindeutige UUID.

### Validierung

#### compare
Vergleicht zwei Eingabe-Werte miteinander.

#### compare_value
Vergleicht einen Eingabe-Wert mit einem fest definierten Wert.

#### customfunction
Ruft eine eigene PHP-Funktion für einen Vergleich auf.

#### empty
Überprüft, ob ein Eingabe-Wert vorhanden ist.

#### in_table
Überprüft, ob ein Eingabe-Wert in der DB vorhanden ist.

#### intfromto
Überprüft ob der Eingabe-Wert zwischen zwei Zahlen liegt.

#### preg_match
Überprüft die Eingabe auf Regex-Regeln.

#### size
Überprüft ob der Eingabe-Wert eine exakte Anzahl von Zeichen hat.

#### size_range
Überprüft ob die Länge des Eingabe-Werts zwischen zwei Zahlen liegt.

#### type
Überprüft ob der Typ des Eingabe-Werts korrekt ist.

#### unique
Überprüft ob der Eingabe-Wert noch nicht in anderen Datensätzen vorhanden ist.

### Table Manager Snippets

#### Spalte ausblenden

```php
<?php
if (rex::isBackend())
{
    rex_extension::register("YFORM_DATA_LIST", function( $ep ) {  
        if ($ep->getParam("table")->getTableName()=="gewuenschte_tabelle"){
            $list = $ep->getSubject();
            $list->removeColumn("id");
        }
    });
}
```

#### Spalteninhalt vor Anzeige ändern

```php
<?php
if (rex::isBackend())
{
    rex_extension::register('YFORM_DATA_LIST', function( $ep ) {  
        if ($ep->getParam('table')->getTableName()=="gewuenschte_tabelle"){
            $list = $ep->getSubject();
            $list->setColumnFormat(
                'title',
                'custom',
                function($a){ 
                    $neuer_wert=$a['value']." ".$a['list']->getValue('xyz');
                    return $neuer_wert;
                }
            );
        }
    });
}
```

#### Bilderspalte in Tabellenansicht

```php
<?php
if (rex::isBackend() && rex_request('table_name') == 'rex_test') {
    rex_extension::register('YFORM_DATA_LIST', function( $ep ) {
        $list = $ep->getSubject();
        $list->setColumnFormat('bild', 'custom', function ($params ) {
            return '<img src="/images/rex_medialistbutton_preview/'.$params['list']->getValue('bild').'">';                
        });            
    });        
}
```

### be_manager_relation Tutorial

#### One to Many (1:n)

##### Tabelle News-Tags
- name (text)

##### Tabelle News-Beiträge
- titel (text)
- text (textarea)
- id_tag (be_manager_relation)

##### Frontend-Ausgabe

```php
<?php
$sql = rex_sql::factory();
$query = 'SELECT * FROM ' . rex::getTable('news_beitrag') . ' JOIN ' . rex::getTable('news_tag') . ' ON ' . rex::getTable('news_beitrag') . '.id_tag = ' . rex::getTable('news_tag') . '.id ';
$rows = $sql->getArray($query);
foreach($rows as $row) {
    echo '<h1>' . $row['titel'] . '</h1>';
    echo '<p>' . $row['text'] . '</p>';
    echo '<p>Tag: ' . $row['name'] . '</p>';
}
?>
```

#### Many to Many (m:n)

##### Tabelle News-Tags
- name (text)

##### Tabelle News-Beiträge
- titel (text)
- text (textarea)

##### Tabelle News-Tags-zu-Beitrag
- id_tag (be_manager_relation)
- id_beitrag (be_manager_relation)

##### Frontend-Ausgabe

```php
<?php
$sql = rex_sql::factory()
$query = 'SELECT ' . rex::getTable('news_beitrag') . '.*, GROUP_CONCAT(' . rex::getTable('news_tag') . '.name SEPARATOR ",") AS tags ';
$query.= 'FROM ' . rex::getTable('news_beitrag') . ' ';
$query.= 'JOIN ' . rex::getTable('news_tag_beitrag') . ' ON ' . rex::getTable('news_beitrag') . '.id = ' . rex::getTable('news_tag_beitrag') . '.id_beitrag ';
$query.= 'JOIN ' . rex::getTable('news_tag') . ' ON ' . rex::getTable('news_tag') . '.id = ' . rex::getTable('news_tag_beitrag') . '.id_tag ';
$query.= 'GROUP BY ' . rex::getTable('news_beitrag') . '.id';
$rows = $sql->getArray($query);
foreach($rows as $row) {
    echo '<h1>' . $row['titel'] . '</h1>';
    echo '<p>' . $row['text'] . '</p>';
    echo '<p>Tags: ' . $row['tags'] . '</p>';
}
?>
```

### CSRF Schutz der Table-Manager-Links

```php
// Key auslesen per getCSRFKey 
$_csrf_key = $table->getCSRFKey();
// Token erstellen
$_csrf_params = rex_csrf_token::factory($_csrf_key)->getUrlParams();
```

Rückwärtskompatible Lösung:
```php
$_csrf_key = 'table_field-'.$table->getTableName();
```

## 4. YOrm (Object-relational mapping)

YOrm erleichtert den Umgang mit in YForm Table Manager angemeldeten Tabellen und deren Daten.

### YOrm ohne eigene Model Class verwenden

```php
<?php
$items = rex_yform_manager_table::get('rex_my_table')->query()->find();
dump($items);
```

### YOrm mit eigener Model Class verwenden

#### Klasse erstellen

```php
<?php
class MyTable extends \rex_yform_manager_dataset
{
}
```

#### Klasse registrieren

```php
<?php
rex_yform_manager_dataset::setModelClass('rex_my_table', MyTable::class);
```

### Praxis-Beispiele

```php
<?php
$items = MyTable::query()
            ->alias('t')
            ->joinRelation('relation_id', 'r')
            ->select('r.name', 'relation_name')
            ->where('t.status', '1')
            ->orderBy('t.name')
            ->find();
```

```php
<?php
$item = MyTable::create()
              ->setValue('user_id', 5)
              ->setValue('article_id', 6)
              ->save();
```

```php
<?php
MyTable::query()
                ->where('user_id', 5)
                ->where('article_id', 6)
                ->find()
                ->delete();
```

### Datensatz abfragen

```php
<?php
$post = rex_yform_manager_dataset::get($id, 'rex_blog_post');
?>
<article>
    <h1><?= $post->title ?></h1>
    <p><?= $post->text ?></p>
</article>
```

### Datensatz mit YForm-Formular

```php
<?php
$dataset = rex_yform_manager_dataset::get(2,'tabelle');
$yform = $dataset->getForm();
$yform->setObjectparams('form_method','get');
$yform->setObjectparams('form_action',rex_getUrl(REX_ARTICLE_ID));
$yform->setObjectparams('getdata',true);
$yform->setActionField('showtext',array('','Gespeichert'));
echo $dataset->executeForm($yform);
?>
```

### Datensatz ändern

```php
<?php
$post = rex_yform_manager_dataset::get($id, 'rex_blog_post');
$post->title = 'REDAXO-Tag in Wackershofen (am Grundbach)';
$post->text = '...';

if ($post->save()) {
    echo 'Gespeichert!';
} else {
    echo implode('<br>', $post->getMessages());
}
```

### Datensatz erstellen

```php
<?php
$post = rex_yform_manager_dataset::create('rex_blog_post');
$post->title = 'REDAXO-Tag in Wackershofen (am Grundbach)';
$post->text = '...';

if ($post->save()) {
    echo 'Gespeichert!';
} else {
    echo implode('<br>', $post->getMessages());
}
```

### Eigene Modelklassen

```php
<?php
// boot.php
rex_yform_manager_dataset::setModelClass(
    'rex_blog_author',
    rex_blog_author::class
);
```

```php
<?php
// lib/author.php
class rex_blog_author extends rex_yform_manager_dataset
{
    public function getFullName(): string
    {
        return $this->first_name.' '.$this->last_name;
    }
}
```

### Query-Klasse

```php
<?php
$query = rex_blog_post::query();

$query
->where('status', 1)
->where('created', $date, '>')
->orderBy('created', 'desc')
;

$posts = $query->find();
// $post = $query->findOne();
```

### Collection-Klasse

```php
<?php
$posts->isEmpty();
$posts->getIds();
$posts->toKeyIndex();
$posts->toKeyValue('title', 'text');
$posts->getValues('title');
$posts->groupBy('author_id');
$posts->setValue('author_id', $authorId);
$posts->save();
$posts->delete();
```

### Relationen

```php
<?php
foreach ($posts as $post) {
    $author = $post->getRelatedDataset('author_id');
    echo 'Autor: '.$author->getFullName();
    echo $post->title;
}

$posts = $author->getRelatedCollection('posts');
```

### Paginierung

```php
<?php
$pager = new rex_pager(20);

$query = rex_blog_post::query();
//$query->...

$posts = $query->paginate($pager);

foreach ($posts as $post) {
    // ...
}

$pager->getRowCount();
$pager->getCurrentPage();
$pager->getLastPage();
$pager->getPageCount();
```

### Formulare

```php
<?php
$post = rex_blog_post::get($id);

$yform = $post->getForm();

// $yform->setHiddenField();
// $yform->setObjparams();

echo $post->executeForm($yform)
```

### Methoden-Referenz

#### collection-Methoden
- delete
- executeForm
- filter
- first
- getForm
- getIds
- getTable
- getTableName
- getUniqueValue
- getValues
- groupBy
- isEmpty
- isValid
- isValueUnique
- last
- map
- populateRelation
- save
- setData
- setValue
- shuffle
- slice
- sort
- split
- toKeyIndex
- toKeyValue

#### query-Methoden
- alias
- getTableAlias
- count
- exists
- find
- findId
- findIds
- findOne
- get
- getAll
- groupBy
- groupByRaw
- resetGroupBy
- joinRaw
- joinRelation
- joinType
- joinTypeRelation
- leftJoin
- leftJoinRelation
- resetJoins
- limit
- resetLimit
- orderBy
- orderByRaw
- resetOrderBy
- paginate
- query
- queryOne
- save
- resetSelect
- select
- selectRaw
- getTable
- getTableName
- resetWhere
- setWhereOperator
- where
- whereNot
- whereNull
- whereNotNull
- whereBetween
- whereNotBetween
- whereNested
- whereRaw
- whereListContains
- resetHaving
- setHavingOperator
- having
- havingNot
- havingNull
- havingNotNull
- havingBetween
- havingNotBetween
- havingRaw
- havingListContains

#### dataset-Methoden
- create
- get
- getAll
- getData
- getForm
- getId
- getMessages
- getRaw
- getRelatedCollection
- getRelatedDataset
- getTable
- getTableName
- getValue
- hasValue
- isValid
- loadData

### Debugging

#### Variante 1

```php
<?php
$query = MyTable::query();
$query
    ->alias('t')
    ->joinRelation('relation_id', 'r')
    ->select('r.name', 'relation_name')
    ->where('t.status', '1')
    ->orderBy('t.name')
$items = rex_sql::factory()->setDebug()->getArray($query->getQuery(), $query->getParams());
$items = $query->find();
```

#### Variante 2

Datei `/redaxo/src/addons/yform/plugins/manager/lib/yform/manager/dataset.php` und die Variable `private static $debug = false;` auf `true` setzen

### Tricks

#### Aus dem Dataset ungewollte Felder herausfiltern

```php
<?php
class rex_data_mydata extends rex_yform_manager_dataset
{
    public function getFields(array $filter = [])
    {
        $fields = $this->getTable()->getFields($filter);

        if (rex::isBackend()) {
            return $fields;
        }

        foreach ($fields as $i => $field) {
            if ('interne_links' == $field->getName()) {
                unset($fields[$i]);
            }
            if ('user' == $field->getName()) {
                unset($fields[$i]);
            }
        }

        return $fields;
    }
}
```

### AND und OR innerhalb eines Queries

#### Möglichkeit 1: setWhereOperator
```php
$query
  ->setWhereOperator('OR')
  ->where('foo', 1)
  ->where('bar', 2)
;
```

#### Möglichkeit 2: whereRaw
```php
$query->whereRaw('(foo = :foo OR bar = :bar)', ['foo' => 1, 'bar' => 2]);
```

#### Möglichkeit 3: whereNested Array-Notation
```php
$query->whereNested(['foo' => 1, 'bar' => 2], 'OR');
```

#### Möglichkeit 4: whereNested Callback-Notation
```php
$query->whereNested(function (rex_yform_manager_query $query) {
  $query
    ->where('foo', 1)
    ->where('bar', 2)
  ;
}, 'OR');
```

## 5. REST-API

### Erste Schritte

Mit dem REST-Plugin lässt sich eine Schnittstelle aktivieren, mit der man YForm Tabellen von außen abrufen, verändern und löschen kann. Die Schnittstelle orientiert sich an https://jsonapi.org/format/.

### Konfiguration / Endpoints

Die Standardroute der REST-API ist auf "/rest" gesetzt. Die Konfiguration wird über PHP festgelegt und sollte im project-AddOn in der boot.php abgelegt werden.

```php
<?php
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

\rex_yform_rest::addRoute($route);
```

### GET - Datensätze abrufen

RequestType: `GET`
URL: `https://url.localhost/rest/v1/users/[id]`

Header:
```
Content-Type: application/x-www-form-urlencoded
Authorization: Bearer [token]
```

Response:
```json
{
    "id": "[id]",
    "type": "rex_ycom_user",
     "attributes": {
            "login": "jannie",
            "email": "jan.kristinus@yakamara.de"
        },
        "relationships": {
            "ycom_groups": {
                "data": [
                    { "type": "tags", "id": "2" },
                    { "type": "tags", "id": "3" }
                ]
            }
        },
    "links": {
        "self": "https:\/\/url.localhost\/rest\/v1\/users\/[id]"
    }
}
```

#### Filter
Über URL Parameter können die internen Feldsuchen verwendet werden: `filter[feldname]=suchwert`

#### Includes
Über `include=title,user.login,user.email` kann man entscheiden welche Werte man empfangen möchte.

#### Weitere Parameter
- `per_page=x`: Anzahl der Treffer pro Seite
- `page=x`: Direkt auf Ergebnisseiten springen
- `order[Feldname]=asc` oder `desc`: Sortierung

### POST - Datensatz anlegen

RequestType: `POST`
URL: `https://url.localhost/rest/v1/users/`

Body:
```json
{
    "data": {
        "type": "rex_ycom_user",
        "attributes": {
            "login": "jannie",
            "email": "jan.kristinus@yakamara.de"
        },
        "relationships": {
            "ycom_groups": {
                "data": [
                    { "type": "tags", "id": "2" },
                    { "type": "tags", "id": "3" }
                ]
            }
        }
    }
}
```

### DELETE - Löschen

#### Mit Filtern
RequestType: `DELETE`
URL: `https://url.localhost/rest/v1/users/?filter[login]=jannie`

Response:
```json
{
    "all": 1,
    "deleted": 1,
    "failed": 0,
    "dataset": [{
        "id": "[id]"
    }]
}
```

#### Mit ID
RequestType: `DELETE`
URL: `https://url.localhost/rest/v1/users/[id]`

### Authentifizierung

#### Standardauthentifizierung
Wenn im Model `'\rex_yform_rest_auth_token::checkToken()'` angegeben wurde, werden Token aus der YForm:Rest:Tokenverwaltung überprüft.

### Header

Allgemein:
```php
\rex_yform_rest::setHeader('Access-Control-Allow-Origin', '*');
```

Route-spezifisch:
```php
$route->setHeader('Access-Control-Allow-Origin', 'redaxo.org');
```

## 6. Demos (Tutorials)

### E-Mail mit Dateianhang versenden

#### PHP-Schreibweise
```php
<?php
$yform->setValueField('upload', array('upload','Dateianhang','100,10000','.pdf,.odt,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.zip'));
$yform->setValueField('php', array('php_attach','Datei anhängen','<?php if (isset($this->params[\'value_pool\'][\'files\'])) { $this->params[\'value_pool\'][\'email_attachments\'] = $this->params[\'value_pool\'][\'files\']; } ?>'));
```

#### Pipe-Schreibweise
```
upload|upload|Dateianhang|100,10000|.pdf,.odt,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.zip|

php|php_attach|Datei anhängen|<?php if (isset($this->params['value_pool']['files'])) { $this->params['value_pool']['email_attachments'] = $this->params['value_pool']['files']; } ?>
```

### Datei(en) aus dem Medienpool als Anhang

```php
<?php
$yform->setValueField('php', array('php_attach', 'Datei anhängen', '<?php $this->params[\'value_pool\'][\'email_attachments\'][] = [\'agb.pdf\', rex_path::media(\'agb.pdf\')]; ?>'));
```

### Glossar / FAQ

#### Modul-Eingabe
```html
<div class="alert alert-info">
  Dieses Modul gibt das Glossar aus. Keine weiteren Einstellungsmöglichkeiten. 
</div>
```

#### Modul-Ausgabe
```php
<?php
$db_table = "rex_glossar";
$sql = rex_sql::factory();
$sql->setDebug(false);
$query = "SELECT * FROM $db_table  ORDER BY Begriff ";
$rows = $sql->getArray($query);
$counter = $bcounter = 1;
if ($sql->getRows() > 0) {
foreach($rows as $row)
{
 $id = $row["id"];
 $begriff = $row["Begriff"];
 $char = strtoupper(mb_substr($begriff,0,1));
 $beschreibung = $row["beschreibung"];
 $counter++;
 if ($char != $dummy) { 
    $bcounter++;   
    $buchstabe ='<h2 id="buchstabe'.$char.'">'.$char. '</h2>'; 
    $index .= '<a type="button" class="btn btn-default" href="#buchstabe'.$char.'">'.$char. '</a>';
 }  else {
    $buchstabe = "";
}
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
$dummy = $char;
 } 
echo $index;
echo $out;
}
?>
```

### Versteckte Werte übergeben (Hidden-Fields)

#### Serverseitige Lösung

PHP-Schreibweise:
```php
<?php
$yform->setHiddenField("summe", 150);
// oder mit Variable
$yform->setHiddenField("summe", $bestellung_summe);
```

Formbuilder Pipe-Schreibweise:
```
hidden|summe|150|
```

#### Wert aus GET-Parameter
```php
<?php
// www.domain.de/meinformular/?q=Foo
$yform->setValueField('hidden', array("suche", "q", REQUEST));
```

#### Clientseitige Lösung

PHP-Schreibweise:
```php
<?php
$yform->setValueField('text', array('anzahl','Anzahl','0','0','{"type":"hidden"}'));
```

Pipe-Schreibweise:
```
text|anzahl|Anzahl|0|0|{"type":"hidden"}|
```

### Schutz vor Spam

#### Via Zeitstempel

1. Feld vom Typ PHP anlegen:
```
php|validate_timer|Spamschutz|<?php echo '<input name="validate_timer" type="hidden" value="'.microtime(true).'" />' ?>|
```

2. Validierung vom Typ custom_function anlegen:
```
validate|customfunction|validate_timer|yform_validate_timer|5|Spambots haben keine Chance|
```

3. Funktion hinterlegen:
```php
<?php
function yform_validate_timer($label,$microtime,$seconds)
{
    if (($microtime + $seconds) > microtime(true)) {
        return true;
    } else {
        return false;
    }
}
```

#### Via hidden E-Mail-Field

1. Feld vom Typ `email` anlegen, als Label `email` verwenden
2. Weiteres Feld vom Typ `email` anlegen, als Label z.B. `xmail` verwenden
3. Validierung vom Typ `compare` anlegen, das Feld darf nicht `>0` sein
4. Eingabefeld `email` via CSS verstecken

### Eigene Templates verwenden

In die boot.php:
```php
<?php
$path = rex_addon::get('project')->getPath('ytemplates');
rex_yform::addTemplatePath($path);
?>
```

In der PHP Schreibweise:
```php
<?php
$yform->setObjectparams('form_ytemplate', 'meinetemplates,bootstrap', true);
?>
```

### Mitgliedsantrag

#### Tabelle anlegen: rex_yf_member

Felder:
- text|firstname|Vorname|{"required":"required"}
- text|lastname|Nachname|{"required":"required"}
- text|street|Straße|{"required":"required"}
- text|zip|PLZ|{"required":"required"}
- text|city|Ort|{"required":"required"}
- text|bankname|Kreditinstitut
- text|iban|IBAN|{"required":"required"}
- text|bic|BIC
- text|email|E-Mail-Adresse|{"required":"required","type":"email"}
- index|key|key|email|md5
- hashvalue|token|Token|iban|sha512|/Ru7Vz(%tQ5VV%vkV#VYB=KhaQYAavf/?APtYpe}59ynUTybL\
- datestamp|createdate|Angemeldet am|mysql||nur wenn leer
- datestamp|updatedate|Aktualisiert am|mysql||immer

#### Formular-Code
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

#### Aktivierungs-Modul

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

## 7. Formbuilder

### Installation
Innerhalb von YForm gibt es im Menüpunkt `Übersicht` unter `Setup` den Button `Modul "YForm Formbuilder installieren"`.

### Syntax

Normalschema: Feldtyp|Name|Optionen (getrennt durch Pipe |)

Beispiel:
```
text|name|Nachname
validate|empty|name|Bitte einen Nachnamen eingeben
choice|anrede|Anrede|Anrede=,Frau=w,Herr=m
action|db|adressen|
```

Zusätzliche Attribute:
```
text|phone|Telefon|#attributes:{"class":"phone-class"}
choice|is_approved|Neu|ja,nein|1|1|#group_attributes:{"class": "custom-control custom-switch"}
```

### PHP-Schreibweise

```php
<?php
$yform = new rex_yform();
$yform->setValueField('text', array("name","Nachname"));
$yform->setValidateField('empty', array("name","Bitte einen Nachnamen eingeben"));
$yform->setValueField('choice', array("anrede","Anrede","Anrede=,Frau=w,Herr=m"));
$yform->setActionField('db', array('adressen'));
echo $yform->getForm();
```

### Vordefinierte Aktionen

- Nur in Datenbank speichern
- Nur E-Mail versenden
- E-Mail versenden und in Datenbank speichern
- Meldung bei erfolgreichem Versand

## Objparams

### Allgemeine Objparams

#### form_show
```
objparams|form_show|0
```

#### form_name
```
objparams|form_name|formular
```

#### form_class
```
objparams|form_class|contact_form
```

#### form_wrap_id
```
objparams|form_wrap_id|contact_form
```

#### form_wrap_class
```
objparams|form_wrap_class|contact_form
```

#### form_label_type
```
objparams|form_label_type|html
```

#### csrf_protection
```
objparams|csrf_protection|0
```

### Formular-Optik

#### form_ytemplate
```
objparams|form_ytemplate|classic
```

#### submit_btn_label
```
objparams|submit_btn_label|Formular senden
```

#### submit_btn_show
```
objparams|submit_btn_show|0
```

#### error_class
```
objparams|error_class|my_form_error
```

#### real_field_names
```
objparams|real_field_names|1
```

### Formularversand

#### form_method
```php
$yform->setObjectparams('form_method','get');
```

#### form_action
```
objparams|form_action|zielseite.html
```

#### form_action_query_params
```
objparams|form_action_query_params|key1,key2,key3
```

#### form_anchor
```
objparams|form_anchor|my_form
```

#### form_showformafterupdate
```
objparams|form_showformafterupdate|1
```

#### debug
```
objparams|debug|1
```

#### hide_top_warning_messages
```
objparams|hide_top_warning_messages|1
```

#### getdata
```
objparams|getdata|1
objparams|main_where|id=1
objparams|main_table|rex_table
```

#### form_exit
```
objparams|form_exit|1
```

## Values (Feldtypen)

### be_link
```php
$yform->setValueField('be_link', array("link","Link zu Artikel"));
```

### be_manager_relation
```php
$yform->setValueField('be_manager_relation', array("manager_relation","Beispiel","rex_yf_messages","","0","0","","","","rex_yf_employees"));
```

### be_media
```php
$yform->setValueField('be_media', array("image","Bild","1","0","general","jpg,gif,png,jpeg"));
```

### be_table
```php
$yform->setValueField('be_table', array("table","Tabelle","Menge,Preis,Gewicht"));
```

### checkbox
```php
$yform->setValueField('checkbox', array("checkbox","Checkbox","1"));
```

### choice
```php
// Select
$yform->setValueField('choice',["selectfield","Verkehrsmittel","Auto,Bus,Fahrrad",0,0]);

// Gruppiertes Checkboxfeld
$yform->setValueField('choice',["mycheckboxfield","Speisen",'{"Vorspeisen": {"Salat":"insalata","Suppe":"piatto"},"Dessert":{"Eis":"ghiaccio"}}',1,1]);
```

### date
```php
$yform->setValueField('date', array("date","Datum","2016","+5","DD/MM/YYYY","1","","select"));
```

### datestamp
```php
$yform->setValueField('datestamp', array("createdate","Zeitstempel","mysql","0","0"));
```

### datetime
```php
$yform->setValueField('datetime', array("datetime","Datetime","2016","+5","00,15,30,45","DD/MM/YYYY HH:ii","0","","select"));
```

### email
```php
$yform->setValueField('email', array("email","E-Mail-Adresse"));
```

### emptyname
```php
$yform->setValueField('emptyname', array("emptyname","Emptyname"));
```

### fieldset
```php
$yform->setValueField('fieldset', array("fieldset","Fieldset"));
```

### generate_key
```
generate_key|name|[no_db]
```

### hashvalue
```php
$yform->setValueField('hashvalue', array("hashvalue","Hashvalue"));
```

### hidden
```php
$yform->setValueField('hidden', array("name", "Max Muster"));
```

### html
```php
$yform->setValueField('html', array("html","HTML","<p>Hallo Welt!</p>"));
```

### index
```php
$yform->setValueField('index', array("index","Index"));
```

### integer
```php
$yform->setValueField('integer', array("int","Integer"));
```

### ip
```php
$yform->setValueField('ip', array("ip"));
```

### number
```
number|zahl|Zahl|6|2|5||cm|Hinweis Number
```

### password
```php
$yform->setValueField('password', array("name","label", "default_value"));
```

### php
```php
$yform->setValueField('php', array("php","PHP","<? echo 'hallo welt'; ?>"));
```

### prio
```php
$yform->setValueField('prio', array("prio","Reihenfolge"));
```

### resetbutton
```php
$yform->setValueField('resetbutton', array("reset","reset","Reset"));
```

### showvalue
```
showvalue|name|label|defaultwert|notice
```

### submit
```php
$yform->setValueField('submit', array("submit","Submit"));
```

### text
```php
$yform->setValueField('text', array("text","Text"));
```

### textarea
```php
$yform->setValueField('textarea', array("textarea","Textarea"));
```

### time
```php
$yform->setValueField('time', array("time","Zeit","","00,15,30,45","HH:ii","","select"));
```

### upload
```php
$yform->setValueField('upload', array("upload","Upload","config" => $json_config));
```

### uuid
```
uuid|name|
```

## Validierungen

### compare
```php
$yform->setValidateField('compare', array("wert1","wert2","!=", "Die Felder haben unterschiedliche Werte"));
```

### compare_value
```php
$yform->setValidateField('compare_value', array("wert1",2,"<", "Der Wert ist kleiner als 2!"));
```

### customfunction
```
validate|customfunction|label|[!]function/class::method|weitere_parameter|warning_message
```

### empty
```php
$yform->setValidateField('empty', array("name","Bitte geben Sie einen Namen an!"));
```

### in_table
```
validate|existintable|label,label2|tablename|feldname,feldname2|warning_message
```

### intfromto
```php
$yform->setValidateField('intfromto', array("wert","2", "4", "Der Wert ist kleiner als 2 und größer als 4!"));
```

### in_names
```php
$yform->setValidateField('in_names', array("vorname, name, tel", "1", "2", "Fehlermeldung"));
```

### preg_match
```php
$yform->setValidateField('preg_match', array("eingabe","/[a-z]+/", "Es dürfen nur klein geschriebene Buchstaben eingegeben werden!"));
```

### size
```php
$yform->setValidateField('size', array("plz","5", "Die Eingabe hat nicht die korrekte Zeichenlänge!"));
```

### size_range
```php
$yform->setValidateField('size_range', array("summe", "3", "10", "Die Eingabe hat nicht die korrekte Zeichenlänge!"));
```

### type
```php
$yform->setValidateField('type', array("wert", "numeric", "Die Eingabe ist keine Nummer!"));
```

### unique
```php
$yform->setValidateField('unique', array("email", "Ein User mit dieser E-Mail-Adresse existiert schon!","rex_user"));
```

## Actions

### callback
```
action|callback|mycallback / myclass::mycallback
```

### copy_value
```php
$yform->setActionField('copy_value', array("name","user"));
```

### create_table
```php
$yform->setActionField('create_table', array("rex_order"));
```

### db
```php
$yform->setActionField('db', array("rex_warenkorb", "main_where"));
```

### db_query
```php
$yform->setActionField('db_query', array("insert into rex_ycom_user set name = ?, email = ?", "name,email"));
```

### email
```php
$yform->setActionField('email', array("from@mustermann", "to@mustermann.de", "Test", "Hallo ###name###"));
```

### html
```php
$yform->setActionField('html', array("<b>fett</b>"));
```

### readtable
```php
$yform->setActionField('readtable', array("shop_user", "fname", "name"));
```

### redirect
```php
$yform->setActionField('redirect', array("32"));
```

### showtext
```php
$yform->setActionField('showtext', array("Hallo das ist Redaxo", "<p>", "</p>", "0"));
```

### tpl2email
```php
$yform->setActionField('tpl2email', array("emailtemplate", "email"));
```

## 8. Tools

### Selects erweitern

CSS-Klasse `selectpicker`:
```json
{"class": "form-control selectpicker", "placeholder": "My Placeholder"}
```

Mit Suchfeld:
```json
{"class": "form-control selectpicker", "data-live-search": "true", "placeholder": "My Placeholder"}
```

### inputmask

Datumformat erzwingen:
```json
{"data-inputmask-alias":"datetime", "data-yform-tools-inputmask":"", "data-inputmask-inputformat":"yyyy-mm-dd"}
```

### daterangepicker

Datepicker:
```json
{"data-yform-tools-datepicker":"YYYY-MM-DD"}
```

Datetimepicker:
```json
{"data-yform-tools-datetimepicker":"YYYY-MM-DD HH:ii:ss"}
```

### Kombinationen

Datepicker und Inputmask:
```json
{"data-yform-tools-datepicker":"YYYY-MM-DD", "data-inputmask-alias":"datetime", "data-yform-tools-inputmask":"", "data-inputmask-inputformat":"yyyy-mm-dd"}
```

Datetimepicker und Inputmask:
```json
{"data-yform-tools-datetimepicker":"YYYY-MM-DD HH:ii:ss", "data-inputmask-alias":"datetime", "data-yform-tools-inputmask":"", "data-inputmask-inputformat":"yyyy-mm-dd hh:mm:mm"}
```

## 📘 YForm Anleitung: Erstellung und Verwendung von Exportsets

Diese Anleitung beschreibt den standardisierten Aufbau und Einsatz von YForm-Exportsets im JSON-Format – insbesondere für den Import und Export von Tabellen, Feldern und Relationen innerhalb des REDAXO CMS.

---

### 📦 Was ist ein YForm-Exportset?

Ein YForm-Exportset ist eine JSON-Datei, die eine oder mehrere Tabellen mit ihren zugehörigen Feldern und Konfigurationen beschreibt.  
Es dient dem schnellen Aufbau oder der Wiederverwendung komplexer Datenstrukturen.

---

### 📂 Aufbau eines Exportsets

Das Exportset ist ein JSON-Objekt mit folgendem Aufbau:

```json
{
  "rex_meinetabelle": {
    "fields": [ ... ],
    "table": { ... }
  },
  "rex_weitere_tabelle": {
    ...
  }
}
```

---

### 🧱 Struktur eines Eintrags

#### 1. `"table"`: Definition der Tabelle

```json
"table": {
  "status": 1,
  "name": "rex_meinetabelle",
  "table_name": "rex_meinetabelle",
  "list_amount": 50,
  "search": 1,
  "hidden": 0
}
```

**Feldbeschreibung:**

| Feld         | Bedeutung                                                  |
|--------------|------------------------------------------------------------|
| `status`     | 1 = aktiv, 0 = inaktiv                                     |
| `name`       | Anzeigename (kann identisch zu `table_name` sein)         |
| `table_name` | Technischer Tabellenname (z. B. `rex_meinetabelle`)        |
| `list_amount`| Anzahl Einträge pro Seite im Backend                       |
| `search`     | 1 = Suchfunktion aktiv                                     |
| `hidden`     | 1 = Tabelle im Backend ausblenden                          |

---

#### 2. `"fields"`: Liste der Felder der Tabelle

Jedes Feld ist ein Objekt mit folgender Struktur:

```json
{
  "type_id": "value",
  "type_name": "text",
  "db_type": "varchar(255)",
  "name": "mein_feld",
  "label": "Mein Feld",
  ...
}
```

**Wichtige Standard-Felder:**

| Feld         | Bedeutung                                        |
|--------------|--------------------------------------------------|
| `type_id`    | Immer `"value"`                                  |
| `type_name`  | YForm-Feldtyp (z. B. `text`, `number`, `choice`) |
| `db_type`    | SQL-Datentyp (z. B. `varchar(255)`, `int(11)`)   |
| `name`       | Technischer Feldname                             |
| `label`      | Feldbezeichnung im Backend                       |
| `search`     | 1 = durchsuchbar                                 |
| `list_hidden`| 1 = nicht in Listenansicht anzeigen              |
| `validate`   | Array mit Validierungen (z. B. `["empty"]`)      |
| `placeholder`| Platzhalter-Text im Formularfeld                 |

---

### 🔗 Relationen in YForm

#### Typen von Relationen (nur `type_name: "be_manager_relation"`):

| Relationstyp (`type`) | Bedeutung                        |
|------------------------|----------------------------------|
| `"0"`                  | Einfache 1:1-Relation             |
| `"1"`                  | Multiple-Relation (Mehrfachauswahl) |
| `"5"`                  | Inline-Relation (eingebettete Datenbearbeitung) |

#### Zusätzliche Felder bei Relationen:

| Feld     | Bedeutung                                |
|----------|------------------------------------------|
| `table`  | Ziel-Tabelle der Relation                |
| `type`   | Relationstyp (`0`, `1`, `5`)             |
| `field`  | Feld in der Ziel-Tabelle (i. d. R. `id`) |

**Beispiel Inline-Relation (type 5):**

```json
{
  "type_id": "value",
  "type_name": "be_manager_relation",
  "db_type": "int(11)",
  "name": "meine_inline_relation",
  "label": "Inline-Daten",
  "table": "rex_zieltabelle",
  "type": "5",
  "field": "id"
}
```

---

### 🛠 Erstellung eigener Exportsets

1. Definiere Tabellenstruktur in einem JSON-Editor.
2. Für jede Tabelle:
    - Erstelle `table`-Metadaten.
    - Liste alle `fields` als Array.
3. Verwende eindeutige Feldnamen.
4. Nutze `validate`, `placeholder`, `search`, `list_hidden` gezielt.
5. Für Relationen:
    - Einfache Relation → `type: "0"`
    - Mehrfachrelation → `type: "1"`
    - Inline-Relation → `type: "5"` + `"field": "id"`

---

### 📥 Import in REDAXO

1. Öffne `YForm → Tabellen → Import/Export → Importieren`.
2. Lade die JSON-Datei hoch.
3. Tabellen und Felder werden automatisch erzeugt.

---

### 📤 Export bestehender Tabellen

1. Öffne `YForm → Tabellen`.
2. Markiere gewünschte Tabellen.
3. Klicke auf **Exportieren**.
4. Lade das generierte JSON herunter.

---

### ✅ Best Practices

- Nutze konsistente Namenskonventionen (`rex_` Prefix).
- Nutze Relationen statt Duplikate.
- Verwende `list_hidden` für technische Felder (z. B. `id`, `updatedate`).
- Dokumentiere komplexe Strukturen mit Kommentaren (außerhalb der JSON-Datei).

---

### 📎 Formatvalidierung

Achte darauf, dass deine Datei:

- Ein valides JSON ist (verwende JSON-Validatoren)
- Die Struktur exakt einhält (`table`, `fields`, `type_name`, ...)

---

**Letzte Überprüfung:** Automatisch generiert  
**Gültig für:** REDAXO 5.x mit YForm


