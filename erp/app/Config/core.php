<?php
/**
 * This is core configuration file.
 *
 * Use it to configure core behavior of Cake.
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

//setLocale(LC_ALL, 'deu');
//Configure::write('Config.language', 'deu');

/**
 * CakePHP Debug Level:
 *
 * Production Mode:
 *  0: No error messages, errors, or warnings shown. Flash messages redirect.
 *
 * Development Mode:
 *  1: Errors and warnings shown, model caches refreshed, flash messages halted.
 *  2: As in 1, but also with full debug messages and SQL output.
 *
 * In production mode, flash messages redirect after a time interval.
 * In development mode, you need to click the flash message to continue.
 */
 
 //die("sorry");
 
    Configure::write('debug',0);
        define('approval_amount', 10000);
        define('cards', 0);
        define('security', 0);
        define('card_charge', 50);
        define('servicecharge', 10);
        
        define('shiftstart'," 06:00:00");
        define('shiftend'," 05:59:59");
        
        define('apiKey', 'sk_97e5cb89072847fab9e77d676c6b5185');
        define('appName', 'FarmkrispNew');
        define('srcNumber', '917087666303');
        define('appId', 'ae47ee57-e79b-41cf-9b09-3fd83a795481');
        define('templateId', '0b011dfc-d90c-4c20-aee1-c544f6bbe64d');
        define('pdfUrl', 'https://phylopos.in/ABA/erp/Cron/dsr_pdf_whatsapp');
        
        // define('sync_link','http://phylopos.in/ABA/erp/Cron');
        define('sync_store_id',[1]);
        define('sync_store_id_val',1);
        
        // Reelo integration configuration
        define('REELO_VENDOR_ID', '');
        define('REELO_BASE_URL', '');
        define('REELO_AUTH_KEY', '');
        
        /**
         * Eazydiner API configurations
         */
        Configure::write('Eazydiner', array(
            'login_url'       => '',
            'status_url'      => '',
            'cron_token_url'  => '',
            'cron_status_url' => '',
            'secret'          => '',
            'version'         => '',
            'username'        => '',
            'authkey'         => ''
        ));
        
        /**
         * Zomato API configurations
         */
        Configure::write('Zomato', array(
            'validate_url' => '',
            'api_key'      => '',
            'partner_id'   => 0,
            'debug'        => 1
        ));
        
                
        $push_tables = [
            'order_payments' => 'Order_payment',
            'order_orderhistories' => 'Order_orderhistory',
            'order_modifier' => 'Order_modifier',
            'order_customers' => 'Order_customers',
            'online_order' => 'Online_order',
            'order_waiters' => 'Order_waiter',
            'order_transactions' => 'Order_transaction',
            'order_tax' => 'Order_tax',
            'order_table_tracking' => 'Order_table_tracking',
            'order_split_payments' => 'Order_split_payment',
            'order_split_transactions' => 'Order_split_transaction',
            'order_split_orderhistories' => 'Order_split_orderhistory',
            'order_orderhistories_json' => 'Order_orderhistory_json',
            'user_tracks' => 'User_track',
            'sync_errors' => 'Sync_error',
            'stock_ledger' => 'Stock_ledger',
            'tax_charges' => 'Tax_charge',
            'reelo_discounts' => 'ReeloDiscount'
        ];
        define('push_tables',$push_tables);

        $bill_tables = [
            'order_transactions' => 'Order_transaction',
            'order_orderhistories' => 'Order_orderhistory',
            'order_tax' => 'Order_tax',
            'order_payments' => 'Order_payment',
            'order_modifier' => 'Order_modifier',
            'order_split_payments' => 'Order_split_payment',
            'order_split_transactions' => 'Order_split_transaction',
            'order_split_orderhistories' => 'Order_split_orderhistory',
            'order_orderhistories_json' => 'Order_orderhistory_json'
        ];
        define('bill_tables', $bill_tables);

        $non_bill_tables = [
            'order_customers' => 'Order_customers',
            'online_order' => 'Online_order',
            'order_waiters' => 'Order_waiter',
            'order_table_tracking' => 'Order_table_tracking',
            'user_tracks' => 'User_track',
            'sync_errors' => 'Sync_error',
            'stock_ledger' => 'Stock_ledger',
            'tax_charges' => 'Tax_charge'
       ];
        define('non_bill_tables', $non_bill_tables);
        
        define('push_table_config', [
            // bill tables — all use store_id
            'order_payments'              => ['site_key' => 'store_id'],
            'order_orderhistories'        => ['site_key' => 'store_id'],
            'order_modifier'              => ['site_key' => 'store_id'],
            'order_transactions'          => ['site_key' => 'store_id'],
            'order_tax'                   => ['site_key' => 'store_id'],
            'order_split_payments'        => ['site_key' => 'store_id'],
            'order_split_transactions'    => ['site_key' => 'store_id'],
            'order_split_orderhistories'  => ['site_key' => 'store_id'],
            'order_orderhistories_json'   => ['site_key' => 'store_id'],
			
			// other charges taxes
            'tax_charges'   => ['site_key' => 'store_id'], 
        
            // non-bill tables — verify column exists before setting
            'order_customers'             => ['site_key' => 'store_id'],   // verify
            'online_order'                => ['site_key' => 'store_id'],   // verify
            'order_table_tracking'        => ['site_key' => 'store_id'],
            'stock_ledger'                => ['site_key' => 'store_id'],
        
            // tables with location_id
            'order_waiters'               => ['site_key' => 'location_id'],
            'user_tracks'                 => ['site_key' => 'location_id'], // or null if column missing
            
            'sync_errors'  => ['site_key' => 'store_id'],
        ]);
        
        define('sync_bill_limit', 100);
        define('sync_record_limit', 200);
        define('sync_max_quarantine_attempts', 3);
        define('batchSize', 100);
        
        $pull_tables = [ 
            'catalog_category' => 'Catalog_category',
            'catalog_category_branches' => 'Catalog_category_branches',
            'catalog_modifier' => 'Catalog_modifier',
            'catalog_modifier_group' => 'Catalog_modifier_group',
            'catalog_package' => 'Catalog_package',
            'catalog_product' => 'Catalog_product',
            'catalog_product_attribute' => 'Catalog_product_attribute',
            'catalog_product_branches' => 'Catalog_product_branches',
            'catalog_product_tax' => 'Catalog_product_tax',
            'catalog_subcategory' => 'Catalog_subcategory',
            'catalog_tax' => 'Catalog_tax',
            'catalog_units' => 'Catalog_unit',
            'catalog_unit_mapping' => 'Catalog_unit_mapping',
            'kds_users_station_mapping' => 'Kds_users_station_mapping',
            'kds_users' => 'Kds_user',
            'locations' => 'Location',
            'user_modules' => 'User_module',
            'user_locations' => 'User_location',
            'users' => 'User',
            // 'pos_printers' => 'Pos_printer',
            'pos_offers' => 'Offer',
            'inventory_linking' => 'Inventory_linking',
            'catalog_product_tax_outlet' => 'Catalog_product_tax_outlet',
            'inventory_unit' => 'Inventory_unit',
            'inventory_skus' => 'Inventory_sku',
            'inventory_tax' => 'Inventory_tax',
            'inventory_sku_tax' => 'Inventory_sku_tax',
            'inventory_semifinished' => 'Inventory_semifinished',
            'inventory_semifinished_tax' => 'Inventory_semifinished_tax',
            'order_tables' => 'Order_table',
            'order_waiters' => 'Order_waiter'
        ];
        define('pull_tables',$pull_tables);
        
        $states = [
            'Andhra Pradesh' => 'Andhra Pradesh',
            'Arunachal Pradesh' => 'Arunachal Pradesh',
            'Assam' => 'Assam',
            'Bihar' => 'Bihar',
            'Chandigarh' => 'Chandigarh',
            'Chhattisgarh' => 'Chhattisgarh',
            'Delhi' => 'Delhi',
            'Goa' => 'Goa',
            'Gujarat' => 'Gujarat',
            'Haryana' => 'Haryana',
            'Himachal Pradesh' => 'Himachal Pradesh',
            'Jharkhand' => 'Jharkhand',
            'Karnataka' => 'Karnataka',
            'Kerala' => 'Kerala',
            'Madhya Pradesh' => 'Madhya Pradesh',
            'Maharashtra' => 'Maharashtra',
            'Manipur' => 'Manipur',
            'Meghalaya' => 'Meghalaya',
            'Mizoram' => 'Mizoram',
            'Nagaland' => 'Nagaland',
            'Odisha' => 'Odisha',
            'Punjab' => 'Punjab',
            'Rajasthan' => 'Rajasthan',
            'Sikkim' => 'Sikkim',
            'Tamil Nadu' => 'Tamil Nadu',
            'Telangana' => 'Telangana',
            'Tripura' => 'Tripura',
            'Uttar Pradesh' => 'Uttar Pradesh',
            'Uttarakhand' => 'Uttarakhand',
            'West Bengal' => 'West Bengal'
        ];
        define('states',$states);
        
        $modules=[
            'card_management',
            'quick_order',
            'table_order',
            'reports'
        ];
        $moduleswithlabel=[
            'card_management' => 'Card Management',
            'quick_order' => 'Quick Order',
            'table_order' => 'Table Order',
            'reports' => 'Reports'
        ];
        $subadminmodule=[
            'location_management',
            'payment_partner_list',
            'subadmin_management',
            'cashier_management',
            'kds_management',
            'card_management',
            'catalogue_management',
            'inventory_management',
            'indent_management',
            'table_management',
            'waiter_management',
            'coupon_management',
            'report_management',
            'booking_management',
            'purchase_head_management',
            'sub_report_management',
            // 'editing_management'
        ];
        $subadminmoduleswithlabel=[
            'location_management' => 'Location Management',
            'payment_partner_list' => 'Payment Partner',
            'subadmin_management' => 'Subadmin Management',
            'cashier_management' => 'Cashier Management',
            'kds_management' => 'KDS Management',
            'card_management' => 'Card Management',
            'catalogue_management' => 'Catalogue Management',
            'inventory_management' => 'Inventory Management',
            'indent_management' => 'Indent Management',
            'table_management' => 'Table Management',
            'waiter_management' => 'Waiter Management',
            'coupon_management' => 'Coupon Management',
            'report_management' => 'Report Management',
            'booking_management' => 'Booking Management',
            'purchase_head_management' => 'Purchase Head Management',
            'sub_report_management' => 'Sub Report Management',
            // 'editing_management'    => 'Editing Management',
        ];
        define('modules',$modules);
        define('moduleswithlabel',$moduleswithlabel);
        define('subadminmodule',$subadminmodule);
        define('subadminmoduleswithlabel',$subadminmoduleswithlabel);
        
        $subadminsubmenus = [
            'kds_management' => [
                'kds'         => 'KDS',
                'kds_users'   => 'Users',
                'kds_reports' => 'Report',
            ],
            'catalogue_management' => [
                'category_list'         => 'Category List',
                'store_category'        => 'Store Categories',
                'subcategory_list'      => 'Sub Category List',
                'unit_list'             => 'Units',
                'unit_mapping'          => 'Unit Mapping',
                'tax_list'              => 'Taxes',
                'outlet_tax_list'       => 'Outlet Tax Overrides',
                'product_list'          => 'Product List',
                'store_products'        => 'Store Products',
                'modifiers'             => 'Modifiers',
                'modifiers_group'       => 'Modifiers Group',
                'offers'                => 'Offers',
                'replace_sku'           => 'Replace SKU'
            ],
            'inventory_management' => [
                'inv_category_list'         => 'Category List',
                'inv_subcategory_list'      => 'Sub Category List',
                'sku_list'                  => 'SKUs',
                'semifinished_list'         => 'Semi-Finished',
                'recipe_integrity'          => 'Recipe Integrity',
                'vendor_list'               => 'Vendors',
                'internal_indent_list'      => 'Internal Indent',
                'po_list'                   => 'Purchase Order',
                'services'                  => 'Services',
                'service_po_list'           => 'Service Purchase Order',
                'stockin'                   => 'Stockin',
                'auditstock'                => 'SKU Audit',
                'stock_damage'              => 'SKU Damage',
                'stock_transfer'            => 'Stock Transfer',
                'return_to_warehouse'            => 'Return to Warehouse',
            ],
            'indent_management' => [
                'indent' => 'Indent',
                'grn'    => 'GRN',
            ],
            'report_management' => [
                'dsr_report'                     => 'DSR Report',
                'employee_sales_report'          => 'Employee Sales Report',
                'wallet_report'                  => 'Wallet Report',
                'transaction_report'             => 'Transaction Report',
                'item_sale_report'               => 'Item Sale Report',
                'discount_report'                => 'Discount Report',
                'bill_wise_summary'              => 'Bill Wise Summary',
                'hourly_sales'                   => 'Hourly Sales',
                'bill_cancel'                    => 'Bill Cancel Report',
                'void_order'                     => 'Void Order Report',
                'wallet_history'                 => 'Wallet History',
                'card_report'                    => 'Card History',
                'stock_level'                    => 'DC Stock Level',
                'purchaseorder_report'           => 'Purchase Order Report',
                'purchase_item_ledger'           => 'Purchase Item Ledger Report',
                'complimentary_detail'           => 'Complimentary Report',
                'reprint_report'                 => 'Reprint Report',
                'bill_kot_delete_report'         => 'Bill & KOT Delete Report',
                'removed_service_charges_report' => 'Removed Service Charges Report',
                'kot_detail_report'              => 'KOT Detail Report',
                'bill_item_detail_report'        => 'Bill Item Report',
                'bill_wise_sales_report'         => 'Bill Wise Sales Report',
                'vendor_wise_purchase_report'    => 'Vendor Wise Purchase Report',
                'po_report_v2'                   => 'PO Report V2',
                'service_po_report'              => 'Service Purchase Order Report',
                'item_wise_purchase_report'      => 'Item Wise Purchase Report',
                'purchase_classification'        => 'CAPEX/OPEX Classification',
                'material_usage_report'        => 'Material Usage & Cost Report',
                'bills_without_sc'        => 'Service Charge Removal Report',
                'employeewise_customer_info'   => 'Employee Wise Customer Info'
            ],
        ];
        
        define('subadminsubmenus', $subadminsubmenus);
        
        define('smslink','https://phylopos.in/SMS/');
        
        date_default_timezone_set('Asia/Kolkata');
        Configure::write('Config.timezone', 'Asia/Kolkata');

