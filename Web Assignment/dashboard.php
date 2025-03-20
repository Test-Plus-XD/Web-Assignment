<!DOCTYPE html>
<html>
<?php
    // Determine which content to load; default to "overview".
    $content = $_GET['content'] ?? 'overview';
    // Format the content string for display (e.g., "all_products" becomes "All Products").
    $displayContent = ucwords(str_replace('_', ' ', $content));
    // Set the page title dynamically.
    $pageTitle = "Dashboard/{$displayContent}";
    $pageCSS = 'dashboard.css';

    // Include the head section; head.php should output the <head> content using $pageTitle and $pageCSS.
    require_once 'head.php';

    // Check if the user is an admin; if not, redirect them to index.php.
    if (empty($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"]) {
        header("Location: index.php");
        exit;
    }
?>
<body>
    <main class="dashboard_main">
        <div class="dashboard-container">
            <aside class="dashboard-sidebar">
                <div class="accordion" id="sidebarAccordion">
                    <!-- Item 1: Overview (Not collapsible) -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button type="button" class="accordion-button sidebar-link no-arrow p-2" onclick="window.location.href='dashboard.php?content=overview';">
                                &nbsp;&nbsp;<i class="bi bi-card-text"></i>&nbsp; Overview
                            </button>
                        </h2>
                    </div>
                    <!-- Item 2: Report with collapsible sub-menu -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingReport">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseReport" aria-expanded="false" aria-controls="collapseReport">
                            <i class="bi bi-journal-album"></i>    
                            &nbsp; Report
                            </button>
                        </h2>
                        <div id="collapseReport" class="accordion-collapse collapse" aria-labelledby="headingReport" data-bs-parent="#sidebarAccordion">
                            <div class="accordion-body">
                                <ul class="sidebar-list">
                                    <li><button type="button" class="sidebar-link sidebar-submenu" onclick="window.location.href='dashboard.php?content=all_owned_products';"><i class="bi bi-file-earmark-ruled"></i></i>&nbsp; Purchase Records</a></li>
                                    <li><button type="button" class="sidebar-link sidebar-submenu" onclick="window.location.href='dashboard.php?content=profit';"><i class="bi bi-cash-coin"></i>&nbsp; Profit</a></li>
                                    <li><button type="button" class="sidebar-link sidebar-submenu" onclick="window.location.href='dashboard.php?content=stock';"><i class="bi bi-box-seam-fill"></i>&nbsp; Stock</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <!-- Item 3: Products with collapsible sub-menu -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingProducts">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseProducts" aria-expanded="false" aria-controls="collapseProducts">
                            <i class="bi bi-boxes"></i>    
                            &nbsp; Products
                            </button>
                        </h2>
                        <div id="collapseProducts" class="accordion-collapse collapse" aria-labelledby="headingProducts" data-bs-parent="#sidebarAccordion">
                            <div class="accordion-body">
                                <ul class="sidebar-list">
                                    <li><button type="button" class="sidebar-link sidebar-submenu" onclick="window.location.href='dashboard.php?content=all_products';"><i class="bi bi-collection-fill"></i>&nbsp; All Products</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <!-- Item 4: Users with collapsible sub-menu -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingUsers">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUsers" aria-expanded="false" aria-controls="collapseUsers">
                            <i class="bi bi-journal-album"></i>
                            &nbsp; Users
                            </button>
                        </h2>
                        <div id="collapseUsers" class="accordion-collapse collapse" aria-labelledby="headingUsers" data-bs-parent="#sidebarAccordion">
                            <div class="accordion-body">
                                <ul class="sidebar-list">
                                    <li><button type="button" class="sidebar-link sidebar-submenu" onclick="window.location.href='dashboard.php?content=all_users';"><i class="bi bi-person-rolodex"></i>&nbsp; All Users</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
            <section class="dashboard-content">
                <?php
                    // Based on the 'content' query parameter, include the corresponding backend file.
                    // For update functionality, the URL should be: 
                    // dashboard.php?content=update&type=product&id=123 or dashboard.php?content=update&type=user&id=456
                    $content = $_GET['content'] ?? 'overview';
                    $file = 'Backend/' . $content . '.php';
                    // Check if the file exists; if not, default to "overview.php"
                    if (file_exists($file)) {
                        require_once $file;
                    } else {
                        require_once 'Backend/overview.php';
                    }
                ?>
            </section>
        </div>
    </main>
    <script src="js/dashboard.js?v=1.0" defer></script>
</body>
</html>