<?php

// Klasa koja predstavlja predložak za view.
// Prikaz view-a će se napraviti tako da se objektu tipa Template pozove funkcija članica show.
// Njoj se kao parametar proslijedi ime view-a kojeg želimo prikazati.
class Template 
{
	// Zajednički registry koji se dijeli sa routerom i controllerom.
	private $registry;

	// Asocijativno polje u koje spremamo varijable koje će biti direktno dostupne u view-u.
	private $vars = array();

	function __construct( $registry ) 
	{
		$this->registry = $registry;
	}


	// Setter za varijable u asocijativnom polju.
	public function __set($index, $value)
	{
	    $this->vars[$index] = $value;
	}


	// Funkcija koja efektivno prikazuje view imena $name
	function show( $name ) 
	{
		$path = __SITE_PATH . '/view' . '/' . $name . '.php';

		if( file_exists($path) === false )
		{
			throw new Exception( 'Template not found in ' . $path );
			return false;
		}

		// Stvori par (varijabla, vrijednost) za svaki par (ključ, vrijednost) iz asoc. polja vars.
		// Npr. ako je vars['ime'] = 'Mirko', vars['ocjena'] = 5, ovo će napraviti varijable $ime='Mirko' i $ocjena=5.
		// Tako ćemo iz view-a moći direktno raditi echo $ime, a ne echo $vars['ime'].
		foreach( $this->vars as $key => $value )
		{
			$$key = $value;
		}

		// Ovdje ne koristimo require_once, zato da bi controller i više puta mogao prikazati jedan te isti view.
		// (Na primjer, za svakog usera pozove jedan (uvijek isti) view koji prikaže podatke o tom useru.)
		require ($path); 
	}
}

?>
