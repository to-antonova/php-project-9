<?php
/**
 * @var string $navLink
 * @var string $content
 * @var Slim\Interfaces\RouteParserInterface $router
 */
?>

<!DOCTYPE html>

<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Анализатор страниц</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
</head>

<body class="min-vh-100 d-flex flex-column">

<header class="flex-shrink-0">
    <nav class="navbar navbar-expand-md navbar-dark bg-dark px-2">
        <div class="container-fluid d-flex justify-content-start px-2">
            <a class="navbar-brand" href="<?= $router->urlFor('main') ?>">Анализатор страниц</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link <?= $navLink == MAIN_PAGE ? 'active' : '' ?>" href="<?= $router->urlFor('main') ?>">Главная</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $navLink == SITES_PAGE ? 'active' : '' ?>" href="<?= $router->urlFor('urls.index') ?>">Сайты</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<main class="flex-grow-1">
    <?= $content?>
</main>

</body>

<div>
    <?= $this->fetch('footer.php') ?>
</div>

</html>