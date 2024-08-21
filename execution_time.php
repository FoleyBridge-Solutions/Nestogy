<?php
// Read the file
$filename = 'public/execution_time.txt';
$lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$execution_times = [];
$original_times = [];
// Process each line
foreach ($lines as $line) {
    // Extract the URL, timestamp, and execution time
    preg_match('/(\/public\/[^\s]*) @ ([\d-]+\s[\d:]+) - ([\d.]+) seconds/', $line, $matches);
    if (count($matches) === 4) {
        $url = $matches[1];
        $timestamp = $matches[2];
        $time = (float)$matches[3];

        // Normalize URLs ending with _id=x
        $normalized_url = preg_replace('/_id=\d+/', '_id', $url);

        // Aggregate the execution times
        if (!isset($execution_times[$normalized_url])) {
            $execution_times[$normalized_url] = [];
            $original_times[$normalized_url] = [];
        }
        $execution_times[$normalized_url][] = ['timestamp' => $timestamp, 'time' => $time];
        $original_times[$normalized_url][] = ['url' => $url, 'timestamp' => $timestamp, 'time' => $time];
    }
}

// Function to calculate IQR and detect outliers
function detect_outliers($times) {
    $time_values = array_column($times, 'time');
    sort($time_values);
    $count = count($time_values);
    $q1 = $time_values[(int)($count * 0.25)];
    $q3 = $time_values[(int)($count * 0.75)];
    $iqr = $q3 - $q1;
    $lower_bound = $q1 - 1.5 * $iqr;
    $upper_bound = $q3 + 1.5 * $iqr;

    $outliers = array_filter($times, function($entry) use ($lower_bound, $upper_bound) {
        return $entry['time'] < $lower_bound || $entry['time'] > $upper_bound;
    });

    return $outliers;
}

// Calculate detailed statistics for each URL and detect outliers
$statistics = [];
$outliers_data = [];
foreach ($execution_times as $url => $entries) {
    $times = array_column($entries, 'time');
    $count = count($times);
    $sum = array_sum($times);
    $average = $sum / $count;
    $min = min($times);
    $max = max($times);
    $variance = array_sum(array_map(function($time) use ($average) {
        return pow($time - $average, 2);
    }, $times)) / $count;
    $std_dev = sqrt($variance);
    $outliers = detect_outliers($entries);

    $statistics[$url] = [
        'count' => $count,
        'average' => round($average, 3),
        'min' => round($min, 3),
        'max' => round($max, 3),
        'std_dev' => round($std_dev, 3),
        'outliers' => $outliers,
        'entries' => $entries
    ];

    if (!empty($outliers)) {
        $outliers_data[$url] = array_filter($original_times[$url], function($entry) use ($outliers) {
            return in_array($entry, $outliers);
        });
    }
}

// Sort the URLs by average execution time in descending order
uasort($statistics, function($a, $b) {
    return $b['average'] <=> $a['average'];
});

// Output the detailed statistics and outliers as a Bootstrap-styled table
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Execution Time Statistics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Reload the page every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000); // 30000 milliseconds = 30 seconds
    </script>
</head>
<body>
<div class="container mt-5">
    <h1>Detailed Statistics for Slowest Pages</h1>
    <p>Standard deviation is a measure of the amount of variation or dispersion of a set of values. A low standard deviation indicates that the values tend to be close to the mean (average) of the set, while a high standard deviation indicates that the values are spread out over a wider range.</p>
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>URL</th>
                <th>Requests</th>
                <th>Average Time (seconds)</th>
                <th>Min Time (seconds)</th>
                <th>Max Time (seconds)</th>
                <th>Std Dev (seconds)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($statistics as $url => $stats): ?>
                <tr>
                    <td><?php echo htmlspecialchars($url); ?></td>
                    <td><?php echo $stats['count']; ?></td>
                    <td><?php echo $stats['average']; ?></td>
                    <td><?php echo $stats['min']; ?></td>
                    <td><?php echo $stats['max']; ?></td>
                    <td><?php echo $stats['std_dev']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Outliers</h2>
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>URL</th>
                <th>Original URL</th>
                <th>Timestamp</th>
                <th>Execution Time (seconds)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($outliers_data as $url => $outliers): ?>
                <?php foreach ($outliers as $outlier): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($url); ?></td>
                        <td><?php echo htmlspecialchars($outlier['url']); ?></td>
                        <td><?php echo htmlspecialchars($outlier['timestamp']); ?></td>
                        <td><?php echo round($outlier['time'], 3); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Execution Time Charts</h2>
    <?php foreach ($statistics as $url => $stats): ?>
        <h3><?php echo htmlspecialchars($url); ?></h3>
        <canvas id="chart-<?php echo md5($url); ?>" width="400" height="200"></canvas>
        <script>
            var ctx = document.getElementById('chart-<?php echo md5($url); ?>').getContext('2d');
            var chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_column($stats['entries'], 'timestamp')); ?>,
                    datasets: [{
                        label: 'Execution Time (seconds)',
                        data: <?php echo json_encode(array_column($stats['entries'], 'time')); ?>,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        fill: false,
                        tension: 0.1
                    }]
                },
                options: {
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Timestamp'
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Execution Time (seconds)'
                            }
                        }
                    }
                }
            });
        </script>
    <?php endforeach; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>