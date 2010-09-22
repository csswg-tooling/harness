<?php
require_once "config.phi";

// db utility class
// Provides shorter, more intuitive function names in an object-oriented way
class db {
	public $db_handle;
	public $debug = false; //When set to true, echoes all queries rather than running them.

	// $options should be an array containing any of the following keys: "host", "username", "password", "database".
	public function __construct( $key_var_array = array() ) {
		extract( array_merge( array( "host"=>"localhost", "username"=>DB_USER, "password"=>DB_PASSWORD, "database"=>DB_NAME ), $key_var_array ) );
		$this->db_handle = mysql_connect( $host, $username, $password, true ) or die( mysql_error() );
		if( $this->db_handle ) {
			mysql_select_db( $database, $this->db_handle );}}

			
	// The basic query function.  Returns an ordinary mysql result resource.
	// Use this when you don't care about the return result, such as when doing an UPDATE or INSERT.
	// (If the provided db::insert and db::update functions don't suffice for some reason.)
	public function q ( $sql ) {
		GLOBAL $mysql_dbh;
		if( is_resource( $sql ) ) {
			return $sql;}
		if( $debug ) {
			echo $sql;}
		else {
			$result = mysql_query( $sql, $this->db_handle) or die( mysql_error($this->db_handle) . "<br>\nQuery: $sql" );
			return $result;}}


	public function id () {
		return mysql_insert_id( $this->db_handle );}


	// Static function that escapes text in the mysql manner.  db::s() is a lot shorter than mysql_real_escape_string()
	public static function s ( $text ) {
		return mysql_real_escape_string( $text );}


	/*
	 * Query functions, for simple queries.
	 */

	// OFFICIALLY DEPRECATED
	// Function for building an insert/update statement.  Insert if $specifiers is null.
	// $table is a string, $vars and $specifiers are arrays of column/value pairs
	// Note: This was ill-conceived.  It is only still alive because I might have used it.
	// OFFICIALLY DEPRECATED
	public function i ( $table, $vars, $specifiers = FALSE ) {
		if( !$specifiers ) {
			$this->insert( $table, $vars );}
		else {
			$this->update( $table, $vars, $specifiers );}}


	public function insert ( $table, $vars ) {
		$table = db::s( $table );
		$this->q( "INSERT INTO $table ( " . implode( ", ", array_map( "mysql_real_escape_string", array_keys($vars) ) ) . " ) VALUES ( '" . implode( "', '", array_map( "mysql_real_escape_string", $vars ) ) . "')" );}


	public function update ( $table, $vars, $specifiers ) {
		$table = db::s( $table );
		foreach( $vars as $col=>$val ) {
			$col = db::s( $col );
			$val = db::s( $val );
			$v[] = "$col = '$val'";}
		foreach( $specifiers as $col=>$val ) {
			$w[] = db::formatWhere($col,$val);}
		$this->q( "UPDATE $table SET " . implode( ", ", $v ) . " WHERE " . implode( " AND ", $w ) );}


	public function delete ( $table, $specifiers ) {
		$table = db::s( $table );
		foreach( $specifiers as $col=>$val ) {
			$w[] = db::formatWhere($col,$val);}
		$this->q( "DELETE FROM $table WHERE " . implode( " AND ", $w ) );}


	private static function formatWhere($col,$val) {
		$return = "`" . db::s($col) . "` ";
		if( is_array( $val ) ) {
			switch($val[0]) {
			case "between":
				$return .= "BETWEEN '" . db::s($val[1]) . "' AND " . db::s($val[2]);
				break;
			case "<":
				$return .= "< '" . db::s($val[1]) . "'";
				break;
			case "<=":
				$return .= "<= '" . db::s($val[1]) . "'";
				break;
			case ">":
				$return .= "> '" . db::s($val[1]) . "'";
				break;
			case ">=":
				$return .= ">= '" . db::s($val[1]) . "'";
				break;
			case "in":
				$return .= "IN ('" . implode( "','", array_map(array("db","s"), $val[1])) . "')";
				break;}}
		else {
			$return .= "= '" . db::s($val) . "'";}
		return $return;}


