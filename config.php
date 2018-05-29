<?php

// Min and max temperature for watering, in Celsius deger :
const TEMPERATURE_FOR_WATERING_MIN = 25;
const TEMPERATURE_FOR_WATERING_MAX = 38;

// Min and max delay of watering, in minutes :
const DELAY_WATERING_MIN = 15;
const DELAY_WATERING_MAX = 45;

// Quantity of precipitation for detect a raining day, in mm :
const PRECIPITATION_FOR_DETECT_A_RAINING_DAY = 2;

// Delay min since last watering (by irrigation or by raining), in days :
const DELAY_MIN_SINCE_LAST_WATERING = 3;

// APIXU infos :
// (The given key is not a miss, the service is free to 10 000 calls by day, so I let you use my personal key)
const APIXU_KEY = '774e4c6f0eb04258b79165458181105';
const APIXU_CITY = 'Limoges';

// Email which will receive notifications :
const EMAIL_TO = 'your@email.com';

// Pin numero where connect the controlled interruptor :
const INTERRUPTOR_PIN_NUMERO = 15;
