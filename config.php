<?php

// Min and max delay of watering, in minutes :
const DELAY_WATERING_MIN = 15;
const DELAY_WATERING_MAX = 45;

// Temperature where start the watering, in Celsius deger :
const TEMPERATURE_FOR_START_WATERING = 10;

// Temperature where the delay max of watering is reached, in Celsius deger :
const TEMPERATURE_FOR_DELAY_WATERING_MAX = 38;

// Quantity of precipitation for detect the last raining day, in mm :
const QUANTITY_OF_PRECIPITATION_FOR_DETECT_LAST_RAINING = 20;

// Delay min since last watering (by irrigation or by raining), in days :
const DELAY_MIN_SINCE_LAST_WATERING = 2;

// APIXU infos (This not a miss, the service is free to 10000 calls by day, so I let you use my personal key) :
const APIXU_KEY = '774e4c6f0eb04258b79165458181105';
const APIXU_CITY = 'Limoges';

// Email which will receive notifications :
const EMAIL_TO = 'your@email.com';

// Pin numero where connect the controlled interruptor :
const INTERRUPTOR_PIN_NUMERO = 17;