	// Extremely simple select functionality, for when you just need a simple query.
	public function select( $table, $specifiers = false, $type = "result", $columns = "*", $extras = array() ) {
		$query = "SELECT ";
		if( is_array( $columns ) ) {
			foreach( $columns as $alias=>$col ) {
				if( is_int( $alias ) ) { // they just passed a column name
					$c[] = db::s( $col );}
				else { // they passed alias=>column
					$c[] = db::s( $col ) . " AS " . db::s( $alias );}}
			$query .= implode( ", ", $c );}
		elseif( is_string($columns) ){
			$query .= db::s($columns);}
		else {
			$query .= "*";}
		$query .= " FROM " . db::s( $table );
		if( $specifiers ) {
			foreach( $specifiers as $col=>$val ) {
				$w[] = db::formatWhere($col,$val);}
			$query .= " WHERE " . implode( " AND ", $w );}
		if( isset($extras['orderby']) ) {
			if( is_string($extras['orderby']) ) {
				$query .= " ORDER BY " . db::s($extras['orderby']);}
			else {
				$query .= " ORDER BY " . implode( ",", array_map(array("db","s"), $extras['orderby']));}}
		if( isset($extras['limit']) ) {
			$query .= " LIMIT " . intval($extras['limit']);}
		switch( $type ) {
			case "value":
			case "row":
			case "none":
				$query .= " LIMIT 1"; // fallthrough!
			case "col":
			case "result":
			case "index":
				return $this->$type($query);}}


	// Heuristic query that returns values intelligently based on the query and result.
	// If query returns no rows, function returns FALSE.
	// If query returns exactly one row and one column, and you explicitly asked for such (that is, didn't ask for * in a 1-column table), function returns as if value()
	// If query return exactly one row and you explicitly asked for that (a "LIMIT 1" appears in query), function returns as if row()
	// Otherwise, function returns as if result()
	public function f ( $sql ) {
		$result = $this->q( $sql );
		$numrows = mysql_num_rows( $result );
		$numcols = mysql_num_fields( $result );

		if( $numrows == 0 ) { // empty result
			return FALSE;}
		if( $numrows == 1 and $numcols == 1 and ( stripos( $sql, "select *" ) === FALSE ) ) { // purposeful single-value query
			return $this->value( $result );}
		if( $numrows == 1 and ( stripos( $sql, "LIMIT 1" ) !== FALSE ) ) { // purposeful single-row query
			return $this->row( $result );}
		return $this->result( $result );}


	// Returns TRUE if query returns no rows, FALSE otherwise
	public function none( $sql ) {
		return mysql_num_rows( $this->q( $sql ) ) == 0;}


	// Returns the first value in the first row.
	public function value ( $sql ) {
		$result = $this->q( $sql );
		if( mysql_num_rows( $result ) == 0 ) {
			return false;}
		list( $answer ) = mysql_fetch_row( $result );
		return $answer;}


	// If result has a single column, packs the values into a normal array.
	// Otherwise, packs the values from the first and second column into an associative array.
	public function col ( $sql ) {
		$result = $this->q( $sql );
		if( mysql_num_fields( $result ) == 1 ) {
			while( list( $value ) = mysql_fetch_row( $result ) ) {
				$answer[] = $value;}}
		else {
			while( list( $key, $value ) = mysql_fetch_row( $result ) ) {
				$answer[$key] = $value;}}
		return $answer;}


	// Returns the first row as a generic object with the value of each column as a property.
	public function row( $sql ) {
		$result = $this->q( $sql );
		if( mysql_num_rows( $result ) > 0 ) {
			return mysql_fetch_object( $result );}
		else {
			return FALSE;}}


