<?php

use Symfony\Component\HttpClient\HttpClient;
use PhpGpio\Gpio;
use Symfony\Component\Dotenv\Dotenv;

class WaterTheGardenService
{
    const MODE_NOW = 'now';
    const MODE_COMPUTED_DELAY = 'computed';
    const MODE_RESET_HARDWARE = 'reset';
    const DATE_FORMAT_SHORT = 'Y-m-d';
    const DATE_FORMAT_LONG = 'Y-m-d H:i:s';
    const ERROR_VALUE = 100000000000;
    const LAST_TEMPERATURE_FILENAME = 'lasttemperature.txt';
    const LAST_WATERING_FILENAME = 'lastwatering.txt';

    public function __construct()
    {
        $dotenv = new Dotenv();
        $dotenv->load(__DIR__ . '/../.env');
    }

    /**
     * Open the pump for the appropriate delay.
     * Modes :
     * 		fixed : the garden is watered during $_ENV['DELAY_MIN'] min.
     *  	computed : the garden is watered during a delay computed from the today temperature and the delay since the last watering.
     * 		reset : open the relay, wait 10s, then close it.
     */
    public function waterTheGarden(string $mode): bool
    {
        $isOK = true;

        switch ($mode) {
            case self::MODE_NOW:
                $isOK = $this->waterTheGardenNow();
                break;
            case self::MODE_RESET_HARDWARE:
                $this->openThenCloseThePump(0.1);
                break;
            case self::MODE_COMPUTED_DELAY:
                $isOK = $this->waterTheGarderWithComputedDelay();
                break;
        }

        return $isOK;
    }

    /**
     * Open the pump during a computed delay.
     */
    public function waterTheGarderWithComputedDelay(): bool
    {
        $isOk = false;

        // Get today date :
        $todayDatetime = new DateTime();
        $todayStr = $todayDatetime->format(self::DATE_FORMAT_SHORT);

        // FilesPaths :
        $lastTemperaturePath = __DIR__ . '/' . self::LAST_TEMPERATURE_FILENAME;
        $lastWateringPath = __DIR__ . '/' . self::LAST_WATERING_FILENAME;

        // Get today temperature :
        $todayTemperature = $this->getValueFromFile($lastTemperaturePath);
        if ($todayTemperature !== self::ERROR_VALUE) {
            // Get delay since last watering :
            $delaySinceLastWatering = $this->getDelaySinceLastWatering($lastWateringPath);

            // If this delay allow to launch a watering today :
            if ($delaySinceLastWatering !== self::ERROR_VALUE) {
                if ($_ENV['DELAY_MIN_BETWEEN_WATERING'] <= $delaySinceLastWatering) {
                    // Get watering time :
                    $delayForWatering = $this->getDelayForWatering($todayTemperature);
                    if (0 < $delayForWatering) {
                        // Open then close the pump :
                        $numberOfFlowPulses = $this->openThenCloseThePumpCarefully($delayForWatering);
                        if ($numberOfFlowPulses !== 0) {
                            // Save the date of this watering :
                            $isOkSave = $this->storeInFile($lastWateringPath, $todayStr);
                            $isOk = true;

                            // Send a notification :
                            $dateNow = new DateTime();
                            $dayOrDays = (1 < $delaySinceLastWatering ? 'days' : 'day');
                            $this->sendNotification(
                                "The garden have been successfully watered today. \n" .
                                'Today temperature : ' . $todayTemperature . "C \n" .
                                'Temperature for start watering : ' . $_ENV['TEMPERATURE_FOR_DELAY_MIN'] . "C \n" .
                                'Delay since last watering : ' . $delaySinceLastWatering . ' ' . $dayOrDays . " \n" .
                                'Delay minimal between watering : ' . $_ENV['DELAY_MIN_BETWEEN_WATERING'] . ' ' . $dayOrDays . " \n" .
                                'Delay max of consecutive pump and valve running : ' . $_ENV['DELAY_MAX_RUNNING'] . " minutes \n" .
                                'Waiting delay to avoid pump or valve overheated : ' . $_ENV['DELAY_BETWEEN_RUNNING'] . " minutes \n" .
                                'Delay of watering : ' . $delayForWatering . " minutes \n" .
                                'Date of watering start : ' . $todayDatetime->format(self::DATE_FORMAT_LONG) . " \n" .
                                'Date of watering end : ' . $dateNow->format(self::DATE_FORMAT_LONG) . " \n" .
                                'Number of pulses measured by the flowmeter : ' . \number_format($numberOfFlowPulses, 0, ',', ' ')
                            );
                        } else {
                            $this->sendNotification(
                                "The garden has probably not been watered today because a error occurred during handling the relay module. \n" .
                                'It would be a good idea to check the hardware system.'
                            );
                        }
                    } else {
                        $this->sendNotification(
                            'No watering today because the today temperature was too low : ' . $todayTemperature . "C. \n" .
                            'The start is defined to ' . $_ENV['TEMPERATURE_FOR_DELAY_MIN'] . 'C.'
                        );
                    }
                } else {
                    $dayOrDays = (1 < $delaySinceLastWatering ? 'days' : 'day');
                    $this->sendNotification(
                        'No watering today because the last watering was ' . $delaySinceLastWatering . ' ' . $dayOrDays . " ago. \n" .
                        'The delay minimum between watering is defined to ' . $_ENV['DELAY_MIN_BETWEEN_WATERING'] . ' days.'
                    );
                }
            } else {
                $this->sendNotification('No watering today because cannot get the last watering date from file.');
            }
        } else {
            $this->sendNotification('No watering today because cannot get the today temperature from file.');
        }

        return $isOk;
    }

