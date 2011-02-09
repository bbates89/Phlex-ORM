<?php

class DataObject
{
	/* Database Connection Holder */
	var $db;

	/* Container Data */
	var $container 	= null;
	var $foreignKey = null;
	var $hasMany;
	var $belongsTo;
	
	/* Query Data */
	var $result 		= false;
	
	/* Container Results */
	var $childResult;
	var $parentResult;
	
	/* FetchRow Result Iterator */
	var $resultIterator = 0;
	
	/* Table Data */
	var $useTable;
	var $primaryKey = 'id';
	
	/* AutoMapping Flag */
	var $autoMap;
	
	/* Database Fields */
	var $primaryFieldSet;
	var $fieldSetStack;
	
	function DataObject( &$db )
	{
		$this->db = &$db;
	
		if( !isset( $this->autoMap ) )
			$this->autoMap = true;
			
		if( $this->autoMap )
		{
			$result = $result = $this->db->query( "DESCRIBE " . $this->useTable );
			
			$this->primaryFieldSet = new FieldSet();
			
			foreach( $result as $row )
			{
				$tempField = new Field();
				
				$fieldName = $row['Field'];
				$tempField->fieldName = $fieldName;
				
				$this->primaryFieldSet->fields[ $fieldName ] = $tempField;
				
				$this->fields[$fieldName]->fieldValue = &$this->$fieldName;
			}
		}
	}
	
	/*
	 *	DataObject::Find
	 *
	 *	Executes a find using the given parameters.
	 
	 	sParams
			Search parameters, in a named array, such as 
				'id' = 1,
				'password' = "hello"
	 */
	 
	 
	function find( $sParams, $limit = null )
	{
		$this->resultIterator 	= 0;
		$this->fieldSetStack 	= array();
	
		if( !is_array( $sParams ) )
		{
			if( $sParams == 'all' )
			{
			
				$sql = "SELECT * FROM " . $this->useTable;
				
				if( $limit != null)
					$sql .= " LIMIT " . $limit[0] . ',' . $limit[1];
			
				$result = $this->db->query( $sql );
				
				if( $result )
				{
					$this->result = true;
				
					foreach( $result as $row )
					{
						$tempFieldSet = clone $this->primaryFieldSet;
						$tempFieldSet->inject( $row );
						array_push( $this->fieldSetStack, $tempFieldSet );
					}
				}
			}
		}
	}
	
	/*
	 *	DataObject::fetchRow
	 *
	 *	
	 *
	 */
	function fetchRow()
	{
		// If there is still a row set
		if( $this->result && isset( $this->fieldSetStack[ $this->resultIterator ] ) )
		{
			// If the resultIterator is over zero, some data may
			// need to be saved.
			if( $this->resultIterator > 0 )
			{
				$fields = $this->fieldSetStack[ $this->resultIterator - 1]->returnArray();
				
				foreach( $fields as $fieldName => $fieldValue )
				{
					$this->fieldSetStack[ $this->resultIterator - 1 ]->saveField( $fieldName, $this->$fieldName );
				}
			}
			// For each value in the row, set the local variable.
			$fields = $this->fieldSetStack[ $this->resultIterator ]->returnArray();
			
			foreach( $fields as $fieldName => $fieldValue )
				$this->$fieldName = $fieldValue;

			
			$this->resultIterator++;
			
			return true;
		} else
		{
			return false;
		}
	}
	
	
	/*
	 *	DataObject::save
	 *
	 *  Saves an object.
	 *  
	 */
	function save()
	{
		$queryAr = array();
		
		$i = 0;
		
		if( $this->result ) {
		
			$fields = $this->fieldSetStack[ $this->resultIterator - 1 ]->returnArray();
			
			foreach( $fields as $fieldName => $fieldValue )
			{
				$this->fieldSetStack[ $this->resultIterator - 1 ]->saveField( $fieldName, $this->$fieldName );
			}
		
			foreach( $this->fieldSetStack as $fieldSet ) {
				$update = $fieldSet->returnSaveArray();
				
				$queryAr[ $i ] = "UPDATE " . $this->useTable . " SET ";
				
				foreach( $update as $fieldName => $fieldValue )
				{
					$queryAr[ $i ] .= $this->useTable . "." . $fieldName . " = '" . $fieldValue . "',";
				}
				
				$queryAr[ $i ] = substr( $queryAr[ $i ], 0, (strlen( $queryAr[ $i ] ) - 1) );
				
				$queryAr[ $i ] .= " WHERE " . $this->useTable . "." . $this->primaryKey . " = " . $fieldSet->getField( $this->primaryKey );
				
				echo $queryAr[ $i++ ] . '<br>';
			}
		}
	}
}