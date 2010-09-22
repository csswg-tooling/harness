<?php
require_once "class.db.php";

// A pool of database connections, so you're not either constantly creating database connections,
// or having to pass around db variables (or worse, rely on conveniently named global variables).
// ::get($name) will return the database named $name.  If a connection to it doesn't yet exist, the pool
// will try to create it using only the database name (not host or password or anything).
// If you have to provide extra parameters when creating a db, call ::set() first with the preferred name
// of the db, then the settings array you would pass to the db constructor.
class dbPool {
	public static $dbConnections = array();

	static function get( $name  ) {
		if( array_key_exists( $name, self::$dbConnections ) ) {
			return self::$dbConnections[$name];
		} else {
			return self::set( $name, array( "database"=>$name ) );
		}
	}

	static function set( $name, $options = array() ) {
		$db = new db( $options );
		self::$dbConnections[ $name ] = $db;
		return $db;
	}
}