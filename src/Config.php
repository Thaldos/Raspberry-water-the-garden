<?php

class Config
{
	// Min and max temperature for watering, in Celsius deger :
	const TEMPERATURE_FOR_DELAY_MIN = 18;
	const TEMPERATURE_FOR_DELAY_MAX = 38;

	// Min and max delay for watering, in minutes :
	const DELAY_MIN = 30;
	const DELAY_MAX = 50;

	// Delay min to wait between watering, in days :
	const DELAY_MIN_BETWEEN_WATERING = 2;

	// Delay max of consecutive pump and valve running, in minutes (to avoid pump or valve overheated) :
	const DELAY_MAX_RUNNING = 15;

	// Waiting delay to avoid pump or valve overheated, in minutes :
	const DELAY_BETWEEN_RUNNING = 15;

	// API infos (https://openweathermap.org/) :
	const API_KEY = 'yourAPIKey';
	const API_CITY = 'Limoges,FR';

	// Email which will receive notifications :
	const EMAIL_TO = 'your@email.com';

	// Pin numero where the relay module is connected :
	const PIN_NUMERO = 15;
}
