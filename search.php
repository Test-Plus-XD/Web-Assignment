<!DOCTYPE html>
<html>
<?php
$pageTitle = 'Search';
$pageCSS = 'search.css';
require_once 'head.php';
?>
<body style="font-family:Zen Maru Gothic"> 
    <main class="search_main">
        <div class="container">
            <h1 class="mt-4">Search for Product</h1>
            <!-- Search form -->
            <div class="sticky-top" style="top:7.5vh">
                <form id="searchForm" class="my-2 mx-2">
                    <div class="row">
                    <!-- Search input -->
                    <div class="col-12 col-sm-4 col-md-5 col-lg-5">
                        <div class="form-floating">
                            <input type="text" id="searchInput" class="form-control" placeholder=" ">
                            <label for="searchInput">Search here</label>
                        </div>
                    </div>
                    <div class="col-12 col-sm-2 col-md-1 col-lg-1">
                        <button type="submit" id="clearButton" class="btn btn-light" style="display: none;">&times;</button>
                    </div>
                    <!-- Dropdown for filtering -->
                    <div class="col-12 col-sm-2 col-md-2 col-lg-2">
                        <select id="priceFilter" class="form-select">
                            <option value="all" selected>All Price</option>
                            <option value="free">Free Games</option>
                            <option value="paid">Paid Games</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-2 col-md-2 col-lg-2">
                        <select id="typeFilter" class="form-select">
                            <option value="all" selected>All Type</option>
                            <option value="digital">Digital Products</option>
                            <option value="physical">Physical Products</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-2 col-md-2 col-lg-2">
                        <button type="submit" id="searchButton" class="btn btn-primary">Search</button>
                    </div>
                </form>
            </div>
            <!-- Suggestions container -->
            <div id="suggestions" class="mt-1"></div>
            <!-- Search results container -->
            <div id="searchResults" class="mt-5"></div>
        </div>

        <div class="container">
            <!-- Row for Cards -->
            <div class="row d-flex justify-content-evenly">
                <?php include 'product_cards.php'; ?>
            </div>
        </div>
    </main>

    <?php require_once 'footer.php'; ?>
    <script src="src/js/search.js?v=1.0" defer></script> <!-- Verify Script Cache -->
</body>
</html>