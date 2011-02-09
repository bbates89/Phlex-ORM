<?php
/*
 * This class provides an "SQL Object", which can be built using
 * the static SQLBuilder class, and executed using SQLObject::execute.
 */
class Field
{

	var $fieldName;
	var $fieldValue;
	
	var $modified = false;
	
	function Field()
	{
		
	}
	
	function __clone()
	{
	}
	

}
