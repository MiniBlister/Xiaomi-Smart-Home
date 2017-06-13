
# Xiaomi-Smart-Home

Implementierung von Xiaomi Smart Home Gateway mit angeschlossenen Geräten in IP-Symcon.

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang) 
2. [Voraussetzungen](#2-voraussetzungen)
3. [Developer Modus](#3-developer-modus)
4. [Installation](#4-installation)
5. [Vorbereitungen](#5-vorbereitungen)
6. [Einrichten der Instanzen in IPS](#6-einrichten-der--instanzen-in-ips)
7. [Funktionen der Instanzen] (#7-funktionen-der-instanzen)
8. [PHP-Befehlsreferenz](#8-php-befehlsreferenz) 
9. [Parameter / Modul-Infos](#8-parameter--modul-infos) 
10. [Tips & Tricks](#10-tips--tricks) 
11. [Anhang](#11-anhang)
12. [Lizenz](#12-lizenz)

## 1. Funktionsumfang

Stellt eine Schnittstelle zum Xiaomi Smat Home Gateway her und ermöglicht es, mit dem Gateway verbundenen Geräte wie Sensoren oder Tastern auszulesen bzw. zu steuern.
Folgende Geräte werden derzeit unterstützt und wurden gestestet:
  - [Xiaomi Mi Smart Home Door / Window Sensors](https://xiaomi-mi.com/mi-smart-home/xiaomi-mi-door-window-sensors/)
  - [Xiaomi Mi Smart Home Occupancy Sensor](https://xiaomi-mi.com/sockets-and-sensors/xiaomi-mi-occupancy-sensor/)
  - [Xiaomi Mi Smart Home Wireless Switch](https://xiaomi-mi.com/sockets-and-sensors/xiaomi-mi-wireless-switch/)
  - [Xiaomi Mi Smart Home Temperature / Humidity Sensor](https://xiaomi-mi.com/sockets-and-sensors/xiaomi-mi-temperature-humidity-sensor/)

Folgende Geräte werden prizipiell unterstützt, wurden aber noch nicht getestet:
  - [Xiaomi Aqara Smart Light Control Set](https://xiaomi-mi.com/sockets-and-sensors/xiaomi-aqara-smart-light-control-set/)
  - [Xiaomi Aqara Air Conditioning Companion + Temperature / Humidity Sensor](https://xiaomi-mi.com/sockets-and-sensors/xiaomi-aqara-air-conditioning-companion-temperature-humidity-sensor/)
  - [Xiaomi Mi Smart Socket Plug](https://xiaomi-mi.com/sockets-and-sensors/xiaomi-mi-smart-socket-plug/)
  - [Xiaomi Mi Smart Home Cube](https://xiaomi-mi.com/sockets-and-sensors/xiaomi-mi-smart-home-cube-white/)

Nicht unterstützte Geräte:
Andere Xiaomi-Geräte, die sich nicht direkt mit dem Xiaomi Gateway verbinden, wie z. B. Roboter-Vakuum, WiFi-Steckdosen, Bluetooth-Leuchten, Luftreiniger, Wasserkocher usw. 
Auch wenn diese Geräte in der Mi Home App verfügbar sind, kommunizieren sie nicht direkt mit dem Gateway.

## 2. Voraussetzungen

 - IPS ab Version 4.2
 - Xiaomi Mi Smart Home Gateway V2
 - Mi Home App installiert auf einem Android bzw. iOS Gerät 
 - Das Xiaomi Mi Smart Home Gateway muss in den Developer Modus versetzt werden, so dass ein Zugriff vom lokalen Netzwerk möglich ist. [siehe Developer Modus]((#3-developer-modus))
   
## 3. Developer Modus

Es ist zwigend notwenidg, dass das Mi Smart Home Gateway in einen Developer Modus versetzt wird. Dies funktioniert nur mit dem Gateway in der Version 2.
Das Gateway wird über die Mi Home App in den Developer Modus versetzt.

Android Mi Home App: https://play.google.com/store/apps/details?id=com.xiaomi.smarthome
iOs Mi Home App: https://itunes.apple.com/us/app/mi-home-xiaomi-for-your-smarthome/id957323480?mt=8   

Die App ist noch nicht vollständig auf Englisch übersetzt. Jedes Update wird diesbezüglich besser aber ab und an wird man nicht mit Chinesischen Schriftzeichen überrascht.

  - Installiere die App auf einem Android-Gerät oder iOS-Gerät 
  - als Region bitte Festland China unter Einstellungen -> Locale
  - Sprache kann hier auf Englisch eingestellt werden
  - Wähle dein Gateway in Mi Home App 
  - am oberen rechten Bildschirm die 3 Punkte klicken 
  - Tippe auf die Version (2.23 ist die aktuelle Android-Version ab 8. März 2017) Nummer am unteren Rand des Bildschirms widerholt 
  - jetzt sollten 2 zusätzliche Optionen auf Englisch (war Chinesisch in früheren Versionen) erscheinen, bis der Entwickler-Modus aktiviert ist. [Wenn nicht alle Schritte wieder versuchen!] 
  - Wählen Sie die erste neue Option Tippen Sie dann auf den ersten Toggle-Schalter, um LAN-Funktionen zu aktivieren.  

## 4. Installation

Die Installation in IP-Symcon ist relativ einfach. Das Modul muss IP-Symcon als Modul zu Verfügung stehen.
Dazu bitte den folgenden Link in IP-Symcon unter Core->Modules eintragen.

## 5. Vorbereitungen

Nachdem das Modul erfolgreich installiert wurden, muss eine Konfigurator Instanz erstellt werden.
Dazu in IP-SYMCON eine Instanz des Typ Konfigurator erstellt. 
Diese erstellt automatisch einen Splitter und eine I/O Instanz.
Die Kommunikation zum Gateway erfolgt über einen Multicast Socket, welcher automatisch erstellt, jedoch konfiguriert werden muss.

IP Adresse: muss am Router ermittelt werden und hier eingegeben werden
Multicast IP: 
Multicast Port:

Nachdem die I/O Instanz konfiguriert ist, werden im Konfigurator alle mit dem Gateway verbundenen Geräte angezeigt. 
Wie ein Gerät an das Gateway angebunden wird, kann der Mi Home App entnommen werden.
Die in der Mi Home App hinterlegten Informationen wie Name oder Raum in welchem sich das Gerät befindet, werden in der API nicht zu Verfügung gestellt und stehen damit IP-Symcon ebenfalls nicht zu Verfügung. 

## 6. Erstellen einer Geräte Instanz in IPS

Das Erstellen eines Gerätes ist aus dem Konfigurator heraus leicht möglich. 
Dazu bitte die Zeile in der Liste auswählen für welches ein Gerät erstellt werden soll und mit Hinzugefügt bestätigen.
Leider bietet IP-Symcon noch keine Möglichkeit von dynamischen Listen, daher muss der Konfigurator einmal geschlossen und wieder geöffnet werden um die Änderung sichtbar zu machen. 
Das Gerät besitzt zu Beginn keine Varriablen. Diese werden jedoch automatisch erstellt sobald der Wert vom Gateway zu Verfügung gestellt wird. Dies kann durch ein regelmässiges Lesen der Daten oder durch eine aktive Betätigung eines Tasten Befehls am Switch durch den Benutzer geschehen.

## 7. Funktionen der Instanzen

Folgenden Funktionen bzw. Daten werden derzeit unterstützt.

| Device                        | Typ       | Beschreibung                            |
|:------------------------------|:----------|:----------------------------------------|
| Door / Window Sensors         | Float     | Voltage (x.xV)                          |
|                               | Boolean   | Status (Offen/Geschlossen)              |
| Occupancy Sensor              | Float     | Voltage (x.xV)                          |
|                               | Boolean   | Status (Bewegung erkannt/nicht erkannt) |
| Temperature / Humidity Sensor | Float     | Humidity (xx.x%)                        |
|                               | Float     | Temperature (xx.x°C)                    |
| Wireless Switch               | Float     | Voltage (x.xV)                          |
|                               | Boolean   | Status Click                            | 
|                               | Boolean   | Status Double Click                     | 
|                               | Boolean   | Status Long Click Press                 |
|                               | Boolean   | Status Long Click Release               |    

## 11. Lizenz  

[CC BY-NC-SA 4.0] (https://creativecommons.org/licenses/by-nc-sa/4.0/) 