<?php

include_once 'config.php';
require 'library/vendor/autoload.php';

use PhpGpio\Gpio;

const DATE_FORMAT = 'Y-m-d';
const LAST_WATERINGS_FILENAME = 'lastwaterings.txt';

/**
 *
 */
function waterTheGarden()
{
    // Get today date :
    $dateToDay = new DateTime('NOW');
    $toDay = $dateToDay->format(DATE_FORMAT);

    // Get the max temperature and total precipitation of today :
    $temperaturePrecipitationToday = getTemperaturePrecipitation($toDay);
    if ($temperaturePrecipitationToday !== false) {
        $todayPrecipitation = $temperaturePrecipitationToday['precipitation'];
        $todayTemperature = $temperaturePrecipitationToday['temperature'];

        // Save the today precipitation in text file :
        if (QUANTITY_OF_PRECIPITATION_FOR_DETECT_LAST_RAINING < $todayPrecipitation) {
            $isOkSave = setInFile(LAST_WATERINGS_FILENAME, $toDay);
        }

        // Get delay since last watering :
        $delaySinceLastWatering = getDelaySinceLastWatering($dateToDay);

        // Get watering time :
        $wateringTime = getDelayForWatering($todayTemperature, $delaySinceLastWatering);

        // Open then close the pump :
        $isOkManage = openThenCloseThePump($wateringTime);

        // Send a goo notification :
        if ($isOkManage !== false) {
            // Save the date of this watering :
//            $isOkSave = setInFile(LAST_WATERINGS_FILENAME, $toDay);

            $dateNow = new DateTime('NOW');
            //sendNotification('The garden have been successfully watered during ' . $wateringTime . ' minutes between '
             //   . $dateToDay->format('Y-m-d H:i:s') . ' and ' . $dateNow->format('Y-m-d H:i:s') . '.');
        }
    }
}

/**
 *
 */
function openThenCloseThePump($wateringTime) {
    $isOk = true;

//    if (DELAY_WATERING_MIN <= $wateringTime && $wateringTime <= DELAY_WATERING_MIN) {
        // Initialize the pin :
        //$gpio = new GPIO();
        //$gpio->setup(INTERRUPTOR_PIN_NUMERO, "out");

        // Open the pump :
        // $gpio->output(INTERRUPTOR_PIN_NUMERO, 1);

        // Wait during the watering time :
        //sleep($wateringTime);

        // Close the pump :
        // $gpio->output(INTERRUPTOR_PIN_NUMERO, 0);
//    }

    return $isOk;
}

/**
 * @return int
 */
function getDelaySinceLastWatering($dateTime) {
    $delaySinceLastWatering = 0;

    // Get existing content :
    $contentJson = file_get_contents(LAST_WATERINGS_FILENAME);
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
        sendNotification('Cannot get content from file ' . LAST_WATERINGS_FILENAME);
    }

    return $delaySinceLastWatering;
}


/**
 *
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
 *
 */
function getDelayForWatering($temperatureToday, $delaySinceLastWatering)
{
    $delayForWatering = 0;

    if (DELAY_MIN_SINCE_LAST_WATERING < $delaySinceLastWatering &&
        TEMPERATURE_FOR_START_WATERING < $temperatureToday) {
        $delayForWatering = DELAY_WATERING_MIN;

        if (DELAY_WATERING_MAX < $delayForWatering) {
            $delayForWatering = DELAY_WATERING_MAX;
        }
    }

    // If the today temperature is more that the threshold and the sum of last two days precipitations is less that the threshold :
    //if ($tempPrecipToday['temperature'] > TEMPERATURE_THRESHOLD &&
    //    $tempPrecipToday['precipitation'] + $tempPrecipYesterday['precipitation'] < PRECIPITATION_THRESHOLD) {

    //}


    return $delayForWatering;
}

/**
 *
 */
function getTemperaturePrecipitation($date)
{
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
                            'temperature' => $forecastday->day->maxtemp_c,
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
            sendNotification('Error from the APIXU response : ' . $response->error->message);
        }
    } else {
        sendNotification('Cannot get response from APIXU');
    }

    return $temperaturePrecipitation;
}

/**
 * Send notification in terminal and by email.
 */
function sendNotification($message)
{
    var_dump($message);
    mail(EMAIL_TO, 'Raspberry garden watering notification', $message);
}

waterTheGarden();
