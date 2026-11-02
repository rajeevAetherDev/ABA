<?php
/**
 * Routes configuration
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different URLs to chosen controllers and their actions (functions).
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       app.Config
 * @since         CakePHP(tm) v 0.2.9
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
 
/**
 * Here, we are connecting '/' (base path) to controller called 'Pages',
 * its action called 'display', and we pass a param to select the view file
 * to use (in this case, /app/View/Pages/home.ctp)...
 */
 
 Router::parseExtensions('xml');

	Router::connect('/', array('controller' => 'dashboard', 'action' => 'login'));
/**
 * ...and connect the rest of 'Pages' controller's URLs.
 */
	//Router::connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));
	

// =============================================================================
// UrbanPiper Integration Routes  (added 2026-03-03)
// MUST be before require CAKE default routes to avoid wildcard match
// Cloud webhook receivers
Router::connect('/urbanpiper/webhook/order',
    array('controller' => 'urbanpiper', 'action' => 'order_webhook'));
Router::connect('/urbanpiper/webhook/order_update',
    array('controller' => 'urbanpiper', 'action' => 'order_update_webhook'));
Router::connect('/urbanpiper/webhook/menu_callback',
    array('controller' => 'urbanpiper', 'action' => 'menu_callback_webhook'));

// Store pull-agent endpoints
Router::connect('/urbanpiper/store/pull_jobs',
    array('controller' => 'urbanpiper', 'action' => 'pull_jobs'));
Router::connect('/urbanpiper/store/ack_job',
    array('controller' => 'urbanpiper', 'action' => 'ack_job'));

// Admin view (requires CakePHP session auth)
Router::connect('/urbanpiper/admin/orders',
    array('controller' => 'urbanpiper', 'action' => 'admin_orders'));

// Local POS stubs (store-side only — block externally via Apache)
Router::connect('/local_up/order_create',
    array('controller' => 'local_up', 'action' => 'order_create'));
Router::connect('/local_up/order_update',
    array('controller' => 'local_up', 'action' => 'order_update'));
Router::connect('/local_up/order_cancel',
    array('controller' => 'local_up', 'action' => 'order_cancel'));
// =============================================================================

Router::connect("/dashboard/online_orders",
    array("controller" => "dashboard", "action" => "online_orders"));

// Payment Partner Controller Routes
Router::connect('/paymentpartner/process_partner_payment', array('controller' => 'paymentpartner', 'action' => 'process_partner_payment'));
Router::connect('/paymentpartner/process_eazydiner_verification', array('controller' => 'paymentpartner', 'action' => 'process_eazydiner_verification'));
Router::connect('/paymentpartner/get_eazydiner_status', array('controller' => 'paymentpartner', 'action' => 'get_eazydiner_status'));
Router::connect('/paymentpartner/update_bills_discount', array('controller' => 'paymentpartner', 'action' => 'update_bills_discount'));

/**
 * Load all plugin routes. See the CakePlugin documentation on
 * how to customize the loading of plugin routes.
 */
 
	CakePlugin::routes();

/**
 * Load the CakePHP default routes. Only remove this if you do not want to use
 * the built-in default routes.
 */
	require CAKE . 'Config' . DS . 'routes.php';
