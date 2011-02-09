<?php

class DataObject
{
	/* Database Connection Holder */
	var $db;
	
	var $loaded = false;

	/* Container Data */
	var $container 	= null;
	var $foreignKey = null;
	var $hasOne;
	var $hasMany;
	var $belongsTo;
	
	/* Object Name */
	var $objectName;
	
	/* Query Data */
	var $result 		= false;
	
	/* Container Results */
	var $ParentObject;
	
	/* FetchRow Result Iterator */
	var $resultIterator = 0;
	
	/* Link to Data Model */
	var $DataModel;
	
	/* Table Data */
	var $useTable;
	var $primaryKey = 'id';
	
	/* AutoMapping Flag */
	var $autoMap;
	
	/* Database Fields */
	var $primaryFieldSet;
	var $fieldSetStack;
	
	function DataObject( &$db, &$DataModel )
	{
		$this->db = &$db;
	
		$this->DataModel = &$DataModel;
	
		$this->objectName = get_class( $this );
	
		if( !isset( $this->autoMap ) )
			$this->autoMap = true;
			
		if( $this->autoMap )
		{
			$query = new QueryBuilder;
		
			$result = $this->db->query( 
				$query->describe()
					->addTable($this->useTable)
					->buildQuery()
			);
			
			$this->primaryFieldSet = new FieldSet();
			
			foreach( $result as $row )
			{
				$tempField = new Field();
				
				$fieldName = $row['Field'];
				$tempField->fieldName = $fieldName;
				
				$this->primaryFieldSet->fields[ $fieldName ] = $tempField;
				
				$this->fields[$fieldName]->fieldValue = &$this->$fieldName;
			}
			
			$this->loaded = true;
		}
	}
	
	function loadRelated()
	{
		if( !empty( $this->hasOne ) )
		{
			foreach( $this->hasOne as $value )
			{
				if( class_exists( $this->DataModel->$value ) )
					$this->$value = new $value( $this->db, $this->DataModel );
			}
		}
		
		if( !empty( $this->hasMany ) )
		{
			foreach( $this->hasMany as $value )
			{
				//if( class_exists( $this->DataModel->$value ) )
					$this->$value = new $value( $this->db, $this->DataModel );
					$this->$value->loadRelated();
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
	 
	 
	function find( $sType, $sParams = null )
	{
		$this->resultIterator 	= 0;
	
		if( !is_array( $sType ) )
		{
			if( $sType == 'all' )
			{
			
				$query = new QueryBuilder();
				
				$query->select();
				
				foreach( $this->primaryFieldSet->describeFields() as $field )
				{
					$query->addField( "{$this->useTable}.{$field}", "!phlextable:{$this->useTable};{$field}" );
				}
				
				$query->addTable( $this->useTable );
			
				if( isset( $sParams['conditions'] ) )
				{
					foreach( $sParams['conditions'] as $name => $value )
						$query->condition( $this->useTable . '.' . $name, '=', $value );
				}
			
				foreach( $this->hasMany as $object )
				{
					$this->$object->ParentObject = &$this;
					$this->$object->_formatQuery( $query );
				}
			
				$result = $this->db->query( $query->buildQuery() );
				
				//print( $query->buildQuery() );  die();
				
				if( $result )
				{
					$this->result = true;
					
					$rows = array();
					
					foreach( $result as $row )
					{
						$resultAr = array();
						
						foreach( $row as $key=>$val )
							$resultAr[ $key ] = $val;
						
						array_push( $rows, $resultAr );
					}
					
					return $this->populateFields( $rows );
				}
			}
		}
	}
	
	function _formatQuery( &$QueryObject )
	{
		foreach( $this->primaryFieldSet->describeFields() as $field )
			$QueryObject->addField( "{$this->useTable}.{$field}", "!phlextable:" . $this->_getTableString( $this ) . ";{$field}" );
		
		$QueryObject->join( $this->ParentObject->useTable 
			. '.id', $this->useTable 
			. '.' . $this->ParentObject->useTable . '_id' );
			
		if( !empty( $this->hasMany ) ) {
			foreach( $this->hasMany as $object )
			{
				$this->$object->ParentObject = &$this;
				$this->$object->_formatQuery( $QueryObject );
			}
		}
			
	}
	
	function _getTableString( &$object )
	{
		if( isset( $object->ParentObject ) )
			return $object->_getTableString( $object->ParentObject ) . '.' . $object->useTable;
		else
			return $object->useTable;
	}
	
	function populateFields( $ResultArray )
	{
		
		$this->fieldSetStack 	= array();
		$usedPk = array();
		$tableStr = $this->_getTableString( $this );
		
		foreach( $ResultArray as $row )
		{
			$tablePk = $row["!phlextable:" . $tableStr . ";" . $this->primaryKey];
			
			if( !in_array( $tablePk, $usedPk ) )
			{
				$tempFieldSet = clone $this->primaryFieldSet;
				$tempFieldSet->inject( $row, $tableStr );
				array_push( $this->fieldSetStack, $tempFieldSet );
				
				array_push( $usedPk, $tablePk );
			}
		}
		
		$dataResult = new DataResult( $this->fieldSetStack );
		if( $this->ParentObject == null )
			$dataResult->ParentObject = null;
		else
			$dataResult->ParentObject = &$this->ParentObject;
		
		$dataResult->ObjectLink = &$this;
		
		if( !empty( $this->hasMany ) )
		{
			$dataResult->hasMany = $this->hasMany;
		
			foreach( $this->hasMany as $object )
			{
				$dataResult->$object = $this->$object->populateFields( $ResultArray );	
			}
		}
		
		return $dataResult;
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