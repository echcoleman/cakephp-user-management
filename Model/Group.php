<?php
App::uses('UserManagementAppModel', 'UserManagement.Model');
/**
 * Group Model
 *
 * @property User $User
 */
class Group extends UserManagementAppModel {
/**
 * Display field
 *
 * @var string
 */
	public $displayField = 'name';
/**
 * Validation rules
 *
 * @var array
 */
	public $validate = array(
		'key' => array(
			'notempty' => array(
				'rule' => array('notempty'),
				'message' => 'Please enter group key',
				'required' => true,
			),
			'alphanumeric' => array(
				'rule' => array('alphanumeric'),
				'message' => 'Group key must only contain letters and numbers',
				'required' => true,
			),
			'isUnique' => array(
				'rule' => array('isUnique'),
				'message' => 'The group key is already in use. Please enter a different one',
				'required' => true,
			),
		),
		'name' => array(
			'notempty' => array(
				'rule' => array('notempty'),
				'message' => 'Please enter group name',
				'required' => true,
			),
		),
	);

/**
 * hasMany associations
 *
 * @var array
 */
	public $hasMany = array(
		'User' => array(
			'className' => 'UserManagement.User',
			'foreignKey' => 'group_id',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		)
	);
	
/**
 * Behaviors
 * 
 * @var array 
 */
	public $actsAs = array('Acl' => array('type' => 'requester'));

/**
 * Group is top level node so return NULL
 * 
 * @return null Return NULL
 */
	public function parentNode() {
		return null;
	}
	
}