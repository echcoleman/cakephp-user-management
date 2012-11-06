<?php
App::uses('AuthComponent', 'Controller/Component');

/**
 * UserAuthComponent
 * 
 * Adds additional information to return about current logged in user
 */
class UserAuthComponent extends AuthComponent {
	
/**
 * Get the current user.
 * Works exactly like parent AuthComponent function except it can return the group key.
 * To retrieve group key pass the key as 'group'
 * 
 * @param string $key field to retrieve.  Leave null to get entire User record
 * @return mixed User record. or null if no user is logged in.
 */
	public static function user($key = null) {
		if ($key == 'group') {
			$key = 'Group.key';
		}
		return parent::user($key);
	}
	
}