    /**
     * Open the pump during the DELAY_MIN delay.
     */
    public function waterTheGardenNow(): bool
    {
        $isOk = false;

        // Get today date :
        $todayDatetime = new DateTime();
        $todayStr = $todayDatetime->format(self::DATE_FORMAT_SHORT);

        // Open then close the pump during the minimum delay :
        $numberOfFlowPulses = $this->openThenCloseThePumpCarefully($_ENV['DELAY_MIN']);

        // Send a notification :
        if ($numberOfFlowPulses !== 0) {
            // Save the date of this watering :
            $lastWateringsPath = __DIR__ . '/' . self::LAST_WATERING_FILENAME;
            $isOkSave = $this->storeInFile($lastWateringsPath, $todayStr);
            $isOk = true;

            // Send a notification :
            $dateNow = new DateTime();
            $this->sendNotification(
                "The garden have been successfully manually watered today. \n" .
                'Delay of watering : ' . $_ENV['DELAY_MIN'] . "min \n" .
                'Date of watering start : ' . $todayDatetime->format(self::DATE_FORMAT_LONG) . " \n" .
                'Date of watering end : ' . $dateNow->format(self::DATE_FORMAT_LONG) . " \n" .
                'Number of pulses measured by the flowmeter : ' . \number_format($numberOfFlowPulses, 0, ',', ' ')
            );
        } else {
            $this->sendNotification(
                "The garden has probably not been watered today because a error occurred during handling the relay module. \n" .
                'It would be a good idea to check the hardware system.'
            );
        }

        return $isOk;
    }

    /**
     * Open then close the pump with taking care of risk of overheated for the pump and the valve.
     * If the $delayForWatering if greater than the DELAY_MAX_RUNNING, a sub sequence of watering
     * will created, with waiting delay between running times, in order to let the pump
     * and valve get cold.
     * Return the number of pulses measured by the flowmeter during this period.
     */
    public function openThenCloseThePumpCarefully(int $delayForWatering): int
    {
        $numberOfFlowPulses = 0;

        // Get number of sub round of watering needed to avoid overheating :
        $nbrOfWateringRound = floor($delayForWatering / $_ENV['DELAY_MAX_RUNNING']);
        for ($i = 0; $i < $nbrOfWateringRound; $i++) {
            $numberOfFlowPulses += $this->openThenCloseThePump($_ENV['DELAY_MAX_RUNNING']);
            $secondes = $_ENV['DELAY_BETWEEN_RUNNING'] * 60;
            \sleep($secondes);
        }

        // Watering of the eventually rest of delay of watering :
        $restOfDelayOfWatering = $delayForWatering % $_ENV['DELAY_MAX_RUNNING'];
        if ($restOfDelayOfWatering !== 0) {
            $numberOfFlowPulses += $this->openThenCloseThePump($restOfDelayOfWatering);
        }

        return $numberOfFlowPulses;
    }

