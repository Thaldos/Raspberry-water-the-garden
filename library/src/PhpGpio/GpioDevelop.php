<?php

namespace PhpGpio;

/**
 * This Gpio class is for developing not in raspberry environment
 * There is no need for root user and special file structure.
 *
 * @author Vaidas Lažauskas <vaidas@notrix.lt>
 */
class GpioDevelop implements GpioInterface
{
    /**
     * @var array
     */
    public $pins = array(14, 15, 17, 18);

    /**
     * @var array
     */
    public $hackablePins = array(17, 18);

    /**
     * @var int
     */
    public $inputValue = GpioInterface::IO_VALUE_OFF;

    /**
     * @var string
     */
    public $direction = GpioInterface::DIRECTION_OUT;

    /**
     * getHackablePins : the pins you can hack with.
     * @link http://elinux.org/RPi_Low-level_peripherals
     *
     * @return array
     */
    public function getHackablePins()
    {
        return $this->hackablePins;
    }

    /**
     * Setup pin, takes pin number and direction (in or out)
     *
     * @param  int    $pinNo
     * @param  string $direction
     *
     * @return GpioDevelop or boolean false
     */
    public function setup($pinNo, $direction)
    {
        return $this;
    }

    /**
     * Get input value
     *
     * @param  int   $pinNo
     *
     * @return int GPIO value or boolean false
     */
    public function input($pinNo)
    {
        return $this->inputValue;
    }

    /**
     * Set output value
     *
     * @param  int    $pinNo
     * @param  string $value
     *
     * @return GpioDevelop or boolean false
     */
    public function output($pinNo, $value)
    {
        return $this;
    }

    /**
     * Unexport Pin
     *
     * @param  int $pinNo
     *
     * @return GpioDevelop or boolean false
     */
    public function unexport($pinNo)
    {
        return $this;
    }

    /**
     * Unexport all pins
     *
     * @return GpioDevelop or boolean false
     */
    public function unexportAll()
    {
        return $this;
    }

    /**
     * Check if pin is exported
     *
     * @param int $pinNo
     *
     * @return boolean
     */
    public function isExported($pinNo)
    {
        return in_array($pinNo, $this->pins) || in_array($pinNo, $this->hackablePins);
    }

    /**
     * get the pin's current direction
     *
     * @param int $pinNo
     *
     * @return string pin's direction value or boolean false
     */
    public function currentDirection($pinNo)
    {
        return $this->direction;
    }

    /**
     * Check for valid direction, in or out
     *
     * @param string $direction
     *
     * @return boolean
     */
    public function isValidDirection($direction)
    {
        return $direction == GpioInterface::DIRECTION_IN || $direction == GpioInterface::DIRECTION_OUT;
    }

    /**
     * Check for valid output value
     *
     * @param mixed $output
     *
     * @return boolean
     */
    public function isValidOutput($output)
    {
        return $output == GpioInterface::IO_VALUE_ON || $output == GpioInterface::IO_VALUE_OFF;
    }

    /**
     * Check for valid pin value
     *
     * @param int $pinNo
     *
     * @return boolean
     */
    public function isValidPin($pinNo)
    {
        return in_array($pinNo, $this->pins) || in_array($pinNo, $this->hackablePins);
    }
}
