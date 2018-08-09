<?php

include_once 'config.php';
require 'library/vendor/autoload.php';

use PhpGpio\Gpio;

const DATE_FORMAT = 'Y-m-d';
const LAST_WATERINGS_FILENAME = 'lastwaterings.txt';

/**
 * Open the pump during the appropriate delay.
 * The delay is computed from the today temperature and the delay since the last watering.
 * The last watering is a raining day or a Raspberry Pi watering.
 */
function waterTheGarden() {
    // Get today date :
    $dateToDay = new DateTime('NOW');
    $toDay = $dateToDay->format(DATE_FORMAT);

    // Get the max temperature and total precipitation of today :
    $temperaturePrecipitationToday = getTemperaturePrecipitation($toDay);
    if ($temperaturePrecipitationToday !== false) {
        $temperatureToday = $temperaturePrecipitationToday['temperature'];
        $precipitationToday = $temperaturePrecipitationToday['precipitation'];
        $lastWateringsFilename = dirname(__FILE__) . DIRECTORY_SEPARATOR . LAST_WATERINGS_FILENAME;

        // Save the today precipitation in text file :
        if (PRECIPITATION_FOR_DETECT_A_RAINING_DAY < $precipitationToday) {
            $isOkSave = setInFile($lastWateringsFilename, $toDay);
        }

        // Get delay since last watering :
        $delaySinceLastWatering = getDelaySinceLastWatering($lastWateringsFilename, $dateToDay);

        // Get watering time :
        $delayOfWatering = getDelayOfWatering($temperatureToday, $delaySinceLastWatering);

        // Recheck to be safe (but already done in getDelayOfWatering()) :
        if (DELAY_WATERING_MIN <= $delayOfWatering && $delayOfWatering <= DELAY_WATERING_MAX) {
            // Open then close the pump :
            $isOkOpen = openThenCloseCarefullyThePump($delayOfWatering);

            // Send a notification :
            if ($isOkOpen !== false) {
                // Save the date of this watering :
                $isOkSave = setInFile($lastWateringsFilename, $toDay);
                $dateNow = new DateTime('NOW');
                sendNotification(
                    "The garden have been successfully watered today. \n" .
                    "Today temperature : " . $temperatureToday . "C \n" .
                    "Temperature for start watering : " . TEMPERATURE_FOR_WATERING_MIN . "C \n" .
                    "Today precipitation : " . $precipitationToday . "mm \n" .
                    "Quantity of precipitation for detect a raining day : " . PRECIPITATION_FOR_DETECT_A_RAINING_DAY . "mm \n" .
                    "Delay since last watering : " . $delaySinceLastWatering . " days \n" .
                    "Delay minimal since last watering : " . DELAY_MIN_SINCE_LAST_WATERING . " days \n".
                    "Delay max of consecutive pump and valve running : " . DELAY_MAX_CONSECUTIVE_RUNNING . " minutes \n".
                    "Waiting delay to avoid pump or valve overheated : " . DELAY_TO_WAIT_BETWEEN_RUNNING . " minutes \n".
                    "Delay of watering : " . $delayOfWatering . "min \n" .
                    "Date of watering start : " . $dateToDay->format('Y-m-d H:i:s') . "\n" .
                    "Date of watering end : " . $dateNow->format('Y-m-d H:i:s') . "\n"
                );
            } else {
                sendNotification(
                    'The garden has probably not been watered today because ' .
                    ' a error occurred during handling the relay module. It would be a good idea ' .
                    ' the go to check the hardware system.'
                );
            }
        } else {
            sendNotification(
                "The garden hasn't been watered today. \n" .
                "Today temperature : " . $temperatureToday . "C \n" .
                "Temperature for start watering : " . TEMPERATURE_FOR_WATERING_MIN . "C \n" .
                "Today precipitation : " . $precipitationToday . "mm \n" .
                "Quantity of precipitation for detect a raining day : " . PRECIPITATION_FOR_DETECT_A_RAINING_DAY . "mm \n" .
                "Delay since last watering : " . $delaySinceLastWatering . " days \n" .
                "Delay minimal since last watering : " . DELAY_MIN_SINCE_LAST_WATERING . " days \n"
            );
        }
    } else {
        sendNotification('The garden hasn\'t been watered today because cannot get the today temperature and precipitation from APIXU.');
    }
}

/**
 * Open the pump during the DELAY_WATERING_MIN delay.
 */
