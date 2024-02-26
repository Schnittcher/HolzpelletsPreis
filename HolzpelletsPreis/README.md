# HolzpelletsPreis
Das Modul ruft den aktuellen Preis pro Tonne ovn Heizpellets24.de ab.
    
## Inhaltverzeichnis
- [HolzpelletsPreis](#holzpelletspreis)
  - [Inhaltverzeichnis](#inhaltverzeichnis)
  - [1. Konfiguration](#1-konfiguration)
  - [2. Funktionen](#2-funktionen)

## 1. Konfiguration

Feld | Beschreibung
------------ | ----------------
Aktiv | Das Abrufen kann über diese Eigenschaft ein- bzw. ausgeschaltet werden.
Aktualisierungsintervall | Hier wird in Stunden hinterlegt, wie oft der Preis abgerufen werden soll.
## 2. Funktionen

**HP_Update($InstanceID)**\
Mit dieser Funktion ist es möglich den Preis sofort zu aktualisieren.
```php
HP_Update(12345);
```