//Configure::write('');
/**
 * Configure the Error handler used to handle errors for your application. By default
 * ErrorHandler::handleError() is used. It will display errors using Debugger, when debug > 0
 * and log errors with CakeLog when debug = 0.
 *
 * Options:
 *
 * - `handler` - callback - The callback to handle errors. You can set this to any callable type,
 *   including anonymous functions.
 *   Make sure you add App::uses('MyHandler', 'Error'); when using a custom handler class
 * - `level` - integer - The level of errors you are interested in capturing.
 * - `trace` - boolean - Include stack traces for errors in log files.
 *
 * @see ErrorHandler for more information on error handling and configuration.
 */
    Configure::write('Error', array(
        'handler' => 'ErrorHandler::handleError',
        'level' => E_ALL & ~E_DEPRECATED,
        'trace' => true
    ));

/**
 * Configure the Exception handler used for uncaught exceptions. By default,
 * ErrorHandler::handleException() is used. It will display a HTML page for the exception, and
 * while debug > 0, framework errors like Missing Controller will be displayed. When debug = 0,
 * framework errors will be coerced into generic HTTP errors.
 *
 * Options:
 *
 * - `handler` - callback - The callback to handle exceptions. You can set this to any callback type,
 *   including anonymous functions.
 *   Make sure you add App::uses('MyHandler', 'Error'); when using a custom handler class
 * - `renderer` - string - The class responsible for rendering uncaught exceptions. If you choose a custom class you
 *   should place the file for that class in app/Lib/Error. This class needs to implement a render method.
 * - `log` - boolean - Should Exceptions be logged?
 * - `extraFatalErrorMemory` - integer - Increases memory limit at shutdown so fatal errors are logged. Specify
 *   amount in megabytes or use 0 to disable (default: 4 MB)
 * - `skipLog` - array - list of exceptions to skip for logging. Exceptions that
 *   extend one of the listed exceptions will also be skipped for logging.
 *   Example: `'skipLog' => array('NotFoundException', 'UnauthorizedException')`
 *
 * @see ErrorHandler for more information on exception handling and configuration.
 */
    Configure::write('Exception', array(
        'handler' => 'ErrorHandler::handleException',
        'renderer' => 'ExceptionRenderer',
        'log' => true
    ));

