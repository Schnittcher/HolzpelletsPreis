# Changelog

## 19.10.2026 - Version 2.0

### Neu
- Option, den Preis im Archiv zu loggen: Die Kachel zeigt dann den eigenen Verlauf im wählbaren Zeitraum bis zu 365 Tage.
- Schaltfläche und Funktion "Preisverlauf importieren" übernehmen den Verlauf der API ins Archiv.
- Neue Kachel für die Kachel-Visualisierung mit Preis, Trend zum Vortag und zur Vorwoche, Preisverlauf der letzten Tage und Nachfrage.
- Voraussetzung ist jetzt IP-Symcon 9.0.
- Preis und Nachfrage nutzen die neuen Variablendarstellungen statt der alten Profile.
- Instanzstatus meldet ein ungültiges Land und einen fehlgeschlagenen Abruf.
- Achtung: Bestehende Instanzen werden beim ersten Laden einmalig auf die neue Darstellung umgestellt. Werte, Idents und Archivdaten bleiben erhalten. Variablen, an denen kein Profil mehr hängt, werden nicht angefasst.

### Fixes
- Ein fehlgeschlagener Abruf (kein Netz, Server nicht erreichbar) führt nicht mehr zu einem Skriptfehler, die letzten Werte bleiben erhalten.
- Der Abruf wartet höchstens 10 Sekunden auf die Antwort.
- Beim Systemstart wird erst abgerufen, wenn der Kernel bereit ist.
- Die Nachfrage wird sauber auf ganze Prozent gerundet.
- Das Formular zeigt die Beschriftung "Aktualisierungsintervall" nicht mehr doppelt.
