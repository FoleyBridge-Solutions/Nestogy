<?php

// Read the CSV file and find ranges that cover 17422 O'Connor
$csvFile = '/tmp/TX-County-FIPS-029.csv';
$handle = fopen($csvFile, 'r');

// Skip header
fgetcsv($handle);

echo "🔍 Searching for address ranges covering 17422 O'Connor in 78247...\n\n";

$targetAddress = 17422;
$foundRanges = [];

while (($data = fgetcsv($handle)) !== FALSE) {
    if (count($data) >= 27) {
        $street = $data[5]; // Street
        $zip = $data[10]; // Zip
        $from = intval($data[1]); // From
        $to = intval($data[2]); // To
        
        // Check if this range covers our target address
        if (($street === "O'CONNOR" || $street === "OCONNOR") && 
            $zip === '78247' && 
            $from <= $targetAddress && 
            $to >= $targetAddress) {
            
            $foundRanges[] = [
                'street' => $street,
                'from' => $from,
                'to' => $to,
                'county_taid' => trim($data[18]),
                'city_taid' => trim($data[19]), 
                'transit1_taid' => trim($data[20]),
                'transit2_taid' => trim($data[21]),
                'spd1_taid' => trim($data[22]),
                'spd2_taid' => trim($data[23]),
                'spd3_taid' => trim($data[24]),
                'spd4_taid' => trim($data[25])
            ];
        }
    }
}

fclose($handle);

echo "📊 Found " . count($foundRanges) . " ranges covering 17422:\n\n";

foreach ($foundRanges as $range) {
    echo "🏠 {$range['street']}: {$range['from']}-{$range['to']}\n";
    echo "   County TAID: '{$range['county_taid']}'\n";
    echo "   City TAID: '{$range['city_taid']}'\n";
    echo "   Transit 1: '{$range['transit1_taid']}'\n";
    echo "   Transit 2: '{$range['transit2_taid']}'\n";
    echo "   SPD 1: '{$range['spd1_taid']}'\n";
    echo "   SPD 2: '{$range['spd2_taid']}'\n";
    echo "   SPD 3: '{$range['spd3_taid']}'\n";
    echo "   SPD 4: '{$range['spd4_taid']}'\n\n";
}

echo "🎯 Analysis completed\n";