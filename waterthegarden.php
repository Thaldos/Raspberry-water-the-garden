<?php

include_once 'config.php';
require 'library/vendor/autoload.php';

use PhpGpio\Gpio;

const DATE_FORMAT = 'Y-m-d';
const LAST_PRECIPITATIONS_FILENAME = 'lastprecipitations.txt';

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
        savePrecipitation($todayPrecipitation);

        // Get delay since last watering :
        $delaySinceLastWatering = getDelaySinceLastWatering();

        // Get delay since last raining :
        $delaySinceLastRaining = getDelaySinceLastRaining();

        // Get watering time :
        $wateringTime = getWateringTime($todayTemperature, $delaySinceLastRaining, $delaySinceLastWatering);

        // Open then close the pump :
        $isOkManage = openThenCloseThePump($wateringTime);

        // Send a goo notification :
        if ($isOkManage !== false) {
            $dateNow = new DateTime('NOW');
            //sendNotification('The garden have been successfully watered during ' . $wateringTime . ' minutes between '
             //   . $dateToDay->format('Y-m-d H:i:s') . ' and ' . $dateNow->format('Y-m-d H:i:s') . '.');
        }
    }
}

/**
 * @return int
 */
function getDelaySinceLastWatering() {
    $delaySinceLastWatering = 0;

    // Get existing content :
    $contentJson = file_get_contents(LAST_PRECIPITATIONS_FILENAME);
    if ($contentJson !== false) {
        $content = json_decode($contentJson, true);
        if (!empty($content)) {
            // Get today date :
            $dateToDay = new DateTime('NOW');

            // Get date bewteen today and the last watering :
            for ($i = 0; $i <= DELAY_SINCE_LAST_WATERING; $i++) {
                $dateToDay->add('');
            }
        }
    } else {
        sendNotification('Cannot get content from file ' . LAST_PRECIPITATIONS_FILENAME);
    }

    return $delaySinceLastWatering;
}

/**
 * @return int
 */
function getDelaySinceLastRaining() {
    $delaySinceLastRaining = 0;

    return $delaySinceLastRaining;
}

/**
 *
 */
function openThenCloseThePump($wateringTime) {
    $isOk = true;

    if (DELAY_WATERING_MIN <= $wateringTime && $wateringTime <= DELAY_WATERING_MIN) {
        // Initialize the pin :
        //$gpio = new GPIO();
        //$gpio->setup(INTERRUPTOR_PIN_NUMERO, "out");

        // Open the pump :
        // $gpio->output(INTERRUPTOR_PIN_NUMERO, 1);

        // Wait during the watering time :
        //sleep($wateringTime);

        // Close the pump :
        // $gpio->output(INTERRUPTOR_PIN_NUMERO, 0);
    }

    return $isOk;
}

/**
 *
 */
function savePrecipitation($precipitation) {
    $isOk = true;

    // Get today date :
    $dateToDay = new DateTime('NOW');
    $toDay = $dateToDay->format('d');

    // Get existing content :
    $contentJson = file_get_contents(LAST_PRECIPITATIONS_FILENAME);
    if ($contentJson !== false) {
        $content = json_decode($contentJson, true);

        // If not yet content :
        if (empty($content)) {
            // Create new content :
            $newContent = json_encode(array($toDay => $precipitation));
            $putReturn = file_put_contents(LAST_PRECIPITATIONS_FILENAME, $newContent);
            if ($putReturn === false) {
                $isOk = false;
                sendNotification('Cannot save precipitation ' . $precipitation . ' in file ' . LAST_PRECIPITATIONS_FILENAME);
            }
        } else {
            // Add the precipitation :
            $content[$toDay] = $precipitation;
            $newContentJson = json_encode($content);
            $putReturn = file_put_contents(LAST_PRECIPITATIONS_FILENAME, $newContentJson);
            if ($putReturn === false) {
                $isOk = false;
                sendNotification('Cannot save precipitation ' . $precipitation . ' in file ' . LAST_PRECIPITATIONS_FILENAME);
            }
        }
    } else {
        $isOk = false;
        sendNotification('Cannot get content from file ' . LAST_PRECIPITATIONS_FILENAME);
    }

    return $isOk;
}

/**
 *
 */
function getWateringTime($temperatureToday, $lastPrecipitations, $lastWatering)
{
    $wateringTime = 0;

    // If the today temperature is more that the threshold and the sum of last two days precipitations is less that the threshold :
    //if ($tempPrecipToday['temperature'] > TEMPERATURE_THRESHOLD &&
    //    $tempPrecipToday['precipitation'] + $tempPrecipYesterday['precipitation'] < PRECIPITATION_THRESHOLD) {

    //}
    //if ($wateringTime > WATERING_TIME_MAX) {
    //    $wateringTime = WATERING_TIME_MAX;
    //}

    return $wateringTime;
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
