<?php
/*
 * This class provides an "SQL Object", which can be built using
 * the static SQLBuilder class, and executed using SQLObject::execute.
 *
 */
 
 define( "PHLEX_SELECT", 1000 );
 define( "PHLEX_INSERT", 2000 );
 define( "PHLEX_UPDATE", 3000 );
 define( "PHLEX_DELETE", 4000 );
 define( "PHLEX_DESCRIBE", 5000 );
 
class QueryBuilder
{
	// Query Parts
	
	// Command - Can be SELECT, INSERT, UPDATE, or DELETE
	var $command 			= null;
	var $fields				= array( );
	var $tables				= array( );
	var $joins				= array( );
	
	var $conditions			= array();
	
	// Whole Query
	var $sql				= null;
	
	// SQL Ready State
	var $executeReady		= false;
	
	function QueryBuilder()
	{
		$this->sql = '';
	}
	
	function select()
	{
		$this->command = PHLEX_SELECT;
		
		return $this;
	}
	
	function describe()
	{
		$this->command = PHLEX_DESCRIBE;
		
		return $this;
	}
	
	/*
	 *
	 */
	function addField( $a1, $a2 = null )
	{
		if( strpos( $a1, '.' ) )
		{
			$temp = explode( '.', $a1, 2 );
			$a1 = '`' . $temp[0] . '`.`' . $temp[1] . '`';
		} else
		{
			$a1 = '`' . $a1 . '`';
		}
		
		if( isset( $a2 ) )
			$this->fields[ $a2 ] = $a1;
		else
			array_push( $this->fields, $a1 );
			
		return $this;
	}
	
	function addTable( $a1, $a2 = null )
	{
		if( isset( $a2 ) )
			$this->tables[ $a2 ] = $a1;
		else
			array_push( $this->tables, $a1 );
			
		return $this;
	}
	
	function condition( $a1, $a2, $a3 )
	{
		if( strpos( $a1, '.' ) )
		{
			$temp = explode( '.', $a1, 2 );
			$a1 = '`' . $temp[0] . '`.`' . $temp[1] . '`';
		}
	
		array_push( $this->conditions, '' . $a1 . ' ' . $a2 . ' ' . $a3 );
		
		return $this;
	}
	
	function join( $a1, $a2 )
	{
		$this->joins[ $a1 ] = $a2;
				
		return $this;
	}
	
	function buildQuery()
	{
		switch( $this->command )
		{
			// Select Statement
			case PHLEX_SELECT:
				$this->sql = "SELECT";
				
				if( empty( $this->fields ) )
				{
					$this->sql .= " *";
				} else
				{
					foreach( $this->fields as $fAlias => $fName )
					{
						if( is_numeric( $fAlias ) )
							$this->sql .= ' ' . $fName . ',';
						else
							$this->sql .= ' ' . $fName . ' AS `' . $fAlias . '`,';
					}
					
					$this->sql = substr( $this->sql, 0, strlen( $this->sql ) - 1 );
				}
				
				$this->sql .= " FROM";
				
				foreach( $this->tables as $tAlias => $tName )
				{
					if( is_numeric( $tAlias ) )
						$this->sql .= ' `' . $tName . '`,';
					else
						$this->sql .= ' `' . $tName . '` AS `' . $tAlias . '`,';
				}
				
				$this->sql = substr( $this->sql, 0, strlen( $this->sql ) - 1 );
				
				foreach( $this->joins as $joinLeft => $joinRight )
				{
					if( strpos( $joinLeft, '.' ) && strpos( $joinRight, '.' ) )
					{
						$joinLeft = explode( '.', $joinLeft, 2 );
						$joinRight = explode( '.', $joinRight, 2 );
						$t1 = $joinLeft[0];
						$f1 = $joinLeft[1];
						$t2 = $joinRight[0];
						$f2 = $joinRight[1];
						
						$this->sql .= " LEFT JOIN `" . $t2 . "` ON `" . $t1 . "`.`" . $f1 . "` = `" . $t2 . "`.`" . $f2 . "`";
					}
				}
				
				if( !empty( $this->conditions ) )
				{
					$this->sql .= ' WHERE';
					
					foreach( $this->conditions as $condition )
					{
						$this->sql .=  ' ' . $condition . ',';
					}
				}
				
				$this->sql = substr( $this->sql, 0, strlen( $this->sql ) - 1 );
				
				break;
			
			case PHLEX_DESCRIBE:
				$this->sql = "DESCRIBE ";
				
				$this->sql .= '`' . $this->tables[0] . '`';
				
				break;
		}
		
		return $this->sql;
	}

}
