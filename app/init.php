<?php

// Učitaj definiciju bazne klase za controllere.
require_once __SITE_PATH . '/app/' . 'controller_base.class.php';

// Učitaj definiciju registry klase.
require_once __SITE_PATH . '/app/' . 'registry.class.php';

// Učitaj definiciju routera.
require_once __SITE_PATH . '/app/' . 'router.class.php';

// Učitaj definiciju templatea.
require_once __SITE_PATH . '/app/' . 'template.class.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Automatsko učitavanja klasa iz modela kad se pozove new.
function __autoload( $class_name )
{
	// Imena datoteke od klasa će biti napisana malim slovima.
	// Npr. za klasu User će biti spremljeno u user.class.php
	$filename = strtolower($class_name) . '.class.php';
	$file = __SITE_PATH .'/model/' . $filename;

	if( file_exists($file) === false )
	{
	    return false;
	}
	require_once ($file);
}

?>
