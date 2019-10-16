<?php
// klasa za povezivanje na bazu
class DB
{
	private static $db = null;

	private function __construct() { }
	private function __clone() { }

	public static function getConnection()
	{
		if( DB::$db === null )
		{
			try
			{
				//otvaramo bazu
				DB::$db = $myPDO = new PDO("pgsql:host=localhost;port=5432;dbname=searchme;user=postgres;password=pass");

				DB::$db-> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			}
			catch( PDOException $e ) { exit( 'PDO Error: ' . $e->getMessage() ); }
		}
		return DB::$db;
	}
}

?>
