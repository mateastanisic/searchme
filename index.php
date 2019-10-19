<?php

//definiramo globalno vidljive constante:
// __SITE_PATH = putanja na disku servera do index.php
// __SITE_URL  = URL do index.php
define( '__SITE_PATH', realpath( dirname( __FILE__ ) ) );
define( '__SITE_URL', dirname( $_SERVER['PHP_SELF'] ) );

//da se možemo vratit natrag (pritiskom na <- u browseru)
ini_set('session.cache_limiter','public');
session_cache_limiter(false);

//započnemo/nastavimo session
session_start();

// inicijaliziraj aplikaciju (učitava bazne klase, autoload klasa iz modela)
require_once 'app/init.php';

//stvori zajednički registry podataka u aplikaciji
$registry = new Registry();

//stvori novi router, spremi ga u registry
$registry->router = new Router($registry);

//javi routeru putanju gdje su spremljeni svi controlleri
$registry->router->setPath( __SITE_PATH . '/controller' );

//stvori novi template za prikaz view-a
$registry->template = new Template($registry);

//učitaj controller pomoću routera
$registry->router->loader();

?>
