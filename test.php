<?php

require __DIR__ . '/vendor/autoload.php';

// Store the current temperature into file :
$waterTheGardenService = new WaterTheGardenService();
$waterTheGardenService->waitAndGetNumberOfFlowPulses(10);
