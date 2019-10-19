<?php

// Jednostavni zajednički registry za dijeljenje podataka između controllera i viewa.
class Registry 
{
	// Asocijativno polje (mapa) u koje ćemo moći dodati bilo što.
	private $vars = array();

	// Settter koji omogućava dodavanje novih podataka u registry (tj. u gornje privatno polje)
	public function __set( $index, $value )
	{
		$this->vars[$index] = $value;
	}

	// Getter iz privatnog polja.
	public function __get( $index )
	{
		return $this->vars[$index];
	}
}

?>
