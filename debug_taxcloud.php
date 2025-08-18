<?php

echo "TaxCloud API Debug Information\n\n";

// The original base64 string you provided
$original = "dHhjMl9fblVrdEhIWm5vR0M3NXZ4RVNYRzhyN3h6cXlyWUdVTzc6c2NTRTQ3dDVoenlLc1gzbjdwWWNxZmVWcHJCZzJ3dVNYa0Z4bU1sMzJNa2VhYkpzSTlFZi1vSmEyTU53WTdhdQ==";
$decoded = base64_decode($original);

echo "Original base64: " . $original . "\n";
echo "Decoded: " . $decoded . "\n\n";

// Split by colon
$parts = explode(':', $decoded);
echo "Split by colon:\n";
echo "Part 1 (Login ID): " . $parts[0] . "\n";
echo "Part 2 (API Key): " . $parts[1] . "\n\n";

// The error suggests TaxCloud expects different format
// Let me check if we need to use the same value for customer ID or if it should be different

echo "Current .env configuration:\n";
echo "TAXCLOUD_API_LOGIN_ID=" . $parts[0] . "\n";
echo "TAXCLOUD_API_KEY=" . $parts[1] . "\n";
echo "TAXCLOUD_CUSTOMER_ID=" . $parts[0] . " (using login ID)\n\n";

echo "TaxCloud typically expects:\n";
echo "- API Login ID: Usually starts with 'api' or is a UUID\n";
echo "- API Key: A long random string\n";
echo "- Customer ID: Often same as Login ID or a separate identifier\n\n";

echo "The error 'Could not find any recognizable digits' suggests:\n";
echo "1. The credentials might be for a different service\n";
echo "2. TaxCloud might be expecting a different format\n";
echo "3. The account might not be properly set up\n\n";

echo "To properly set up TaxCloud:\n";
echo "1. Sign up at https://taxcloud.com\n";
echo "2. Get your API Login ID and API Key from the account dashboard\n";
echo "3. The Login ID is typically your account identifier\n";
echo "4. The API Key is a separate authentication token\n";