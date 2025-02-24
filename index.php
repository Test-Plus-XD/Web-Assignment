<!DOCTYPE html>
<html>
<?php
$pageTitle = 'Homepage of Vapor';
$pageCSS = 'index.css';
include 'head.php';
?>
<body style="font-family:Zen Maru Gothic">
    <main class="index_main">
        <div class="container-fluid">
            <!--Row1-->
            <div class="row align-items-center">
                <!-- Slideshow Column -->
                <div class="col-lg-8 col-md-7 col-sm-6 order-lg-1 order-md-1 order-sm-1 order-first">
                    <div id="carousel_Index" class="carousel slide" data-bs-ride="carousel" data-bs-interval="1500">
                        <a class="carousel-control-prev" href="#carousel_Index" role="button" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="sr-only"></span>
                        </a>
                        <a class="carousel-control-next" href="#carousel_Index" role="button" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="sr-only"></span>
                        </a>
                        <ol class="carousel-indicators">
                            <li data-bs-target="#carousel_Index" data-bs-slide-to="0" class="active"></li>
                            <li data-bs-target="#carousel_Index" data-bs-slide-to="1"></li>
                            <li data-bs-target="#carousel_Index" data-bs-slide-to="2"></li>
                            <li data-bs-target="#carousel_Index" data-bs-slide-to="3"></li>
                            <li data-bs-target="#carousel_Index" data-bs-slide-to="4"></li>
                            <li data-bs-target="#carousel_Index" data-bs-slide-to="5"></li>
                            <li data-bs-target="#carousel_Index" data-bs-slide-to="6"></li>
                        </ol>
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <img class="d-block w-100" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/769560/header.jpg?t=1710799442"
                                     alt="Night of Full Moon"><!--0-->
                                <a href=""></a>
                            </div>
                            <div class="carousel-item">
                                <img class="d-block w-100" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1085660/header.jpg?t=1728420487"
                                     alt="Destiny 2"><!--1-->
                                <a href=""></a>
                            </div>
                            <div class="carousel-item">
                                <img class="d-block w-100" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/3092660/header.jpg?t=1728442747"
                                     alt="Reverse:1999"><!--2-->
                                <a href=""></a>
                            </div>
                            <div class="carousel-item">
                                <img class="d-block w-100" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1964200/header.jpg?t=1712779391"
                                     alt="Phantom Rose 2 Sapphire"><!--3 -->
                                <a href="https://store.steampowered.com/app/1964200/Phantom_Rose_2_Sapphire/"></a>
                            </div>
                            <div class="carousel-item">
                                <img class="d-block w-100" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/2139460/header.jpg?t=1728889424"
                                     alt="Once Human"><!--4-->
                                <a href=""></a>
                            </div>
                            <div class="carousel-item">
                                <img class="d-block w-100" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/570/header.jpg?t=1727827653"
                                     alt="Dota 2"><!--5-->
                                <a href=""></a>
                            </div>
                            <div class="carousel-item">
                                <img class="d-block w-100" src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/730/header.jpg?t=1719426374"
                                     alt="Counter-Strike"><!--6-->
                                <a href=""></a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Column for title -->
                <div class="col-lg-4 col-md-5 col-sm-6 order-lg-3 order-md-2 order-sm-2 order-2">
                    <div class="d-flex flex-column" style="text-align:right">
                        <div class="p-2"><h1 class="index_title" style="font-size:12vw;padding-right:2px">VAPOR</h1></div>
                        <div class="p-2"><h5 class="index_title" style="font-size:4vw">Every Games You Dreamed<br>Can Be Found Here</h5></div>
                    </div>
                </div>
            </div>
            <!--Row2-->
            <div class="row mt-4">
                <div class="col-lg-4 col-md-4 col-sm-12">
                    <p class="index_main_content">
                        <!--Placeholder-->
                    </p>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-12">
                    <p class="index_main_content">
                        <!--Placeholder-->
                    </p>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-12 text-right">
                    <p class="index_main_content">
                        <!--Placeholder-->
                    </p>
                </div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>