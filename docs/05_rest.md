# REST-API

> **Hinweis:**
> Plugins erweitern YForm und können optional aktiviert werden.

## Erste Schritte mit der REST-API von YForm

Mit dem REST-Plugin lässt sich eine Schnittstelle aktivieren, mit der man YForm Tabellen von außen abrufen, verändern und löschen kann. Dabei wird auf die REST-API gesetzt.
Die Klasse muss zunächst registriert werden siehe [YOrm](yorm.md), damit auf diese mit REST zugegriffen werden kann. Alle Übergaben und Rückgabewerte werden als JSON übertragen.

> Hinweis: Die Tabellem müssen mit YForm verwaltbar sein, da diese Felder automatisch genutzt werden.

Die Schnittstelle orientiert sich an <https://jsonapi.org/format/>. Aufrufe und JSON Formate sind ähnlich bis exakt so aufgebaut.

[Im Rahmen einer REDAXOHour ist eine Video Einführung entstanden, die viele Punkte dieses Kapitels erklärt.](https://www.youtube.com/watch?v=o88DHxsOLOs)

## Konfiguration / Endpoints

Die Zugriff über REST muss für jeden Endpoint einzeln definiert werden. D.h. man muss für jede Tabelle und für unterschiedliche Nutzungszenarien diese fest definieren.

Die Standardroute der REST-API ist auf "/rest" gesetzt, d.h. hierunter können eigene Routen definiert werden.

Die Konfiguration wird über PHP festgelegt und sollte im project-AddOn in der boot.php abgelegt werden. Kann aber auch an andere Stelle abgelegt werden, solange diese während der Initialisierung aufgerufen wird.

Hier ein Beispiel, um YCom-User über die REST-API zu verwalten:

```php
<?php

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
                'rex_ycom_user' => [ /* Name der Model-Klasse, nicht der Tabelle, ggf. inkl. Namespace */
                    'id',
                    'login',
                    'email',
                    'name'
                 ],
                 'rex_ycom_group' => [ /* Name der Model-Klasse, nicht der Tabelle, ggf. inkl. Namespace */
                    'id',
                    'name'
                 ]
            ]
        ],
        'post' => [
            'fields' => [
                'rex_ycom_user' => [ /* Name der Model-Klasse, nicht der Tabelle, ggf. inkl. Namespace */
                    'login',
                    'email',
                    'ycom_groups'
                ]
            ]
        ],
        'delete' => [
            'fields' => [
                'rex_ycom_user' => [ /* Name der Model-Klasse, nicht der Tabelle, ggf. inkl. Namespace */
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

### Route-Konfiguration

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

## Nutzung eines Endpoints

URL (z. B. <https://domain/rest/v1/user>)
In den Beispielen wird davon ausgegangen, dass es keine eigene Authentifizierung gibt. Um zu sehen wie die Aufrufe funktionieren bitte hier <https://jsonapi.org/format/> nachschlagen.

### GET

#### Datensätze abrufen

RequestType: ````GET````

URL: ```https://url.localhost/rest/v1/users/[id]```

Header:

```
Content-Type: application/x-www-form-urlencoded
token: [token]
```

Response:

```
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

Man kann das Ergebnis filtern. Über URL Parameter können die internen Feldsuchen verwendet werden (getSearchFilter). Über einen oder mehrere ```filter[feldname]=suchwert``` Parameter werden die Suchfilter verwendet. Ein Suchfeld kann nur verwendet werden, wenn es in der Route als Feld definiert wurde.

#### Includes

über ```include=title,user.login,user.email``` kann man entscheiden welche Werte man empfangen möchte. So kann man z.B. für kompakter Ergebnislisten sorgen, falls bestimmte Relation oder Werte nicht nötig sind.

#### Weitere Parameter

Mit ```per_page=x``` kann man die Anzahl der Treffer pro Seite einschränken oder erweitern. Durch ```page=x``` besteht die Möglichkeit direkt auf untere Ergebnisseiten zu springen und über ```order[Feldname]=asc oder desc``` lassen sich die Ergebislisten entsprechend der Feldnamen sortieren. order lasst sich auch mehrfach verwenden um unterschiedliche Sortierungen zu kombinieren.

### POST

##### Anlegen eines Datensatzes

Hier ein Beispiel für das Anlegen eines Datensatzes:

RequestType: ````POST````

URL: ```https://url.localhost/rest/v1/users/```

Header:

```
Content-Type: application/x-www-form-urlencoded
token: [token]
```

Body:

```
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

##### Aktualisieren von Datensätzen

[fehlt noch]

### DELETE

##### Löschen mit Filtern

RequestType: ````DELETE````

URL: ```https://url.localhost/rest/v1/users/?filter[login]=jannie```

Header:

```
Content-Type: application/x-www-form-urlencoded
token: [token]
```

Response:

```
{
    "all": 1,
    "deleted": 1,
    "failed": 0,
    "dataset": [{
        "id": "[id]"
    }]
}
```

##### Löschen mit einer ID

Hier ein Beispiel für das Löschen eines Datensatzes mit einer ID:

RequestType: ````DELETE````

URL: ```https://url.localhost/rest/v1/users/[id]```
oder ```https://url.localhost/rest/v1/users/?filter[id]=[id]```

Header:

```
Content-Type: application/x-www-form-urlencoded
token: [token]
```

Response:

```
{
    "all": 1,
    "deleted": 1,
    "failed": 0,
    "dataset": [{
        "id": "[id]"
    }]
}
```

Response ohne Treffer:

```
{
    "all": 0,
    "deleted": 0,
    "failed": 0
}
```

## Authentifizierung

### Standardauthentifizierung

Wenn im Model folgende Authentifizerung angegeben wurde: `'\rex_yform_rest_auth_token::checkToken()'` ist das die Standardauthentifizierung mit Token aus der YForm:Rest:Tokenverwaltung.

Die hier erstellen Token werden entsprechend überprüft und müssen im Header übergeben werden. `token=###meintoken###` Nur aktive Token funktionieren.
Über das REST PlugIn kann man im Backend diese Zugriffe einschränken und tracken. D.h. Es können Einschränkungen wir Zugriffe / Stunde oder ähnliches eingestellt werden.
Jeder Zugriff auf die REST-API wird erfasst.

## Header

Man kann eigene Header setzen indem man allgemein der REST Api Header zuweist

``\rex_yform_rest::setHeader('Access-Control-Allow-Origin', '*');``

oder einer Route einen speziellen eigenen Header zuweist, welcher der allgemeinen Header überschreiben würde.

``$route->setHeader('Access-Control-Allow-Origin', 'redaxo.org');``