/**
 * Application wide charset encoding
 */
    Configure::write('App.encoding', 'UTF-8');

/**
 * To configure CakePHP *not* to use mod_rewrite and to
 * use CakePHP pretty URLs, remove these .htaccess
 * files:
 *
 * /.htaccess
 * /app/.htaccess
 * /app/webroot/.htaccess
 *
 * And uncomment the App.baseUrl below. But keep in mind
 * that plugin assets such as images, CSS and JavaScript files
 * will not work without URL rewriting!
 * To work around this issue you should either symlink or copy
 * the plugin assets into you app's webroot directory. This is
 * recommended even when you are using mod_rewrite. Handling static
 * assets through the Dispatcher is incredibly inefficient and
 * included primarily as a development convenience - and
 * thus not recommended for production applications.
 */
    //Configure::write('App.baseUrl', env('SCRIPT_NAME'));

/**
 * To configure CakePHP to use a particular domain URL
 * for any URL generation inside the application, set the following
 * configuration variable to the http(s) address to your domain. This
 * will override the automatic detection of full base URL and can be
 * useful when generating links from the CLI (e.g. sending emails).
 * If the application runs in a subfolder, you should also set App.base.
 */
    //Configure::write('App.fullBaseUrl', 'http://example.com');

/**
 * The base directory the app resides in. Should be used if the
 * application runs in a subfolder and App.fullBaseUrl is set.
 */
    //Configure::write('App.base', '/my_app');

