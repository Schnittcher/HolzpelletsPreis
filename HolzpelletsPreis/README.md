# HolzpelletsPreis
Das Modul ruft den aktuellen Pelletpreis pro Tonne und die Nachfrage von Heizpellets24 (Deutschland oder Österreich) ab und legt sie als Variablen ab. Die Instanz wird manuell angelegt.

## Inhaltsverzeichnis
- [HolzpelletsPreis](#holzpelletspreis)
  - [Inhaltsverzeichnis](#inhaltsverzeichnis)
  - [1. Konfiguration](#1-konfiguration)
  - [2. Variablen](#2-variablen)
  - [3. Funktionen](#3-funktionen)
  - [4. Spenden](#4-spenden)
  - [5. Lizenz](#5-lizenz)

## 1. Konfiguration

Feld | Beschreibung
------------ | ----------------
Land | Quelle der Preise: Deutschland (heizpellets24.de) oder Österreich (heizpellets24.at). Standard: Deutschland.
Aktiv | Das Abrufen kann über diese Eigenschaft ein- bzw. ausgeschaltet werden. Beim Aktivieren wird sofort einmal abgerufen.
Aktualisierungsintervall | Hier wird in Stunden hinterlegt, wie oft der Preis abgerufen werden soll. Bei 0 gibt es keinen automatischen Abruf, der Preis wird nur beim Speichern der Instanz und über `HP_Update` aktualisiert.
Preis im Archiv loggen und in der Kachel anzeigen | Schaltet das Loggen von "Preis pro Tonne" im Archiv ein und lässt die Kachel das Diagramm aus dem eigenen Archiv zeichnen (Tagesmittel). Ohne die Option oder bei weniger als zwei Archivtagen nutzt die Kachel den Verlauf der API (höchstens 90 Tage). Das Loggen wird beim Abwählen nicht ausgeschaltet. Standard: aus.
Zeitraum des Diagramms | 30, 90, 180 oder 365 Tage. Mit API-Verlauf sind höchstens 90 Tage möglich. Standard: 90.

Kann der Preis nicht abgerufen werden, zeigt die Instanz den Status "Preisdaten konnten nicht abgerufen werden". Die letzten Werte bleiben erhalten.

## 2. Variablen

Variable | Ident | Typ | Beschreibung
------------ | ------------ | ------------ | ----------------
Preis pro Tonne | `PricePerTon` | Kommazahl (Euro) | Letzter Preis pro Tonne laut Heizpellets24. Nur lesend.
Nachfrage | `Demand` | Ganzzahl (Prozent) | Letzte Nachfrage laut Heizpellets24, auf ganze Prozent gerundet. Nur lesend.

### Kachel
Die Instanz hat eine Kachel für die Kachel-Visualisierung (Vollbild-Ansicht ab Symcon 9.1). Sie zeigt den aktuellen Preis pro Tonne groß, die Veränderung zum Vortag und zur Vorwoche, den Verlauf (aus dem eigenen Archiv oder von der API) mit Tief und Hoch sowie die Nachfrage als Balken. Sie hat keine Bedienelemente und aktualisiert sich bei jedem Abruf. Ohne abgerufene Daten zeigt sie einen Hinweis.

## 3. Funktionen

`integer HP_ImportHistory(integer $InstanzID);`
Übernimmt den Preisverlauf der API (rund 90 Tage) für alle Tage ins Archiv, für die dort noch kein Wert steht. Voraussetzung ist die aktive Option "Preis im Archiv loggen und in der Kachel anzeigen". Liefert die Zahl der importierten Werte. Auch als Schaltfläche "Preisverlauf importieren" in der Instanz. Mehrfaches Ausführen ist unkritisch.

`void HP_Update(integer $InstanzID);`
Ruft den aktuellen Preis sofort ab. Wird auch vom Timer gemäß Aktualisierungsintervall aufgerufen.

```php
HP_Update(12345);
```

## 4. Spenden
Dieses Modul ist für die nicht kommerzielle Nutzung kostenlos, Schenkungen als Unterstützung für den Autor werden hier akzeptiert:

[![PayPal](https://img.shields.io/badge/PayPal-Spenden-00457C?logo=paypal&logoColor=white&style=for-the-badge)](https://www.paypal.com/donate?hosted_button_id=EK4JRP87XLSHW) [![Amazon Wunschzettel](https://img.shields.io/badge/Amazon-Wunschzettel-FF9900?logo=amazon&logoColor=white&style=for-the-badge)](https://www.amazon.de/hz/wishlist/ls/3JVWED9SZMDPK?ref_=wl_share)

## 5. Lizenz

[CC BY-NC-SA 4.0](https://creativecommons.org/licenses/by-nc-sa/4.0/)
