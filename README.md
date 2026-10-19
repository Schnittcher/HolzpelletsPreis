[![Version](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Version](https://img.shields.io/badge/Symcon%20Version-9.0%20%3E-blue.svg)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Check Style](https://github.com/Schnittcher/HolzpelletsPreis/actions/workflows/style.yml/badge.svg)](https://github.com/Schnittcher/HolzpelletsPreis/actions/workflows/style.yml)
[![Tests](https://github.com/Schnittcher/HolzpelletsPreis/actions/workflows/tests.yml/badge.svg)](https://github.com/Schnittcher/HolzpelletsPreis/actions/workflows/tests.yml)

# HolzpelletsPreis
Die Bibliothek ruft den aktuellen Preis für Holzpellets von Heizpellets24 (Deutschland und Österreich) ab.

## Inhaltsverzeichnis
- [HolzpelletsPreis](#holzpelletspreis)
  - [Inhaltsverzeichnis](#inhaltsverzeichnis)
  - [Screenshots](#screenshots)
  - [1. Voraussetzungen](#1-voraussetzungen)
  - [2. Funktionsumfang](#2-funktionsumfang)
  - [3. Enthaltene Module](#3-enthaltene-module)
  - [4. Installation](#4-installation)
  - [5. Konfiguration in IP-Symcon](#5-konfiguration-in-ip-symcon)
  - [6. Spenden](#6-spenden)
  - [7. Lizenz](#7-lizenz)

## Screenshots

![Kachel mit Preis, Trend und Verlauf](docs/screenshots/kachel.png)

## 1. Voraussetzungen

* mindestens IPS Version 9.0
* Internetzugang für den Abruf von heizpellets24.de bzw. heizpellets24.at

## 2. Funktionsumfang
* Abruf des aktuellen Pelletpreises pro Tonne und der Nachfrage
* Wahl zwischen Deutschland und Österreich
* Automatische Aktualisierung im einstellbaren Intervall
* Kachel für die Kachel-Visualisierung mit Preisverlauf, wahlweise aus dem eigenen Archiv

Die einzelnen Funktionen stehen bei den Instanzen unter "Enthaltene Module" und in der README des jeweiligen Moduls.

## 3. Enthaltene Module

* [HolzpelletsPreis](HolzpelletsPreis/README.md)
  * Ruft den aktuellen Preis pro Tonne und die Nachfrage von Heizpellets24 ab.
  * Bringt eine Kachel mit Preisverlauf und Trend mit.

## 4. Installation
Die Installation erfolgt über den IP-Symcon Module Store.

## 5. Konfiguration in IP-Symcon
Eine Instanz "HolzpelletsPreis" anlegen, Land und Aktualisierungsintervall wählen und die Instanz aktivieren. Die Einzelheiten stehen in der README des Moduls.

## 6. Spenden
Dieses Modul ist für die nicht kommerzielle Nutzung kostenlos, Schenkungen als Unterstützung für den Autor werden hier akzeptiert:

[![PayPal](https://img.shields.io/badge/PayPal-Spenden-00457C?logo=paypal&logoColor=white&style=for-the-badge)](https://www.paypal.com/donate?hosted_button_id=EK4JRP87XLSHW) [![Amazon Wunschzettel](https://img.shields.io/badge/Amazon-Wunschzettel-FF9900?logo=amazon&logoColor=white&style=for-the-badge)](https://www.amazon.de/hz/wishlist/ls/3JVWED9SZMDPK?ref_=wl_share)

## 7. Lizenz

[CC BY-NC-SA 4.0](https://creativecommons.org/licenses/by-nc-sa/4.0/)
