// A helper module to dynamically create Chart.js charts
// Function to draw a bar chart on a canvas element
// Parameters:
//   canvasId: the id attribute of the <canvas> element
//   labels: an array of labels for the x-axis
//   data: an array of numerical values for the y-axis
//   chartLabel: a label for the dataset (displayed in the legend)
//   customOptions: (optional) an object to override default chart options
function drawBarChart(canvasId, labels, data, chartLabel, customOptions = {}) {
    // Get the canvas context
    const ctx = document.getElementById(canvasId).getContext('2d');

    // Define default configuration for the bar chart
    const defaultConfig = {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: chartLabel,
                data: data,
                backgroundColor: 'rgba(54, 162, 235, 0.75)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1,
                barThickness: 90,
                maxBarThickness: 120
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { autoSkip: false, color: '#ffffff' },
                    grid: { display: true, color: 'rgba(250, 250, 250, 1)', borderDash: [5, 5]},// Creates dashed lines (5px dash, 5px gap)
                },
                x: {
                    ticks: { autoSkip: true, color: '#ffffff'},
                    grid: { display: true, color: 'rgba(150, 150, 150, 1)'},
                    barPercentage: 0.35,
                    categoryPercentage: 0.7
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: '#ffffff' // Legend text colour (White)
                    }
                },
                tooltip: {
                    enabled: true,
                    titleColor: '#ffffff', // Tooltip title text colour
                    bodyColor: '#ffffff',  // Tooltip content text colour
                    backgroundColor: 'rgba(0, 0, 0, 0.75)' // Tooltip background (Dark)
                }
            }
        }
    };

    // Merge default configuration with any custom options provided
    const config = Object.assign({}, defaultConfig, customOptions);

    // Create and return the new chart
    return new Chart(ctx, config);
}