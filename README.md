# Contao Bayern ERTL Bundle 

E-Mail Registration + Token Login


## Anforderungen nach Installation des Bundles


### Parameter in config/parameters.yml ergänzen

In der Datei `config/parameters.yml` die Parameter ergänzen, die dasBundle benötigt

```
parameters:
    # Parameter aus der Contao Installation
    database_host: ...
    database_port: ...
    database_user: ...
    database_password: ...
    database_name: ...
    secret: ...
    # Parameter für das Bundle (Beispiele)
    ertl_assign_groups.example.com: [1,2,3]
    ertl_assign_groups.example.org: [1,4]
```


### Fehlerseiten anlegen 

Bei einem gescheiterten Loginversuch mit einem ungültigen Token wird auf eine
Fehlerseite weitergeleitet. Dafür sollten `401` "not authenticated",
`403` "access denied" und `404` "not found" Seiten im Seitenbaum existieren.


### Formular

Das Formular muss mindestens ein Eingabefeld "E-Mail-Adresse" haben, das `email` heißen muss!

Weitere Felder werden beim Anlegen des Mitglieds, das eingelogt werden soll übernommen, sofern 
ihr Name einer Spalte in der Mitgliedertabelle `tl_member` entspricht. Bsp.: (`firstname`, `lastname`)

Zur Identifikation des Formulars muss ein verstecktes Formularfeld angelegt werden, das
den Namen `ertl_login` und den Wert `9uetwrg7K83z7` hat.

Die Weiterleitungsseite für den Tokenlogin wird über den Loginlink gesteuert:

```
https://example.com/_login/<token>/<id der Seite zu der weitergeleitet werden soll>
```

Die `<id der Seite zu der weitergeleitet werden soll>` wird mittels eines verstecken 
Formular Felds festgelegt, das `redirecttopagewithid` heißen muss. Der zugehörige Wert
ist die ID der gewünschten Seite. Ist dieses Feld nicht vorhanden, so wird zur Startseite
weitergeleitet. Tip: Soll auf die Seite weitergeleitet werden, auf der sich das Formular befindet, so 
kann der Insert Tag `{{page::id}}` verwendet werden. 


### Notifications

Damit User einen Loginlink zugeschickt bekommen, muss im Notification Center eine Notification vom Typ 
"ER+TL Registrierung" angelegt werden. In dieser kann über Simple Tokens auf die Formulardaten zugegriffen 
werden (insbes. `##form_email##` für den Empfänger der E-Mail). 
Der Loginlink steht über das Simple Token `##loginlink##` zur Verfügung. 

Ein Beispiel für den Text der E-Mail:

```
Hallo ##form_firstname## ##form_lastname##

Dein Loginlink für ##domain## lautet 

##loginlink##
```


### Features

* Beim Löschen eines Members werden die zugehörigen `tl_member_login_token`-Records gelöscht
* Wird ein Member im Backend deaktiviert, so ist der Login unterbunden (bei Verwendung des 
  zugehörigen Loginlinks erhalten User einen Fehler `403`)


## TODOs


## Cron Jobs

* `tl_member` und `tl_member_token` "purgen" via noch zu erstellender `contao-console` Commands

