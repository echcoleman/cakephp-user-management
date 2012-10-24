<?php
/**
 * Routes for User Management Plugin
 */

Router::connect('/login', array('plugin' => 'UserManagement', 'controller' => 'users', 'action' => 'login'));
Router::connect('/logout', array('plugin' => 'UserManagement', 'controller' => 'users', 'action' => 'logout'));
Router::connect('/forgotten_password', array('plugin' => 'UserManagement', 'controller' => 'users', 'action' => 'forgotten_password'));
Router::connect('/forgotten_confirm', array('plugin' => 'UserManagement', 'controller' => 'users', 'action' => 'forgotten_confirm'));
Router::connect('/view_profile', array('plugin' => 'UserManagement', 'controller' => 'users', 'action' => 'view_profile'));
Router::connect('/edit_profile', array('plugin' => 'UserManagement', 'controller' => 'users', 'action' => 'edit_profile'));