    /**
     * Open then close the pump.
     * Return the number of pulses measured by the flowmeter during this period.
     */
    public function openThenCloseThePump(int $delayOfWatering): int
    {
        $numberOfFlowPulses = self::ERROR_VALUE;

        // Initialize the pin :
        $pin = (int) $_ENV['RELAY_PIN_NUMERO'];
        $gpio = new GPIO();
        $isOkSetup = $gpio->setup($pin, 'out');
        if ($isOkSetup !== false) {
            // Open the pump :
            $isOkOutPutOne = $gpio->output($pin, 1);
            if ($isOkOutPutOne !== false) {
                //  Wait during the watering time and get the flowmeter data :
                $delayOfWateringInSeconds = $delayOfWatering * 60;
                $numberOfFlowPulses = $this->waitAndGetNumberOfFlowPulses($delayOfWateringInSeconds);

                // Close the pump :
                $isOkOutPutZero = $gpio->output($pin, 0);
                if ($isOkOutPutZero !== false) {
                    $isOkUnexport = $gpio->unexportAll();
                    if ($isOkUnexport === false) {
                        $this->sendNotification('Cannot unexport the pin numero ' . $pin);
                    }
                } else {
                    $this->sendNotification('Cannot close the pin numero ' . $pin);
                }
            } else {
                $this->sendNotification('Cannot open the pin numero ' . $pin);
            }
        } else {
            $this->sendNotification('Cannot initialize the pin numero ' . $pin);
        }

        return $numberOfFlowPulses;
    }

    /**
     * Return the delay in days since the last watering day.
     */
    public function getDelaySinceLastWatering(string $lastWateringFilename): int
    {
        $delaySinceLastWatering = self::ERROR_VALUE;

        // Get existing content :
        $contentJson = file_get_contents($lastWateringFilename);
        if ($contentJson !== false) {
            $content = json_decode($contentJson, true);
            if (!empty($content)) {
                $todayDateTime = new DateTime();

                // For each date from file :
                foreach ($content as $dateFromFile) {
                    $dateTimeFromFile = DateTime::createFromFormat(self::DATE_FORMAT_SHORT, $dateFromFile);
                    $delaySinceLastWatering = $dateTimeFromFile->diff($todayDateTime)->format('%a');
                }
            }
        } else {
            $this->sendNotification('Cannot read content from file ' . $lastWateringFilename);
        }

        return $delaySinceLastWatering;
    }

    /**
     * Store the value in file.
     * Return false if error occurred, true else.
     */
    public function storeInFile(string $filePath, string $value): bool
    {
        $isOk = true;

        $todayDate = new DateTime('NOW');
        $todayStr = $todayDate->format(self::DATE_FORMAT_SHORT);

        // Create new content :
        $newContent = json_encode([$todayStr => $value]);
        $putReturn = file_put_contents($filePath, $newContent);
        if ($putReturn === false) {
            $isOk = false;
            $this->sendNotification('Cannot save ' . $todayStr . ' - ' . $value . ' in file ' . $filePath);
        }

        return $isOk;
    }

    /**
     * Return the today stored value.
     */
    public function getValueFromFile($filePath): float
    {
        $value = self::ERROR_VALUE;

        $contentJson = file_get_contents($filePath);
        if ($contentJson !== false) {
            $content = json_decode($contentJson, true);
            if (!empty($content)) {
                $todayDateTime = new DateTime();
                $todayStr = $todayDateTime->format(self::DATE_FORMAT_SHORT);
                foreach ($content as $date => $v) {
                    if ($date == $todayStr) {
                        $value = $v;
                    }
                }
            } else {
                $this->sendNotification('Nothing find in ' . $filePath . '.');
            }
        } else {
            $this->sendNotification('Cannot read file ' . $filePath . '.');
        }

        return $value;
    }

