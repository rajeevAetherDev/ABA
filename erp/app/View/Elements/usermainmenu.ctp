<?php

$menuOptions ="";


$currentURI = $_SERVER['REQUEST_URI'];

// Extract the page name from the URI
$currentPage = basename($currentURI);

// If the current page is a directory index file (e.g., index.php), remove it from the URI
$currentPage = str_replace('index.php', '', $currentPage);

// Remove any query parameters from the URI
$currentPage = strtok($currentPage, '?');

$id=$this->Session->read('Auth.User.id');
$role=$this->Session->read('Auth.User.role');	
$modules=subadminmodule;
$flags = [];

// ============================================================
// CHANGED: parse both parent flags AND submenu flags together
// ============================================================
if($role!='admin'){
    foreach ($modules as $module) {
        $flags[$module] = false;
    }

    $modulenames=$this->Session->read('Auth.User.modules');	
    $modulesarray = explode(',', $modulenames);

    // NEW: separate parent modules and submenu entries
    $subFlags = []; // ['inventory_management' => ['sku_list' => true, ...]]

    foreach ($modulesarray as $entry) {
        $entry = trim($entry);
        if ($entry === '') continue;

        if (strpos($entry, ':') !== false) {
            // submenu entry e.g. 'inventory_management:sku_list'
            list($parent, $sub) = explode(':', $entry, 2);
            $subFlags[$parent][$sub] = true;
        } else {
            // parent module entry
            if (array_key_exists($entry, $flags)) {
                $flags[$entry] = true;
            }
        }
    }
} else {
    foreach ($modules as $module) {
        $flags[$module] = true;
    }
    // Admin gets all submenus
    $subFlags = [];
    foreach (subadminsubmenus as $parent => $subs) {
        $subFlags[$parent] = array_fill_keys(array_keys($subs), true);
    }
}

