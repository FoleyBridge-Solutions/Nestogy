<?php

// Read the CSV file and find all O'Connor addresses in 78247
$csvFile = '/tmp/TX-County-FIPS-029.csv';
$handle = fopen($csvFile, 'r');

// Skip header
fgetcsv($handle);

echo "🔍 Searching for O'Connor addresses in 78247...\n\n";

$foundAddresses = [];
while (($data = fgetcsv($handle)) !== FALSE) {
    if (count($data) >= 27) {
        $street = $data[5]; // Street
        $city = $data[8]; // Postal City
        $zip = $data[10]; // Zip
        $from = intval($data[1]); // From
        $to = intval($data[2]); // To
        
        if ($street === "O'CONNOR" && $zip === '78247' && $from <= 17422 && $to >= 17422) {
            $foundAddresses[] = [
                'from' => $from,
                'to' => $to,
                'county_taid' => $data[18],
                'city_taid' => $data[19], 
                'transit1_taid' => $data[20],
                'transit2_taid' => $data[21],
                'spd1_taid' => $data[22],
                'spd2_taid' => $data[23],
                'spd3_taid' => $data[24],
                'spd4_taid' => $data[25]
            ];
        }
    }
}

fclose($handle);

echo "📊 Found " . count($foundAddresses) . " matching addresses:\n\n";

foreach ($foundAddresses as $addr) {
    echo "🏠 Address Range: {$addr['from']}-{$addr['to']}\n";
    echo "   County TAID: {$addr['county_taid']}\n";
    echo "   City TAID: {$addr['city_taid']}\n";
    echo "   Transit 1: {$addr['transit1_taid']}\n";
    echo "   Transit 2: {$addr['transit2_taid']}\n";
    echo "   SPD 1: {$addr['spd1_taid']}\n";
    echo "   SPD 2: {$addr['spd2_taid']}\n";
    echo "   SPD 3: {$addr['spd3_taid']}\n";
    echo "   SPD 4: {$addr['spd4_taid']}\n\n";
}

echo "🎯 CSV analysis completed\n";