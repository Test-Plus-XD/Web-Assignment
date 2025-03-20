<?php
// Function to calculate pagination data and return an array with pagination parameters and data
function getPaginationData($dataObj, $records_per_page) {
    if (!$dataObj) {
        die("Error: Data object is null in getPaginationData()");
    }

    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($current_page < 1) {
        $current_page = 1;
    }

    $offset = ($current_page - 1) * $records_per_page;
    $totalRecords = $dataObj->countAll();
    if ($totalRecords === null) {
        die("Error: countAll() returned NULL in getPaginationData()");
    }

    $totalPages = ($totalRecords > 0) ? ceil($totalRecords / $records_per_page) : 1;
    $dataList = $dataObj->display($records_per_page, $offset);
    if ($dataList === null) {
        die("Error: display() returned NULL in getPaginationData()");
    }

    // Determine insert_type based on the class name of $dataObj
    $insert_type = strtolower(substr(get_class($dataObj),0,-1)); // Example: 'Products' ¡÷ 'product'
    
    return array(
        'dataList'     => $dataList,
        'current_page' => $current_page,
        'totalPages'   => $totalPages,
        'base_url'     => "dashboard.php?content=" . urlencode($_GET['content'] ?? '') . "&page=",
        'insert_type'  => $insert_type
    );
}

// Function to display the insert button and the pagination UI using the pagination data provided
function displayPagination($pagination) {
    // Extract variables from the pagination array
    $current_page = $pagination['current_page'];
    $totalPages   = $pagination['totalPages'];
    $base_url     = $pagination['base_url'];
    $insert_type  = $pagination['insert_type'];
    
    // Output the insert button if the insert type is set
    if (!empty($insert_type)) {
        echo '<button class="btn btn-outline-primary" style="background-color: aqua;" onclick="insertRecord(event, \'' . $insert_type . '\')">&plus; Add New ' . ucfirst($insert_type) . '</button>';
    }
    
    // Output the pagination UI if there is more than one page
    if ($totalPages > 1) {
        echo '<div class="d-flex justify-content-center mt-3">';
        echo '<nav aria-label="Pagination">';
        echo '<ul class="pagination pagination-sm" style="background-color: mediumblue; border-radius: 25%;">';
        if ($current_page > 1) {
            echo '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($base_url . ($current_page - 1)) . '" aria-label="Previous"><span aria-hidden="true">&laquo; Prev</span></a></li>';
        } else {
            echo '<li class="page-item disabled"><span class="page-link">&laquo; Prev</span></li>';
        }
        for ($i = 1; $i <= $totalPages; $i++) {
            $activeClass = ($i == $current_page) ? 'active' : '';
            echo '<li class="page-item ' . $activeClass . '"><a class="page-link" href="' . htmlspecialchars($base_url . $i) . '">' . $i . '</a></li>';
        }
        if ($current_page < $totalPages) {
            echo '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($base_url . ($current_page + 1)) . '" aria-label="Next"><span aria-hidden="true">Next &raquo;</span></a></li>';
        } else {
            echo '<li class="page-item disabled"><span class="page-link">Next &raquo;</span></li>';
        }
        echo '</ul>';
        echo '</nav>';
        echo '</div>';
    }
}
?>