<?php
// Database Connection
$dsn = "mysql:host=localhost;dbname=euro_universities;charset=utf8mb4";
$username = "euro_admin";
$password = "euroglobal123";

try {
    $pdo = new PDO($dsn, $username, $password);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch Programs
$query = "SELECT * FROM programs";
$stmt = $pdo->query($query);
$programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<!-- Mirrored from html.creativegigstf.com/charles/service-details.html by HTTrack Website Copier/3.x [XR&CO'2014], Thu, 18 Jul 2024 01:06:06 GMT -->

<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-P0RM2XGQ2R"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());

        gtag('config', 'G-P0RM2XGQ2R');
    </script>
    <meta charset="UTF-8">
    <!-- For IE -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- For Resposive Device -->
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- For Window Tab Color -->
    <!-- Chrome, Firefox OS and Opera -->
    <meta name="theme-color" content="#061948">
    <!-- Windows Phone -->
    <meta name="msapplication-navbutton-color" content="#061948">
    <!-- iOS Safari -->
    <meta name="apple-mobile-web-app-status-bar-style" content="#061948">
    <title>Study Abroad Consultancy: Expert Guidance & Top Universities</title>
    <!-- Description Tag -->
    <meta name="description"
        content="Fulfill your study abroad dreams with us. We offer expert guidance, personalized support, and access to prestigious universities all over the world.">
    <!-- Language Tag -->
    <meta property="og:locale" content="en_US">
    <!-- Open Graph Tags -->
    <meta property="og:type" content="website" />
    <meta property="og:title" content="Study Abroad Consultancy: Expert Guidance & Top Universities">
    <meta property="og:description"
        content="Fulfill your study abroad dreams with us. We offer expert guidance, personalized support, and access to prestigious universities all over the world.">
    <meta property="og:url" content="https://euroglobalconsultancy.com/study-abroad.html">
    <meta property="og:site_name" content="Euro Global Consultancy">
    <!-- Canonical Tag -->
    <link rel="canonical" href="https://www.euroglobalconsultancy.com/study-abroad.html" />
    <!-- Robots Tag -->
    <meta name="robots" content="index, follow">
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="56x56" href="images/logo/logo-2.1.ico">
    <!-- Main style sheet -->
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <!-- responsive style sheet -->
    <link rel="stylesheet" type="text/css" href="css/responsive.css">

    <!-- Fix Internet Explorer ______________________________________-->
    <!--[if lt IE 9]>
            <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
            <script src="vendor/html5shiv.js"></script>
            <script src="vendor/respond.js"></script>
        <![endif]-->
</head>

<body>
    <div class="main-page-wrapper">

        <section class="training-section course_section">
            <div class="auto-container">
                <h3 class="title1">Find Your Dream Program</h3>
                <div class="filters">

                    <div class="ui-group">
                        <h3>Find by Level of Study</h3>
                        <div class="button-group js-radio-button-group" data-filter-group="color1">
                            <!-- <button class="button is-checked" data-filter="">All</button> -->
                            <button class="button is-checked" data-filter=".bachelors">Bachelors</button>
                            <button class="button" data-filter=".masters">Masters</button>

                        </div>
                    </div>

                    <div class="ui-group">
                        <h3>Find by Country</h3>
                        <div class="button-group js-radio-button-group" data-filter-group="size">
                            <button class="button is-checked" data-filter="*">All</button>
                            <!-- <button class="button" data-filter=".usa">USA</button>
                            <button class="button" data-filter=".uk">UK</button>
                            <button class="button" data-filter=".europe">Europe</button>
                            <button class="button" data-filter=".canada">Canada</button>
                            <button class="button" data-filter=".germany">Germany</button> -->
                            <button class="button" data-filter=".italy">Italy</button>
                            <button class="button" data-filter=".hungary">Hungary</button>
                            <button class="button" data-filter=".latvia">Latvia</button>
                            <button class="button" data-filter=".slovakia">Slovakia</button>
                            <button class="button" data-filter=".portugal">Portugal</button>
                            <button class="button" data-filter=".czech">Czech Republic</button>
                            <button class="button" data-filter=".lithuania">Lithuania</button>
                            <button class="button" data-filter=".malta">Malta</button>
                            <!-- <button class="button" data-filter="."></button> -->


                        </div>
                    </div>
                    <div class="ui-group">
                        <h3>Find by Domain</h3>
                        <div class="button-group js-radio-button-group" data-filter-group="color">

                            <button class="button is-checked" data-filter=".engineering">Engineering</button>
                            <button class="button" data-filter=".arts">Arts &amp; Science</button>
                            <button class="button" data-filter=".management">Management</button>
                            <button class="button" data-filter=".health">Health &amp; Medicine</button>
                            <button class="button" data-filter=".Building">Building &amp; Architecture</button>


                        </div>
                    </div>
                </div>
                <div class="row grid mt-5">
                    <?php foreach ($programs as $program): ?>
                        <div
                            class="service-block col-lg-4 color-shape <?= htmlspecialchars($program['level_of_study']) ?> <?= htmlspecialchars($program['country']) ?> <?= htmlspecialchars($program['domain']) ?>">
                            <div class="inner-box">
                                <div class="content-box">
                                    <h6 class="lab"><?= htmlspecialchars($program['program_lab']) ?></h6>
                                    <h5 class="title"><a
                                            href="<?= htmlspecialchars($program['know_more_url']) ?>"><?= htmlspecialchars($program['program_title']) ?></a>
                                    </h5>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="course_tags">
                                                <div class="course_tag">
                                                    <div class="icontag">
                                                        <i class="fa fa-university"></i>
                                                    </div>
                                                    <div class="icon_text">
                                                        <h6>University</h6>
                                                        <p><?= htmlspecialchars($program['university']) ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tags py-2">
                                        <?php foreach (explode(',', $program['tags']) as $tag): ?>
                                            <span class="cc"><?= htmlspecialchars(trim($tag)) ?></span>
                                        <?php endforeach; ?>
                                        <span class="cc2 cc"><a style="color: inherit;"
                                                href="<?= htmlspecialchars($program['know_more_url']) ?>">Know
                                                More</a></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>


        <!-- Optional JavaScript _____________________________  -->

        <!-- jQuery first, then Popper.js, then Bootstrap JS -->
        <!-- jQuery -->
        <script src="vendor/jquery.2.2.3.min.js"></script>
        <!-- Popper js -->
        <script src="vendor/popper.js/popper.min.js"></script>
        <!-- Bootstrap JS -->
        <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
        <!-- Camera Slider -->
        <script src='vendor/Camera-master/scripts/jquery.mobile.customized.min.js'></script>
        <script src='vendor/Camera-master/scripts/jquery.easing.1.3.js'></script>
        <script src='vendor/Camera-master/scripts/camera.min.js'></script>
        <!-- menu  -->
        <script src="vendor/menu/src/js/jquery.slimmenu.js"></script>
        <!-- WOW js -->
        <script src="vendor/WOW-master/dist/wow.min.js"></script>
        <!-- owl.carousel -->
        <script src="vendor/owl-carousel/owl.carousel.min.js"></script>
        <!-- js count to -->
        <script src="vendor/jquery.appear.js"></script>
        <script src="vendor/jquery.countTo.js"></script>
        <!-- Fancybox -->
        <script src="vendor/fancybox/dist/jquery.fancybox.min.js"></script>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.isotope/3.0.6/isotope.pkgd.min.js"></script>
        <script>
            // external js: isotope.pkgd.js

            // init Isotope
            var $grid = $('.grid').isotope({
                itemSelector: '.color-shape',
                filter: '.health'
            });

            // store filter for each group
            var filters = {};
            $('.filters').on('click', '.button', function (event) {
                var $button = $(event.currentTarget);
                // get group key
                var $buttonGroup = $button.parents('.button-group');
                var filterGroup = $buttonGroup.attr('data-filter-group');
                // set filter for group
                filters[filterGroup] = $button.attr('data-filter');
                // combine filters
                var filterValue = concatValues(filters);
                // set filter for Isotope
                $grid.isotope({ filter: filterValue });
            });

            // change is-checked class on buttons
            $('.button-group').each(function (i, buttonGroup) {
                var $buttonGroup = $(buttonGroup);
                $buttonGroup.on('click', 'button', function (event) {
                    $buttonGroup.find('.is-checked').removeClass('is-checked');
                    var $button = $(event.currentTarget);
                    $button.addClass('is-checked');
                });
            });

            // flatten object by concatting values
            function concatValues(obj) {
                var value = '';
                for (var prop in obj) {
                    value += obj[prop];
                }
                return value;
            }
        </script>

        <script>
            if ($(".clients-carousel").length) {
                $(".clients-carousel").owlCarousel({
                    loop: true,
                    margin: 10,
                    nav: false,
                    dots: false,
                    smartSpeed: 100,
                    autoplay: true,
                    navText: ['<span class="fa fa-angle-left"></span>', '<span class="fa fa-angle-right"></span>'],
                    responsive: {
                        0: { items: 1 },
                        480: { items: 2 },
                        600: { items: 3 },
                        768: { items: 4 },
                        1023: { items: 4 }
                    }
                });
            }

            if ($(".clients-carousel1").length) {
                $(".clients-carousel1").owlCarousel({
                    loop: true,
                    margin: 10,
                    nav: false,
                    dots: false,
                    smartSpeed: 100,
                    autoplay: true,
                    autoplayTimeout: 2000,
                    navText: ['<span class="fa fa-angle-left"></span>', '<span class="fa fa-angle-right"></span>'],
                    responsive: {
                        0: { items: 1 },
                        480: { items: 2 },
                        600: { items: 3 },
                        768: { items: 5 },
                        1023: { items: 5 }
                    }
                });
            }

            if ($(".clients-carousel-two").length) {
                $(".clients-carousel-two").owlCarousel({
                    loop: true,
                    margin: 0,
                    nav: false,
                    smartSpeed: 100,
                    autoplay: true,
                    navText: ['<span class="fa fa-angle-left"></span>', '<span class="fa fa-angle-right"></span>'],
                    responsive: {
                        0: { items: 1 },
                        480: { items: 2 },
                        600: { items: 3 },
                        991: { items: 4 },
                        1200: { items: 5 },
                        1400: { items: 6 }
                    }
                });
            }

            if ($(".clients-carousel4").length) {
                $(".clients-carousel4").owlCarousel({
                    loop: true,
                    margin: 200,
                    nav: false,
                    smartSpeed: 100,
                    autoplay: true,
                    navText: ['<span class="fa fa-angle-left"></span>', '<span class="fa fa-angle-right"></span>'],
                    responsive: {
                        0: { items: 1 },
                        480: { items: 2 },
                        600: { items: 3 },
                        768: { items: 3 },
                        1023: { items: 3 }
                    }
                });
            }

        </script>
        <!-- Theme js -->
        <script src="js/theme.js"></script>
    </div> <!-- /.main-page-wrapper -->
</body>

</html>