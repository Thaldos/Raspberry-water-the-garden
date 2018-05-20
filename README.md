# Raspberry-water-the-garden
Water the garden with a Raspberry pi

<br>

## Prerequisites
You'll need:

* [Raspberry Pi 3 model B](https://www.adafruit.com/product/3055) (35€)
* [Raspberry power supply (5V 2.5A)](https://www.amazon.fr/SainSmart-Certified-Raspberry-Adaptateur-Certification/dp/B01LHE8DBU/ref=sr_1_cc_2?s=aps&ie=UTF8&qid=1513517344&sr=1-2-catcorr&keywords=raspberry+3+Power+supply) (8€)
* [Raspberry case + heat sink](https://www.amazon.fr/gp/product/B01CPCMWWO/ref=oh_aui_detailpage_o00_s00?ie=UTF8&psc=1) (7€)
* [Relay board module](https://www.amazon.fr/gp/product/B019GTTS3K/ref=oh_aui_detailpage_o01_s00?ie=UTF8&psc=1) (2€)
* [SD card 16 Go](https://www.amazon.fr/gp/product/B073S9SFK2/ref=oh_aui_detailpage_o08_s00?ie=UTF8&psc=1) (11€)
* Patience and passion (prices not available)

Total: 63€

<br>

## Raspberry Pi installation
### Installation of Raspbian
Download the NOOBS OS : https://downloads.raspberrypi.org/NOOBS_latest

Extract the archive.

Copy past the files on your SD cart.

Insert the SD cart in your Raspberry Pi and start it.

Follow the installations steps.

### Installation of PHP 7
#### Solution 1 
On your Raspberry, open a terminal and type :

<code>apt-get install apt-transport-https lsb-release ca-certificates</code>

<code>wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg</code>

<code>echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list</code>

<code>apt-get update</code>

<code>apt install --no-install-recommends php7.1 libapache2-mod-php7.1 php7.1-mysql php7.1-curl php7.1-json php7.1-gd php7.1-mcrypt php7.1-msgpack php7.1-memcached php7.1-intl php7.1-sqlite3 php7.1-gmp php7.1-geoip php7.1-mbstring php7.1-redis php7.1-xml php7.1-zip</code>

Test by typing <code>php -v</code> in your terminal.

#### An other solution
If an error occurred, try this :

<code>nano /etc/apt/sources.list</code> 

Uncomment the line : 
<code>deb-src http://raspbian.raspberrypi.org/raspbian/ stretch main contrib non-free rpi</code> 

<code>apt-get update</code> 

<code>apt-get install -t stretch php7.0 php7.0-curl php7.0-gd php7.0-fpm php7.0-cli php7.0-opcache php7.0-mbstring php7.0-xml php7.0-zip</code> 

Test by typing <code>php -v</code> in your terminal. You should have something like :

```
PHP 7.0.4-7 (cli) ( NTS )  
Copyright (c) 1997-2016 The PHP Group  
Zend Engine v3.0.0, Copyright (c) 1998-2016 Zend Technologies  
with Zend OPcache v7.0.6-dev, Copyright (c) 1999-2016, by Zend Technologies
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

### Fix your Raspberry pi IP
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


### Install VNC server on the Raspberry
To easily access to your Raspberry every time, you should install VNC. You have to install VNC server on your Raspberry and VNC viewer on you desktop. Follow this good tutorial:

https://www.raspberrypi.org/forums/viewtopic.php?t=123457

### Install VNC viewer on your desktop
https://www.realvnc.com/en/connect/download/viewer/

Launch VNC viewer and add a new connection to `192.168.1.201:1`

> Important note: be sure te be on same Wi-Fi network on both sides.

<br>
    
## Project installation
### Copy project files on your Raspberry pi
Copy all this project files to your Raspberry in `/home/pi/controlled_interruptor/`.

### Configure
Customize the constants in the `config.php` file.

<br>

## Enjoy!
Your Raspberry pi will take a picture every day, and send you pictures every month by e-mail.
