# Contao Bayern ERTL Bundle 

E-Mail Registration + Token Login


## Anforderungen nach Installation des Bundles


### Parameter

In der Datei `config/parameters.yml` die Parameter ergänzen, die dasBundle benötigt (s.u.).


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


### Features

* Beim Löschen eines Members werden die zugehörigen `tl_member_login_token`-Records gelöscht


## TODOs

### Parameter in config/parameters.yml ergänzen:

```
parameters:
    # Parameter aus der Contao Installation
    database_host: ...
    database_port: ...
    database_user: ...
    database_password: ...
    database_name: ...
    secret: ...
    # Parameter für das Bundle (Beispiele -- noch nicht implementiert)
    ertl_ASSIGN_GROUPS: [1,2,3] --> TODO: multidomain und "nicht gesetzt berücksichtigen"
```


## Cron Jobs

* `tl_member` und `tl_member_token` "purgen"