/**
 * Web path to the public images directory under webroot.
 * If not set defaults to 'img/'
 */
    //Configure::write('App.imageBaseUrl', 'img/');

/**
 * Web path to the CSS files directory under webroot.
 * If not set defaults to 'css/'
 */
    //Configure::write('App.cssBaseUrl', 'css/');

/**
 * Web path to the js files directory under webroot.
 * If not set defaults to 'js/'
 */
    //Configure::write('App.jsBaseUrl', 'js/');

/**
 * Uncomment the define below to use CakePHP prefix routes.
 *
 * The value of the define determines the names of the routes
 * and their associated controller actions:
 *
 * Set to an array of prefixes you want to use in your application. Use for
 * admin or other prefixed routes.
 *
 *  Routing.prefixes = array('admin', 'manager');
 *
 * Enables:
 *  `admin_index()` and `/admin/controller/index`
 *  `manager_index()` and `/manager/controller/index`
 */
    //Configure::write('Routing.prefixes', array('admin'));
Configure::write('Routing.prefixes', array('admin','manager'));
/**
 * Turn off all caching application-wide.
 */
    //Configure::write('Cache.disable', true);

/**
 * Enable cache checking.
 *
 * If set to true, for view caching you must still use the controller
 * public $cacheAction inside your controllers to define caching settings.
 * You can either set it controller-wide by setting public $cacheAction = true,
 * or in each action using $this->cacheAction = true.
 */
    //Configure::write('Cache.check', true);

