<?php

// Funkcionalnost routera je izdvojena u zasebnu klasu.
class Router 
{
	// Router također dijeli registry.
	private $registry;

	// Varijabla u koju ćemo spremiti putanju do controllera.
	private $path;
	private $args = array();
	public $file;

	// Objekt tipa controller.
	public $controller;

	// Ime akcije koja će se izvršiti u controlleru (index po defaultu).
	public $action; 

	function __construct( $registry )
	{
	    $this->registry = $registry;
	}

	// Sprema putanju do kontrolera u $this->path.
	function setPath( $path ) 
	{
		// Provjeri je li u $path zaista spremljena putanja (niz direktorija).
		if( is_dir( $path ) == false )
		{
			throw new Exception ( 'Invalid controller path: `' . $path . '`' );
		}

		$this->path = $path;
	}


	// Funkcija koja učitava (include-a) kod odgovarajućeg controllera.
	public function loader()
	{
		// Analiziraj rutu do kontrolera (razvoji $this->file, $this->path).
		$this->getController();

		// Ako traženi kontroler ne postoji, prikaži 404.
		if( is_readable( $this->file ) === false )
		{
			$this->file = $this->path . '/_404Controller.php';
            $this->controller = '_404';
		}

		// Učitaj ispravni controller.
		require_once $this->file;

		// Stvori novi controller objekt. Proslijedi mu registry.
		$class = $this->controller . 'Controller';
		$controller = new $class( $this->registry );

		// Provjeri da li controller sadrži traženu akciju.
		if( method_exists( $controller, $this->action ) === false )
			$action = 'index';
		else
			$action = $this->action;

		// Pozovi odgovarajuću akciju.
		$controller->$action();
	}


	// Analiziraj $_GET['rt'], odredi ime controllera i akcije.
	private function getController() 
	{
		// Dohvati $_GET['rt']
		$route = ( empty( $_GET['rt'] ) ) ? '' : $_GET['rt'];

		if( empty( $route ) )
			$route = 'index';
		else
		{
			// Ako $_GET['rt'] nije prazan, podijeli ga na controller i akciju.
			$parts = explode( '/', $route );
			$this->controller = $parts[0];
			if( isset( $parts[1] ) )
				$this->action = $parts[1];
		}

		if( empty( $this->controller ) )
			$this->controller = 'index';

		if( empty( $this->action ) )
			$this->action = 'index';

		// Sad imamo putanju do controllera.
		$this->file = $this->path .'/'. $this->controller . 'Controller.php';
	}
}

?>
