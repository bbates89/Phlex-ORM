<?php
/*
 * This class provides an "SQL Object", which can be built using
 * the static SQLBuilder class, and executed using SQLObject::execute.
 */
class FieldSet
{

	var $fields;
	
	var $saveFlag;
	
	function __clone()
	{
		foreach( $this->fields as &$field )
		{
			$field = clone $field;
		}
	}
	
	function inject( $ar, $tableName = '' )
	{
		$this->saveFlag = false;
	
		foreach( $this->fields as &$field )
			if( isset( $ar[ '!phlextable:' . $tableName . ';' . $field->fieldName ] ) )
				$field->fieldValue =$ar[ '!phlextable:' . $tableName . ';' . $field->fieldName ];
		
		unset( $field );
	}
	
	function returnArray()
	{
		$ar = array();
	
		foreach( $this->fields as $field )
				$ar[ $field->fieldName ] = $field->fieldValue;
				
		return $ar;
	}
	
	function describeFields()
	{
		$ar = array();
	
		foreach( $this->fields as $field )
				array_push( $ar, $field->fieldName );
				
		return $ar;
	}
	
	function returnSaveArray()
	{
		$ar = array();
		
		foreach( $this->fields as $field )
		{
			if( $field->modified )
			{
				$ar[ $field->fieldName ] = $field->fieldValue;
			}
		}
		
		return $ar;
	}
	
	function getField( $fieldName )
	{
		return $this->fields[ $fieldName ]->fieldValue;
	}
	
	function saveField( $fieldName, $fieldValue )
	{
		if( $this->fields[ $fieldName ]->fieldValue != $fieldValue )
		{
			$this->fields[ $fieldName ]->fieldValue = $fieldValue;
			$this->fields[ $fieldName ]->modified = true;
		}
	}
}
