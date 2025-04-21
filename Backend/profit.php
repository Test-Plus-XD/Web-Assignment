<?php
// Include shared API helpers and constants
require_once "firestore.php"; // Assumed to provide callInternalApiGet, DateTimeZone, etc.

// Set grouping interval ('day' or 'month'). Get from query parameter, default to 'day'.
$groupingInterval = $_GET['group'] ?? 'day';

// Timezone for HKT
$hkTimezone = new DateTimeZone('Asia/Hong_Kong');

// Initial Data
$labels = [];         // Chart labels (YYYY-MM-DD or YYYY-MM strings, HKT)
$profits = [];        // Chart data values
$errorFetchingData = false; // API error flag
$errorMessages = []; // Store API error details
$profitDataAvailable = false; // Chart data ready flag

// Fetch Data
$purchases = callInternalApiGet('/Class_purchases.php/all');
$products = callInternalApiGet('/Class_products.php/all');

// Handle Fetch Errors
if (isset($purchases['error'])) {
    $errorFetchingData = true;
    $errorMessages[] = "Purchases API: " . ($purchases['details']['error'] ?? $purchases['details'] ?? 'Unknown error');
}
if (isset($products['error'])) {
    $errorFetchingData = true;
    $errorMessages[] = "Products API: " . ($products['details']['error'] ?? $products['details'] ?? 'Unknown error');
}

