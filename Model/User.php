<?php
App::uses('UserManagementAppModel', 'UserManagement.Model');
App::uses('AuthComponent', 'Controller/Component');

/**
 * User Model
 *
 * @property Group $Group
 */
class User extends UserManagementAppModel {
/**
 * Display field
 *
 * @var string
 */
	public $displayField = 'username';
	
/**
 * Validation rules
 *
 * @var array
 */
	public $validate = array(
		'group_id' => array(
			'notempty' => array(
				'rule' => array('notempty'),
				'message' => 'Please select group',
				'required' => true,
				'on' => 'create'
			),
			'numeric' => array(
				'rule' => array('numeric'),
				'message' => 'Please select valid group',
				'required' => true,
				'on' => 'create'
			),
		),
		'username' => array(
			'notempty' => array(
				'rule' => array('notempty'),
				'message' => 'Please enter username',
				'required' => true,
			),
			'alphanumeric' => array(
				'rule' => array('alphanumeric'),
				'message' => 'Username must only contain letters and numbers',
				'required' => true,
			),
			'between' => array(
				'rule' => array('between', 6, 20),
				'message' => 'Username length must be minimum of 6 and maximum of 20 characters',
				'required' => true,
			),
			'isUnique' => array(
				'rule' => array('isUnique'),
				'message' => 'Your username has already been used. Please enter a different one',
				'required' => true,
			),
		),
		'email' => array(
			'notempty' => array(
				'rule' => array('notempty'),
				'message' => 'Please enter email address',
				'required' => true,
			),
			'email' => array(
				'rule' => array('email'),
				'message' => 'Please enter valid email address',
				'required' => true,
			),
			'isUnique' => array(
				'rule' => array('isUnique'),
				'message' => 'Your email has already been used. Please enter a different one',
				'required' => true,
			),
		),
		'password' => array(
			'notempty' => array(
				'rule' => array('notempty'),
				'message' => 'Please enter password',
				'required' => true,
				'on' => 'create'
			),
			'between' => array(
				'rule' => array('between', 6, 30),
				'message' => 'Password length must be minimum of 6 and maximum of 30 characters',
				'required' => true,
				'on' => 'create',
			),
		),
	);

/**
 * belongsTo associations
 *
 * @var array
 */
	public $belongsTo = array(
		'Group' => array(
			'className' => 'UserManagement.Group',
			'foreignKey' => 'group_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);
	
/**
 * Behaviors
 * 
 * @var array 
 */
//	public $actsAs = array('Acl' => array('type' => 'requester'));
	
/**
 * Return parent Group information for User
 * 
 * @return mixed Return array with Group info, otherwise return NULL
 */
//	public function parentNode() {
//		if (!$this->id && empty($this->data)) {
//			return null;
//		}
//		if (isset($this->data['User']['group_id'])) {
//			$groupId = $this->data['User']['group_id'];
//		} else {
//			$groupId = $this->field('group_id');
//		}
//		if (!$groupId) {
//			return null;
//		} else {
//			return array('Group' => array('id' => $groupId));
//		}
//	}
	
/**
 * Simplify ACL to use per-group only permissions
 * 
 * @param User $user User object
 * @return array Bind node array
 */
	public function bindNode($user) {
		return array('model' => 'Group', 'foreign_key' => $user['User']['group_id']);
	}
	
/**
 * Ensure password is saved in hashed format
 * 
 * @param array $options Options array passed to beforeSave function
 * @return boolean True
 */
	public function beforeSave($options = array()) {
		if (isset($this->data['User']['password'])) {
			
			// if password not modified, do not update
			if (strlen(trim($this->data['User']['password'])) == 0) {
				unset($this->data['User']['password']);
				
				// no need to continue
				return true;
			}
			
			// set salt
			$salt = Security::generateAuthKey();
			$this->data['User']['salt'] = $salt;
			
			// do not use AuthComponent::password to prevent adding system salt
			$password_salted = $this->data['User']['password'] . $salt;
			$this->data['User']['password'] = Security::hash($password_salted);
		}
		return true;
	}
	
/**
 * Send forgotten password confrimation to a user
 * 
 * @param array $user User to send confrimation for
 * @return boolean Return true on successful send, otherwise return false
 */
	public function sendForgottenPassword($user) {
		App::uses('CakeEmail', 'Network/Email');
		try {
			// get encrypted url
			$key = Security::hash($user['User']['salt'] . $user['User']['id']);
			$url = Router::url(array(
				'controller' => 'users',
				'action' => 'forgotten_confirm',
				'?' => array(
					'uid' => $user['User']['id'],
					'key' => $key
				)
			), true);
			
			$email = new CakeEmail('default');
			$email->to($user['User']['email'])
				->subject(__('Forgotten Password'))
				->template('forgotten_password')
				->emailFormat('text')
				->viewVars(compact('user', 'url'))
				->send();
			
			return true;
		}
		catch (SocketException $e) {
			$this->log("Unable to send forgotten password to user id ({$user['User']['id']}): {$e->getMessage()})", LOG_ERROR);
		}
		return false;
	}
	
/**
 * Update user password and email them the new password
 * 
 * @param array $user User array
 * @param string $key Encrypted key to validate request
 * @return boolean Return true on success, otherwise return false
 */
	public function sendForgottenNewPassword($user, $key) {
		
		// verify key
		if ($key == Security::hash($user['User']['salt'] . $user['User']['id'])) {
			$password = $this->_generatePassword();
			$user['User']['password'] = $password;
			if ($this->save($user, false, array('password', 'salt'))) {
				
				App::uses('CakeEmail', 'Network/Email');
				try {
					$email = new CakeEmail('default');
					$email->to($user['User']['email'])
						->subject(__('Password Updated'))
						->template('password_updated')
						->emailFormat('text')
						->viewVars(compact('user', 'password'))
						->send();

					return true;
				}
				catch (SocketException $e) {
					$this->log("Unable to send updated password to user id ({$user['User']['id']}): {$e->getMessage()})", LOG_ERROR);
				}
			}
		}
		
		return false;
	}
	
/**
 * Generate a random password
 * 
 * @param int $length Length of the password. Default 8
 * @return string Random password
 */
	protected function _generatePassword($length = 8) {
		
		$length = (int) $length;
		$password = '';

		// define possible characters
		$possible = '2346789bcdfghjkmnpqrtvwxyzBCDFGHJKLMNPQRTVWXYZ';

		// check for length overflow and truncate if necessary
		$maxlength = strlen($possible);
		if ($length > $maxlength) {
			$length = $maxlength;
		}

		// add random characters to $password until $length is reached
		$i = 0;
		while ($i < $length) {

			// pick a random character from the possible ones
			$char = substr($possible, mt_rand(0, $maxlength - 1), 1);

			// have we already used this character in $password?
			if (!strstr($password, $char)) {
				$password .= $char;
				$i++;
			}
		}
		
		return $password;
	}
}