if (!empty($this->Session->read('Auth.User.role'))) {

    
    $menuOptions .= '<li class="nav-item">
        <h6 class="menu-title">
            <a class="' . ($currentPage == 'home' ? 'active' : '') . '" href="' . $this->Html->url(array('controller' => 'Dashboard', 'action' => 'home')) . '">Dashboard</a>
        </h6>
    </li>';
    $menuOptions .= '<li class="nav-item' . (empty($flags['location_management']) ? ' hidden' : '') . '">
        <h6 class="menu-title">
            <a class="' . (in_array($currentPage, ['create_location', 'location_list', 'view_location', 'edit_location']) ? 'active' : '') . '" href="' . $this->Html->url(array('controller' => 'Dashboard', 'action' => 'location_list')) . '">Location&nbsp;Management</a>
        </h6>
    </li>';
    
    $menuOptions .= '<li class="nav-item' . (empty($flags['payment_partner_list']) ? ' hidden' : '') . '">
        <h6 class="menu-title">
            <a class="' . (in_array($currentPage, ['create_payment_partner', 'payment_partner_list']) ? 'active' : '') . '" href="' . $this->Html->url(array('controller' => 'Dashboard', 'action' => 'payment_partner_list')) . '">Payment&nbsp;Partners</a>
        </h6>
    </li>';
    
    $menuOptions .= '<li class="nav-item' . (empty($flags['subadmin_management']) ? ' hidden' : '') . '">
        <h6 class="menu-title">
            <a class="' . (in_array($currentPage, ['create_subadmin', 'subadmin_list', 'edit_subadmin']) ? 'active' : '') . '" href="' . $this->Html->url(array('controller' => 'Dashboard', 'action' => 'subadmin_list')) . '">Subadmin&nbsp;Management</a>
        </h6>
    </li>';
    
    $menuOptions .= '<li class="nav-item' . (empty($flags['cashier_management']) ? ' hidden' : '') . '">
        <h6 class="menu-title">
            <a class="' . (in_array($currentPage, ['create_cashier', 'cashier_list', 'edit_cashier']) ? 'active' : '') . '" href="' . $this->Html->url(array('controller' => 'Dashboard', 'action' => 'cashier_list')) . '">Cashier&nbsp;Management</a>
        </h6>
    </li>';
    
    //kds
    $kdsPages = [
        'kds' => ['KDS', ['create_kds', 'kds']],
        'kds_users' => ['Users', ['kds_users','create_kds_users','edit_kds_users']],
        // 'kds_reports' => ['Report', ['kds_reports']],
    ];
    
    $allkdsPages = [];
    foreach ($kdsPages as $kdsdetails) {
        if (is_array($kdsdetails)) {
            $allkdsPages = array_merge($allkdsPages, $kdsdetails[1]);
        }
    }
    
    $kdsActive = in_array($currentPage, $allkdsPages);
    $kdsDisplayStyle = $kdsActive ? 'block' : 'none';
    
    $menuOptions .= '<li class="nav-item ' . (empty($flags['kds_management']) ? ' hidden' : '') . '" style="cursor:pointer;">';
    $menuOptions .= '<h6 class="menu-title" onclick="togglekds()">KDS Management</h6>';
    $menuOptions .= '<ul class="nav flex-column kds-submenu" style="display:' . $kdsDisplayStyle . ';">';
    
    // CHANGED: filter kds submenus by $subFlags
    foreach ($kdsPages as $page => $kdsdetails) {
        if ($role != 'admin' && empty($subFlags['kds_management'][$page])) {
            continue;
        }
        $label = is_array($kdsdetails) ? $kdsdetails[0] : $kdsdetails;
        $isActive = in_array($currentPage, is_array($kdsdetails) ? $kdsdetails[1] : [$page]) ? ' active' : '';
        $menuOptions .= '<li class="nav-item"><a class="nav-link' . $isActive . '" href="' . $this->Html->url(array('controller' => 'Dashboard', 'action' => $page)) . '">' . $label . '</a></li>';
    }
    
    $menuOptions .= '</ul></li>';
    
    $menuOptions .= '<li class="nav-item' . (empty($flags['card_management']) ? ' hidden' : '') . '">
        <h6 class="menu-title">
            <a class="' . ($currentPage == 'card_list' ? 'active' : '') . '" href="' . $this->Html->url(array('controller' => 'Dashboard', 'action' => 'card_list')) . '">Card&nbsp;Management</a>
        </h6>
    </li>';
    
    // Define all current catalogue pages
    $allCataloguePages = [
        'category_list', 'create_category', 'edit_category', 'view_category',
        'subcategory_list', 'create_subcategory', 'edit_subcategory', 'view_subcategory',
        'tax_list', 'create_tax', 'edit_tax',
        'unit_list', 'create_unit', 'edit_unit',
        'unit_mapping', 'create_unit_mapping', 'edit_unit_mapping',
        'outlet_tax_list', 'save_outlet_tax', 'delete_outlet_tax','add_outlet_tax',
        'product_list', 'create_product', 'edit_product', 'view_product', 'product_recipe', 'product_modifier',
        'store_category', 'manage_store_category', 'store_products', 'manage_store_products',
        'modifiers', 'create_modifier', 'edit_modifiers', 'view_modifier', 'modifier_recipe',
        'modifiers_group', 'create_modifiers_group', 'edit_modifier_group', 'view_modifiers_group',
        'offers', 'create_offers', 'view_offer', 'replace_sku','edit_offer'
    ];
    
    $catalogueActive = in_array($currentPage, $allCataloguePages);
    $catalogueDisplayStyle = $catalogueActive ? 'block' : 'none';
    
    $cataloguePages = [
        'category_list' => ['Category List', ['category_list', 'create_category', 'edit_category', 'view_category']],
        'store_category' => ['Store Categories', ['store_category', 'manage_store_category']],
        'subcategory_list' => ['Sub Category List', ['subcategory_list', 'create_subcategory', 'edit_subcategory', 'view_subcategory']],
        'unit_list' => ['Units', ['unit_list', 'create_unit', 'edit_unit']],
        'unit_mapping' => ['Unit Mapping', ['unit_mapping', 'create_unit_mapping', 'edit_unit_mapping']],
        'tax_list' => ['Taxes', ['tax_list', 'create_tax', 'edit_tax']],
        'outlet_tax_list' => ['Outlet Tax Overrides', ['outlet_tax_list', 'save_outlet_tax', 'delete_outlet_tax','add_outlet_tax']],
        'product_list' => ['Product List', ['product_list', 'create_product', 'edit_product', 'view_product', 'product_recipe', 'product_modifier']],
        'store_products' => ['Store Products', ['store_products', 'manage_store_products']],
        'modifiers' => ['Modifiers', ['modifiers', 'create_modifier', 'edit_modifiers', 'view_modifier', 'modifier_recipe']],
        'modifiers_group' => ['Modifiers Group', ['modifiers_group', 'create_modifiers_group', 'edit_modifier_group', 'view_modifiers_group']],
        'offers' => ['Offers', ['offers', 'create_offers', 'view_offer','edit_offer']],
        'replace_sku' => ['Replace SKU', ['replace_sku']]
    ];
    
    $menuOptions .= '<li class="nav-item ' . (empty($flags['catalogue_management']) ? ' hidden' : '') . '" style="cursor:pointer;">';
    $menuOptions .= '<h6 class="menu-title" onclick="togglecatalog()">Catalogue&nbspManagement</h6>';
    $menuOptions .= '<ul class="nav flex-column catalog-submenu" style="display:' . $catalogueDisplayStyle . ';">';
    
    // CHANGED: filter catalogue submenus by $subFlags
    foreach ($cataloguePages as $link => [$label, $relatedPages]) {
        if ($role != 'admin' && empty($subFlags['catalogue_management'][$link])) {
            continue;
        }
        $isActive = in_array($currentPage, $relatedPages) ? ' active' : '';
        $menuOptions .= '<li class="nav-item"><a class="nav-link' . $isActive . '" href="' . $this->Html->url(array('controller' => 'Dashboard', 'action' => $link)) . '">' . $label . '</a></li>';
    }
    
    $menuOptions .= '</ul></li>';
    
    $inventoryPages = [
        'inv_category_list' => ['Category List', ['inv_category_list', 'inv_create_category', 'inv_edit_category', 'inv_view_category']],
        'inv_subcategory_list' => ['Sub Category List', ['inv_subcategory_list', 'inv_create_subcategory', 'inv_edit_subcategory', 'inv_view_subcategory']],
        'sku_list' => ['SKUs', ['sku_list', 'create_sku', 'edit_sku', 'view_sku']],
        'semifinished_list' => ['Semi-Finished', ['semifinished_list', 'create_semifinished', 'edit_semifinished', 'view_semifinished', 'add_recipe']],
        'recipe_integrity' => ['Recipe Integrity', ['recipe_integrity', 'create_missing_ingredient']], 
        'vendor_list' => ['Vendors', ['vendor_list', 'create_vendor', 'edit_vendor', 'view_vendor', 'map_vendor_with_sku']],
        'internal_indent_list' => ['Internal Indent', ['internal_indent_list', 'create_internal_indent', 'edit_internal_indent', 'view_internal_indent']],
        'po_list' => ['Purchase Order', ['po_list', 'create_po', 'edit_po', 'view_po','create_po_without_indent']],
        'services' => ['Services', ['services', 'add_service', 'edit_service']],
        'service_po_list' => ['Service Purchase Order', ['service_po_list', 'create_service_po', 'edit_service_po', 'view_service_po']],
        'stockin' => ['Stockin', ['stockin']],
        'auditstock' => ['SKU Audit', ['auditstock', 'create_audit_stock', 'view_audit']],
        'stock_damage' => ['SKU Damage', ['stock_damage', 'create_damage', 'edit_damage', 'view_damage']],
        'return_to_warehouse' => ['Return to Warehouse', ['return_to_warehouse', 'view_return_to_warehouse', 'approve_return_to_warehouse']],
    ];
    
    $allInventoryPages = [];
    foreach ($inventoryPages as $details) {
        if (is_array($details)) {
            $allInventoryPages = array_merge($allInventoryPages, $details[1]);
        }
    }
    
    $inventoryActive = in_array($currentPage, $allInventoryPages);
    $inventoryDisplayStyle = $inventoryActive ? 'block' : 'none';
    
    $menuOptions .= '<li class="nav-item ' . (empty($flags['inventory_management']) ? ' hidden' : '') . '" style="cursor:pointer;">';
    $menuOptions .= '<h6 class="menu-title" onclick="toggleinventory()">Inventory&nbsp;Management</h6>';
    $menuOptions .= '<ul class="nav flex-column inventory-submenu" style="display:' . $inventoryDisplayStyle . ';">';
    
    // CHANGED: filter inventory submenus by $subFlags
    foreach ($inventoryPages as $page => $details) {
        if ($role != 'admin' && empty($subFlags['inventory_management'][$page])) {
            continue;
        }
        $label = is_array($details) ? $details[0] : $details;
        $isActive = in_array($currentPage, is_array($details) ? $details[1] : [$page]) ? ' active' : '';
        $menuOptions .= '<li class="nav-item"><a class="nav-link' . $isActive . '" href="' . $this->Html->url(array('controller' => 'Dashboard', 'action' => $page)) . '">' . $label . '</a></li>';
    }
    
    $menuOptions .= '</ul></li>';
    
    // $menuOptions .= '<li class="nav-item' . (empty($flags['editing_management']) ? ' hidden' : '') . '">
    //     <h6 class="menu-title">
    //         <a class="' . (in_array($currentPage, ['Editing Management', ['editing_management']]) ? 'active' : '') . '" href="editing_management">Editing&nbsp;Management</a>
    //     </h6>
    // </li>';
    
    $menuOptions .= '<li class="nav-item' . (empty($flags['purchase_head_management']) ? ' hidden' : '') . '">
        <h6 class="menu-title">
            <a class="' . (in_array($currentPage, ['po_approve_list','view_approve_po']) ? 'active' : '') . '" href="' . $this->Html->url(array('controller' => 'Dashboard', 'action' => 'po_approve_list')) . '">Purchase&nbsp;Approval</a>
        </h6>
    </li>';
    
    
    $indentPages = [
        'indent' => ['Indent', ['indent', 'approve_indent', 'process_indent']],
        'grn' => ['GRN', ['grn']],
    ];
    
    $allIndentPages = [];
    foreach ($indentPages as $details) {
        if (is_array($details)) {
            $allIndentPages = array_merge($allIndentPages, $details[1]);
        }
    }
    
    $indentActive = in_array($currentPage, $allIndentPages);
    $indentDisplayStyle = $indentActive ? 'block' : 'none';
    
    $menuOptions .= '<li class="nav-item ' . (empty($flags['indent_management']) ? ' hidden' : '') . '" style="cursor:pointer;">';
    $menuOptions .= '<h6 class="menu-title" onclick="toggleindent()">Outlet&nbsp;Management</h6>';
    $menuOptions .= '<ul class="nav flex-column indent-submenu" style="display:' . $indentDisplayStyle . ';">';
    
    // CHANGED: filter indent submenus by $subFlags
    foreach ($indentPages as $page => $details) {
        if ($role != 'admin' && empty($subFlags['indent_management'][$page])) {
            continue;
        }
        $label = is_array($details) ? $details[0] : $details;
        $isActive = in_array($currentPage, is_array($details) ? $details[1] : [$page]) ? ' active' : '';
        $menuOptions .= '<li class="nav-item"><a class="nav-link' . $isActive . '" href="' . $this->Html->url(array('controller' => 'Dashboard', 'action' => $page)) . '">' . $label . '</a></li>';
    }
    
    $menuOptions .= '</ul></li>';
    
    
    // Table Management
    if (!empty($flags['table_management'])) {
        $tableActive = in_array($currentPage, ['create_table', 'table_list', 'edit_table', 'table_category_list', 'create_table_category', 'edit_table_category']);
        $tableDisplayStyle = $tableActive ? 'block' : 'none';
        
        $menuOptions .= '<li class="nav-item" style="cursor:pointer;">';
        $menuOptions .= '<h6 class="menu-title" onclick="toggletable()">Table&nbsp;Management</h6>';
        $menuOptions .= '<ul class="nav flex-column table-submenu" style="display:' . $tableDisplayStyle . ';">';
        
        $isTableListActive = in_array($currentPage, ['create_table', 'table_list', 'edit_table']) ? ' active' : '';
        $isCatListActive = in_array($currentPage, ['create_table_category', 'table_category_list', 'edit_table_category']) ? ' active' : '';
        
        $menuOptions .= '<li class="nav-item"><a class="nav-link' . $isTableListActive . '" href="' . $this->Html->url(array('controller' => 'Dashboard', 'action' => 'table_list')) . '">Tables</a></li>';
        $menuOptions .= '<li class="nav-item"><a class="nav-link' . $isCatListActive . '" href="' . $this->Html->url(array('controller' => 'Dashboard', 'action' => 'table_category_list')) . '">Table Categories</a></li>';
        $menuOptions .= '</ul></li>';
    }
    
    // Waiter Management — no submenus, unchanged
    if (!empty($flags['waiter_management'])) {
        $active = in_array($currentPage, ['create_waiter', 'waiter_list', 'edit_waiter']) ? 'active' : '';
        $menuOptions .= '<li class="nav-item"><h6 class="menu-title"><a class="' . $active . '" href="' . $this->Html->url(array('controller' => 'Dashboard', 'action' => 'waiter_list')) . '">Waiter Management</a></h6></li>';
    }
    
    // Coupon Management — no submenus, unchanged
    if (!empty($flags['coupon_management'])) {
        $active = in_array($currentPage, ['create_coupon', 'coupon_list', 'edit_coupon']) ? 'active' : '';
        $menuOptions .= '<li class="nav-item"><h6 class="menu-title"><a class="' . $active . '" href="' . $this->Html->url(array('controller' => 'Dashboard', 'action' => 'coupon_list')) . '">Coupon Management</a></h6></li>';
    }
    
    
    $reportPages = [
        'dsr_report' => 'DSR Report',
        'employee_sales_report' => 'Employee Sales Report',
        'wallet_report' => 'Wallet Report',
        'transaction_report' => 'Transaction Report',
        'item_sale_report' => 'Item Sale Report',
        'discount_report' => 'Discount Report',
        'bill_wise_summary' => 'Bill Wise Summary',
        'hourly_sales' => 'Hourly Sales',
        'bill_cancel' => 'Bill Cancel Report',
        'void_order' => 'Void Order Report',
        'wallet_history' => 'Wallet History',
        'card_report' => 'Card History',
        'stock_level' => 'DC Stock Level',
        'purchaseorder_report' => 'Purchase Order Report',
        'purchase_item_ledger' => 'Purchase Item Ledger Report',
        'complimentary_detail' => 'Complimentary Report',
        'reprint_report' => 'Reprint Report',
        'bill_kot_delete_report' => 'Bill & KOT Delete Report',
        'removed_service_charges_report' => 'Removed Service Charges Report',
        'kot_detail_report' => 'KOT Detail Report',
        'bill_item_detail_report' => 'Bill Item Report',
        'bill_wise_sales_report' => 'Bill Wise Sales Report',
        'vendor_wise_purchase_report' => 'Vendor Wise Purchase Report',
        'po_report_v2' => 'PO Report V2',
        'service_po_report' => 'Service Puchase Order Report',
        'item_wise_purchase_report' => 'Item Wise Purchase Report',
		'purchase_classification' => 'CAPEX/OPEX Classification',
		'material_usage_report' => 'Material Usage & Cost Report',
		'bills_without_sc' => 'Service Charge Removal Report',
// 		'bill_lifecycle_report' => 'Bill Lifecycle Report',
		'employeewise_customer_info' => 'Employee Wise Customer Info',
   ];
    
    $reportActive = in_array($currentPage, array_keys($reportPages));
    $submenuDisplayStyle = $reportActive ? 'block' : 'none';
    $menuOptions .= '<li class="nav-item ' . (empty($flags['report_management']) ? ' hidden' : '') . '" style="cursor:pointer;">';
    $menuOptions .= '<h6 class="menu-title" onclick="toggleReports()">Report&nbspManagement</h6>';
    $menuOptions .= '<ul class="nav flex-column report-submenu" style="display:' . $submenuDisplayStyle . ';">';
    
    // CHANGED: filter report submenus by $subFlags and use Report controller URL
    foreach ($reportPages as $page => $label) {
        if ($role != 'admin' && empty($subFlags['report_management'][$page])) {
            continue;
        }
        $isActive = ($currentPage == $page) ? ' active' : '';
        $menuOptions .= '<li class="nav-item"><a class="nav-link' . $isActive . '" href="' . $this->Html->url(array('controller' => 'Report', 'action' => $page)) . '">' . $label . '</a></li>';
    }
    $menuOptions .= '</ul></li>';
    
}else{
    // ---- cashier/outlet role section ----
    $menuOptions .= '<li class="nav-item">
        <h6 class="menu-title">
            <a class="' . (in_array($currentPage, ['indent', 'create_indent', 'edit_indent', 'view_indent']) ? 'active' : '') . '" href="' . $this->Html->url(array('controller' => 'Outlet', 'action' => 'indent')) . '">Indent</a>
        </h6>
    </li>';
    $menuOptions .= '<li class="nav-item">
        <h6 class="menu-title">
            <a class="' . (in_array($currentPage, ['stockin']) ? 'active' : '') . '" href="' . $this->Html->url(array('controller' => 'Outlet', 'action' => 'stockin')) . '">Stock IN</a>
        </h6>
    </li>';
    $menuOptions .= '<li class="nav-item">
        <h6 class="menu-title">
            <a class="' . (in_array($currentPage, ['direct_stockin']) ? 'active' : '') . '" href="' . $this->Html->url(array('controller' => 'Outlet', 'action' => 'direct_stockin')) . '">Direct Stockin</a>
        </h6>
    </li>';
    $menuOptions .= '<li class="nav-item">
        <h6 class="menu-title">
            <a class="' . (in_array($currentPage, ['grn_history']) ? 'active' : '') . '" href="' . $this->Html->url(array('controller' => 'Outlet', 'action' => 'grn_history')) . '">GRN History</a>
        </h6>
    </li>';
    
    $menuOptions .= '<li class="nav-item">
        <h6 class="menu-title">
            <a class="' . (in_array($currentPage, ['return_to_warehouse', 'view_return_to_warehouse', 'edit_return_to_warehouse']) ? 'active' : '') . '" href="' . $this->Html->url(array('controller' => 'Outlet', 'action' => 'return_to_warehouse')) . '">Return to Warehouse</a>
        </h6>
    </li>';
    
    $menuOptions .= '<li class="nav-item">
        <h6 class="menu-title">
            <a class="' . (in_array($currentPage, ['po_grn_history']) ? 'active' : '') . '" href="' . $this->Html->url(array('controller' => 'Outlet', 'action' => 'po_grn_history')) . '">PO GRN History</a>
        </h6>
    </li>';
    $menuOptions .= '<li class="nav-item">
        <h6 class="menu-title">
            <a class="' . (in_array($currentPage, ['po_report_v2']) ? 'active' : '') . '" href="' . $this->Html->url(array('controller' => 'Outlet', 'action' => 'po_report_v2')) . '">PO Report V2</a>
        </h6>
    </li>';
    $menuOptions .= '<li class="nav-item">
        <h6 class="menu-title">
            <a class="' . (in_array($currentPage, ['imprest']) ? 'active' : '') . '" href="' . $this->Html->url(array('controller' => 'Outlet', 'action' => 'imprest')) . '">Imprest</a>
        </h6>
    </li>';
    $menuOptions .= '<li class="nav-item">
        <h6 class="menu-title">
            <a class="' . (in_array($currentPage, ['auditstock', 'create_audit_stock', 'view_audit']) ? 'active' : '') . '" href="' . $this->Html->url(array('controller' => 'Outlet', 'action' => 'auditstock')) . '">SKU Audit</a>
        </h6>
    </li>';
    $menuOptions .= '<li class="nav-item">
        <h6 class="menu-title">
            <a class="' . (in_array($currentPage, ['semi_auditstock','semi_create_audit_stock', 'semi_view_audit']) ? 'active' : '') . '" href="' . $this->Html->url(array('controller' => 'Outlet', 'action' => 'semi_auditstock')) . '">Semifinished Audit</a>
        </h6>
    </li>';
    $menuOptions .= '<li class="nav-item">
        <h6 class="menu-title">
            <a class="' . (in_array($currentPage, ['stock_damage','create_damage', 'edit_damage', 'view_damage']) ? 'active' : '') . '" href="' . $this->Html->url(array('controller' => 'Outlet', 'action' => 'stock_damage')) . '">SKU Damage</a>
        </h6>
    </li>';
    $menuOptions .= '<li class="nav-item">
        <h6 class="menu-title">
            <a class="' . (in_array($currentPage, ['semi_stock_damage','semi_create_damage', 'semi_edit_damage', 'semi_view_damage']) ? 'active' : '') . '" href="' . $this->Html->url(array('controller' => 'Outlet', 'action' => 'semi_stock_damage')) . '">Semifinished Damage</a>
        </h6>
    </li>';
    $menuOptions .= '<li class="nav-item">
        <h6 class="menu-title">
            <a class="' . (in_array($currentPage, ['direct_purchase','view_direct_purchase', 'create_direct_purchase']) ? 'active' : '') . '" href="' . $this->Html->url(array('controller' => 'Outlet', 'action' => 'direct_purchase')) . '">Direct Purchase</a>
        </h6>
    </li>';
    $menuOptions .= '<li class="nav-item">
        <h6 class="menu-title">
            <a class="' . (in_array($currentPage, ['stock_level']) ? 'active' : '') . '" href="' . $this->Html->url(array('controller' => 'Outlet', 'action' => 'stock_level')) . '">Semifinished&nbspStock&nbspLevel</a>
        </h6>
    </li>';
    $menuOptions .= '<li class="nav-item">
        <h6 class="menu-title">
            <a class="' . (in_array($currentPage, ['sku_stock_level']) ? 'active' : '') . '" href="' . $this->Html->url(array('controller' => 'Outlet', 'action' => 'sku_stock_level')) . '">SKU Stock Level</a>
        </h6>
    </li>';
    $menuOptions .= '<li class="nav-item">
        <h6 class="menu-title">
            <a class="' . (in_array($currentPage, ['stock_transfer','view_stock_transfer', 'create_stock_tranfer']) ? 'active' : '') . '" href="' . $this->Html->url(array('controller' => 'Outlet', 'action' => 'stock_transfer')) . '">SKU Stock Transfer</a>
        </h6>
    </li>';
}
$menuOptions .= '<li class="nav-item">
    <h6 class="menu-title">
        <a href="'.$this->Html->url(['controller' => 'dashboard', 'action' => 'logout']).'">Logout</a>
    </h6>