// Process Profit Data
if (!$errorFetchingData && is_array($purchases) && is_array($products)) {

    // Build product price lookup (ID => itemPrice)
    $productPrices = [];
    foreach ($products as $product) {
        if (isset($product['ID'], $product['itemPrice'])) {
            $productPrices[$product['ID']] = (float) $product['itemPrice'];
        } else {
            error_log("profit.php: Skip product in lookup: Missing ID/itemPrice for " . json_encode($product));
        }
    }
    // Aggregate profit by HKT date/month
    $dailyProfit = [];

    // Iterate through purchases
    foreach ($purchases as $purchase) {
        if (isset($purchase['product_id'], $purchase['date']) && is_string($purchase['date']) && $purchase['date'] !== '') {
            $productId = $purchase['product_id'];
            $purchaseDateString = $purchase['date']; // Expected format: 'd-m-Y H:i:s.u' (HKT)

            $price = $productPrices[$productId] ?? null;

            if ($price !== null) {
                $purchaseDateTime = null;
                // Parse date string as HKT
                $dateFormat = 'd-m-Y H:i:s.u';
                try {
                    // Parse the string, then set timezone to HKT
                    $purchaseDateTime = DateTimeImmutable::createFromFormat($dateFormat, $purchaseDateString, $hkTimezone);

                    if ($purchaseDateTime === false) {
                        error_log("profit.php: Date parse fail for purchase " . ($purchase['ID'] ?? 'N/A') . ": '" . $purchaseDateString . "'");
                        continue;
                    }
                } catch (Exception $e) {
                    error_log("profit.php: Date parse exception for purchase " . ($purchase['ID'] ?? 'N/A') . ": '" . $purchaseDateString . "' Error: " . $e->getMessage());
                    continue;
                }

                // Determine format for grouping key based on interval
                $groupingFormat = ($groupingInterval === 'month') ? 'Y-m' : 'Y-m-d';
                $groupingKey = $purchaseDateTime->format($groupingFormat);

                // Aggregate profit
                $dailyProfit[$groupingKey] = ($dailyProfit[$groupingKey] ?? 0.0) + (float)$price;
            } else {
                error_log("profit.php: Skip purchase " . ($purchase['ID'] ?? 'N/A') . ": itemPrice not found for product " . $productId);
            }
        } else {
            error_log("profit.php: Skip purchase " . ($purchase['ID'] ?? 'N/A') . ": Missing product_id/date or date not string. Data: " . json_encode($purchase));
        }
    }
    // Sort by date/month keys
    ksort($dailyProfit);

    // Prepare data for Chart.js
    if (!empty($dailyProfit)) {
        $labels = array_keys($dailyProfit); // YYYY-MM-DD or YYYY-MM strings (HKT)
        $profits = array_values($dailyProfit); // Aggregated profit
        $profitDataAvailable = true;
    } else {
        error_log("profit.php: No profit data aggregated.");
    }
} else {
    error_log("profit.php: Data fetching failed. Error flag: " . ($errorFetchingData ? 'true' : 'false'));
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Profit Chart by Purchased Date<?php if($groupingInterval === 'month') echo ' (Grouped by Month)'; ?></h2>
    <div class="dropdown">
        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="groupingDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="padding-inline-end: 150px">
            Group by <?php echo ucfirst($groupingInterval); ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="groupingDropdown">
            <li><a class="dropdown-item" href="#" data-group-interval="day">Group by Day</a></li>
            <li><a class="dropdown-item" href="#" data-group-interval="month">Group by Month</a></li>
        </ul>
    </div>
</div>
<div class="chart-container" style="background-color: #4b4e52a0; padding: 20px;">
    <?php if ($profitDataAvailable): ?>
        <canvas id="profitChart"></canvas>
    <?php elseif ($errorFetchingData): ?>
        <p style="color: red;">Error loading profit data:</p>
        <ul>
        <?php foreach ($errorMessages as $msg): ?>
            <li style="color: red;"><?= htmlspecialchars($msg) ?></li>
        <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No profit data available.</p>
    <?php endif; ?>
</div>
<?php if ($profitDataAvailable): ?>
<script>
    // Chart labels (YYYY-MM-DD or YYYY-MM strings, HKT)
    const labels = <?php echo json_encode($labels); ?>;
    // Numerical profit values
    const profits = <?php echo json_encode($profits); ?>;
    // Grouping interval ('day' or 'month')
    const groupingInterval = <?php echo json_encode($groupingInterval); ?>;

    document.addEventListener("DOMContentLoaded", () => {
        const canvasElement = document.getElementById('profitChart');
        if (canvasElement && typeof drawBarChart === 'function') {

            // Format Labels for Chart Display
            // Convert date/month strings from PHP into a readable format for chart labels (HKT).
            const chartLabels = labels.map(dateString => {
                try {
                    let displayFormat = '';
                    let dateObj;

                    if (groupingInterval === 'month') {
                        // Parse YYYY-MM string. Use first day of month for consistent parsing.
                        // Interpret as HKT midnight for the month's start date.
                        // Using Date.parse() first can help with consistency across browsers
                        const timestamp = Date.parse(dateString + '-01T00:00:00+08:00');
                        if (isNaN(timestamp)) throw new Error('Invalid month string parse');
                         dateObj = new Date(timestamp);
                        // Format for month display (e.g., "April 2025")
                        displayFormat = dateObj.toLocaleDateString('en-GB', { month: 'long', year: 'numeric' });
                    } else { // Grouping by day
                        // Parse YYYY-MM-DD string, interpret as HKT midnight.
                        // Using Date.parse() first can help with consistency
                        const timestamp = Date.parse(dateString + 'T00:00:00+08:00');
                         if (isNaN(timestamp)) throw new Error('Invalid date string parse');
                         dateObj = new Date(timestamp);
                        // Format for day display (e.g., "21-4-2025" or "21/4/2025")
                        // Use manual construction for exact D-M-YYYY or D/M/YYYY format
                        const day = dateObj.getDate();
                        const month = dateObj.getMonth() + 1; // 0-indexed (0=Jan, 11=Dec)
                        const year = dateObj.getFullYear();
                        // displayFormat = `${day}/${month}/${year}`; // Format: "21/4/2025"
                        displayFormat = `${day}-${month}-${year}`; // Format: "21-4-2025" // Default hyphen format
                    }
                    return displayFormat;
                } catch (e) {
                    console.error("Error formatting chart label date '" + dateString + "':", e);
                    return 'Invalid Date'; // Return placeholder on error
                }
            });
            // Draw the chart with the formatted labels and profit data
            drawBarChart('profitChart', chartLabels, profits, 'Profit');
        } else {
            console.error("Canvas element or drawBarChart function not found. Cannot draw chart.");
            const chartContainer = document.querySelector('.chart-container');
            if(chartContainer && !chartContainer.querySelector('p')) {
                chartContainer.innerHTML = '<p style="color: red;">Unable to load chart.</p>';
            }
        }
    });
    // Dropdown Change Handler
    document.addEventListener("DOMContentLoaded", function() {
        const dropdownItems = document.querySelectorAll('#groupingDropdown + .dropdown-menu .dropdown-item');
        dropdownItems.forEach(item => {
            item.addEventListener('click', function(event) {
                event.preventDefault(); // Prevent the default link behavior
                const interval = this.dataset.groupInterval; // Get the interval ('day' or 'month')
                // Construct the new URL with the selected grouping parameter
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('group', interval);
                // Navigate to the new URL, causing a page reload
                window.location.href = currentUrl.toString();
            });
        });
    });
</script>
<?php endif; ?>