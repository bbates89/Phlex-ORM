<?php

class Post extends DataObject
{
	var $useTable = 'post';
	
	var $hasMany = array(
		'Comment'
	);
}

?>