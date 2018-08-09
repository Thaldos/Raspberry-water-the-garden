<?php

// Min and max temperature for watering, in Celsius deger :
const TEMPERATURE_FOR_WATERING_MIN = 25;
const TEMPERATURE_FOR_WATERING_MAX = 35;

// Min and max delay of watering, in minutes :
const DELAY_WATERING_MIN = 30;
const DELAY_WATERING_MAX = 60;

// Quantity of precipitation for detect a raining day, in mm :
const PRECIPITATION_FOR_DETECT_A_RAINING_DAY = 2;

// Delay min since last watering (by irrigation or by raining), in days :
const DELAY_MIN_SINCE_LAST_WATERING = 2;

// Delay max of consecutive pump and valve running, in minutes. In order to avoid pump or valve overheated :
// /!\ Use only int.
const DELAY_MAX_CONSECUTIVE_RUNNING = 15;

// Waiting delay to avoid pump or valve overheated, in minutes :
// /!\ Use only int.
const DELAY_TO_WAIT_BETWEEN_RUNNING = 15;

// APIXU infos :
// (The given key is not a miss, the service is free to 10 000 calls by day, so I let you use my personal key)
const APIXU_KEY = '774e4c6f0eb04258b79165458181105';
const APIXU_CITY = 'Limoges';

// Email which will receive notifications :
const EMAIL_TO = 'your@email.com';

// Pin numero where the relay module is connected :
const PIN_NUMERO = 15;
