<?php

/*
 *	Phlex DataModel Class
 *		This class represents the entire model of the
 *		data system.
 *
 *	@author Brendan Bates <brendan.bates@maine.edu>
 *	
 */

class DataModel
{
	/* Database Connection */
	var $db;
	var $connection;
	
	/* DataObject Usage */
	var $objects;
 
	/* Path to Object Directory */
	var $objectPath;
 
	/*
	 *	Constructor
	 */
	function DataModel()
	{
		/* This class is a template.  It can only be extended. */
		if( get_class( $this ) == "DataModel" )
			throw new Exception( "Phlex::DataModel can not be run standalone." );
			
		/* Try to connect to database. */
		try {
			$this->db = new PDO( $this->connection['driver']
				. ':dbname=' . $this->connection['database'] 
				. ';host=' . $this->connection['host'], $this->connection['username'],
				$this->connection['password']);
				
		} catch ( PDOException $e ) {
			echo 'Connection failed: ' . $e->getMessage();
		}
		
		/* Turn Object Path to an absolute path */
		$this->objectPath = substr( $_SERVER['SCRIPT_FILENAME'], 0, strrpos($_SERVER['SCRIPT_FILENAME'], '/') ) . '/' . $this->objectPath;
	
		/* Create new class instances for each object specified. */
		foreach( $this->objects as $class )
		{
			if( file_exists( $this->objectPath . '/' . $class . '.class.php' ) )
				require( $this->objectPath . '/' . $class . '.class.php' );
			else
				die( "Class file for class '" . $class . "' does not exist at location " . $this->objectPath );
			
			if( class_exists( $class ) && get_parent_class( $class ) == 'DataObject' )
			{
				$this->$class = new $class( $this->db, $this );
			} else {
				die( "Class '" . $class . "' does not exist, or is not inheriting from DataObject, and must be created to continue." );
			}
		}
		
		// Now that all objects are loaded, the related objects in
		// each object can be loaded (such as hasMany, etc...).
		foreach( $this->objects as $class )
		{
			$this->$class->loadRelated();
		}
		
	}
	
	
}