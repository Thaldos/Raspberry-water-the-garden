# Raspberry-water-the-garden
Water the garden with a Raspberry pi

<br>

## Prerequisites
You'll need:

* [(1) Raspberry power supply (5V 2.5A)](https://www.amazon.fr/SainSmart-Certified-Raspberry-Adaptateur-Certification/dp/B01LHE8DBU/ref=sr_1_cc_2?s=aps&ie=UTF8&qid=1513517344&sr=1-2-catcorr&keywords=raspberry+3+Power+supply) (8€)
* [(2) Raspberry Pi 3 model B](https://www.adafruit.com/product/3055) (35€)
* [Raspberry case + heat sink](https://www.amazon.fr/gp/product/B01CPCMWWO/ref=oh_aui_detailpage_o00_s00?ie=UTF8&psc=1) (7€)
* [(3) Relay board 5V - 220V](https://www.amazon.fr/gp/product/B019GTTS3K/ref=oh_aui_detailpage_o01_s00?ie=UTF8&psc=1) (2€)
* [(4) Relay board 220V - 220V](https://www.domomat.com/23422-contacteur-tesys-lc1d-bobine-230vac-3p-ac3-440v-4kw-3f--schneider-electric-lc1d09p7.html) (21€)
* [SD card 16 Go](https://www.amazon.fr/gp/product/B073S9SFK2/ref=oh_aui_detailpage_o08_s00?ie=UTF8&psc=1) (11€)
* [(5) Vertical Tank Water Level Sensor](https://www.amazon.com/gp/product/B016Q6S2VU/ref=oh_aui_detailpage_o00_s00?ie=UTF8&psc=1) (7€)
* [(6) Water tank](https://www.leboncoin.fr/) (60€)
* [(7) Pump](https://www.leroymerlin.fr/v3/p/produits/pompe-arrosage-manuelle-einhell-gc-gp-6538-3800-l-h-e1500896297) (60€)
* [(8) Solenoid valve (12V 2A AC)](https://www.amazon.fr/U-S-Solid-%C3%89lectrovanne-Normalement-entra%C3%AEnement/dp/B01MS7H1ZP/ref=sr_1_7?ie=UTF8&qid=1533749267&sr=8-7&keywords=Solenoid+valve) (24€)
* [Solenoid valve power supply 12V 2A AC](https://www.amazon.fr/Eleidgs-Adaptateur-Chargeur-Alimentation-Imprimante/dp/B06Y1MBYDW/ref=sr_1_1_sspa?s=electronics&ie=UTF8&qid=1533749358&sr=1-1-spons&keywords=power+supply+12V+2A+AC&psc=1) (9€)
* Patience and passion (prices not yet available)

Total: 244€

<br>


## Hardware 
Connect your devices like this:

[![Hardware](https://image.ibb.co/ekqYtz/Raspberry_pump_idee_4.jpg)](https://image.ibb.co/ekqYtz/Raspberry_pump_idee_4.jpg)

<br>

## Raspberry Pi installation
### Installation of Raspbian
Download the NOOBS OS : https://downloads.raspberrypi.org/NOOBS_latest

Extract the archive.

Copy past the files on your SD cart.

Insert the SD cart in your Raspberry Pi and start it.

Follow the installations steps.

### Installation of PHP 7.3
<code>nano /etc/apt/sources.list</code> 

Uncomment the line : 
<code>deb-src http://raspbian.raspberrypi.org/raspbian/ stretch main contrib non-free rpi</code> 

<code>apt-get update</code> 

<code>apt-get install -t stretch php7.3 php7.3-curl php7.3-gd php7.3-fpm php7.3-cli php7.3-opcache php7.3-mbstring php7.3-xml php7.3-zip</code> 

Test by typing <code>php -v</code> in your terminal. You should have something like :

```
PHP 7.0.4-7 (cli) ( NTS )  
Copyright (c) 1997-2016 The PHP Group  
Zend Engine v3.0.0, Copyright (c) 1998-2016 Zend Technologies  
with Zend OPcache v7.0.6-dev, Copyright (c) 1999-2016, by Zend Technologies
```

### Installation of Curl
Type in Raspberry terminal :

```
sudo apt-get install curl
```

### Installation of Composer
Type in Raspberry terminal :

```
cd /usr/src  
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
```


### Wi-Fi autoconnecting
Type in your terminal :
<code>sudo nano /etc/wpa_supplicant/wpa_supplicant.conf</code>
 
 And append some thing like :
 
```
network={  
    ssid="Livebox-12345"  
    psk="123456789AZERTY"  
}
```

Now when you will restart your Raspberry, it will automatically connects to this network. 

### Fixing of the Raspberry Pi IP
Type in Raspberry terminal : `sudo nano /etc/network/interfaces` and append this :

```
# interfaces(5) file used by ifup(8) and ifdown(8)
# Please note that this file is written to be used with dhcpcd
# For static IP, consult /etc/dhcpcd.conf and 'man dhcpcd.conf'
# Include files from /etc/network/interfaces.d:
source-directory /etc/network/interfaces.d
auto lo
iface lo inet loopback
iface eth0 inet manual
allow-hotplug wlan0
iface wlan0 inet manual
wpa-conf /etc/wpa_supplicant/wpa_supplicant.conf
allow-hotplug wlan1
iface wlan1 inet manual
wpa-conf /etc/wpa_supplicant/wpa_supplicant.conf
```

Then type in Raspberry terminal `sudo nano /etc/dhcpcd.conf` and append this :

```
# Configuration ip fix wlan :
interface wlan0
static ip_address=192.168.1.201/24 #replace 201 by your wish
static routers=192.168.1.1
static domain_name_servers=192.168.1.1
```
> [More details on this step here](http://limen-arcanum.fr/2016/03/raspberry-3-et-ip-fixe-en-wifi/) (French link)


Check if your IP address is set well:
* Reboot then check your local IP : `hostname -I`

* Reboot again and re-check your local IP

It should be the same.


### Installation of VNC server on the Raspberry
To easily access to your Raspberry every time, you should use VNC. You have to enable VNC server on your Raspberry and install VNC viewer on you desktop.

Menu > Preference > Raspberry configuration > Interfaces > Enable VNC


[![Raspberry VNC](https://image.ibb.co/cMPMny/raspberry_vnc.jpg)](https://image.ibb.co/cMPMny/raspberry_vnc.jpg)

### Installation of VNC viewer on your desktop
https://www.realvnc.com/en/connect/download/viewer/

Launch VNC viewer and add a new connection to `192.168.1.201`, with the user `pi` and password `raspberry`.


> Important note: be sure te be on same Wi-Fi network on both sides.

### Installation of SMTP
Follow this good tutorial:

https://hotfirenet.com/blog/1704-envoyer-mail-depuis-le-raspberry-pi/ (French link)

If, like me, you use gmail, this is a good configuration :
```
hostname=anexistingwebdomain.com
root=monLogin@gmail.com
mailhub=smtp.gmail.com:587
AuthUser=monLogin@gmail.com
AuthPass=monbeauPaSsWoRd
FromLineOverride=YES
UseSTARTTLS=YES
```

<br> 
    
## Project installation
### Copying of the project files on your Raspberry pi
Copy all this project files to your Raspberry in `/home/pi/Raspberry-water-the-garden/`.

Then Chmod the files `/home/pi/Raspberry-water-the-garden/src/temperatures.txt` and `/home/pi/Raspberry-water-the-garden/src/lastwatering.txt` to 777.

### Customization
Customize the constants in the `src/Config.php` file.

### Download the vendors 
Then type in Raspberry terminal :

```
cd /home/pi/Raspberry-water-the-garden/ 
composer install
```

### Set the cron tab
On your Raspberry, in terminal, type `crontab -e` and add the lines:
```
0 15 * * * sudo php /home/pi/Raspberry-water-the-garden/storecurrenttemperature.php 2>&1
0 23 * * * sudo php /home/pi/Raspberry-water-the-garden/waterthegarden.php 2>&1
```


<br>

## Enjoy!
Your Raspberry pi will check every day at 23pm if your garden need to be watered, and if it is needed, the Raspberry will water your garden during the appropriate delay.

<br>

## Note
You can run manually the watering by typing in your Rapsberry Pi terminal :

```
sudo php /home/pi/Raspberry-water-the-garden/waterthegarden.php fixed 
```
The garden will be watered during the delay defined by `DELAY_MIN` in `config.php`.
<br>
<br>
<br>
You can reset the relay by typing in your Rapsberry Pi terminal :

```
sudo php /home/pi/Raspberry-water-the-garden/waterthegarden.php reset 
```


<br>

## Thanks
Special thanks to my lovely wife for the logic contained in the function `getDelayForWatering($temperature)`.
