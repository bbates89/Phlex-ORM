<?php

class DataResult
{
	var $fieldSetStack = array();
	var $resultIterator;
	var $result = false;
	
	var $ParentObject = null;
	var $ObjectLink = null;
	
	var $hasMany;
	
	var $cForeignKey;
	
	function DataResult( $fieldSetStack )
	{
		$this->fieldSetStack = $fieldSetStack;
		$this->resultIterator = 0;
		$this->result = true;
	}
	
	function fetchRow()
	{
		// If there is still a row set
		if( $this->result && isset( $this->fieldSetStack[ $this->resultIterator ] )  )
		{
			if( $this->ParentObject == null )
			{
				// If the resultIterator is over zero, some data may
				// need to be saved.
				/*if( $this->resultIterator > 0 )
				{
					$fields = $this->fieldSetStack[ $this->resultIterator - 1]->returnArray();
					
					foreach( $fields as $fieldName => $fieldValue )
					{
						$this->fieldSetStack[ $this->resultIterator - 1 ]->saveField( $fieldName, $this->$fieldName );
					}
				}*/
				// For each value in the row, set the local variable.
				$fields = $this->fieldSetStack[ $this->resultIterator ]->returnArray();
				
				foreach( $fields as $fieldName => $fieldValue ) {
					$this->$fieldName = $fieldValue;
				}
				
				if( isset( $this->hasMany ) )
				{
					$primaryKey = $this->ObjectLink->primaryKey;
				
					foreach( $this->hasMany as $object ) {
						$this->$object->cForeignKey = $this->$primaryKey;
						$this->$object->resultIterator = 0;
					}
				}
				
				$this->resultIterator++;
				
				return true;
			} else if( $this->cForeignKey != null ) {				
				while( isset( $this->fieldSetStack[ $this->resultIterator ] ) ) {
					
					$fields = $this->fieldSetStack[ $this->resultIterator ]->returnArray();
				
					if( $fields[ $this->ParentObject->useTable . '_id' ] == $this->cForeignKey )
					{
						foreach( $fields as $fieldName => $fieldValue ) {
							$this->$fieldName = $fieldValue;
						}
						
						$this->resultIterator++;
							
						if( isset( $this->hasMany ) )
						{
							$primaryKey = $this->ObjectLink->primaryKey;
						
							foreach( $this->hasMany as $object ) {
								$this->$object->cForeignKey = $this->$primaryKey;
								$this->$object->resultIterator = 0;
							}
						}
					
						return true;
						
						break;
					} else
					{
						$this->resultIterator++;
					}
				}
				
				return false;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	
	function toArray()
	{
		$objectName = $this->ObjectLink->objectName;
		
		$tArray = array(  );
		
		$i = 0;
		
		$fields = $this->ObjectLink->primaryFieldSet->describeFields();
		
		while( $this->fetchRow() )
		{
			foreach( $fields as $field )
			{
				$tArray[$i][$field] = $this->$field;
			}
			
			if( isset( $this->hasMany ) )
			{
				foreach( $this->hasMany as $object )
				{
					$tArray[ $i ][ $object ] = $this->$object->toArray();
				}
			}
			
			$i++;
		}
		
		$tArrayReturn[ $objectName ] = $tArray;
		
		if( !isset( $this->ParentObject ))
			return $tArrayReturn;
		else
			return $tArray;
		
	}
}
?>