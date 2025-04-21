<?php
// Define the allowed records per page as a constant
const ALLOWED_RECORDS_PER_PAGE = [2, 5, 10, 25, 50];

class FirestoreAdapter {
    private $formattedData = [];

    public function format($firestoreData) {
        $this->formattedData = $firestoreData; // all_.php now passes the 'data' array directly
        return $this; // Return the instance to allow method chaining
    }

    public function countAll() {
        return is_array($this->formattedData) ? count($this->formattedData) : 0;
    }

    public function display($records_per_page, $offset) {
        return is_array($this->formattedData) ? array_slice($this->formattedData, $offset, $records_per_page) : [];
    }
}

// Function to calculate pagination data and return an array with pagination parameters and data
function getPaginationData(FirestoreAdapter $dataFormatter, $default_records_per_page = 20) {
    $records_per_page = $default_records_per_page; // Set default here
    if (isset($_GET['rpp']) && in_array((int)$_GET['rpp'], ALLOWED_RECORDS_PER_PAGE)) {
        $records_per_page = (int)$_GET['rpp'];
    }

    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($current_page < 1) {
        $current_page = 1;
    }

    $offset = ($current_page - 1) * $records_per_page;
    $totalRecords = $dataFormatter->countAll();
    $totalPages = ($totalRecords > 0) ? ceil($totalRecords / $records_per_page) : 1;
    $dataList = $dataFormatter->display($records_per_page, $offset);

    // Construct the base URL dynamically
    $base_url = $_SERVER['PHP_SELF'] . "?";
    $query_params = $_GET;
    unset($query_params['page']); // Remove existing page parameter
    $base_url .= http_build_query($query_params) . "&page=";

    // Determine insert_type from the 'content' parameter if available, default to 'user'
    $insert_type = $_GET['content'] ?? 'user';

    return array(
        'dataList'             => $dataList,
        'current_page'         => $current_page,
        'totalPages'           => $totalPages,
        'base_url'             => $base_url,
        'insert_type'          => $insert_type,
        'records_per_page'     => $records_per_page,
        'allowed_records_per_page' => ALLOWED_RECORDS_PER_PAGE
    );
}

// Function to display the insert button and the pagination UI using the pagination data provided
function displayPagination($pagination) {
    // Extract variables from the pagination array
    $current_page = $pagination['current_page'];
    $totalPages    = $pagination['totalPages'];
    $base_url     = $pagination['base_url'];
    $insert_type  = $pagination['insert_type'];
    $records_per_page = $pagination['records_per_page'];
    $allowed_records_per_page = $pagination['allowed_records_per_page'];

    // Output the insert button if the insert type is set
if (!empty($insert_type)) {
    $processedLabel = preg_replace('/^all_/i', '', $insert_type);
    // 2. Remove trailing 's' if present (to singularise)
    if (str_ends_with($processedLabel, 's')) $processedLabel = substr($processedLabel, 0, -1);
    $labelText = ucfirst($processedLabel);
    echo '<button class="btn btn-outline-primary" style="background-color: aqua;" onclick="insertRecord(event, \'' . $insert_type . '\')">&plus; Add New ' . $labelText . '</button>';
}


    // Output the records per page dropdown
    echo '<div class="mb-3">';
    echo '<label for="recordsPerPage" class="form-label" style="color: white;">Records per page:</label>';
    echo '<select class="form-select form-select-sm" id="recordsPerPage" onchange="window.location.href=window.updateQueryStringParameter(window.location.href, \'rpp\', this.value)">';
    foreach ($allowed_records_per_page as $rpp) {
        $selected = ($rpp == $records_per_page) ? 'selected' : '';
        echo '<option value="' . $rpp . '" ' . $selected . '>' . $rpp . '</option>';
    }
    echo '</select>';
    echo '</div>';

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