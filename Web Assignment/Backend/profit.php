<?php
// Include the necessary class files.
require_once "Class_db_connect.php";
require_once "Class_Owned_Products.php";

$DB = new Database();
$conn = $DB->getConnection();
$ownedProducts = new Owned_Products($conn);

// Retrieve profit data using the profit() function.
$profitData = $ownedProducts->profit();

// Prepare arrays for Chart.js.
$labels = [];
$profits = [];

if (!empty($profitData)) {
    foreach ($profitData as $row) {
        // Format the date if necessary.
        $labels[] = $row['purchased_date'];
        $profits[] = $row['total_profit'];
    }
}
?>
<h2>Profit Chart by Purchased Date</h2>
<div class="chart-container" style="background-color: #4b4e52a0;">
    <canvas id="profitChart"></canvas>
</div>

<?php if (!empty($profitData)): ?>
<script>
    // Pass PHP arrays to JavaScript
    const labels = <?php echo json_encode($labels); ?>;
    const profits = <?php echo json_encode($profits); ?>;

    // Use the drawBarChart function from diagram.js to create the chart
    window.addEventListener("load", function() {
        drawBarChart('profitChart', labels, profits, 'Profit');
    });
</script>
<?php else: ?>
<p>No profit data available.</p>
<?php endif; ?>