function waterTheGardenNow() {
    // Get today date :
    $dateToDay = new DateTime('NOW');
    $toDay = $dateToDay->format(DATE_FORMAT);

    // Open then close the pump during the minimum delay :
    $isOkOpen = openThenCloseCarefullyThePump(DELAY_WATERING_MIN);

    // Send a notification :
    if ($isOkOpen !== false) {
        // Save the date of this watering :
        $lastWateringsFilename = dirname(__FILE__) . DIRECTORY_SEPARATOR . LAST_WATERINGS_FILENAME;
        $isOkSave = setInFile($lastWateringsFilename, $toDay);
        $dateNow = new DateTime('NOW');
        sendNotification(
            "The garden have been successfully manually watered today. \n" .
            "Delay max of consecutive pump and valve running : " . DELAY_MAX_CONSECUTIVE_RUNNING . " minutes \n".
            "Waiting delay to avoid pump or valve overheated : " . DELAY_TO_WAIT_BETWEEN_RUNNING . " minutes \n".
            "Delay of watering : " . DELAY_WATERING_MIN . "min \n" .
            "Date of watering start : " . $dateToDay->format('Y-m-d H:i:s') . "\n" .
            "Date of watering end : " . $dateNow->format('Y-m-d H:i:s') . "\n"
        );
    } else {
        sendNotification(
            'The garden has probably not been watered today because ' .
            'a error occurred during handling the relay module. It would be a good idea ' .
            'to go to check the hardware system. ' .
            'Error : ' . $isOkOpen
        );
    }
}

/**
 * Open then close the pump with taking care of risk of overheated for the pump and the valve.
 * If the $delayOfWatering if greater than the DELAY_MAX_CONSECUTIVE_RUNNING, than a sub sequence
 * of watering will created, with waiting delay bewteen running times, in order to let the pump
 * and valve get cold.
 * Return false if error occurred, true else.
 *
 * @return bool
 */
function openThenCloseCarefullyThePump($delayOfWatering) {
    $isOk = true;

    // Get number of sub round of watering needed to avoid overheating :
    $nbrOfWateringRound = floor($delayOfWatering / DELAY_MAX_CONSECUTIVE_RUNNING);
    for ($i = 0; $i < $nbrOfWateringRound; $i++) {
        $isOkOpen = openThenCloseThePump(DELAY_MAX_CONSECUTIVE_RUNNING);
        $secondes = DELAY_TO_WAIT_BETWEEN_RUNNING * 60;
        $isOkSleep = sleep($secondes);
        $isOk = $isOk && $isOkOpen && ($isOkSleep !== false);
    }

    // Watering of the eventually rest of delay of watering :
    $restOfDelayOfWatering = $delayOfWatering % DELAY_MAX_CONSECUTIVE_RUNNING;
    if ($restOfDelayOfWatering !== 0) {
        $isOkRestOpen = openThenCloseThePump($restOfDelayOfWatering);
        $isOk = $isOk && $isOkRestOpen;
    }

    return $isOk;
}

/**
 * Open then close the pump.
 * Return false if error occurred, true else.
 *
 * @return bool
 */
function openThenCloseThePump($delayOfWatering) {
    $isOk = false;

    // Initialize the pin :
    $gpio = new GPIO();
    $isOkSetup = $gpio->setup(PIN_NUMERO, "out");
    if ($isOkSetup !== false) {
        // Open the pump :
        $isOkOutPutOne = $gpio->output(PIN_NUMERO, 1);
        if ($isOkOutPutOne !== false) {
            // Wait during the watering time :
            $seconds = $delayOfWatering * 60;
            $isOkSleep = sleep($seconds);
            if ($isOkSleep !== false) {
                // Close the pump :
                $isOkOutPutZero = $gpio->output(PIN_NUMERO, 0);
                if ($isOkOutPutZero !== false) {
                    $isOkUnexport = $gpio->unexportAll();
                    if ($isOkUnexport !== false) {
                        $isOk = true;
                    } else {
                        sendNotification('Cannot unexport the pin numero ' . PIN_NUMERO);
                    }
                } else {
                    sendNotification('Cannot close the pin numero ' . PIN_NUMERO);
                }
            } else {
                sendNotification('Cannot sleep for ' . $delayOfWatering . ' minutes');
            }
        } else {
            sendNotification('Cannot open the pin numero ' . PIN_NUMERO);
        }
    } else {
        sendNotification('Cannot initialize the pin numero ' . PIN_NUMERO);
    }

    return $isOk;
}

/**
 * Return the delay in days between the last watering day and the given date.
 *
 * @param string $lastWateringsFilename
 * @param DateTime $dateTime
 *
 * @return int
 */
