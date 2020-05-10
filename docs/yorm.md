# Yorm ORM

## Mini-ORM für YForm 
(ORM = Object-relational mapping = Objektrelationale Abbildung)

Yorm erlecihtert den Umgang mit in YForm Table Manager angemeldeten Tabellen und deren Daten. So ist es möglich mittels eigener Modelclasses die Daten zu verarbeiten und aufbereitet auszugeben. Werden im Table Manager neue Felder hinzugefügt oder entfernt, passen sich über Yorm ausgegegebene Formulare sofort darauf an. Die übliche PIPE oder PHP-Programmierung entfällt. Formulare müssen meist nur durch wenige Parameter ergänzt werden um sofort zu funktionieren.  

> - [YOrm ohne eigene Model Class verwenden](#ohne-model-class)
> - [YOrm mit eigener Model Class verwenden](#eigene-model-class)
>   - [Klasse erstellen](#klasse-erstellen)
>   - [Klasse registrieren](#klasse-registrieren)
> - [Praxis-Beispiele](#praxis-beispiele)
>   - [Datensatz abfragen](#datensatz-abfragen)
>   - [Datensatz ändern](#datensatz-ändern)
>   - [Datensatz erstellen](#datensatz-erstellen)
>   - [Query-Klasse](#query-klasse)
>   - [Collection-Klasse](#collection-klasse)
>   - [Relationen](#relationen)
>   - [Paginierung](#paginierung)
>   - [Formulare](#formulare)
> - [Methoden-Referenz](#methoden-referenz)
>   - [Collection-Methoden](#collection-methoden)
>   - [Query-Methoden](#query-methoden)
>   - [Dataset-Methoden](#dataset-methoden)
> - [Debugging](#debugging)
>   - [Variante 1](#debugging-variante-1)
>   - [Variante 2](#debugging-variante-2)
> - [Tricks](#tricks)
>   - [Dataset filtern](#dataset-filter)



<a name="ohne-model-class"></a>
## YOrm ohne eigene Model Class verwenden

Hole alle Daten der Tabelle `rex_my_table` und zeige das Objekt. 

```php
$items = rex_yform_manager_table::get('rex_my_table')->query()->find();
dump($items);
```

<a name="eigene-model-class"></a>
## YOrm mit eigener Model Class verwenden

> Hinweis: Eine eigene Model Class ist nicht zwingend erforderlich, vereinfacht das Ansprechen der Tabelle mittels der OO-Notation.

Es stehen folgende Klassen zur Verfügung:

* `rex_yform_manager_dataset`
* `rex_yform_manager_collection` 
* `rex_yform_manager_query`

<a name="klasse-erstellen"></a>
### Klasse erstellen

Zunächst wird eine Klasse erstellt und in das `project` AddOn im Ordner `lib` abgelegt

```php
<?php
class MyTable extends \rex_yform_manager_dataset
{
}
```

<a name="klasse-registrieren"></a>
### Klasse registrieren

Jetzt muss die erstellte Klasse noch registiert werden. Dazu öffnet man die Datei `boot.php` des `project` AddOns und fügt nachfolgenden Code ein. Wird das theme-Addon verwendet, den Code in die Datei `functions.php` einfügen.

```php
rex_yform_manager_dataset::setModelClass('rex_my_table', MyTable::class);
```

Nun kann man alle Daten wie folgt holen:

```php
$items = MyTable::query()->find();
```

<a name="praxis-beispiele"></a>
## Praxis-Beispiele

```php
$items = MyTable::query()
            ->alias('t')
            ->joinRelation('relation_id', 'r')
            ->select('r.name', 'relation_name')
            ->where('t.status', '1')
            ->orderBy('t.name')
            ->find();
```

```php
$item = MyTable::create()
              ->setValue('user_id', 5)
              ->setValue('article_id', 6)
              ->save();;
```

```php
MyTable::query()
                ->where('user_id', 5)
                ->where('article_id', 6)
                ->find()
                ->delete();
```

```php
$table = rex_yform_manager_table::get('rex_data_product');

$products = $table->query()
    ->joinRelation('category_id', 'c') // Join auf rex_data_product_category gemäß Relationsfeld category_id, mit Alias "c" für weitere Verwendung
    ->select('c.name', 'category_name') // Aus der gejointen Tabelle den Kategorienamen mit auslesen mit Alias "category_name"
    ->where('status', 1)
    ->find();

foreach ($products as $product) {
    echo $product->name;
    echo $product->category_name; // Value aus der gejointen Tabelle, siehe oben

    // Alternativ das komplette Objekt für die Kategorie auslesen
    $category = $product->getRelatedDataset('category_id');
    echo $category->name;
}
```

<a name="datensatz-abfragen"></a>
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

**Beispiel:** Datensatz auslesen und YForm-Formular bereitstellen

```php
// Datensatz aus Tabelle mit ID 2
$dataset = rex_yform_manager_dataset::get(2,'tabelle');
// Formular auslesen
$yform = $dataset->getForm();
// Parameter festlegen
$yform->setObjectparams('form_method','get');
// Ziel des Formulars, sonst erhält man nur Index.php ...
$yform->setObjectparams('form_action',rex_getUrl(REX_ARTICLE_ID));
// Sollen die Daten des Datensatzes ausgelesen werden? (true = ja , false = nein) 
$yform->setObjectparams("getdata",true);
$yform->setActionField('showtext',array('','Gespeichert'));
// Ausgabe des Formulars
echo $dataset->executeForm($yform);
} ?>
```

<a name="datensatz-ändern"></a>
### Datensatz ändern

```php
$post = rex_yform_manager_dataset::get($id, 'rex_blog_post');
$post->title = 'REDAXO-Tag in Wackershofen (am Grundbach)';
$post->text = '...';

if ($post->save()) { 
    echo 'Gespeichert!';
} else {
    echo implode('<br>', $post->getMessages());
}
```

<a name="datensatz-erstellen"></a>
### Datensatz erstellen

```php
$post = rex_yform_manager_dataset::create('rex_blog_post');
$post->title = 'REDAXO-Tag in Wackershofen (am Grundbach)'; 
$post->text = '...';

if ($post->save()) {
    echo 'Gespeichert!';
} else {
    echo implode('<br>', $post->getMessages());
}
```

***Beispiel*** Neuen Datensatz erstellen und Formular bereitstellen***

```php
// Neuen leeren Datensatz erstellen
$dataset = rex_yform_manager_dataset::create('tabelle');
// Formular auslesen
$yform = $dataset->getForm();
// Parameter festlegen
$yform->setObjectparams('form_action',rex_getUrl(REX_ARTICLE_ID));
// Ziel des Formulars, sonst erhält man nur Index.php ...
$yform->setObjectparams('form_action',rex_getUrl());
$yform->setActionField('showtext',array('','Gespeichert'));
echo $dataset->executeForm($yform);
} ?>
```


<a name="eigene-modelklassen"></a>
### Eigene Modelklassen

```php
// boot.php  
rex_yform_manager_dataset::setModelClass(  
    'rex_blog_author',  
    rex_blog_author::class  
);
```

```php
// lib/post.php 
class rex_blog_post extends rex_yform_manager_dataset 
{ 
     
}
```

oder

```php 
  
// boot.php  
rex_yform_manager_dataset::setModelClass(  
    'rex_blog_author',  
    rex_blog_author::class  
);  
```

```php 
// lib/author.php 
class rex_blog_author extends rex_yform_manager_dataset 
{ 
    public function getFullName(): string 
    { 
        return $this->first_name.' '.$this->last_name; 
    } 
} 
```

```php 
// Template 
$author = rex_blog_author::get($id); 
echo $author->getFullName();
```


<a name="query-klasse"></a>
### Query-Klasse
 
```php 
$query = rex_blog_post::query();  

$query  
->where('status', 1)  
->where('created', $date, '>')  
->orderBy('created', 'desc')  
; 

$posts = $query->find(); 

// $post = $query->findOne();
```

<a name="collection-Klasse"></a>
### Collection-Klasse
 
```php
$query = rex_blog_post::query();  

// $query->...  

$posts = $query->find();  

foreach ($posts as $post) { 
echo $post->title; 
echo $post->text; 
}
```

```php 

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

<a name="relationen"></a>
### Relationen
 
```php

foreach ($posts as $post) {  
$author = $post->getRelatedDataset('author_id');  

echo 'Autor: '.$author->getFullName();  

echo $post->title;  
} 

```php
$posts = $author->getRelatedCollection('posts'); 
``` 

```php 

$query = rex_blog_post::query();  

$query  
->joinRelation('author_id', 'a')  
->selectRaw(  
'CONCAT(a.first_name, " ", a.last_name)',  
'author_name' 
); 

$posts = $query->find(); 

foreach ($posts as $post) { 
echo 'Autor: '.$post->author_name; 
}
``` 

<a name="paginierung"></a>
### Paginierung
 
**Beispiel 1**
```php 

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
**Beispiel 2**

```php
$pager = new rex_pager(10);
$table = rex_yform_manager_table::get('rex_table_name');
$ergebnisse = $table->query()
    ->paginate($pager);
$fragment = new rex_fragment();
$fragment->setVar('urlprovider', rex_article::getCurrent());
$fragment->setVar('pager', $pager);
echo $fragment->parse('core/navigations/pagination.php');

foreach ($ergebnisse as $erg) {
echo "ID: ".$erg->id;
}
echo $pager->getRowCount();
echo $pager->getCurrentPage();
echo $pager->getLastPage();
echo $pager->getPageCount();
```

<a name="formulare"></a>
### Formulare

```php 

$post = rex_blog_post::get($id);  

$yform = $post->getForm();  

// $yform->setHiddenField();  
// $yform->setObjparams();  

echo $post->executeForm($yform)
``` 


<a name="methoden-referenz"></a>
## Methoden-Referenz

<a name="collection-methoden"></a>
### collection-Methoden

- delete
- executeForm
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
- populateRelation
- save
- setData
- setValue
- toKeyIndex
- toKeyValue

<a name="query-methoden"></a>
### query-Methoden

- Alias
    - alias
    - getTableAlias
- count
- exists (liefert true oder false zurück. Optimal für große Abfragen.)
- Find
    - find
    - findId
    - findIds
    - findOne (liefert einen Datensatz als Objekt zurück.)
- Get
    - get
    - getAll
- Group By
    - groupBy
    - groupByRaw
    - resetGroupBy
- Join
    - joinRaw
    - joinRelation
    - joinType
    - joinTypeRelation
    - leftJoin
    - leftJoinRelation
    - resetJoins
- Limit
    - limit
    - resetLimit
- Order By
    - orderBy
    - orderByRaw
    - resetOrderBy
- paginate ([Beispiel](#beispiel-paginate))
- Query
    - query
    - queryOne
- save
- Select
    - resetSelect
    - select
    - selectRaw (lässt individuelle Argumente zu, wie z.B. `CONCAT, SUM`)
- Table
    - getTable
    - getTableName
- Where
    - resetWhere
    - setWhereOperator
    - where
    - whereNested
    - whereRaw

<a name="dataset-methoden"></a>
### dataset-Methoden

- create
- get
- getAll
- getData (liefert Felder als Array zurück)
- getForm (liefert Formular zurück - EXPERIMENTELL!)
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


<a name="debugging"></a>
## Debugging

> Hinweis: Diese Vorgehensweise wird in zukünftigen Versionen optimiert. Beteilige dich aktiv an der Entwicklung auf [github.com/yakamara/redaxo_yform/](http://github.com/yakamara/redaxo_yform/)!

<a name="debugging-variante-1"></a>
### Variante 1

Wichtig ist nur der Part mit `rex_sql`

```php
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

<a name="debugging-variante-2"></a>
### Variante 2

Datei `/redaxo/src/addons/yform/plugins/manager/lib/yform/manager/dataset.php` und die Variable `private static $debug = false;` auf `true` setzen

<a name="tricks"></a>
## Tricks

<a name="dataset-filter"></a>
### Aus dem Dataset ungewollte Felder (z.B. für's Frontend) herausfiltern 

```php 
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
                // hebt das Feld auf, es wird später im Formular auch nicht gezeigt. 
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

Model-Class in boot.php z.B. im project Addon registrieren

```php
rex_yform_manager_dataset::setModelClass('mydata', rex_data_mydate::class);
```
