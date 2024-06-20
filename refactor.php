<?php
function getPhpFiles($dir) {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    $files = array(); 

    foreach ($rii as $file) {
        if ($file->isDir()){ 
            continue;
        }

        if (pathinfo($file->getPathname(), PATHINFO_EXTENSION) === 'php') {
            $files[] = $file->getPathname(); 
        }
    }

    return $files;
}
function refactorFile($file) {
    $contents = file_get_contents($file);

    // Pattern to match mysqli_query constructions with variables
    $pattern = '/mysqli_query\s*\(\s*\$mysqli\s*,\s*"(INSERT|UPDATE|DELETE)\s+INTO\s+([a-zA-Z0-9_]+)\s+SET\s+([^"]+)"\s*\)/i';

    if (preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $originalQuery = $match[0];
            $queryType = $match[1];
            $tableName = $match[2];
            $setClause = $match[3];

            // Extract column names and variable names
            preg_match_all('/([a-zA-Z0-9_]+)\s*=\s*\$([a-zA-Z0-9_]+)/', $setClause, $columnMatches, PREG_SET_ORDER);

            $columns = [];
            $variables = [];
            foreach ($columnMatches as $columnMatch) {
                $columns[] = $columnMatch[1];
                $variables[] = '$' . $columnMatch[2];
            }

            // Construct prepared statement
            $bindTypes = str_repeat("s", count($columns)); // Assuming all variables are strings
            $bindParams = implode(', ', array_map(fn($var) => "&$var", $variables));
            $columnsList = implode(', ', $columns);
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));

            $preparedQuery = <<<EOD
\$stmt = \$mysqli->prepare("$queryType INTO $tableName SET $columnsList = $placeholders");
\$stmt->bind_param("$bindTypes", $bindParams);
\$stmt->execute();
\$result = \$stmt->get_result();
EOD;

            // Replace the original query with the prepared statement
            $refactoredContents = str_replace($originalQuery, $preparedQuery, $contents);
            file_put_contents($file, $refactoredContents);
        }
    }
}


$directory = '/var/www/portal.twe.tech';
$phpFiles = getPhpFiles($directory);

foreach ($phpFiles as $file) {
    refactorFile($file);
}
