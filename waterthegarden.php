<?php

require __DIR__ . '/vendor/autoload.php';

// Get given mode :
$mode = $argv[1] ?? WaterTheGardenService::MODE_COMPUTED_DELAY;

// Water the garden :
$waterTheGardenService = new WaterTheGardenService();
$waterTheGardenService->waterTheGarden($mode);