/**
 * Enable cache view prefixes.
 *
 * If set it will be prepended to the cache name for view file caching. This is
 * helpful if you deploy the same application via multiple subdomains and languages,
 * for instance. Each version can then have its own view cache namespace.
 * Note: The final cache file name will then be `prefix_cachefilename`.
 */
    //Configure::write('Cache.viewPrefix', 'prefix');

/**
 * Session configuration.
 *
 * Contains an array of settings to use for session configuration. The defaults key is
 * used to define a default preset to use for sessions, any settings declared here will override
 * the settings of the default config.
 *
 * ## Options
 *
 * - `Session.cookie` - The name of the cookie to use. Defaults to 'CAKEPHP'
 * - `Session.timeout` - The number of minutes you want sessions to live for. This timeout is handled by CakePHP
 * - `Session.cookieTimeout` - The number of minutes you want session cookies to live for.
 * - `Session.checkAgent` - Do you want the user agent to be checked when starting sessions? You might want to set the
 *    value to false, when dealing with older versions of IE, Chrome Frame or certain web-browsing devices and AJAX
 * - `Session.defaults` - The default configuration set to use as a basis for your session.
 *    There are four builtins: php, cake, cache, database.
 * - `Session.handler` - Can be used to enable a custom session handler. Expects an array of callables,
 *    that can be used with `session_save_handler`. Using this option will automatically add `session.save_handler`
 *    to the ini array.
 * - `Session.autoRegenerate` - Enabling this setting, turns on automatic renewal of sessions, and
 *    sessionids that change frequently. See CakeSession::$requestCountdown.
 * - `Session.cacheLimiter` - Configure the cache control headers used for the session cookie.
 *   See http://php.net/session_cache_limiter for accepted values.
 * - `Session.ini` - An associative array of additional ini values to set.
 *
 * The built in defaults are:
 *
 * - 'php' - Uses settings defined in your php.ini.
 * - 'cake' - Saves session files in CakePHP's /tmp directory.
 * - 'database' - Uses CakePHP's database sessions.
 * - 'cache' - Use the Cache class to save sessions.
 *
 * To define a custom session handler, save it at /app/Model/Datasource/Session/<name>.php.
 * Make sure the class implements `CakeSessionHandlerInterface` and set Session.handler to <name>
 *
 * To use database sessions, run the app/Config/Schema/sessions.php schema using
 * the cake shell command: cake schema create Sessions
 */
    Configure::write('Session', array(
        'defaults' => 'php'
    ));