</li><br><br>';


?>

<div class="main-menu-inner">
                <!-- LOGO -->
                <div class="topbar-left" style="padding: 10px;">
                    <a href="" class="logo">
                        <center><span>
                            <?php echo $this->Html->link($this->Html->image('../assets/images/logo.png',array('height' => '70px')), '#', array('escape'=>false, 'class'=>'toggle', 'data-toggle'=>'dropdown')); ?>
                        </span></center>

                    </a>
                </div>
                <!--end logo // --> 
                <div class="menu-body slimscroll" style="overflow:scroll">                    
                    <div  class="">
                        <div class="title-box">
                            <h6 class="menu-title"><?php echo $leftMenuTitle; ?></h6>       
                        </div>
                        <ul class="nav">
                            <?php echo $menuOptions; ?>
                        </ul>
                    </div><!-- end Analytic -->
                </div><!--end menu-body-->
</div><!-- end main-menu-inner-->
<style>
    .active {
    text-decoration: underline;
}
.menu-body .nav-item ul li {
    margin: 0 !important;
}
.hidden {
    display: none!important;
}

</style>

<script>
function toggleReports() {
    const submenu = document.querySelector('.report-submenu');
    if (submenu) {
        submenu.style.display = submenu.style.display === 'none' ? 'block' : 'none';
    }
}
function toggleinventory() {
    const invsubmenu = document.querySelector('.inventory-submenu');
    if (invsubmenu) {
        invsubmenu.style.display = invsubmenu.style.display === 'none' ? 'block' : 'none';
    }
}
function togglecatalog() {
    const catalogsubmenu = document.querySelector('.catalog-submenu');
    if (catalogsubmenu) {
        catalogsubmenu.style.display = catalogsubmenu.style.display === 'none' ? 'block' : 'none';
    }
}
function toggleindent() {
    const indentsubmenu = document.querySelector('.indent-submenu');
    if (indentsubmenu) {
        indentsubmenu.style.display = indentsubmenu.style.display === 'none' ? 'block' : 'none';
    }
}
function togglekds() {
    const kdssubmenu = document.querySelector('.kds-submenu');
    if (kdssubmenu) {
        kdssubmenu.style.display = kdssubmenu.style.display === 'none' ? 'block' : 'none';
    }
}
function toggletable() {
    const tablesubmenu = document.querySelector('.table-submenu');
    if (tablesubmenu) {
        tablesubmenu.style.display = tablesubmenu.style.display === 'none' ? 'block' : 'none';
    }
}

</script>