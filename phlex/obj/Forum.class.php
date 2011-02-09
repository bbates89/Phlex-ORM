<?php

class Forum extends DataObject
{
	var $useTable = 'forums';
	
	var $hasMany = array(
		'Post'
	);
}

?>