/**
 * A random string used in security hashing methods.
 */
    Configure::write('Security.salt', 'DYgfdgdfg67789df7g786s7dbds78fsd78fh6sd8jf6sd7f6sdmfsdf8sdR2G0FgaC9mi');

/**
 * A random numeric string (digits only) used to encrypt/decrypt strings.
 */
    Configure::write('Security.cipherSeed', '32456234786780348534453542496749683645');

/**
 * Apply timestamps with the last modified time to static assets (js, css, images).
 * Will append a query string parameter containing the time the file was modified. This is
 * useful for invalidating browser caches.
 *
 * Set to `true` to apply timestamps when debug > 0. Set to 'force' to always enable
 * timestamping regardless of debug value.
 */
    //Configure::write('Asset.timestamp', true);

/**
 * Compress CSS output by removing comments, whitespace, repeating tags, etc.
 * This requires a/var/cache directory to be writable by the web server for caching.
 * and /vendors/csspp/csspp.php
 *
 * To use, prefix the CSS link URL with '/ccss/' instead of '/css/' or use HtmlHelper::css().
 */
    //Configure::write('Asset.filter.css', 'css.php');

/**
 * Plug in your own custom JavaScript compressor by dropping a script in your webroot to handle the
 * output, and setting the config below to the name of the script.
 *
 * To use, prefix your JavaScript link URLs with '/cjs/' instead of '/js/' or use JsHelper::link().
 */
    //Configure::write('Asset.filter.js', 'custom_javascript_output_filter.php');

/**
 * The class name and database used in CakePHP's
 * access control lists.
 */
    Configure::write('Acl.classname', 'DbAcl');
    Configure::write('Acl.database', 'default');

/**
 * Uncomment this line and correct your server timezone to fix
 * any date & time related errors.
 */
    //date_default_timezone_set('UTC');

/**
 * `Config.timezone` is available in which you can set users' timezone string.
 * If a method of CakeTime class is called with $timezone parameter as null and `Config.timezone` is set,
 * then the value of `Config.timezone` will be used. This feature allows you to set users' timezone just
 * once instead of passing it each time in function calls.
 */
    //Configure::write('Config.timezone', 'Europe/Paris');