function getDelaySinceLastWatering($lastWateringsFilename, $dateTime) {
    $delaySinceLastWatering = 1000;

    // Get existing content :
    $contentJson = file_get_contents($lastWateringsFilename);
    if ($contentJson !== false) {
        $content = json_decode($contentJson, true);
        if (!empty($content)) {
            $maxInterval = 1000;

            // For each date from file :
            foreach ($content as $dateFromFile) {
                $dateTimeFromFile = DateTime::createFromFormat(DATE_FORMAT, $dateFromFile);
                $interval = $dateTimeFromFile->diff($dateTime)->format('%a');
                if ($interval < $maxInterval) {
                    // Save as max interval :
                    $maxInterval = $interval;
                }
            }
            $delaySinceLastWatering = $maxInterval;
        }
    } else {
        sendNotification('Cannot get content from file ' . $lastWateringsFilename . '. If it is the first watering, it is normal.');
    }

    return $delaySinceLastWatering;
}


/**
 * Store in the given file a entry with the given date.
 * Return false if error occurred, true else.
 *
 * @return bool
 */
function setInFile($fileName, $date) {
    $isOk = true;

    // Get existing content :
    $contentJson = file_get_contents($fileName);
    if ($contentJson !== false) {
        $content = json_decode($contentJson, true);

        // If not yet content :
        if (empty($content)) {
            // Create new content :
            $newContent = json_encode(array($date => $date));
            $putReturn = file_put_contents($fileName, $newContent);
            if ($putReturn === false) {
                $isOk = false;
                sendNotification('Cannot save ' . $date . ' in file ' . $fileName);
            }
        } else {
            // Add the new value :
            $content[$date] = $date;
            $newContentJson = json_encode($content);
            $putReturn = file_put_contents($fileName, $newContentJson);
            if ($putReturn === false) {
                $isOk = false;
                sendNotification('Cannot save ' . $date . ' in file ' . $fileName);
            }
        }
    } else {
        $isOk = false;
        sendNotification('Cannot get content from file ' . $fileName);
    }

    return $isOk;
}

/**
 * Get the delay computed with the given temperature and the given delay since last watering.
 *
 * @return float
 */
function getDelayOfWatering($temperature, $delaySinceLastWatering) {
    $delayOfWatering = 0;

    if (DELAY_MIN_SINCE_LAST_WATERING <= $delaySinceLastWatering && TEMPERATURE_FOR_WATERING_MIN <= $temperature) {
        $v = (DELAY_WATERING_MAX - DELAY_WATERING_MIN) / (TEMPERATURE_FOR_WATERING_MAX - TEMPERATURE_FOR_WATERING_MIN);
        $a = (DELAY_WATERING_MAX - DELAY_WATERING_MIN + TEMPERATURE_FOR_WATERING_MIN * $v) / TEMPERATURE_FOR_WATERING_MAX;
        $b = DELAY_WATERING_MIN - TEMPERATURE_FOR_WATERING_MIN * $v;
        $delayOfWatering = $a * $temperature + $b;

        // Clamp with max delay :
        if (DELAY_WATERING_MAX < $delayOfWatering) {
            $delayOfWatering = DELAY_WATERING_MAX;
        }
    }

    return $delayOfWatering;
}

/**
 * Return the temperature and precipitation of given date, with the APIXU api.
 * Return false if error occurred, true else.
 *
 * @return array|bool
 */
function getTemperaturePrecipitation($date) {
    $temperaturePrecipitation = false;

    // Get weather from APIXU  :
    $url = 'http://api.apixu.com/v1/history.json?key=' . APIXU_KEY . '&q=' . APIXU_CITY . '&dt=' . $date;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $responseJson = curl_exec($curl);
    $response = json_decode($responseJson);

    // Check reponse :
    if (!empty($response)) {
        if (!property_exists($response, 'error')) {
            if (property_exists($response, 'forecast') && property_exists($response->forecast, 'forecastday')) {
                $forecastday = reset($response->forecast->forecastday);
                if ($forecastday !== false) {
                    if (property_exists($forecastday, 'day') &&
                        property_exists($forecastday->day, 'maxtemp_c') &&
                        property_exists($forecastday->day, 'totalprecip_mm')) {
                        // Save max temp and precipitation :
                        $temperaturePrecipitation = array(
                            'temperature'   => $forecastday->day->maxtemp_c,
                            'precipitation' => $forecastday->day->totalprecip_mm
                        );
                    } else {
                        sendNotification('Cannot get maxtemp_c or totalprecip_mm properties from APIXU response');
                    }
                } else {
                    sendNotification('Cannot get forecastday value from APIXU response');
                }
            } else {
                sendNotification('Cannot get forecast and forecastday properties from APIXU response');
            }
        } else {
            sendNotification(
                'Error from the APIXU response.' .
                ' Code : ' . $response->error->code . ', message : ' . $response->error->message
            );
        }
    } else {
        sendNotification('Cannot get response from APIXU');
    }

    return $temperaturePrecipitation;
}

/**
 * Send notification in terminal and by email.
 */
function sendNotification($message) {
    echo $message;
    mail(EMAIL_TO, 'Raspberry garden watering notification', $message);
}
