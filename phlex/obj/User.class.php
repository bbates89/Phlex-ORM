<?php

class User extends DataObject
{
	var $useTable = 'users';
	
	var $link = array(
		'Comment' 	=> 'user_id',
		'Post'		=> 'user_id'
	);
}

?>