    /**
     * Get the delay computed with the given temperature.
     */
    public function getDelayForWatering(float $temperature): float
    {
        $delayOfWatering = 0;

        if ($_ENV['TEMPERATURE_FOR_DELAY_MIN'] <= $temperature) {
            $v = ($_ENV['DELAY_MAX'] - $_ENV['DELAY_MIN']) / ($_ENV['TEMPERATURE_FOR_DELAY_MAX'] - $_ENV['TEMPERATURE_FOR_DELAY_MIN']);
            $a = ($_ENV['DELAY_MAX'] - $_ENV['DELAY_MIN'] + $_ENV['TEMPERATURE_FOR_DELAY_MIN'] * $v) / $_ENV['TEMPERATURE_FOR_DELAY_MAX'];
            $b = $_ENV['DELAY_MIN'] - $_ENV['TEMPERATURE_FOR_DELAY_MIN'] * $v;
            $delayOfWatering = $a * $temperature + $b;

            // Clamp with max delay :
            if ($_ENV['DELAY_MAX'] < $delayOfWatering) {
                $delayOfWatering = $_ENV['DELAY_MAX'];
            }
        }

        return $delayOfWatering;
    }

    /**
     * Store in file the current temperature given by API.
     */
    public function storeCurrentTemperature(): bool
    {
        $isOk = true;

        // Get the current temperature :
        $temperature = $this->getCurrentTemperature();
        if ($temperature !== self::ERROR_VALUE) {
            $filePath = __DIR__ . '/' . self::LAST_TEMPERATURE_FILENAME;
            $isOk = $this->storeInFile($filePath, $temperature);
        } else {
            $isOk = false;
        }

        return $isOk;
    }

    /**
     * Get current temperature from API.
     */
    public function getCurrentTemperature(): float
    {
        $temperature = self::ERROR_VALUE;

        $client = HttpClient::create();
        $response = $client->request('GET', 'https://api.openweathermap.org/data/2.5/weather?q=' .
            $_ENV['API_CITY'] . '&appid=' . $_ENV['API_KEY'] . '&units=metric');
        $statusCode = $response->getStatusCode();
        if ($statusCode == 200) {
            $weatherData = $response->toArray();
            if (key_exists('main', $weatherData) && key_exists('temp', $weatherData['main'])) {
                $temperature = $weatherData['main']['temp'];
            } else {
                $this->sendNotification('Cannot get the temp value from the API response.');
            }
        } else {
            $this->sendNotification('Cannot get the today from API. Status code : ' . $statusCode . ', ' . $response->getContent());
        }

        return $temperature;
    }

    /**
     * Wait during the given period and return the number of flow pulses measured by the flowmeter during this period.
     * The period have to be in seconds.
     */
    public function waitAndGetNumberOfFlowPulses(int $period): int
    {
        $numberOfFlowPulses = 0;

        // Initialize the pin :
        $pin = (int) $_ENV['FLOW_METER_PIN_NUMERO'];
        $gpio = new GPIO();
        $isOkSetup = $gpio->setup($pin, 'in');
        if ($isOkSetup !== false) {
            $start = \time();
            $diff = 0;
            $secureCpt = 0;
            while ($diff < $period && $secureCpt < 10000000) {
                // update the timer :
                $diff = \time() - $start;

                // Read the pin :
                $input = $gpio->input($pin);
                if ($input === 0) {
                    $numberOfFlowPulses += 1;
                }

                // Secure counter to avoid a infinte loop :
                $secureCpt += 1;
            }
        } else {
            $this->sendNotification('Cannot initialize the pin numero ' . $pin);
        }

        return $numberOfFlowPulses;
    }

    /**
     * Send notification in terminal and by email.
     */
    public function sendNotification(string $message): void
    {
        echo $message;
        mail($_ENV['EMAIL_TO'], 'Raspberry garden watering notification', $message);
    }
}
