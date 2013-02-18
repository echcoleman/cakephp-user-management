<?php
App::uses('UserManagementAppController', 'UserManagement.Controller');
/**
 * Users Controller
 *
 * @property User $User
 */
class UsersController extends UserManagementAppController {
	
/**
 * Run before any other functions in controller
 */
	public function beforeFilter() {
		parent::beforeFilter();
		
		// always allow following actions
		$this->Auth->allow('login', 'logout', 'forgotten_password', 'forgotten_confirm');
	}
	
/**
 * User login
 * 
 * @return void
 */
	public function login() {
		$this->set('title_for_layout', __('Login'));
		
		// if user already logged in, redirect
		if ($this->Auth->loggedIn()) {
			$this->redirect($this->Auth->loginRedirect);
		}
		
		// process login request
		if ($this->request->is('post')) {
			if ($this->Auth->login()) {
				$this->redirect($this->Auth->redirect());
			} else {
				// check if user could not login because they aren't active
				$this->Auth->authenticate['UserForm']['scope'] = false;
				if ($this->Auth->identify($this->request, $this->response)) {
					$this->Session->setFlash('Your account is not active. Please confirm your account via email or contact our administrators.', null, null, 'error');
				}
				else {
					$this->Session->setFlash('Your username or password was incorrect. Please try again.', null, null, 'error');
				}
			}
		}
	}

/**
 * User logout
 * 
 * @return void
 */
	public function logout() {
		$this->Session->setFlash('Logged out');
		$this->redirect($this->Auth->logout());
	}
	
/**
 * Forgotten password
 * 
 * @return void
 */
	public function forgotten_password() {
		$this->set('title_for_layout', __('Forgotten Password'));
		
		// process forgotten password
		if ($this->request->is('post')) {
			if (isset($this->request->data['User']['username'])) {
				
				// @todo: Found bug in CakePHP when try to use below statement,
				// had to supply username twice (for username and email values check)
				$username = $this->request->data['User']['username'];
				$user = $this->User->findByUsernameOrEmail($username, $username);
				
				// ensure user is valid and active
				if (!empty($user)) {
					if ($user['User']['active'] == 1) {
						if ($this->User->sendForgottenPassword($user)) {
							$this->Session->setFlash(__('Please check your mail to reset your password'), null, null, 'error');
							unset($this->request->data['User']);
						}
						else {
							$this->Session->setFlash(__('Unable process your request. Please try again.'), null, null, 'error');
						}
					}
					else {
						$this->Session->setFlash('Your account is not active. Please confirm your account via email or contact our administrators.', null, null, 'error');
					}
				}
				else {
					$this->Session->setFlash(__('Unable to find user with submitted username or email address. Please try again.'), null, null, 'error');
				}
			}
		}
	}
	
/**
 * Confirm forgotten password
 * 
 * @return void
 */
	public function forgotten_confirm() {
		$this->set('title_for_layout', __('Forgotten Password Confrimation'));
		
		// ensure user id and key is available
		$invalidConfirm = true;
		if (isset($this->request->query['uid']) && isset($this->request->query['key'])) {
			$user = $this->User->findById($this->request->query['uid']);
			if (!empty($user)) {
				if ($this->User->sendForgottenNewPassword($user, $this->request->query['key'])) {
					$invalidConfirm = false;
					$this->Session->setFlash(__('Your password has been updated and emailed to you'));
				}
				else {
					$this->Session->setFlash(__('Unable to update your password. Please try again.'), null, null, 'error');
				}
			}
		}
		if ($invalidConfirm) {
			$this->Session->setFlash(__('Invalid forgotten password confrimation received. Please try again.'), null, null, 'error');
		}
		$this->redirect(array('action' => 'login'));
	}

/**
 * view method
 *
 * @return void
 */
	public function view_profile() {
		$this->set('title_for_layout', __('Profile'));
		
		$id = $this->Auth->user('id');
		$this->User->id = $id;
		if (!$this->User->exists()) {
			throw new NotFoundException(__('Invalid user'));
		}
		$this->set('user', $this->User->read(null, $id));
	}

/**
 * add/edit method
 *
 * @return void
 */
	public function edit_profile() {
		$this->set('title_for_layout', __('Update Profile'));
		
		$id = $this->Auth->user('id');
		$this->User->id = $id;
		if (!$this->User->exists()) {
			throw new NotFoundException(__('Invalid user'));
		}
		if ($this->request->is('post') || $this->request->is('put')) {
			// remove/set any data users are not allowed to edit
			$this->request->data['id'] = $id;
			unset($this->request->data['group_id']);
			unset($this->request->data['active']);
			if ($this->User->save($this->request->data)) {
				$this->Session->setFlash(__('Your profile has been updated'));
				$this->redirect(array('action' => 'view_profile'));
			} else {
				$this->Session->setFlash(__('Unable to update your profile. Please, try again.'), null, null, 'error');
			}
		}
		if (empty($this->data)) {
			$this->request->data = $this->User->read(null, $id);
		}
	}

/**
 * admin_index method
 *
 * @return void
 */
	public function admin_index() {
		$this->User->recursive = 0;
		$this->set('users', $this->paginate());
	}

/**
 * admin_view method
 *
 * @param string $id
 * @return void
 */
	public function admin_view($id = null) {
		$this->User->id = $id;
		if (!$this->User->exists()) {
			throw new NotFoundException(__('Invalid user'));
		}
		$this->set('user', $this->User->read(null, $id));
	}

/**
 * admin_add method (uses admin_edit method)
 * @return void
 */
	public function admin_add() {
		$this->setAction('admin_edit');
	}

/**
 * admin_add/edit method
 *
 * @param string $id
 * @return void
 */
	public function admin_edit($id = null) {
		if ($id) {
			$this->User->id = $id;
			if (!$this->User->exists()) {
				throw new NotFoundException(__('Invalid user'));
			}
		}
		if ($this->request->is('post') || $this->request->is('put')) {
			if ($this->User->save($this->request->data)) {
				$this->Session->setFlash(__('The user has been saved'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The user could not be saved. Please, try again.'), null, null, 'error');
			}
		}
		if ($id && empty($this->data)) {
			$this->request->data = $this->User->read(null, $id);
		}
		$groups = $this->User->Group->find('list');
		$this->set(compact('groups'));
	}

/**
 * admin_delete method
 *
 * @param string $id
 * @return void
 */
	public function admin_delete($id = null) {
		if (!$this->request->is('post')) {
			throw new MethodNotAllowedException();
		}
		$this->User->id = $id;
		if (!$this->User->exists()) {
			throw new NotFoundException(__('Invalid user'));
		}
		if ($this->User->delete()) {
			$this->Session->setFlash(__('User deleted'));
			$this->redirect(array('action' => 'index'));
		}
		$this->Session->setFlash(__('User was not deleted'), null, null, 'error');
		$this->redirect(array('action' => 'index'));
	}
}