	// Returns the full result.  Each row is an object as row(), which are then packed into an array.
	public function result( $sql ) {
		$result = $this->q( $sql );
		while( $sub_result = mysql_fetch_object( $result ) ) {
				$rows[] = $sub_result;}
		return $rows;}


	// Like result, but returns an array with keys equal to the specified index field.
	public function index( $sql, $index = "id" ) {
		$result = $this->q( $sql );
		while( $subresult = mysql_fetch_object( $result ) ) {
			$rows[$subresult->$index] = $subresult;}
		return $rows;}


/*
 * Specialized Printing Functions
 */


	// Convenience function for displaying a $this->result as a <table>.
	public static function genTable( $result, $options = array() ) {
		extract( array_merge( array( "id"=>"", "empty_text"=>"No results found!" ), $options ) );
		$output = "<table id='$id'>";
		if ( $result ) {
			$output .= "<thead><tr>";
			//loop thru the field names to print the correct headers
			$i = 0;
			foreach( $result[0] as $field=>$val ) {
				$i++;
				$output .= "<th scope='col' class='col$i'>$field</th>";}
			$output .= "</tr></thead>";
			
			//display the data
			$parity = "odd";
			foreach ( $result as $row ) {
				$output .= "<tr class='$parity'>";
				foreach ($row as $data) {
					if( is_array( $data ) or is_object( $data ) ) {
						$output .= "<td><pre>" . print_r( $data, true ) . "</pre></td>";}
					else {
						$output .= "<td>$data</td>";}}
				$output .= "</tr>";
				$parity = $parity == "odd" ? "even" : "odd";}}
		else {
			$output .= "<tr><td>$empty_text</td></tr>";}
		$output .= "</table>";
		return $output;}


	// Convenience function for displaying a $this->col as a <select>
	public static function genSelect( $col, $name, $options = array() ) {
		extract( array_merge( array( "id"=>"", "empty_text"=>"No results found!", "selected"=>false ), array_map( "htmlspecialchars", $options ) ) );
		$name = htmlspecialchars( $name );
		$output = "<select name='$name' id='$id'>";
		if( $col ) {
			foreach( $col as $val=>$text ) {
				$val = htmlspecialchars( $val );
				$text = htmlspecialchars( $text );
				if( $selected == $val ) {
					$selected_text = "selected";}
				else {
					$selected_text = "";}
				$output .= "<option value='$val' $selected_text>$text</option>";}}
		else {
			$output .= "<option value='---'>$empty_text</option>";}
		$output .= "</select>";
		return $output;}


	public static function genDL( $col, $name, $options = array() ) {
		extract( array_merge( array( "id"=>"", "empty_text"=>"No results found!", "initial_option"=>FALSE ), array_map( "htmlspecialchars", $options ) ) );
		$output = "<dl id='$id'>";
		if( $col ) {
			foreach( $col as $dt=>$dd ) {
				$dt = htmlspecialchars( $dt );
				$dd = htmlspecialchars( $dd );
				$output .= "<dt>$dt</dt><dd>$dd</dd>";}
			$output .= "</dl>";}
		else {
			$output = $empty_text;}
		return $output;}


/* 
 * Database management functions
 */

	public static function uuid() {
	    // version 4 UUID
	    return sprintf(
	        '%08x-%04x-%04x-%02x%02x-%012x',
	        mt_rand(),
	        mt_rand(0, 65535),
	        bindec(substr_replace(
	            sprintf('%016b', mt_rand(0, 65535)), '0100', 11, 4)
	        ),
	        bindec(substr_replace(sprintf('%08b', mt_rand(0, 255)), '01', 5, 2)),
	        mt_rand(0, 255),
	        mt_rand());}


	private static function salt() {
		return substr( md5( time() ), 0, 10 );}


	public static function hash( $pw, $salt = false ) {
		if( !$salt ) { 
			$salt = db::salt();
			return $salt . hash( "whirlpool", $salt . $pw );}
		else {
			return hash( "whirlpool", $salt . $pw );}}
}
?>