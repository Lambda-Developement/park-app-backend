<?php
require 'Database.php';

$db = new Database();

echo "START\n";

foreach ($db->getParkingList() as $parray) {
    $park = $parray[0];
    $busyness = exec("python3 ml/Classificator.py");
    try {
        $db->assignOccupiedValue($park, $busyness);
        echo "+ {$park}\n";
    } catch (ENotFoundException $e) {
        echo "!! - {$park}\n";
        continue;
    }
}

echo "Done\n";