/**
 * Cache Engine Configuration
 * Default settings provided below
 *
 * File storage engine.
 *
 *   Cache::config('default', array(
 *      'engine' => 'File', //[required]
 *      'duration' => 3600, //[optional]
 *      'probability' => 100, //[optional]
 *      'path' => CACHE, //[optional] use system tmp directory - remember to use absolute path
 *      'prefix' => 'cake_', //[optional]  prefix every cache file with this string
 *      'lock' => false, //[optional]  use file locking
 *      'serialize' => true, //[optional]
 *      'mask' => 0664, //[optional]
 *  ));
 *
 * APC (http://pecl.php.net/package/APC)
 *
 *   Cache::config('default', array(
 *      'engine' => 'Apc', //[required]
 *      'duration' => 3600, //[optional]
 *      'probability' => 100, //[optional]
 *      'prefix' => Inflector::slug(APP_DIR) . '_', //[optional]  prefix every cache file with this string
 *  ));
 *
 * Xcache (http://xcache.lighttpd.net/)
 *
 *   Cache::config('default', array(
 *      'engine' => 'Xcache', //[required]
 *      'duration' => 3600, //[optional]
 *      'probability' => 100, //[optional]
 *      'prefix' => Inflector::slug(APP_DIR) . '_', //[optional] prefix every cache file with this string
 *      'user' => 'user', //user from xcache.admin.user settings
 *      'password' => 'password', //plaintext password (xcache.admin.pass)
 *  ));
 *
 * Memcached (http://www.danga.com/memcached/)
 *
 * Uses the memcached extension. See http://php.net/memcached
 *
 *   Cache::config('default', array(
 *      'engine' => 'Memcached', //[required]
 *      'duration' => 3600, //[optional]
 *      'probability' => 100, //[optional]
 *      'prefix' => Inflector::slug(APP_DIR) . '_', //[optional]  prefix every cache file with this string
 *      'servers' => array(
 *          '127.0.0.1:11211' // localhost, default port 11211
 *      ), //[optional]
 *      'persistent' => 'my_connection', // [optional] The name of the persistent connection.
 *      'compress' => false, // [optional] compress data in Memcached (slower, but uses less memory)
 *  ));
 *
 *  Wincache (http://php.net/wincache)
 *
 *   Cache::config('default', array(
 *      'engine' => 'Wincache', //[required]
 *      'duration' => 3600, //[optional]
 *      'probability' => 100, //[optional]
 *      'prefix' => Inflector::slug(APP_DIR) . '_', //[optional]  prefix every cache file with this string
 *  ));
 */

/**
 * Configure the cache handlers that CakePHP will use for internal
 * metadata like class maps, and model schema.
 *
 * By default File is used, but for improved performance you should use APC.
 *
 * Note: 'default' and other application caches should be configured in app/Config/bootstrap.php.
 *       Please check the comments in bootstrap.php for more info on the cache engines available
 *       and their settings.
 */
$engine = 'File';

// In development mode, caches should expire quickly.
$duration = '+999 days';
if (Configure::read('debug') > 0) {
    $duration = '+10 seconds';
}

// Prefix each application on the same server with a different string, to avoid Memcache and APC conflicts.
$prefix = 'myapp_';

/**
 * Configure the cache used for general framework caching. Path information,
 * object listings, and translation cache files are stored with this configuration.
 */
Cache::config('_cake_core_', array(
    'engine' => $engine,
    'prefix' => $prefix . 'cake_core_',
    'path' => CACHE . 'persistent' . DS,
    'serialize' => ($engine === 'File'),
    'duration' => $duration
));

/**
 * Configure the cache for model and datasource caches. This cache configuration
 * is used to store schema descriptions, and table listings in connections.
 */
Cache::config('_cake_model_', array(
    'engine' => $engine,
    'prefix' => $prefix . 'cake_model_',
    'path' => CACHE . 'models' . DS,
    'serialize' => ($engine === 'File'),
    'duration' => $duration
));


