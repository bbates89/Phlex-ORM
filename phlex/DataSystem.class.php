<?php

class DataSystem extends DataModel
{
	var $uses;
	
	var $connection;
	
	var $pathToPhlex;
	
	function DataSystem()
	{
		/* Empty Constructor */
	}
	
	/* Initialization Function */
	function init()
	{
		parent::__construct( );
	}
}

?>