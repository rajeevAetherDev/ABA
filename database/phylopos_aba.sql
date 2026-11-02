-- phpMyAdmin SQL Dump
-- version 4.7.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 02, 2026 at 12:28 PM
-- Server version: 10.1.29-MariaDB
-- PHP Version: 7.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `phylopos_aba`
--

-- --------------------------------------------------------

--
-- Table structure for table `account_imprest`
--

CREATE TABLE `account_imprest` (
  `id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `location_name` varchar(111) NOT NULL,
  `amount` double NOT NULL,
  `description` varchar(111) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0',
  `local_sync` int(11) NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `cards`
--

CREATE TABLE `cards` (
  `id` int(11) NOT NULL,
  `cardnumber` varchar(250) NOT NULL,
  `cardbalance` double NOT NULL DEFAULT '0',
  `name` varchar(250) NOT NULL,
  `mobile` bigint(11) NOT NULL,
  `valid_to` date DEFAULT NULL,
  `status` int(11) NOT NULL COMMENT '1-vacant,2-assigned,0-inactive',
  `otp` int(111) DEFAULT NULL,
  `pin_required` tinyint(1) NOT NULL DEFAULT '0',
  `pin` int(11) DEFAULT NULL,
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `cardtracking`
--

CREATE TABLE `cardtracking` (
  `id` int(11) NOT NULL,
  `cardnumber` varchar(250) NOT NULL,
  `cashierID` int(11) NOT NULL,
  `txnid` varchar(111) DEFAULT NULL,
  `cashierName` varchar(250) NOT NULL,
  `customername` varchar(250) NOT NULL,
  `customerphone` bigint(11) NOT NULL,
  `purpose` varchar(250) NOT NULL,
  `oldbalance` double NOT NULL,
  `amount` double NOT NULL,
  `newbalance` double NOT NULL,
  `type` int(11) NOT NULL COMMENT '1-add, 2- deduct,3-security,4-refund',
  `payment_mode` varchar(111) DEFAULT NULL,
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `status` int(11) NOT NULL DEFAULT '0',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `catalog_category`
--

CREATE TABLE `catalog_category` (
  `id` int(11) NOT NULL,
  `name` varchar(111) NOT NULL,
  `image` varchar(111) DEFAULT NULL,
  `is_subcategory` int(11) NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL DEFAULT '3' COMMENT '1-quick order,2-table management,3-both',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `status` int(11) NOT NULL DEFAULT '1',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `catalog_category_branches`
--

CREATE TABLE `catalog_category_branches` (
  `id` int(11) NOT NULL,
  `location_id` varchar(111) NOT NULL,
  `location_name` varchar(111) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `category_name` varchar(111) NOT NULL,
  `image` varchar(111) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `catalog_modifier`
--

CREATE TABLE `catalog_modifier` (
  `id` int(11) NOT NULL,
  `name` varchar(111) NOT NULL,
  `description` longtext,
  `food_type` varchar(111) NOT NULL COMMENT '''0''=>''Veg'',''1''=>''Non-Veg''',
  `sort_order` varchar(111) NOT NULL,
  `price` double NOT NULL,
  `weight` varchar(111) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `catalog_modifier_group`
--

CREATE TABLE `catalog_modifier_group` (
  `id` int(11) NOT NULL,
  `name` varchar(111) DEFAULT NULL,
  `group_type` int(11) DEFAULT NULL COMMENT '''0''=>''Add-On'',''1''=>''Variant''',
  `sort_order` int(11) DEFAULT NULL,
  `min` int(11) NOT NULL,
  `max` int(11) NOT NULL,
  `is_default` int(11) NOT NULL DEFAULT '0',
  `description` longtext,
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `catalog_package`
--

CREATE TABLE `catalog_package` (
  `id` int(11) NOT NULL,
  `main_product_id` int(11) NOT NULL,
  `main_product_name` varchar(111) NOT NULL,
  `main_product_unit_id` int(11) NOT NULL,
  `main_product_unit_name` varchar(111) NOT NULL,
  `main_product_price` double NOT NULL,
  `link_product_id` int(11) NOT NULL,
  `link_product_name` varchar(111) NOT NULL,
  `link_product_unit_id` int(11) NOT NULL,
  `link_product_unit_name` varchar(111) NOT NULL,
  `link_product_price` double NOT NULL,
  `link_quantity` varchar(111) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `catalog_product`
--

CREATE TABLE `catalog_product` (
  `id` int(11) NOT NULL,
  `name` varchar(111) NOT NULL,
  `cat_id` varchar(11) DEFAULT NULL,
  `cat_name` varchar(111) DEFAULT NULL,
  `subcat_id` varchar(111) DEFAULT NULL,
  `subcat_name` varchar(111) DEFAULT NULL,
  `unit` int(11) DEFAULT NULL,
  `unit_name` varchar(111) DEFAULT NULL,
  `description` varchar(111) DEFAULT NULL,
  `dish_type` int(11) NOT NULL DEFAULT '0' COMMENT '''0'' => ''Food'', ''1'' => ''Beverage'', ''2'' => ''Liquor'', ''3'' => ''Others''',
  `food_type` int(11) NOT NULL DEFAULT '0' COMMENT '0-veg,1-non-veg,2-non-alcoholic,3-Alcoholic',
  `package` int(11) NOT NULL DEFAULT '0',
  `preparation_time` varchar(111) NOT NULL DEFAULT '0',
  `serve_time` varchar(111) NOT NULL DEFAULT '0',
  `moq` varchar(111) NOT NULL DEFAULT '0',
  `sort_order` varchar(111) DEFAULT NULL,
  `recommended` varchar(111) DEFAULT NULL,
  `variation` varchar(111) DEFAULT NULL,
  `price` varchar(111) DEFAULT NULL,
  `is_discountable` int(11) NOT NULL DEFAULT '0',
  `yield` varchar(111) NOT NULL DEFAULT '1',
  `food_cost` varchar(111) NOT NULL DEFAULT '0',
  `kds` int(11) NOT NULL DEFAULT '1' COMMENT '1-kitchen,2-bar',
  `markup_price` varchar(111) DEFAULT NULL,
  `allergy_tags` varchar(111) DEFAULT NULL,
  `weight` varchar(111) DEFAULT NULL,
  `serves_people` varchar(111) DEFAULT NULL,
  `image` varchar(111) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `type` int(11) NOT NULL DEFAULT '3' COMMENT '1-quick order,2-table management,3-both',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `catalog_product_attribute`
--

CREATE TABLE `catalog_product_attribute` (
  `id` int(11) NOT NULL,
  `product_id` varchar(111) NOT NULL,
  `name` varchar(111) NOT NULL,
  `value` varchar(111) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `catalog_product_branches`
--

CREATE TABLE `catalog_product_branches` (
  `id` int(11) NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `location_name` varchar(111) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `cat_id` int(11) NOT NULL,
  `cat_name` varchar(255) DEFAULT NULL,
  `subcat_id` int(11) DEFAULT NULL,
  `subcat_name` varchar(255) DEFAULT NULL,
  `price` double NOT NULL DEFAULT '0',
  `unit` varchar(11) DEFAULT NULL,
  `unit_name` varchar(111) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `catalog_product_tax`
--

CREATE TABLE `catalog_product_tax` (
  `id` int(11) NOT NULL,
  `product_id` varchar(111) DEFAULT NULL,
  `product_name` varchar(111) DEFAULT NULL,
  `tax_id` int(11) DEFAULT NULL,
  `tax_name` varchar(111) DEFAULT NULL,
  `tax_percentage` double DEFAULT NULL,
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `status` int(11) NOT NULL DEFAULT '1',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `catalog_product_tax_outlet`
--

CREATE TABLE `catalog_product_tax_outlet` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(111) DEFAULT NULL,
  `outlet_id` int(11) NOT NULL,
  `outlet_name` varchar(111) DEFAULT NULL,
  `tax_id` int(11) NOT NULL,
  `tax_name` varchar(111) DEFAULT NULL,
  `tax_percentage` double NOT NULL DEFAULT '0',
  `effective_from` date DEFAULT NULL COMMENT 'Optional: activate from this date',
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0',
  `local_sync` int(11) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `catalog_subcategory`
--

CREATE TABLE `catalog_subcategory` (
  `id` int(11) NOT NULL,
  `cat_id` int(11) NOT NULL,
  `name` varchar(111) NOT NULL,
  `image` varchar(111) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `catalog_tax`
--

CREATE TABLE `catalog_tax` (
  `id` int(11) NOT NULL,
  `name` varchar(111) DEFAULT NULL,
  `percentage` varchar(111) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `catalog_units`
--

CREATE TABLE `catalog_units` (
  `id` int(11) NOT NULL,
  `name` varchar(111) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `catalog_unit_mapping`
--

CREATE TABLE `catalog_unit_mapping` (
  `id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `unit_name` varchar(111) NOT NULL,
  `mapped_unit_id` int(11) NOT NULL,
  `mapped_unit_name` varchar(111) NOT NULL,
  `conversion_factor` varchar(111) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modifier` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `value` varchar(111) DEFAULT NULL,
  `expiry_date` varchar(20) DEFAULT NULL,
  `status` tinyint(3) DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `eazydiner_tokens`
--

CREATE TABLE `eazydiner_tokens` (
  `id` int(11) NOT NULL,
  `token` text,
  `token_date` date DEFAULT NULL,
  `sync` int(11) NOT NULL DEFAULT '0',
  `local_sync` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `editing_managements`
--

CREATE TABLE `editing_managements` (
  `id` int(11) NOT NULL,
  `reference_id` varchar(100) NOT NULL,
  `cashier_id` int(11) NOT NULL,
  `cashier_name` varchar(255) DEFAULT NULL,
  `store_id` int(11) NOT NULL,
  `store_name` varchar(255) NOT NULL,
  `type` varchar(111) DEFAULT NULL,
  `remarks` text,
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1: Pending, 2: Approved, 3: Rejected',
  `action_by` int(11) DEFAULT NULL COMMENT 'User ID who approved/rejected',
  `action_by_name` varchar(255) DEFAULT NULL,
  `action_date` datetime DEFAULT NULL,
  `sync` int(11) NOT NULL DEFAULT '0',
  `local_sync` int(11) NOT NULL DEFAULT '0',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_category`
--

CREATE TABLE `inventory_category` (
  `id` int(11) NOT NULL,
  `name` varchar(111) NOT NULL,
  `image` varchar(111) DEFAULT NULL,
  `is_subcategory` int(11) NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL DEFAULT '3' COMMENT '1-quick order,2-table management,3-both',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `status` int(11) NOT NULL DEFAULT '1',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_damage`
--

CREATE TABLE `inventory_damage` (
  `id` int(11) NOT NULL,
  `location_id` varchar(111) DEFAULT NULL,
  `location_name` varchar(111) DEFAULT NULL,
  `damage_id` varchar(111) DEFAULT NULL,
  `damage_date` date DEFAULT NULL,
  `amount` double NOT NULL DEFAULT '0',
  `type` varchar(111) DEFAULT NULL COMMENT 'warehouse,outlet',
  `item_type` varchar(111) DEFAULT NULL COMMENT 'sku,semifished',
  `status` int(11) DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_damage_items`
--

CREATE TABLE `inventory_damage_items` (
  `id` int(11) NOT NULL,
  `location_id` varchar(111) DEFAULT NULL,
  `location_name` varchar(111) DEFAULT NULL,
  `damage_id` varchar(111) DEFAULT NULL,
  `item_id` varchar(111) DEFAULT NULL,
  `item_name` varchar(111) DEFAULT NULL,
  `item_category_id` varchar(111) DEFAULT NULL,
  `item_category_name` varchar(111) NOT NULL,
  `unit_id` varchar(111) DEFAULT NULL,
  `unit_name` varchar(111) DEFAULT NULL,
  `item_price` double DEFAULT NULL,
  `quantity` varchar(111) DEFAULT NULL,
  `amount` double NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_indent`
--

CREATE TABLE `inventory_indent` (
  `id` int(11) NOT NULL,
  `indent_id` varchar(111) NOT NULL,
  `location_id` varchar(111) DEFAULT NULL,
  `location_name` varchar(111) DEFAULT NULL,
  `warehouse_id` int(11) DEFAULT NULL,
  `warehouse_name` varchar(111) DEFAULT NULL,
  `expiry` date DEFAULT NULL,
  `created_by` varchar(111) NOT NULL,
  `created_by_name` varchar(111) NOT NULL,
  `total_amount` double NOT NULL,
  `approved_amount` double NOT NULL DEFAULT '0',
  `remarks` longtext,
  `vehicle_no` varchar(111) DEFAULT NULL,
  `approved_selected_date` date DEFAULT NULL,
  `approved_date` datetime DEFAULT NULL,
  `processed_amount` double NOT NULL DEFAULT '0',
  `processed_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `consignment_id` varchar(111) DEFAULT NULL,
  `serial_number` varchar(111) DEFAULT NULL,
  `invoice_no` varchar(111) DEFAULT NULL,
  `invoice` varchar(111) DEFAULT NULL,
  `stockin_id` varchar(111) DEFAULT NULL,
  `stockin_amount` double DEFAULT NULL,
  `stockin_date` varchar(111) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '1-submitted,2-approved,3-processed,4-stockin',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `is_updated` int(11) NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `excise_duty_total` double NOT NULL DEFAULT '0',
  `addl_excise_duty_total` double NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_indent_items`
--

CREATE TABLE `inventory_indent_items` (
  `Id` int(11) NOT NULL,
  `indent_id` varchar(111) NOT NULL,
  `item_type` varchar(111) NOT NULL DEFAULT 'semifinished',
  `sku_id` varchar(25) DEFAULT NULL,
  `sku_name` varchar(111) NOT NULL,
  `sku_brand` varchar(111) DEFAULT NULL,
  `sku_unit` int(11) NOT NULL,
  `sku_unit_name` varchar(111) NOT NULL,
  `sku_price` varchar(100) DEFAULT NULL,
  `sku_quantity` double NOT NULL,
  `sku_total_tax_percentage` double NOT NULL DEFAULT '0',
  `sku_total_tax_value` double NOT NULL DEFAULT '0',
  `sku_amount` double NOT NULL DEFAULT '0',
  `approved_price` double NOT NULL DEFAULT '0',
  `approved_quantity` varchar(111) NOT NULL DEFAULT '0',
  `approved_tax_value` double NOT NULL DEFAULT '0',
  `approved_amount` double NOT NULL DEFAULT '0',
  `processed_quantity` varchar(111) NOT NULL DEFAULT '0',
  `processed_tax_value` double DEFAULT NULL,
  `processed_amount` double DEFAULT NULL,
  `consignment_id` varchar(111) DEFAULT NULL,
  `stockin_quantity` varchar(111) NOT NULL DEFAULT '0',
  `stockin_tax_value` double DEFAULT NULL,
  `stockin_amount` double DEFAULT NULL,
  `status` tinyint(3) DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_internal_indent`
--

CREATE TABLE `inventory_internal_indent` (
  `id` int(11) NOT NULL,
  `indent_id` varchar(111) NOT NULL,
  `location_id` varchar(111) DEFAULT NULL,
  `location_name` varchar(111) DEFAULT NULL,
  `created_by` varchar(111) NOT NULL,
  `created_by_name` varchar(111) NOT NULL,
  `total_amount` double NOT NULL,
  `is_completed` int(11) NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `is_updated` int(11) NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `po_locked` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_internal_indent_items`
--

CREATE TABLE `inventory_internal_indent_items` (
  `Id` int(11) NOT NULL,
  `indent_id` varchar(111) NOT NULL,
  `sku_id` varchar(25) DEFAULT NULL,
  `sku_name` varchar(111) NOT NULL,
  `sku_brand` varchar(111) NOT NULL,
  `sku_unit` int(11) NOT NULL,
  `sku_unit_name` varchar(111) NOT NULL,
  `sku_price` varchar(100) DEFAULT NULL,
  `sku_quantity` double NOT NULL,
  `sku_total_tax_percentage` double DEFAULT NULL,
  `sku_total_tax_value` double DEFAULT NULL,
  `sku_amount` double DEFAULT NULL,
  `completed_quantity` int(11) NOT NULL DEFAULT '0',
  `is_completed` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(3) DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `excise_per_unit` double NOT NULL DEFAULT '0',
  `addl_excise_per_unit` double NOT NULL DEFAULT '0',
  `landed_amt` double NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_linking`
--

CREATE TABLE `inventory_linking` (
  `id` int(11) NOT NULL,
  `parent_type` varchar(111) NOT NULL COMMENT 'Prouct,Semifinished,Modifier,Modifier_group',
  `parent_id` varchar(111) NOT NULL,
  `parent_name` varchar(111) NOT NULL,
  `parent_yield` varchar(111) NOT NULL DEFAULT '1',
  `component_type` varchar(111) NOT NULL COMMENT 'Semifinished,Sku,Modifier,Modifier_group',
  `component_id` varchar(111) NOT NULL,
  `component_name` varchar(111) NOT NULL,
  `component_purchase_unit` varchar(111) NOT NULL DEFAULT '0',
  `component_purchase_unit_name` varchar(111) DEFAULT NULL,
  `quantity` varchar(111) NOT NULL,
  `component_unit` varchar(111) DEFAULT NULL,
  `component_unit_name` varchar(111) DEFAULT NULL,
  `component_price` varchar(111) NOT NULL DEFAULT '0',
  `conversion_quantity` varchar(111) NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_purchase_order`
--

CREATE TABLE `inventory_purchase_order` (
  `id` int(11) NOT NULL,
  `vendor_id` varchar(111) NOT NULL,
  `vendor_name` varchar(111) DEFAULT NULL,
  `location_id` varchar(111) DEFAULT NULL,
  `location_name` varchar(111) DEFAULT NULL,
  `location_type` varchar(111) NOT NULL DEFAULT 'DC',
  `charge` double NOT NULL DEFAULT '0',
  `discount_value` double NOT NULL DEFAULT '0',
  `total_amount` double NOT NULL,
  `p_o_id` varchar(111) DEFAULT NULL,
  `po_date` varchar(111) DEFAULT NULL,
  `approved_by_id` varchar(111) DEFAULT NULL,
  `approved_by_name` varchar(111) DEFAULT NULL,
  `approved_charge` double NOT NULL DEFAULT '0',
  `approved_discount_value` double NOT NULL DEFAULT '0',
  `approved_amount` double NOT NULL DEFAULT '0',
  `approved_on` datetime DEFAULT NULL,
  `processed_on` timestamp NULL DEFAULT NULL,
  `processed_by_id` int(11) DEFAULT NULL,
  `processed_by_name` varchar(111) DEFAULT NULL,
  `processed_charge` double NOT NULL DEFAULT '0',
  `processed_discount_value` double NOT NULL DEFAULT '0',
  `processed_amount` double DEFAULT NULL,
  `stockin_on` timestamp NULL DEFAULT NULL,
  `stockin_by_id` int(11) DEFAULT NULL,
  `stockin_by_name` varchar(111) DEFAULT NULL,
  `stockin_charge` double NOT NULL DEFAULT '0',
  `stockin_discount_value` double NOT NULL DEFAULT '0',
  `stockin_amount` double DEFAULT NULL,
  `is_stockedin` int(11) NOT NULL DEFAULT '1',
  `invoice_no` varchar(111) DEFAULT NULL,
  `internal_indent_id` varchar(111) NOT NULL,
  `is_autogenerated_indent` tinyint(1) NOT NULL DEFAULT '0',
  `stockin_id` varchar(111) DEFAULT NULL,
  `direct_purchase` int(11) NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '0-cancel,1-active,2-approved,3-processed,4-stockin',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `is_updated` int(11) NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `po_type` enum('material','service') NOT NULL DEFAULT 'material',
  `tcs_percent` double NOT NULL DEFAULT '0',
  `excise_duty_total` double NOT NULL DEFAULT '0',
  `addl_excise_duty_total` double NOT NULL DEFAULT '0',
  `vat_total` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `tcs_total` decimal(12,4) NOT NULL DEFAULT '0.0000'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_purchase_order_items`
--

CREATE TABLE `inventory_purchase_order_items` (
  `id` int(11) NOT NULL,
  `p_o_id` varchar(111) NOT NULL,
  `internal_indent_id` varchar(111) NOT NULL,
  `sku_id` varchar(111) NOT NULL,
  `sku_name` varchar(111) NOT NULL,
  `sku_unit` int(11) DEFAULT NULL,
  `sku_unit_name` varchar(111) DEFAULT NULL,
  `sku_price` double NOT NULL,
  `sku_quantity` double NOT NULL,
  `sku_discount` double NOT NULL DEFAULT '0',
  `sku_discount_type` varchar(111) DEFAULT NULL,
  `sku_discount_value` double NOT NULL DEFAULT '0',
  `sku_total_tax_percentage` double DEFAULT NULL,
  `sku_total_tax_value` double DEFAULT NULL,
  `sku_amount` double DEFAULT NULL,
  `approved_quantity` varchar(111) NOT NULL DEFAULT '0',
  `approve_discount` double NOT NULL DEFAULT '0',
  `approve_discount_type` varchar(111) DEFAULT NULL,
  `approve_discount_value` double NOT NULL DEFAULT '0',
  `approved_total_tax_value` double NOT NULL DEFAULT '0',
  `approved_amount` double NOT NULL DEFAULT '0',
  `processed_quantity` varchar(111) DEFAULT NULL,
  `process_discount` double NOT NULL DEFAULT '0',
  `process_discount_type` varchar(111) DEFAULT NULL,
  `process_discount_value` double NOT NULL DEFAULT '0',
  `processed_total_tax_value` double DEFAULT NULL,
  `processed_amount` double DEFAULT NULL,
  `stockin_quantity` varchar(111) NOT NULL DEFAULT '0',
  `stockin_discount` double NOT NULL DEFAULT '0',
  `stockin_discount_type` varchar(111) DEFAULT NULL,
  `stockin_discount_value` double NOT NULL DEFAULT '0',
  `stockin_total_tax_value` double DEFAULT NULL,
  `stockin_amount` double DEFAULT NULL,
  `is_stockedin` int(11) NOT NULL DEFAULT '1',
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '0-cancel,1-active,2-approved,3-processed,4-stockin',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tcs_amount` double NOT NULL DEFAULT '0',
  `excise_duty_amt` double NOT NULL DEFAULT '0',
  `addl_excise_duty_amt` double NOT NULL DEFAULT '0',
  `landed_amt` double NOT NULL DEFAULT '0',
  `vat_percentage` decimal(8,4) NOT NULL DEFAULT '0.0000',
  `vat_amt` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `tcs_percentage` decimal(8,4) NOT NULL DEFAULT '0.0000',
  `tcs_amt` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `excise_per_unit` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `addl_excise_per_unit` decimal(10,4) NOT NULL DEFAULT '0.0000'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_return`
--

CREATE TABLE `inventory_return` (
  `id` int(11) NOT NULL,
  `return_id` varchar(111) NOT NULL,
  `location_id` varchar(111) DEFAULT NULL,
  `location_name` varchar(111) DEFAULT NULL,
  `warehouse_id` int(11) DEFAULT NULL,
  `warehouse_name` varchar(111) DEFAULT NULL,
  `created_by` varchar(111) NOT NULL,
  `created_by_name` varchar(111) NOT NULL,
  `total_amount` double NOT NULL DEFAULT '0',
  `remarks` longtext,
  `admin_updated_date` datetime DEFAULT NULL,
  `admin_updated_amount` double NOT NULL DEFAULT '0',
  `admin_remarks` varchar(111) DEFAULT NULL,
  `admin_status` int(11) NOT NULL DEFAULT '0' COMMENT '0-pending,1-approved,2-reject',
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '0-delete,1-submitted',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_return_items`
--

CREATE TABLE `inventory_return_items` (
  `Id` int(11) NOT NULL,
  `return_id` varchar(111) NOT NULL,
  `sku_id` int(11) NOT NULL,
  `sku_name` varchar(111) NOT NULL,
  `sku_brand` varchar(111) NOT NULL,
  `sku_unit` varchar(111) NOT NULL,
  `sku_unit_name` varchar(111) NOT NULL,
  `sku_price` double NOT NULL,
  `tax_percent` varchar(111) NOT NULL DEFAULT '0',
  `sku_quantity` varchar(111) NOT NULL,
  `tax_value` double NOT NULL DEFAULT '0',
  `amount` double NOT NULL DEFAULT '0',
  `admin_updated_quantity` double NOT NULL DEFAULT '0',
  `admin_updated_tax` double NOT NULL DEFAULT '0',
  `admin_updated_amount` double NOT NULL DEFAULT '0',
  `admin_status` int(11) NOT NULL DEFAULT '0' COMMENT ' 0-reject,1-approved ',
  `status` int(11) NOT NULL DEFAULT '1' COMMENT ' 	0-delete,1-submitted 	',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_semifinished`
--

CREATE TABLE `inventory_semifinished` (
  `id` int(11) NOT NULL,
  `name` varchar(111) NOT NULL,
  `cat_id` varchar(111) DEFAULT NULL,
  `cat_name` varchar(111) DEFAULT NULL,
  `subcat_id` varchar(111) DEFAULT NULL,
  `subcat_name` varchar(111) DEFAULT NULL,
  `unit` varchar(111) NOT NULL,
  `unit_name` varchar(111) NOT NULL,
  `brand` varchar(111) DEFAULT NULL,
  `price` double NOT NULL,
  `consumption_unit_id` int(11) DEFAULT NULL,
  `consumption_unit_name` varchar(111) DEFAULT NULL,
  `consumption_factor` varchar(111) DEFAULT NULL,
  `consumption_unit_price` double DEFAULT NULL,
  `moq` varchar(111) NOT NULL DEFAULT '0',
  `stock` int(11) NOT NULL DEFAULT '0',
  `yield` varchar(111) NOT NULL DEFAULT '1',
  `food_cost` varchar(111) NOT NULL DEFAULT '0',
  `remarks` varchar(111) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `portion_weight_grams` decimal(10,4) DEFAULT NULL COMMENT 'Weight in grams per 1 portion/yield unit. Used for gram<->portion cross-unit costing.'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_semifinished_tax`
--

CREATE TABLE `inventory_semifinished_tax` (
  `id` int(11) NOT NULL,
  `product_id` varchar(111) DEFAULT NULL,
  `product_name` varchar(111) DEFAULT NULL,
  `tax_id` int(11) DEFAULT NULL,
  `tax_name` varchar(111) DEFAULT NULL,
  `tax_percentage` double DEFAULT NULL,
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `status` int(11) NOT NULL DEFAULT '1',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_skus`
--

CREATE TABLE `inventory_skus` (
  `id` int(11) NOT NULL,
  `name` varchar(111) NOT NULL,
  `cat_id` int(11) DEFAULT NULL,
  `cat_name` varchar(111) DEFAULT NULL,
  `subcat_id` varchar(111) DEFAULT NULL,
  `subcat_name` varchar(111) DEFAULT NULL,
  `brand` varchar(111) NOT NULL,
  `item_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1-salable,0-non-salable',
  `unit` varchar(111) NOT NULL,
  `unit_name` varchar(111) NOT NULL,
  `price` double NOT NULL,
  `consumption_unit_id` int(11) DEFAULT NULL,
  `consumption_unit_name` varchar(111) DEFAULT NULL,
  `consumption_factor` varchar(111) DEFAULT NULL,
  `consumption_unit_price` double DEFAULT NULL,
  `moq` varchar(111) NOT NULL DEFAULT '0',
  `stock` int(11) NOT NULL DEFAULT '0',
  `remarks` varchar(111) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expense_type` enum('CAPEX','OPEX','UNCLASSIFIED') NOT NULL DEFAULT 'UNCLASSIFIED',
  `last_stockin_price` double NOT NULL DEFAULT '0',
  `avg_stockin_price` double NOT NULL DEFAULT '0',
  `excise_applicable` tinyint(1) NOT NULL DEFAULT '0',
  `excise_per_unit` double NOT NULL DEFAULT '0',
  `addl_excise_per_unit` double NOT NULL DEFAULT '0',
  `has_variable_tax` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_sku_excise`
--

CREATE TABLE `inventory_sku_excise` (
  `id` int(11) NOT NULL,
  `sku_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL DEFAULT '0',
  `excise_per_unit` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `addl_excise_per_unit` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `vat_percentage` decimal(8,4) NOT NULL DEFAULT '0.0000',
  `tcs_percentage` decimal(8,4) NOT NULL DEFAULT '0.0000',
  `sku_name` varchar(255) NOT NULL DEFAULT '',
  `location_name` varchar(255) NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `sync` tinyint(1) NOT NULL DEFAULT '0',
  `local_sync` tinyint(1) NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_sku_tax`
--

CREATE TABLE `inventory_sku_tax` (
  `id` int(11) NOT NULL,
  `product_id` varchar(111) DEFAULT NULL,
  `product_name` varchar(111) DEFAULT NULL,
  `tax_id` int(11) DEFAULT NULL,
  `tax_name` varchar(111) DEFAULT NULL,
  `tax_percentage` double DEFAULT NULL,
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `status` int(11) NOT NULL DEFAULT '1',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_stockin`
--

CREATE TABLE `inventory_stockin` (
  `id` int(11) NOT NULL,
  `location_type` varchar(111) NOT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `vendor_name` varchar(111) DEFAULT NULL,
  `p_o_id` varchar(111) NOT NULL,
  `indent_id` varchar(111) DEFAULT NULL,
  `stockin_by_id` int(11) NOT NULL,
  `stockin_by_name` varchar(111) NOT NULL,
  `stockin_charge` double NOT NULL,
  `stockin_amount` double NOT NULL,
  `invoice_no` varchar(111) NOT NULL,
  `stockin_id` varchar(111) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0',
  `local_sync` int(11) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `excise_duty_total` double NOT NULL DEFAULT '0',
  `addl_excise_duty_total` double NOT NULL DEFAULT '0',
  `vat_total` double NOT NULL DEFAULT '0',
  `tcs_total` double NOT NULL DEFAULT '0',
  `landed_cost` double NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_stockin_items`
--

CREATE TABLE `inventory_stockin_items` (
  `id` int(11) NOT NULL,
  `stockin_id` varchar(111) NOT NULL,
  `p_o_id` varchar(111) NOT NULL,
  `sku_id` int(11) NOT NULL,
  `sku_name` varchar(111) NOT NULL,
  `sku_unit` int(11) NOT NULL,
  `sku_unit_name` varchar(111) NOT NULL,
  `sku_price` double NOT NULL,
  `stockin_quantity` double NOT NULL,
  `stockin_total_tax_value` double NOT NULL,
  `stockin_amount` double NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0',
  `local_sync` int(11) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_subcategory`
--

CREATE TABLE `inventory_subcategory` (
  `id` int(11) NOT NULL,
  `cat_id` int(11) NOT NULL,
  `name` varchar(111) NOT NULL,
  `image` varchar(111) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_tax`
--

CREATE TABLE `inventory_tax` (
  `id` int(11) NOT NULL,
  `name` varchar(111) DEFAULT NULL,
  `percentage` varchar(111) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_units`
--

CREATE TABLE `inventory_units` (
  `id` int(11) NOT NULL,
  `name` varchar(111) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_vendors`
--

CREATE TABLE `inventory_vendors` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `mobile` text,
  `landline` text,
  `email` text,
  `office_address` text,
  `state` varchar(300) DEFAULT NULL,
  `company` text,
  `pan` text,
  `gst` text,
  `tan` text,
  `bank_ac` text,
  `ifsc` text,
  `bank_name` text,
  `pan_image` varchar(255) DEFAULT NULL,
  `gst_image` varchar(255) DEFAULT NULL,
  `tan_image` varchar(255) DEFAULT NULL,
  `advance_percentage` varchar(3) DEFAULT NULL,
  `payment_days` varchar(4) DEFAULT NULL,
  `po_approval_required` int(11) NOT NULL DEFAULT '1',
  `agreement_image` varchar(255) DEFAULT NULL,
  `fssai_image` varchar(255) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_vendor_service_areas`
--

CREATE TABLE `inventory_vendor_service_areas` (
  `Id` int(11) NOT NULL,
  `vendor_id` varchar(25) DEFAULT NULL,
  `area_id` varchar(25) DEFAULT NULL,
  `area_name` varchar(111) NOT NULL,
  `is_deleted` tinyint(3) DEFAULT '0',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `status` int(11) NOT NULL DEFAULT '1',
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_vendor_sku_served`
--

CREATE TABLE `inventory_vendor_sku_served` (
  `Id` int(11) NOT NULL,
  `vendor_id` varchar(25) DEFAULT NULL,
  `skus_id` varchar(25) DEFAULT NULL,
  `sku_name` varchar(111) NOT NULL,
  `sku_brand` varchar(111) NOT NULL,
  `sku_unit` int(11) NOT NULL,
  `sku_unit_name` varchar(111) NOT NULL,
  `sku_price` varchar(100) DEFAULT NULL,
  `status` tinyint(3) DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kds_tracks`
--

CREATE TABLE `kds_tracks` (
  `id` bigint(10) NOT NULL,
  `local_id` int(11) DEFAULT NULL,
  `order_history_id` varchar(111) DEFAULT NULL,
  `order_reference_id` varchar(50) NOT NULL,
  `kds_group_id` varchar(111) DEFAULT NULL,
  `kds_user_id` int(111) NOT NULL,
  `kds_station_id` varchar(111) DEFAULT NULL,
  `product_id` bigint(50) NOT NULL,
  `product_name` varchar(250) DEFAULT NULL,
  `kds_username` varchar(111) DEFAULT NULL,
  `role` varchar(111) DEFAULT NULL,
  `table_no` varchar(111) DEFAULT NULL,
  `cashier_id` varchar(111) DEFAULT NULL,
  `location_id` varchar(111) DEFAULT NULL,
  `location_name` varchar(111) DEFAULT NULL,
  `remarks` text,
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `status` int(3) NOT NULL DEFAULT '0' COMMENT '0 - unfire\r\n1 - fire\r\n2 - hold\r\n3 - start\r\n4 - bump\r\n5 - flag\r\n6 - send to table\r\n7 - recall',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kds_users`
--

CREATE TABLE `kds_users` (
  `id` int(11) NOT NULL,
  `username` varchar(111) NOT NULL,
  `password` varchar(111) NOT NULL,
  `role` varchar(111) NOT NULL DEFAULT 'User',
  `location_id` varchar(111) NOT NULL,
  `location_name` varchar(111) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kds_users_station_mapping`
--

CREATE TABLE `kds_users_station_mapping` (
  `id` int(11) NOT NULL,
  `kds_id` int(11) NOT NULL,
  `kds_username` varchar(111) NOT NULL,
  `station_id` int(11) NOT NULL,
  `station_name` varchar(111) NOT NULL,
  `station_store_id` int(11) NOT NULL,
  `station_store_name` varchar(111) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kot_sequences`
--

CREATE TABLE `kot_sequences` (
  `store_id` varchar(111) NOT NULL,
  `last_kot` int(11) NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Atomic per-store KOT sequence counters. Never reset.';

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `reelo_merchant_id` varchar(255) DEFAULT NULL,
  `reelo_customer_key` varchar(255) DEFAULT NULL,
  `name` varchar(111) NOT NULL,
  `username` varchar(111) DEFAULT NULL,
  `password` varchar(111) DEFAULT NULL,
  `type` varchar(111) DEFAULT NULL,
  `state` varchar(111) DEFAULT NULL,
  `print_line1` longtext,
  `print_line2` longtext,
  `print_line3` longtext,
  `print_line4` longtext,
  `print_line5` longtext,
  `print_line6` longtext,
  `print_line7` longtext,
  `print_line8` longtext,
  `print_line9` longtext,
  `print_line10` longtext,
  `footer_msg` longtext,
  `footer_msg2` longtext,
  `footer_msg3` longtext,
  `currency_symbol` longtext CHARACTER SET utf8 COLLATE utf8_swedish_ci,
  `service_charge_rate` double NOT NULL DEFAULT '0',
  `shift_start_time` varchar(20) DEFAULT '06:00:00',
  `shift_end_time` varchar(20) DEFAULT '05:59:59',
  `shift_type` varchar(20) DEFAULT 'next_day',
  `eazydiner_outlet_id` varchar(100) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `is_publish` int(11) NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `online_bookings`
--

CREATE TABLE `online_bookings` (
  `id` int(11) NOT NULL,
  `table_number` varchar(111) NOT NULL,
  `reference_id` varchar(111) NOT NULL,
  `pax` int(11) DEFAULT NULL,
  `cover_charge` double NOT NULL,
  `user_id` int(111) NOT NULL,
  `name` varchar(111) DEFAULT NULL,
  `phone` varchar(111) DEFAULT NULL,
  `booking_time` timestamp NULL DEFAULT NULL,
  `payment_type` int(11) DEFAULT NULL COMMENT '1=postpaid,2=prepaid',
  `pay_option` int(11) NOT NULL DEFAULT '0',
  `pay_option_value` varchar(111) DEFAULT NULL,
  `remarks` longtext,
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '3=done,2=switched,1=booked,0=canceled',
  `switch_to` int(11) NOT NULL DEFAULT '0',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `online_order`
--

CREATE TABLE `online_order` (
  `id` int(11) NOT NULL,
  `local_id` int(11) DEFAULT NULL,
  `store_id` varchar(111) DEFAULT NULL,
  `cashier_id` int(11) NOT NULL DEFAULT '0',
  `reference_id` varchar(111) DEFAULT NULL,
  `order_id` varchar(111) DEFAULT NULL,
  `response` longtext,
  `rider_contactno` varchar(111) DEFAULT NULL,
  `rider_name` varchar(111) DEFAULT NULL,
  `rider_status` varchar(111) DEFAULT NULL,
  `delivery_date` datetime DEFAULT NULL,
  `platform_id` varchar(111) DEFAULT NULL,
  `platform_name` varchar(111) DEFAULT NULL,
  `merchant_receivable_amount` varchar(111) DEFAULT NULL,
  `merchant_sponsored_discount` varchar(111) DEFAULT NULL,
  `total_price` varchar(111) NOT NULL DEFAULT '0',
  `total_taxes` varchar(111) DEFAULT '0',
  `total_amount` varchar(111) NOT NULL DEFAULT '0',
  `rider_response` longtext,
  `status` int(11) NOT NULL DEFAULT '0' COMMENT 'Acknowledged=1,Food Ready=2,Dispatched=3,Completed=4,Cancelled=5,default=0',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `notify` int(11) NOT NULL DEFAULT '0',
  `remarks` longtext,
  `cron_run` int(11) NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `order_customers`
--

CREATE TABLE `order_customers` (
  `id` int(11) NOT NULL,
  `local_id` int(11) DEFAULT NULL,
  `store_id` int(11) DEFAULT NULL,
  `phone` varchar(111) NOT NULL,
  `name` varchar(111) DEFAULT NULL,
  `address` longtext,
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `order_modifier`
--

CREATE TABLE `order_modifier` (
  `id` int(11) NOT NULL,
  `local_id` int(11) DEFAULT NULL,
  `orderhistory_id` int(11) DEFAULT NULL,
  `product_id` varchar(111) DEFAULT NULL,
  `product_name` varchar(111) DEFAULT NULL,
  `modifier_group_id` int(11) DEFAULT NULL,
  `modifier_group_name` varchar(111) DEFAULT NULL,
  `modifier_group_type` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `modifier_name` varchar(111) DEFAULT NULL,
  `modifier_price` double DEFAULT NULL,
  `quantity` varchar(111) NOT NULL DEFAULT '0',
  `reference_id` varchar(111) DEFAULT NULL,
  `store_id` varchar(111) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `split_reference_id` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `order_orderhistories`
--

CREATE TABLE `order_orderhistories` (
  `id` bigint(10) NOT NULL,
  `local_id` int(11) DEFAULT NULL,
  `reference_id` varchar(50) NOT NULL,
  `store_id` varchar(111) DEFAULT NULL,
  `group_id` varchar(111) DEFAULT NULL,
  `cashier_id` int(111) NOT NULL,
  `category` varchar(150) NOT NULL DEFAULT '0',
  `category_name` varchar(111) DEFAULT NULL,
  `kds` int(11) NOT NULL DEFAULT '1' COMMENT '1-kitchen,2-bar',
  `dish_type` varchar(111) NOT NULL DEFAULT 'all',
  `kot_number` varchar(111) DEFAULT NULL,
  `kot_date` date DEFAULT NULL,
  `product_id` bigint(50) NOT NULL,
  `product_name` varchar(250) DEFAULT NULL,
  `quantity` double NOT NULL,
  `price` double NOT NULL,
  `total_tax` double NOT NULL DEFAULT '0',
  `total_tax_percentage` double NOT NULL DEFAULT '0',
  `total_price` double NOT NULL DEFAULT '0',
  `discount` double NOT NULL,
  `package` int(11) NOT NULL DEFAULT '0',
  `table_number` varchar(111) NOT NULL DEFAULT '0',
  `is_offer` int(11) NOT NULL DEFAULT '0',
  `offer_id` varchar(111) DEFAULT NULL,
  `offer_name` varchar(111) DEFAULT NULL,
  `offer_type` varchar(111) DEFAULT NULL,
  `remarks` text,
  `device_id` varchar(200) DEFAULT NULL,
  `prep_time` varchar(111) NOT NULL DEFAULT '0',
  `fire_at` datetime DEFAULT NULL,
  `kds_status` int(11) NOT NULL DEFAULT '0' COMMENT '0 - unfire\r\n1 - fire\r\n2 - hold\r\n3 - start\r\n4 - bump\r\n5 - flag\r\n6 - send to table\r\n7 - recall',
  `kds_track_id` varchar(111) DEFAULT NULL,
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `status` int(3) NOT NULL DEFAULT '1',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `order_orderhistories_json`
--

CREATE TABLE `order_orderhistories_json` (
  `id` int(11) NOT NULL,
  `local_id` int(11) DEFAULT NULL,
  `store_id` int(11) DEFAULT NULL,
  `reference_id` varchar(111) NOT NULL,
  `table_number` varchar(111) DEFAULT NULL,
  `json` longtext NOT NULL,
  `sync` int(11) NOT NULL DEFAULT '0',
  `local_sync` int(11) NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `order_payments`
--

CREATE TABLE `order_payments` (
  `id` bigint(10) NOT NULL,
  `local_id` int(11) DEFAULT NULL,
  `reference_id` varchar(50) NOT NULL,
  `store_id` varchar(111) DEFAULT NULL,
  `device_id` varchar(200) DEFAULT NULL,
  `cashier_id` int(111) NOT NULL,
  `type` int(3) NOT NULL COMMENT '1-Cash,2-card,6-UPI,7-No Charge,8-StoreCard,9-online,10-hold_bill,11-online(zomato gold, EazyDiner/EazyDiner Mislleneous/EazyDiner Offline etc), 12-Reelo Wallet',
  `type_value` varchar(50) NOT NULL,
  `amount` double NOT NULL,
  `round` varchar(111) NOT NULL DEFAULT '0',
  `status` int(3) NOT NULL DEFAULT '1',
  `remarks` varchar(150) DEFAULT NULL,
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `order_split_orderhistories`
--

CREATE TABLE `order_split_orderhistories` (
  `id` bigint(10) NOT NULL,
  `local_id` int(11) DEFAULT NULL,
  `split_reference_id` varchar(111) DEFAULT NULL,
  `order_history_id` int(11) NOT NULL DEFAULT '0',
  `reference_id` varchar(50) NOT NULL,
  `store_id` varchar(111) DEFAULT NULL,
  `group_id` varchar(111) DEFAULT NULL,
  `cashier_id` int(111) NOT NULL,
  `category` varchar(150) NOT NULL DEFAULT '0',
  `category_name` varchar(111) DEFAULT NULL,
  `kds` int(11) NOT NULL DEFAULT '1' COMMENT '1-kitchen,2-bar',
  `dish_type` varchar(111) NOT NULL DEFAULT 'all',
  `kot_number` varchar(111) DEFAULT NULL,
  `product_id` bigint(50) NOT NULL,
  `product_name` varchar(250) DEFAULT NULL,
  `quantity` double NOT NULL,
  `price` double NOT NULL,
  `total_tax` double NOT NULL DEFAULT '0',
  `total_tax_percentage` double NOT NULL DEFAULT '0',
  `total_price` double NOT NULL DEFAULT '0',
  `discount` double NOT NULL,
  `package` int(11) NOT NULL DEFAULT '0',
  `table_number` varchar(111) NOT NULL DEFAULT '0',
  `remarks` text,
  `device_id` varchar(200) DEFAULT NULL,
  `prep_time` varchar(111) NOT NULL DEFAULT '0',
  `fire_at` datetime DEFAULT NULL,
  `kds_status` int(11) NOT NULL DEFAULT '0' COMMENT '0 - unfire\r\n1 - fire\r\n2 - hold\r\n3 - start\r\n4 - bump\r\n5 - flag\r\n6 - send to table\r\n7 - recall',
  `kds_track_id` varchar(111) DEFAULT NULL,
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `status` int(3) NOT NULL DEFAULT '1',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `order_split_payments`
--

CREATE TABLE `order_split_payments` (
  `id` bigint(10) NOT NULL,
  `local_id` int(11) DEFAULT NULL,
  `split_reference_id` varchar(111) DEFAULT NULL,
  `reference_id` varchar(50) NOT NULL,
  `main_payment_id` int(11) DEFAULT NULL,
  `split_txn_id` int(11) NOT NULL DEFAULT '0',
  `store_id` varchar(111) DEFAULT NULL,
  `device_id` varchar(200) DEFAULT NULL,
  `cashier_id` int(111) NOT NULL,
  `type` int(3) NOT NULL COMMENT '1-Cash,2-card,6-UPI,7-No Charge,8-StoreCard,9-online,10-hold_bill',
  `type_value` varchar(50) NOT NULL,
  `amount` double NOT NULL,
  `round` varchar(111) NOT NULL DEFAULT '0',
  `status` int(3) NOT NULL DEFAULT '1',
  `remarks` varchar(150) DEFAULT NULL,
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` datetime NOT NULL,
  `modified` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `order_split_transactions`
--

CREATE TABLE `order_split_transactions` (
  `id` bigint(10) NOT NULL,
  `local_id` int(11) DEFAULT NULL,
  `split_reference_id` varchar(111) DEFAULT NULL,
  `reference_id` varchar(111) NOT NULL,
  `group_id` varchar(111) DEFAULT NULL,
  `store_id` varchar(111) DEFAULT NULL,
  `orderno` varchar(50) DEFAULT NULL,
  `cashier_id` int(111) NOT NULL,
  `device_id` varchar(250) NOT NULL DEFAULT '0',
  `table_number` varchar(300) NOT NULL DEFAULT '0',
  `pax` int(11) DEFAULT '1',
  `waiter_id` int(11) DEFAULT NULL,
  `waiter_name` varchar(111) DEFAULT NULL,
  `price` double NOT NULL,
  `total_tax` varchar(111) DEFAULT NULL,
  `other_taxes` varchar(111) NOT NULL DEFAULT '0',
  `servicecharge` double NOT NULL DEFAULT '0',
  `discount` double NOT NULL DEFAULT '0',
  `discount_type` int(11) NOT NULL DEFAULT '1' COMMENT '0-coupon,1-percentage,2-flat',
  `discount_type_value` varchar(111) NOT NULL DEFAULT '0',
  `discount_items` varchar(111) NOT NULL DEFAULT 'all' COMMENT '0-food,1-beverage,2-liquor',
  `delivery_charge` double NOT NULL DEFAULT '0',
  `packing_charge` double NOT NULL DEFAULT '0',
  `amount` double NOT NULL,
  `round` double NOT NULL DEFAULT '0',
  `name` varchar(150) NOT NULL DEFAULT 'No Value',
  `phone` varchar(250) NOT NULL DEFAULT '0',
  `address` text,
  `remarks` text,
  `is_takeaway` int(11) NOT NULL DEFAULT '0',
  `status` int(3) NOT NULL DEFAULT '1' COMMENT '0-cancelled,1-pending,2-completed',
  `tokenno` varchar(150) NOT NULL DEFAULT '0',
  `createtime` time NOT NULL,
  `payment_mode` varchar(100) DEFAULT NULL,
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `reelo_sync` int(11) NOT NULL DEFAULT '0',
  `loyalty_flag` smallint(6) NOT NULL DEFAULT '1',
  `created` datetime NOT NULL,
  `modified` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `order_tables`
--

CREATE TABLE `order_tables` (
  `id` int(4) NOT NULL,
  `number` varchar(50) NOT NULL,
  `table_category_id` int(11) DEFAULT NULL,
  `segment` varchar(100) DEFAULT NULL,
  `reference_id` varchar(200) NOT NULL,
  `cover_charge` varchar(111) DEFAULT NULL,
  `weekend_charge` double NOT NULL,
  `pax` varchar(111) DEFAULT NULL,
  `is_waiter_required` int(11) NOT NULL DEFAULT '0',
  `is_customer_detail_required` int(11) NOT NULL DEFAULT '0',
  `waiter_id` int(11) DEFAULT NULL,
  `waiter_name` varchar(111) DEFAULT NULL,
  `customer_name` varchar(111) DEFAULT NULL,
  `customer_phone` varchar(111) DEFAULT NULL,
  `customer_address` longtext,
  `customer_pax` varchar(111) DEFAULT NULL,
  `is_alert` int(11) NOT NULL DEFAULT '0',
  `location_id` varchar(111) DEFAULT NULL,
  `location_name` varchar(111) DEFAULT NULL,
  `is_takeaway` int(11) NOT NULL DEFAULT '0',
  `status` int(3) NOT NULL DEFAULT '1' COMMENT '0-inactive,1-vacant,2-booked,3-billed,4-merge',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `order_table_tracking`
--

CREATE TABLE `order_table_tracking` (
  `id` int(11) NOT NULL,
  `local_id` int(11) DEFAULT NULL,
  `store_id` int(11) DEFAULT NULL,
  `main_table_id` int(11) NOT NULL,
  `main_table_number` varchar(111) DEFAULT NULL,
  `main_table_ref_id` varchar(111) NOT NULL,
  `merged_table_id` int(11) NOT NULL,
  `merged_table_number` varchar(111) DEFAULT NULL,
  `merged_table_ref_id` varchar(111) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `order_tax`
--

CREATE TABLE `order_tax` (
  `id` int(11) NOT NULL,
  `local_id` int(11) DEFAULT NULL,
  `orderhistory_id` bigint(10) NOT NULL DEFAULT '0',
  `store_id` varchar(111) DEFAULT NULL,
  `product_id` varchar(111) DEFAULT NULL,
  `product_name` varchar(111) DEFAULT NULL,
  `kds` int(11) NOT NULL DEFAULT '1' COMMENT '1-kitchen,2-bar',
  `dish_type` varchar(111) NOT NULL DEFAULT 'all',
  `price` double NOT NULL,
  `taxable_price` double NOT NULL DEFAULT '0',
  `cat_id` int(11) DEFAULT NULL,
  `cat_name` varchar(111) DEFAULT NULL,
  `discount_percentage` double NOT NULL DEFAULT '0',
  `quantity` int(11) NOT NULL,
  `tax_id` int(11) DEFAULT NULL,
  `tax_name` varchar(111) DEFAULT NULL,
  `tax_percentage` double DEFAULT NULL,
  `tax_value` double DEFAULT '0',
  `reference_id` varchar(111) DEFAULT NULL,
  `package` int(11) NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `order_transactions`
--

CREATE TABLE `order_transactions` (
  `id` bigint(10) NOT NULL,
  `local_id` int(11) DEFAULT NULL,
  `reference_id` varchar(111) NOT NULL,
  `group_id` varchar(111) DEFAULT NULL,
  `store_id` varchar(111) DEFAULT NULL,
  `orderno` varchar(50) DEFAULT NULL,
  `cashier_id` int(111) NOT NULL,
  `device_id` varchar(250) NOT NULL DEFAULT '0',
  `table_number` varchar(300) NOT NULL DEFAULT '0',
  `pax` int(11) DEFAULT '1',
  `waiter_id` int(11) DEFAULT NULL,
  `waiter_name` varchar(111) DEFAULT NULL,
  `price` double NOT NULL,
  `total_tax` double DEFAULT NULL,
  `other_taxes` varchar(111) NOT NULL DEFAULT '0',
  `servicecharge` double NOT NULL DEFAULT '0',
  `discount` double NOT NULL DEFAULT '0',
  `discount_type` int(11) NOT NULL DEFAULT '1' COMMENT '0-coupon,1-percentage,2-flat',
  `discount_type_value` varchar(111) NOT NULL DEFAULT '0',
  `discount_items` varchar(111) NOT NULL DEFAULT 'all' COMMENT '0-food,1-beverage,2-liquor',
  `delivery_charge` double NOT NULL DEFAULT '0',
  `packing_charge` double NOT NULL DEFAULT '0',
  `amount` double NOT NULL,
  `round` double NOT NULL DEFAULT '0',
  `name` varchar(150) NOT NULL DEFAULT 'No Value',
  `phone` varchar(250) NOT NULL DEFAULT '0',
  `address` text,
  `remarks` text,
  `is_takeaway` int(11) NOT NULL DEFAULT '0',
  `status` int(3) NOT NULL DEFAULT '1' COMMENT '0-cancelled,1-pending,2-completed',
  `tokenno` varchar(150) NOT NULL DEFAULT '0',
  `createtime` time NOT NULL,
  `payment_mode` varchar(100) DEFAULT NULL,
  `is_split` int(11) NOT NULL DEFAULT '0',
  `split_type` varchar(111) NOT NULL DEFAULT 'no',
  `split_count` int(11) NOT NULL DEFAULT '0',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `reelo_sync` int(11) NOT NULL DEFAULT '0',
  `loyalty_flag` smallint(6) NOT NULL DEFAULT '1',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `order_waiters`
--

CREATE TABLE `order_waiters` (
  `id` int(4) NOT NULL,
  `local_id` int(11) DEFAULT NULL,
  `name` varchar(111) NOT NULL,
  `phone` varchar(111) DEFAULT NULL,
  `location_id` varchar(111) DEFAULT NULL,
  `location_name` varchar(111) DEFAULT NULL,
  `pin_number` varchar(50) NOT NULL,
  `is_pin_required` int(11) NOT NULL DEFAULT '0',
  `status` int(3) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `paymentpartner_api_logs`
--

CREATE TABLE `paymentpartner_api_logs` (
  `id` int(11) NOT NULL,
  `orderid` varchar(255) DEFAULT NULL,
  `partner_name` varchar(100) DEFAULT NULL,
  `pos_reference_id` varchar(255) DEFAULT NULL,
  `response_payload` longtext,
  `order_status` varchar(100) DEFAULT NULL,
  `sync` int(11) NOT NULL DEFAULT '0',
  `local_sync` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `payment_partners`
--

CREATE TABLE `payment_partners` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live sync',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local sync',
  `created` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `phyloai_settings`
--

CREATE TABLE `phyloai_settings` (
  `key` varchar(100) NOT NULL,
  `value` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `pos_auditstock`
--

CREATE TABLE `pos_auditstock` (
  `id` int(11) NOT NULL,
  `pos_audit_id` varchar(25) DEFAULT NULL,
  `store_id` varchar(25) NOT NULL,
  `store_name` varchar(111) DEFAULT NULL,
  `remarks` varchar(150) DEFAULT NULL,
  `foodcost` int(3) NOT NULL DEFAULT '0',
  `status` bigint(20) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `select_all` int(11) NOT NULL DEFAULT '0',
  `item_type` varchar(111) NOT NULL DEFAULT 'sku',
  `type` varchar(111) NOT NULL DEFAULT 'DC',
  `updateon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `auditto` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `pos_auditstock_items`
--

CREATE TABLE `pos_auditstock_items` (
  `id` int(11) NOT NULL,
  `pos_audit_id` varchar(25) DEFAULT NULL,
  `store_id` int(11) NOT NULL DEFAULT '0',
  `sku_id` varchar(100) DEFAULT NULL,
  `sku_name` varchar(100) DEFAULT NULL,
  `item_type` varchar(111) NOT NULL DEFAULT 'sku',
  `type` varchar(111) NOT NULL DEFAULT 'DC',
  `sku_unit` int(11) DEFAULT NULL,
  `sku_unit_name` varchar(111) DEFAULT NULL,
  `sku_price` varchar(300) DEFAULT NULL,
  `openingstock` varchar(300) DEFAULT NULL,
  `closingstock` varchar(300) DEFAULT NULL,
  `physicalstock` varchar(300) DEFAULT NULL,
  `variance` varchar(111) NOT NULL DEFAULT '0',
  `purchased` double NOT NULL DEFAULT '0',
  `consumption` double NOT NULL DEFAULT '0',
  `damage` double NOT NULL DEFAULT '0',
  `auditfrom` varchar(300) DEFAULT NULL,
  `auditto` varchar(300) DEFAULT NULL,
  `remarks` varchar(250) DEFAULT NULL,
  `status` tinyint(3) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `pos_offers`
--

CREATE TABLE `pos_offers` (
  `id` int(11) NOT NULL,
  `location_id` int(11) DEFAULT '1',
  `location_name` varchar(111) DEFAULT 'TGIF Ambience Mall Gurugram',
  `offer_id` varchar(111) DEFAULT NULL,
  `name` varchar(111) DEFAULT NULL,
  `offer_type` varchar(111) NOT NULL DEFAULT 'bogo',
  `item_type` varchar(111) NOT NULL DEFAULT 'item',
  `cat_id` int(11) DEFAULT NULL,
  `cat_name` varchar(111) DEFAULT NULL,
  `subcat_id` int(11) DEFAULT NULL,
  `subcat_name` varchar(111) DEFAULT NULL,
  `sku_id` int(11) DEFAULT NULL,
  `sku_name` varchar(111) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `days` varchar(255) DEFAULT 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
  `sync` int(11) NOT NULL DEFAULT '0',
  `local_sync` int(11) NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '1',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `pos_printers`
--

CREATE TABLE `pos_printers` (
  `id` int(5) NOT NULL,
  `store_id` varchar(200) DEFAULT NULL,
  `store_name` varchar(111) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `kds` int(3) DEFAULT NULL,
  `ip` varchar(50) NOT NULL,
  `status` int(3) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `pos_tempauditstock`
--

CREATE TABLE `pos_tempauditstock` (
  `id` int(11) NOT NULL,
  `pos_audit_id` varchar(25) DEFAULT NULL,
  `store_id` varchar(25) NOT NULL,
  `remarks` varchar(150) DEFAULT NULL,
  `foodcost` int(3) NOT NULL DEFAULT '0',
  `status` bigint(20) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `select_all` int(11) NOT NULL DEFAULT '0',
  `updateon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `pos_tempauditstock_items`
--

CREATE TABLE `pos_tempauditstock_items` (
  `id` int(11) NOT NULL,
  `pos_audit_id` varchar(25) DEFAULT NULL,
  `sku_id` varchar(100) DEFAULT NULL,
  `sku_name` varchar(100) DEFAULT NULL,
  `physicalstock` varchar(300) DEFAULT NULL,
  `auditfrom` varchar(300) DEFAULT NULL,
  `auditto` varchar(300) DEFAULT NULL,
  `remarks` varchar(250) DEFAULT NULL,
  `status` tinyint(3) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `reelo_discounts`
--

CREATE TABLE `reelo_discounts` (
  `id` int(11) NOT NULL,
  `local_id` int(11) DEFAULT NULL,
  `store_id` varchar(111) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `reference_id` varchar(111) DEFAULT NULL,
  `split_reference_id` varchar(111) DEFAULT NULL,
  `item_json` text,
  `reelo_discount_type` int(11) DEFAULT NULL COMMENT '0-coupon, 1-point, 2-wallet_credit',
  `coupon_code` varchar(255) DEFAULT NULL,
  `discount_on` varchar(111) DEFAULT NULL COMMENT 'item/bill',
  `discount_type` varchar(111) DEFAULT NULL COMMENT 'percentage, fixed',
  `discount_value` double DEFAULT NULL,
  `otp` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sync` int(11) DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) DEFAULT '0' COMMENT 'live to local',
  `status` int(11) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text,
  `status` int(11) DEFAULT '1',
  `sync` int(11) DEFAULT '0',
  `local_sync` int(11) DEFAULT '0',
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `service_purchase_orders`
--

CREATE TABLE `service_purchase_orders` (
  `id` int(11) NOT NULL,
  `spo_id` varchar(111) NOT NULL COMMENT 'Unique reference e.g. SPO20260301001',
  `vendor_id` int(11) NOT NULL DEFAULT '0',
  `vendor_name` varchar(255) DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `location_name` varchar(255) DEFAULT NULL,
  `service_type_id` int(11) DEFAULT NULL COMMENT 'FK -> services.id',
  `service_type_name` varchar(255) DEFAULT NULL,
  `description` text,
  `period_from` date DEFAULT NULL,
  `period_to` date DEFAULT NULL,
  `invoice_no` varchar(111) DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `subtotal` double NOT NULL DEFAULT '0',
  `discount` double NOT NULL DEFAULT '0',
  `total_tax` double NOT NULL DEFAULT '0',
  `total_amount` double NOT NULL DEFAULT '0',
  `approved_by_id` int(11) DEFAULT NULL,
  `approved_by_name` varchar(255) DEFAULT NULL,
  `approved_on` datetime DEFAULT NULL,
  `approved_amount` double NOT NULL DEFAULT '0',
  `processed_by_id` int(11) DEFAULT NULL,
  `processed_by_name` varchar(255) DEFAULT NULL,
  `processed_on` datetime DEFAULT NULL,
  `processed_amount` double NOT NULL DEFAULT '0',
  `remarks` text,
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '1=Submitted 2=Approved 3=Processed 0=Cancelled',
  `created_by` int(11) DEFAULT NULL,
  `created_by_name` varchar(255) DEFAULT NULL,
  `sync` int(11) NOT NULL DEFAULT '0',
  `local_sync` int(11) NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Service Purchase Orders (Work Orders)';

-- --------------------------------------------------------

--
-- Table structure for table `service_purchase_order_items`
--

CREATE TABLE `service_purchase_order_items` (
  `id` int(11) NOT NULL,
  `spo_id` varchar(111) NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `description` text,
  `unit` varchar(50) DEFAULT 'Lump Sum' COMMENT 'Monthly|Annual|Per Visit|Lump Sum|Hours',
  `quantity` double NOT NULL DEFAULT '1',
  `unit_price` double NOT NULL DEFAULT '0',
  `discount_value` double NOT NULL DEFAULT '0',
  `tax_percentage` double NOT NULL DEFAULT '0',
  `tax_value` double NOT NULL DEFAULT '0',
  `total_amount` double NOT NULL DEFAULT '0',
  `approved_amount` double NOT NULL DEFAULT '0',
  `processed_amount` double NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '1',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `stock_ledger`
--

CREATE TABLE `stock_ledger` (
  `id` int(11) NOT NULL,
  `local_id` int(11) DEFAULT NULL,
  `store_id` varchar(111) DEFAULT NULL,
  `sku_id` int(11) NOT NULL,
  `sku_name` varchar(111) DEFAULT NULL,
  `sku_price` varchar(111) DEFAULT NULL,
  `unit_id` varchar(111) DEFAULT NULL,
  `unit_name` varchar(111) DEFAULT NULL,
  `record_id` varchar(111) DEFAULT NULL,
  `record_type` varchar(111) DEFAULT NULL,
  `quantity` varchar(111) DEFAULT NULL,
  `balance_stock` varchar(111) DEFAULT NULL,
  `variance` varchar(111) NOT NULL DEFAULT '0',
  `actual_balance` varchar(111) DEFAULT NULL,
  `variance_type` varchar(111) DEFAULT NULL,
  `variance_id` varchar(111) DEFAULT NULL,
  `quantity_type` varchar(111) DEFAULT NULL,
  `item_type` varchar(111) NOT NULL DEFAULT 'sku',
  `type` varchar(111) DEFAULT NULL,
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `alert_flag` tinyint(4) NOT NULL DEFAULT '1',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `stock_transfers`
--

CREATE TABLE `stock_transfers` (
  `id` int(11) NOT NULL,
  `transfer_id` varchar(111) NOT NULL,
  `transfer_from` varchar(111) NOT NULL,
  `transfer_from_name` varchar(111) DEFAULT NULL,
  `transfer_to` varchar(111) NOT NULL,
  `transfer_to_name` varchar(111) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_by_name` varchar(111) DEFAULT NULL,
  `tax` double NOT NULL DEFAULT '0',
  `amount` double NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `stock_transfer_items`
--

CREATE TABLE `stock_transfer_items` (
  `Id` int(11) NOT NULL,
  `transfer_id` varchar(111) NOT NULL,
  `sku_id` int(11) NOT NULL,
  `sku_name` varchar(111) NOT NULL,
  `sku_brand` varchar(111) NOT NULL,
  `sku_unit` varchar(111) NOT NULL,
  `sku_unit_name` varchar(111) NOT NULL,
  `sku_price` double NOT NULL,
  `tax_percent` varchar(111) NOT NULL DEFAULT '0',
  `sku_quantity` varchar(111) NOT NULL,
  `tax_value` double NOT NULL DEFAULT '0',
  `amount` double NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `store_connectors`
--

CREATE TABLE `store_connectors` (
  `id` int(11) NOT NULL,
  `store_id` varchar(111) NOT NULL COMMENT 'maps to locations.id',
  `store_key` varchar(100) NOT NULL COMMENT 'public identifier used in pull request',
  `store_token_hash` varchar(255) NOT NULL COMMENT 'password_hash() of the secret token; never store plaintext',
  `last_pull_at` datetime DEFAULT NULL,
  `last_event_id` int(11) NOT NULL DEFAULT '0' COMMENT 'last event_id successfully acked by this store',
  `is_enabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=disabled, 1=active — flip to 0 for instant cutover',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Store pull-agent authentication and heartbeat';

-- --------------------------------------------------------

--
-- Table structure for table `sync_errors`
--

CREATE TABLE `sync_errors` (
  `id` int(11) NOT NULL,
  `store_id` int(11) DEFAULT NULL,
  `local_id` int(11) DEFAULT NULL,
  `reference_id` varchar(100) DEFAULT NULL COMMENT 'Bill reference_id for bill-related errors',
  `table_name` varchar(100) NOT NULL COMMENT 'Source table that failed sync',
  `record_id` int(11) DEFAULT NULL COMMENT 'ID of the specific record that failed',
  `error_message` text COMMENT 'SQL or application error message',
  `record_data` text COMMENT 'JSON snapshot of the record that failed',
  `direction` enum('push','pull') DEFAULT 'push' COMMENT 'Sync direction when error occurred',
  `attempts` int(11) DEFAULT '1' COMMENT 'Number of sync attempts',
  `resolved` tinyint(4) DEFAULT '0' COMMENT '1 = manually resolved or auto-retried successfully',
  `sync` int(11) NOT NULL DEFAULT '0',
  `created` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Tracks sync failures for quarantine and debugging';

-- --------------------------------------------------------

--
-- Table structure for table `table_categories`
--

CREATE TABLE `table_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1-Active, 0-Inactive',
  `sync` int(11) NOT NULL DEFAULT '0',
  `local_sync` int(11) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `tax_charges`
--

CREATE TABLE `tax_charges` (
  `id` int(11) NOT NULL,
  `local_id` int(11) DEFAULT NULL,
  `store_id` int(11) DEFAULT NULL,
  `online_aggregator_id` varchar(111) DEFAULT NULL,
  `reference_id` varchar(111) DEFAULT NULL,
  `tax_id` int(11) NOT NULL DEFAULT '0',
  `tax_name` varchar(111) DEFAULT NULL,
  `tax_percentage` varchar(111) DEFAULT NULL,
  `tax_value` double NOT NULL DEFAULT '0',
  `charge_title` varchar(111) DEFAULT NULL,
  `sync` int(11) NOT NULL DEFAULT '0',
  `local_sync` int(11) NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '1',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `up_group_option_link`
--

CREATE TABLE `up_group_option_link` (
  `id` int(11) NOT NULL,
  `store_id` varchar(111) NOT NULL,
  `up_group_id` varchar(100) NOT NULL,
  `up_option_id` varchar(100) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='UrbanPiper option-group to option association';

-- --------------------------------------------------------

--
-- Table structure for table `up_item_group_link`
--

CREATE TABLE `up_item_group_link` (
  `id` int(11) NOT NULL,
  `store_id` varchar(111) NOT NULL,
  `up_item_id` varchar(100) NOT NULL,
  `up_group_id` varchar(100) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='UrbanPiper item to option-group association';

-- --------------------------------------------------------

--
-- Table structure for table `up_item_map`
--

CREATE TABLE `up_item_map` (
  `id` int(11) NOT NULL,
  `store_id` varchar(111) NOT NULL,
  `up_item_id` varchar(100) NOT NULL COMMENT 'UP item id',
  `up_item_name` varchar(255) DEFAULT NULL,
  `tgif_product_id` int(11) DEFAULT NULL COMMENT 'FK → catalog_product.id',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=active, 0=inactive',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Mapping: UrbanPiper items -> TGIF catalog products';

-- --------------------------------------------------------

--
-- Table structure for table `up_option_group_map`
--

CREATE TABLE `up_option_group_map` (
  `id` int(11) NOT NULL,
  `store_id` varchar(111) NOT NULL,
  `up_group_id` varchar(100) NOT NULL COMMENT 'UP option group id',
  `up_group_name` varchar(255) DEFAULT NULL,
  `local_group_code` varchar(100) DEFAULT NULL COMMENT 'maps to catalog_modifier_group.id or custom code',
  `min_select` tinyint(3) NOT NULL DEFAULT '0',
  `max_select` tinyint(3) NOT NULL DEFAULT '1',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Mapping: UrbanPiper option groups -> TGIF modifier groups';

-- --------------------------------------------------------

--
-- Table structure for table `up_option_map`
--

CREATE TABLE `up_option_map` (
  `id` int(11) NOT NULL,
  `store_id` varchar(111) NOT NULL,
  `up_option_id` varchar(100) NOT NULL COMMENT 'UP option id',
  `up_option_name` varchar(255) DEFAULT NULL,
  `tgif_modifier_product_id` int(11) DEFAULT NULL COMMENT 'FK → catalog_modifier.id',
  `price_delta` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'price difference vs base (for addons)',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Mapping: UrbanPiper options -> TGIF modifiers';

-- --------------------------------------------------------

--
-- Table structure for table `up_orders`
--

CREATE TABLE `up_orders` (
  `id` int(11) NOT NULL,
  `store_id` varchar(111) NOT NULL COMMENT 'maps to locations.id / store_connectors.store_id',
  `channel` varchar(50) NOT NULL COMMENT 'zomato|swiggy|dunzo|etc',
  `up_order_id` varchar(100) NOT NULL COMMENT 'UP canonical order id',
  `up_order_display_id` varchar(100) DEFAULT NULL COMMENT 'human-readable order number from UP',
  `reference_id` varchar(111) DEFAULT NULL COMMENT 'TGIF local reference_id once created in POS',
  `status` varchar(50) NOT NULL DEFAULT 'received' COMMENT 'received|acknowledged|food_ready|dispatched|completed|cancelled|error',
  `order_type` varchar(20) DEFAULT NULL COMMENT 'delivery|pickup|dine_in',
  `payment_mode` varchar(20) DEFAULT NULL COMMENT 'prepaid|cod',
  `raw_payload` mediumtext COMMENT 'full JSON from UP — never modify',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='UrbanPiper orders received by cloud';

-- --------------------------------------------------------

--
-- Table structure for table `up_order_events`
--

CREATE TABLE `up_order_events` (
  `id` int(11) NOT NULL,
  `up_order_id` varchar(100) NOT NULL COMMENT 'FK-like to up_orders.up_order_id',
  `store_id` varchar(111) NOT NULL,
  `event_type` varchar(50) NOT NULL COMMENT 'PUSH_TO_STORE|UPDATE_STATUS|CANCEL|STATUS_PUSH_RETRY|MENU_CALLBACK',
  `payload` mediumtext COMMENT 'normalized canonical payload for this event',
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending|delivered|acked|failed',
  `attempt_count` smallint(5) NOT NULL DEFAULT '0',
  `last_error` text,
  `delivered_at` datetime DEFAULT NULL,
  `acked_at` datetime DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Job queue for store pull-based processing';

-- --------------------------------------------------------

--
-- Table structure for table `up_tax_map`
--

CREATE TABLE `up_tax_map` (
  `id` int(11) NOT NULL,
  `up_tax_name` varchar(100) NOT NULL COMMENT 'Tax title from UrbanPiper payload',
  `up_tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Tax rate from UP payload',
  `local_tax_id` int(11) NOT NULL COMMENT 'FK -> catalog_tax.id',
  `local_tax_name` varchar(100) DEFAULT NULL,
  `local_tax_percentage` decimal(5,2) DEFAULT NULL,
  `store_id` varchar(111) DEFAULT NULL COMMENT 'NULL = applies to all stores',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=active',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Maps UrbanPiper tax names/rates to local catalog_tax IDs';

-- --------------------------------------------------------

--
-- Table structure for table `urbanpiper_limits`
--

CREATE TABLE `urbanpiper_limits` (
  `id` int(11) NOT NULL,
  `event_code` varchar(50) NOT NULL,
  `event_name` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0' COMMENT '0=Active, 1=Blocked',
  `retry_attempt` int(11) DEFAULT '0' COMMENT 'Number of retry attempts',
  `remarks` text COMMENT 'Last error message or notes',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `urbanpiper_response`
--

CREATE TABLE `urbanpiper_response` (
  `id` int(11) NOT NULL,
  `response` text COMMENT 'Full API response JSON',
  `event` varchar(255) DEFAULT NULL COMMENT 'Type of event',
  `event_type` varchar(50) DEFAULT NULL COMMENT 'Event category identifier',
  `created` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(250) NOT NULL,
  `password` varchar(250) NOT NULL,
  `name` varchar(250) NOT NULL,
  `role` varchar(250) NOT NULL DEFAULT 'cashier',
  `mobile` varchar(111) DEFAULT NULL,
  `device_id` varchar(111) DEFAULT NULL,
  `location_id` varchar(111) DEFAULT NULL,
  `location_name` varchar(111) DEFAULT NULL,
  `billprint_ip` varchar(111) NOT NULL DEFAULT '192.168.18.195',
  `can_settlement` int(11) NOT NULL DEFAULT '1' COMMENT '0-no,1-yes',
  `pin` varchar(111) DEFAULT NULL,
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '1-active,0-inactive',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_locations`
--

CREATE TABLE `user_locations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(111) NOT NULL,
  `location_id` int(11) NOT NULL,
  `location_name` varchar(111) DEFAULT NULL,
  `billing_location` int(11) NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_modules`
--

CREATE TABLE `user_modules` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(111) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_tracks`
--

CREATE TABLE `user_tracks` (
  `id` int(11) NOT NULL,
  `local_id` int(11) DEFAULT NULL,
  `reference_id` varchar(111) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `location_name` varchar(111) NOT NULL,
  `type` varchar(111) DEFAULT NULL,
  `sync` int(11) NOT NULL DEFAULT '0' COMMENT 'local to live',
  `local_sync` int(11) NOT NULL DEFAULT '0' COMMENT 'live to local',
  `status` int(11) NOT NULL DEFAULT '1',
  `remarks` longtext,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_product_recipe_cost`
-- (See below for the actual view)
--
CREATE TABLE `v_product_recipe_cost` (
`id` int(11)
,`name` varchar(111)
,`price` varchar(111)
,`recipe_cost` double(19,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_semifinished_recipe_cost`
-- (See below for the actual view)
--
CREATE TABLE `v_semifinished_recipe_cost` (
`id` int(11)
,`name` varchar(111)
,`price` double
,`consumption_factor` varchar(111)
,`batch_cost` double(19,2)
,`unit_cost` double(23,6)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_sf_unit_costs`
-- (See below for the actual view)
--
CREATE TABLE `v_sf_unit_costs` (
`id` int(11)
,`name` varchar(111)
,`consumption_factor` varchar(111)
,`yield` varchar(111)
,`consumption_unit_name` varchar(111)
,`cost_per_portion` double
,`grams_per_portion` decimal(10,4)
,`cost_per_gram` double(23,6)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_sku_unit_prices`
-- (See below for the actual view)
--
CREATE TABLE `v_sku_unit_prices` (
`sku_id` int(11)
,`sku_name` varchar(111)
,`unit_price` double
);

-- --------------------------------------------------------

--
-- Structure for view `v_product_recipe_cost`
--
DROP TABLE IF EXISTS `v_product_recipe_cost`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_product_recipe_cost`  AS  select `cp`.`id` AS `id`,`cp`.`name` AS `name`,`cp`.`price` AS `price`,round((coalesce((select sum((`il`.`conversion_quantity` * coalesce(`vsp`.`unit_price`,0))) from (`phylopos_tgi`.`inventory_linking` `il` left join `phylopos_tgi`.`v_sku_unit_prices` `vsp` on((`vsp`.`sku_id` = `il`.`component_id`))) where ((`il`.`parent_id` = `cp`.`id`) and (`il`.`parent_type` = 'Product') and (`il`.`component_type` = 'Sku') and (`il`.`status` = 1))),0) + coalesce((select sum((case when ((lcase(`il`.`component_unit_name`) in ('gms','gm','g','gram','grams','ml','millilitre')) and (lcase(`isf`.`consumption_unit_name`) in ('por','portion','nos','no','pce','ea','each')) and (coalesce(`isf`.`portion_weight_grams`,0) > 0)) then ((`il`.`conversion_quantity` / `isf`.`portion_weight_grams`) * coalesce(`isf`.`consumption_unit_price`,0)) when ((lcase(`il`.`component_unit_name`) in ('por','portion','nos','no','pce','ea','each')) and (lcase(`isf`.`consumption_unit_name`) in ('gms','gm','g','gram','grams','ml')) and (coalesce(`isf`.`portion_weight_grams`,0) > 0)) then ((`il`.`conversion_quantity` * `isf`.`portion_weight_grams`) * coalesce(`isf`.`consumption_unit_price`,0)) else (`il`.`conversion_quantity` * coalesce(`isf`.`consumption_unit_price`,0)) end)) from (`phylopos_tgi`.`inventory_linking` `il` join `phylopos_tgi`.`inventory_semifinished` `isf` on((`isf`.`id` = cast(`il`.`component_id` as unsigned)))) where ((`il`.`parent_id` = `cp`.`id`) and (`il`.`parent_type` = 'Product') and (`il`.`component_type` = 'Semifinished') and (`il`.`status` = 1))),0)),2) AS `recipe_cost` from `phylopos_tgi`.`catalog_product` `cp` ;

-- --------------------------------------------------------

--
-- Structure for view `v_semifinished_recipe_cost`
--
DROP TABLE IF EXISTS `v_semifinished_recipe_cost`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_semifinished_recipe_cost`  AS  select `isf`.`id` AS `id`,`isf`.`name` AS `name`,`isf`.`price` AS `price`,`isf`.`consumption_factor` AS `consumption_factor`,round(coalesce(sum((case `il`.`component_type` when 'Sku' then (`il`.`conversion_quantity` * coalesce(`vsp`.`unit_price`,0)) when 'Semifinished' then (`il`.`conversion_quantity` * coalesce(`sf2`.`consumption_unit_price`,0)) else 0 end)),0),2) AS `batch_cost`,round((case when (cast(`isf`.`consumption_factor` as decimal(15,6)) > 0) then (coalesce(sum((case `il`.`component_type` when 'Sku' then (`il`.`conversion_quantity` * coalesce(`vsp`.`unit_price`,0)) when 'Semifinished' then (`il`.`conversion_quantity` * coalesce(`sf2`.`consumption_unit_price`,0)) else 0 end)),0) / cast(`isf`.`consumption_factor` as decimal(15,6))) else 0 end),6) AS `unit_cost` from (((`phylopos_tgi`.`inventory_semifinished` `isf` left join `phylopos_tgi`.`inventory_linking` `il` on(((`il`.`parent_id` = `isf`.`id`) and (`il`.`parent_type` = 'Semifinished') and (`il`.`component_type` in ('Sku','Semifinished')) and (`il`.`status` = 1)))) left join `phylopos_tgi`.`v_sku_unit_prices` `vsp` on(((`vsp`.`sku_id` = `il`.`component_id`) and (`il`.`component_type` = 'Sku')))) left join `phylopos_tgi`.`inventory_semifinished` `sf2` on(((`sf2`.`id` = cast(`il`.`component_id` as unsigned)) and (`il`.`component_type` = 'Semifinished')))) group by `isf`.`id`,`isf`.`name`,`isf`.`price`,`isf`.`consumption_factor` ;

-- --------------------------------------------------------

--
-- Structure for view `v_sf_unit_costs`
--
DROP TABLE IF EXISTS `v_sf_unit_costs`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_sf_unit_costs`  AS  select `isf`.`id` AS `id`,`isf`.`name` AS `name`,`isf`.`consumption_factor` AS `consumption_factor`,`isf`.`yield` AS `yield`,`isf`.`consumption_unit_name` AS `consumption_unit_name`,`isf`.`consumption_unit_price` AS `cost_per_portion`,`isf`.`portion_weight_grams` AS `grams_per_portion`,(case when (`isf`.`portion_weight_grams` > 0) then round((`isf`.`consumption_unit_price` / `isf`.`portion_weight_grams`),6) else NULL end) AS `cost_per_gram` from `phylopos_tgi`.`inventory_semifinished` `isf` where (`isf`.`status` = 1) ;

-- --------------------------------------------------------

--
-- Structure for view `v_sku_unit_prices`
--
DROP TABLE IF EXISTS `v_sku_unit_prices`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_sku_unit_prices`  AS  select `sk`.`id` AS `sku_id`,`sk`.`name` AS `sku_name`,coalesce(`sk`.`consumption_unit_price`,0) AS `unit_price` from `phylopos_tgi`.`inventory_skus` `sk` where (`sk`.`status` = 1) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account_imprest`
--
ALTER TABLE `account_imprest`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cards`
--
ALTER TABLE `cards`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cardtracking`
--
ALTER TABLE `cardtracking`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `catalog_category`
--
ALTER TABLE `catalog_category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `catalog_category_branches`
--
ALTER TABLE `catalog_category_branches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `catalog_modifier`
--
ALTER TABLE `catalog_modifier`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `catalog_modifier_group`
--
ALTER TABLE `catalog_modifier_group`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `catalog_package`
--
ALTER TABLE `catalog_package`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `catalog_product`
--
ALTER TABLE `catalog_product`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `catalog_product_attribute`
--
ALTER TABLE `catalog_product_attribute`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `catalog_product_branches`
--
ALTER TABLE `catalog_product_branches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `catalog_product_tax`
--
ALTER TABLE `catalog_product_tax`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `catalog_product_tax_outlet`
--
ALTER TABLE `catalog_product_tax_outlet`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_outlet` (`product_id`,`outlet_id`),
  ADD KEY `idx_outlet` (`outlet_id`);

--
-- Indexes for table `catalog_subcategory`
--
ALTER TABLE `catalog_subcategory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `catalog_tax`
--
ALTER TABLE `catalog_tax`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `catalog_units`
--
ALTER TABLE `catalog_units`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `catalog_unit_mapping`
--
ALTER TABLE `catalog_unit_mapping`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `eazydiner_tokens`
--
ALTER TABLE `eazydiner_tokens`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `editing_managements`
--
ALTER TABLE `editing_managements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ref_cashier` (`reference_id`,`cashier_id`),
  ADD KEY `idx_store` (`store_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created`);

--
-- Indexes for table `inventory_category`
--
ALTER TABLE `inventory_category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_damage`
--
ALTER TABLE `inventory_damage`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_damage_items`
--
ALTER TABLE `inventory_damage_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_indent`
--
ALTER TABLE `inventory_indent`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_indent_items`
--
ALTER TABLE `inventory_indent_items`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `inventory_internal_indent`
--
ALTER TABLE `inventory_internal_indent`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_internal_indent_items`
--
ALTER TABLE `inventory_internal_indent_items`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `inventory_linking`
--
ALTER TABLE `inventory_linking`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_purchase_order`
--
ALTER TABLE `inventory_purchase_order`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_purchase_order_items`
--
ALTER TABLE `inventory_purchase_order_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_return`
--
ALTER TABLE `inventory_return`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_return_items`
--
ALTER TABLE `inventory_return_items`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `inventory_semifinished`
--
ALTER TABLE `inventory_semifinished`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_semifinished_tax`
--
ALTER TABLE `inventory_semifinished_tax`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_skus`
--
ALTER TABLE `inventory_skus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_expense_type` (`expense_type`);

--
-- Indexes for table `inventory_sku_excise`
--
ALTER TABLE `inventory_sku_excise`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sku_location` (`sku_id`,`location_id`),
  ADD KEY `idx_sku_id` (`sku_id`);

--
-- Indexes for table `inventory_sku_tax`
--
ALTER TABLE `inventory_sku_tax`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_stockin`
--
ALTER TABLE `inventory_stockin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_stockin_items`
--
ALTER TABLE `inventory_stockin_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_subcategory`
--
ALTER TABLE `inventory_subcategory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_tax`
--
ALTER TABLE `inventory_tax`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_units`
--
ALTER TABLE `inventory_units`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_vendors`
--
ALTER TABLE `inventory_vendors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_vendor_service_areas`
--
ALTER TABLE `inventory_vendor_service_areas`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `inventory_vendor_sku_served`
--
ALTER TABLE `inventory_vendor_sku_served`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `kds_tracks`
--
ALTER TABLE `kds_tracks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_local_store` (`local_id`,`location_id`);

--
-- Indexes for table `kds_users`
--
ALTER TABLE `kds_users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kds_users_station_mapping`
--
ALTER TABLE `kds_users_station_mapping`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kot_sequences`
--
ALTER TABLE `kot_sequences`
  ADD PRIMARY KEY (`store_id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `online_bookings`
--
ALTER TABLE `online_bookings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `online_order`
--
ALTER TABLE `online_order`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_local_store` (`local_id`,`store_id`),
  ADD KEY `idx_store_id` (`store_id`);

--
-- Indexes for table `order_customers`
--
ALTER TABLE `order_customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `uq_local_store` (`local_id`,`store_id`),
  ADD KEY `idx_store_id` (`store_id`);

--
-- Indexes for table `order_modifier`
--
ALTER TABLE `order_modifier`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_local_store` (`local_id`,`store_id`),
  ADD KEY `idx_store_id` (`store_id`);

--
-- Indexes for table `order_orderhistories`
--
ALTER TABLE `order_orderhistories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_local_store` (`local_id`,`store_id`),
  ADD KEY `idx_store_id` (`store_id`),
  ADD KEY `idx_ref_status` (`reference_id`,`status`),
  ADD KEY `idx_ref_cashier_status` (`reference_id`,`cashier_id`,`status`),
  ADD KEY `idx_created_status_store` (`created`,`status`,`store_id`);

--
-- Indexes for table `order_orderhistories_json`
--
ALTER TABLE `order_orderhistories_json`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_local_store` (`local_id`,`store_id`),
  ADD KEY `idx_store_id` (`store_id`);

--
-- Indexes for table `order_payments`
--
ALTER TABLE `order_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_local_store` (`local_id`,`store_id`),
  ADD KEY `idx_store_id` (`store_id`),
  ADD KEY `idx_type_status_ref` (`type`,`status`,`reference_id`);

--
-- Indexes for table `order_split_orderhistories`
--
ALTER TABLE `order_split_orderhistories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_local_store` (`local_id`,`store_id`),
  ADD KEY `idx_store_id` (`store_id`);

--
-- Indexes for table `order_split_payments`
--
ALTER TABLE `order_split_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_local_store` (`local_id`,`store_id`),
  ADD KEY `idx_store_id` (`store_id`);

--
-- Indexes for table `order_split_transactions`
--
ALTER TABLE `order_split_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_local_store` (`local_id`,`store_id`),
  ADD KEY `idx_store_id` (`store_id`);

--
-- Indexes for table `order_tables`
--
ALTER TABLE `order_tables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `txnid` (`reference_id`);

--
-- Indexes for table `order_table_tracking`
--
ALTER TABLE `order_table_tracking`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_local_store` (`local_id`,`store_id`),
  ADD KEY `idx_store_id` (`store_id`);

--
-- Indexes for table `order_tax`
--
ALTER TABLE `order_tax`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_local_store` (`local_id`,`store_id`),
  ADD KEY `idx_store_id` (`store_id`),
  ADD KEY `idx_ref_prod_status` (`reference_id`,`product_id`,`status`),
  ADD KEY `idx_orderhistory_store_status` (`orderhistory_id`,`store_id`,`status`);

--
-- Indexes for table `order_transactions`
--
ALTER TABLE `order_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_local_store` (`local_id`,`store_id`),
  ADD KEY `idx_store_id` (`store_id`),
  ADD KEY `idx_created_status_store` (`created`,`status`,`store_id`),
  ADD KEY `idx_ref_store_status` (`reference_id`,`store_id`,`status`);

--
-- Indexes for table `order_waiters`
--
ALTER TABLE `order_waiters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `number` (`pin_number`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `uq_local_store` (`local_id`,`location_id`);

--
-- Indexes for table `paymentpartner_api_logs`
--
ALTER TABLE `paymentpartner_api_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_orderid` (`orderid`(191)),
  ADD KEY `idx_partner` (`partner_name`),
  ADD KEY `idx_pos_ref` (`pos_reference_id`(191));

--
-- Indexes for table `payment_partners`
--
ALTER TABLE `payment_partners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `phyloai_settings`
--
ALTER TABLE `phyloai_settings`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `pos_auditstock`
--
ALTER TABLE `pos_auditstock`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pos_auditstock_items`
--
ALTER TABLE `pos_auditstock_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pos_offers`
--
ALTER TABLE `pos_offers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pos_printers`
--
ALTER TABLE `pos_printers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pos_tempauditstock`
--
ALTER TABLE `pos_tempauditstock`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pos_tempauditstock_items`
--
ALTER TABLE `pos_tempauditstock_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reelo_discounts`
--
ALTER TABLE `reelo_discounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ref` (`reference_id`),
  ADD KEY `idx_store` (`store_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `service_purchase_orders`
--
ALTER TABLE `service_purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_spo_id` (`spo_id`),
  ADD KEY `idx_vendor` (`vendor_id`),
  ADD KEY `idx_location` (`location_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `service_purchase_order_items`
--
ALTER TABLE `service_purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_spo_id` (`spo_id`);

--
-- Indexes for table `stock_ledger`
--
ALTER TABLE `stock_ledger`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_local_store` (`local_id`,`store_id`),
  ADD KEY `idx_ledger_sync_lookup` (`sku_id`,`store_id`,`item_type`,`type`,`created`),
  ADD KEY `idx_ledger_sync_flag` (`sync`,`local_sync`);

--
-- Indexes for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock_transfer_items`
--
ALTER TABLE `stock_transfer_items`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `store_connectors`
--
ALTER TABLE `store_connectors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_store_key` (`store_key`),
  ADD UNIQUE KEY `uq_store_id` (`store_id`),
  ADD KEY `idx_is_enabled` (`is_enabled`);

--
-- Indexes for table `sync_errors`
--
ALTER TABLE `sync_errors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ref` (`reference_id`),
  ADD KEY `idx_unresolved` (`resolved`,`attempts`),
  ADD KEY `idx_direction` (`direction`,`resolved`),
  ADD KEY `idx_store_id` (`store_id`),
  ADD KEY `idx_reference_id` (`reference_id`),
  ADD KEY `idx_direction_resolved` (`direction`,`resolved`);

--
-- Indexes for table `table_categories`
--
ALTER TABLE `table_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tax_charges`
--
ALTER TABLE `tax_charges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_local_store` (`local_id`,`store_id`),
  ADD KEY `idx_store_id` (`store_id`);

--
-- Indexes for table `up_group_option_link`
--
ALTER TABLE `up_group_option_link`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_group_option` (`store_id`,`up_group_id`,`up_option_id`),
  ADD KEY `idx_store_group` (`store_id`,`up_group_id`),
  ADD KEY `idx_store_option` (`store_id`,`up_option_id`);

--
-- Indexes for table `up_item_group_link`
--
ALTER TABLE `up_item_group_link`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_item_group` (`store_id`,`up_item_id`,`up_group_id`),
  ADD KEY `idx_store_item` (`store_id`,`up_item_id`),
  ADD KEY `idx_store_group` (`store_id`,`up_group_id`);

--
-- Indexes for table `up_item_map`
--
ALTER TABLE `up_item_map`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_store_up_item` (`store_id`,`up_item_id`),
  ADD KEY `idx_store_id` (`store_id`),
  ADD KEY `idx_tgif_product_id` (`tgif_product_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `up_option_group_map`
--
ALTER TABLE `up_option_group_map`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_store_up_group` (`store_id`,`up_group_id`),
  ADD KEY `idx_store_id` (`store_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `up_option_map`
--
ALTER TABLE `up_option_map`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_store_up_option` (`store_id`,`up_option_id`),
  ADD KEY `idx_store_id` (`store_id`),
  ADD KEY `idx_tgif_modifier` (`tgif_modifier_product_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `up_orders`
--
ALTER TABLE `up_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_up_order_id` (`up_order_id`),
  ADD KEY `idx_store_id` (`store_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_reference` (`reference_id`),
  ADD KEY `idx_created` (`created`);

--
-- Indexes for table `up_order_events`
--
ALTER TABLE `up_order_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_store_status` (`store_id`,`status`),
  ADD KEY `idx_up_order_id` (`up_order_id`),
  ADD KEY `idx_event_type` (`event_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created`);

--
-- Indexes for table `up_tax_map`
--
ALTER TABLE `up_tax_map`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_name_rate_store` (`up_tax_name`,`up_tax_rate`,`store_id`);

--
-- Indexes for table `urbanpiper_limits`
--
ALTER TABLE `urbanpiper_limits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_code` (`event_code`);

--
-- Indexes for table `urbanpiper_response`
--
ALTER TABLE `urbanpiper_response`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event` (`event`),
  ADD KEY `idx_created` (`created`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_locations`
--
ALTER TABLE `user_locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_modules`
--
ALTER TABLE `user_modules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_tracks`
--
ALTER TABLE `user_tracks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_local_store` (`local_id`,`location_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account_imprest`
--
ALTER TABLE `account_imprest`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cards`
--
ALTER TABLE `cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cardtracking`
--
ALTER TABLE `cardtracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `catalog_category`
--
ALTER TABLE `catalog_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1037;

--
-- AUTO_INCREMENT for table `catalog_category_branches`
--
ALTER TABLE `catalog_category_branches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=169;

--
-- AUTO_INCREMENT for table `catalog_modifier`
--
ALTER TABLE `catalog_modifier`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `catalog_modifier_group`
--
ALTER TABLE `catalog_modifier_group`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `catalog_package`
--
ALTER TABLE `catalog_package`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `catalog_product`
--
ALTER TABLE `catalog_product`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1143;

--
-- AUTO_INCREMENT for table `catalog_product_attribute`
--
ALTER TABLE `catalog_product_attribute`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `catalog_product_branches`
--
ALTER TABLE `catalog_product_branches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3738;

--
-- AUTO_INCREMENT for table `catalog_product_tax`
--
ALTER TABLE `catalog_product_tax`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2940;

--
-- AUTO_INCREMENT for table `catalog_product_tax_outlet`
--
ALTER TABLE `catalog_product_tax_outlet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=722;

--
-- AUTO_INCREMENT for table `catalog_subcategory`
--
ALTER TABLE `catalog_subcategory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1000016;

--
-- AUTO_INCREMENT for table `catalog_tax`
--
ALTER TABLE `catalog_tax`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `catalog_units`
--
ALTER TABLE `catalog_units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT for table `catalog_unit_mapping`
--
ALTER TABLE `catalog_unit_mapping`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=203;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eazydiner_tokens`
--
ALTER TABLE `eazydiner_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `editing_managements`
--
ALTER TABLE `editing_managements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_category`
--
ALTER TABLE `inventory_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `inventory_damage`
--
ALTER TABLE `inventory_damage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_damage_items`
--
ALTER TABLE `inventory_damage_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_indent`
--
ALTER TABLE `inventory_indent`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1950;

--
-- AUTO_INCREMENT for table `inventory_indent_items`
--
ALTER TABLE `inventory_indent_items`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9820;

--
-- AUTO_INCREMENT for table `inventory_internal_indent`
--
ALTER TABLE `inventory_internal_indent`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1174;

--
-- AUTO_INCREMENT for table `inventory_internal_indent_items`
--
ALTER TABLE `inventory_internal_indent_items`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8002;

--
-- AUTO_INCREMENT for table `inventory_linking`
--
ALTER TABLE `inventory_linking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7397;

--
-- AUTO_INCREMENT for table `inventory_purchase_order`
--
ALTER TABLE `inventory_purchase_order`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2469;

--
-- AUTO_INCREMENT for table `inventory_purchase_order_items`
--
ALTER TABLE `inventory_purchase_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14147;

--
-- AUTO_INCREMENT for table `inventory_return`
--
ALTER TABLE `inventory_return`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `inventory_return_items`
--
ALTER TABLE `inventory_return_items`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `inventory_semifinished`
--
ALTER TABLE `inventory_semifinished`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=480;

--
-- AUTO_INCREMENT for table `inventory_semifinished_tax`
--
ALTER TABLE `inventory_semifinished_tax`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `inventory_skus`
--
ALTER TABLE `inventory_skus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1737;

--
-- AUTO_INCREMENT for table `inventory_sku_excise`
--
ALTER TABLE `inventory_sku_excise`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1286;

--
-- AUTO_INCREMENT for table `inventory_sku_tax`
--
ALTER TABLE `inventory_sku_tax`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3507;

--
-- AUTO_INCREMENT for table `inventory_stockin`
--
ALTER TABLE `inventory_stockin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2002;

--
-- AUTO_INCREMENT for table `inventory_stockin_items`
--
ALTER TABLE `inventory_stockin_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9950;

--
-- AUTO_INCREMENT for table `inventory_subcategory`
--
ALTER TABLE `inventory_subcategory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `inventory_tax`
--
ALTER TABLE `inventory_tax`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `inventory_units`
--
ALTER TABLE `inventory_units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_vendors`
--
ALTER TABLE `inventory_vendors`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=142;

--
-- AUTO_INCREMENT for table `inventory_vendor_service_areas`
--
ALTER TABLE `inventory_vendor_service_areas`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=558;

--
-- AUTO_INCREMENT for table `inventory_vendor_sku_served`
--
ALTER TABLE `inventory_vendor_sku_served`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33028;

--
-- AUTO_INCREMENT for table `kds_tracks`
--
ALTER TABLE `kds_tracks`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8753;

--
-- AUTO_INCREMENT for table `kds_users`
--
ALTER TABLE `kds_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `kds_users_station_mapping`
--
ALTER TABLE `kds_users_station_mapping`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `online_bookings`
--
ALTER TABLE `online_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `online_order`
--
ALTER TABLE `online_order`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1000002193;

--
-- AUTO_INCREMENT for table `order_customers`
--
ALTER TABLE `order_customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1000009418;

--
-- AUTO_INCREMENT for table `order_modifier`
--
ALTER TABLE `order_modifier`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1000007226;

--
-- AUTO_INCREMENT for table `order_orderhistories`
--
ALTER TABLE `order_orderhistories`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1000374188;

--
-- AUTO_INCREMENT for table `order_orderhistories_json`
--
ALTER TABLE `order_orderhistories_json`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1000087535;

--
-- AUTO_INCREMENT for table `order_payments`
--
ALTER TABLE `order_payments`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1000041762;

--
-- AUTO_INCREMENT for table `order_split_orderhistories`
--
ALTER TABLE `order_split_orderhistories`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1000002348;

--
-- AUTO_INCREMENT for table `order_split_payments`
--
ALTER TABLE `order_split_payments`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1000000340;

--
-- AUTO_INCREMENT for table `order_split_transactions`
--
ALTER TABLE `order_split_transactions`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1000001043;

--
-- AUTO_INCREMENT for table `order_tables`
--
ALTER TABLE `order_tables`
  MODIFY `id` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=301;

--
-- AUTO_INCREMENT for table `order_table_tracking`
--
ALTER TABLE `order_table_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1000000144;

--
-- AUTO_INCREMENT for table `order_tax`
--
ALTER TABLE `order_tax`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2000876522;

--
-- AUTO_INCREMENT for table `order_transactions`
--
ALTER TABLE `order_transactions`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1000086205;

--
-- AUTO_INCREMENT for table `order_waiters`
--
ALTER TABLE `order_waiters`
  MODIFY `id` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2000000097;

--
-- AUTO_INCREMENT for table `paymentpartner_api_logs`
--
ALTER TABLE `paymentpartner_api_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `payment_partners`
--
ALTER TABLE `payment_partners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `pos_auditstock`
--
ALTER TABLE `pos_auditstock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pos_auditstock_items`
--
ALTER TABLE `pos_auditstock_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pos_offers`
--
ALTER TABLE `pos_offers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9743;

--
-- AUTO_INCREMENT for table `pos_printers`
--
ALTER TABLE `pos_printers`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `pos_tempauditstock`
--
ALTER TABLE `pos_tempauditstock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pos_tempauditstock_items`
--
ALTER TABLE `pos_tempauditstock_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reelo_discounts`
--
ALTER TABLE `reelo_discounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `service_purchase_orders`
--
ALTER TABLE `service_purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_purchase_order_items`
--
ALTER TABLE `service_purchase_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_ledger`
--
ALTER TABLE `stock_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2381625;

--
-- AUTO_INCREMENT for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `stock_transfer_items`
--
ALTER TABLE `stock_transfer_items`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `store_connectors`
--
ALTER TABLE `store_connectors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sync_errors`
--
ALTER TABLE `sync_errors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1000012113;

--
-- AUTO_INCREMENT for table `table_categories`
--
ALTER TABLE `table_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tax_charges`
--
ALTER TABLE `tax_charges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2737;

--
-- AUTO_INCREMENT for table `up_group_option_link`
--
ALTER TABLE `up_group_option_link`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `up_item_group_link`
--
ALTER TABLE `up_item_group_link`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `up_item_map`
--
ALTER TABLE `up_item_map`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `up_option_group_map`
--
ALTER TABLE `up_option_group_map`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `up_option_map`
--
ALTER TABLE `up_option_map`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `up_orders`
--
ALTER TABLE `up_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1994;

--
-- AUTO_INCREMENT for table `up_order_events`
--
ALTER TABLE `up_order_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11558;

--
-- AUTO_INCREMENT for table `up_tax_map`
--
ALTER TABLE `up_tax_map`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `urbanpiper_limits`
--
ALTER TABLE `urbanpiper_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `urbanpiper_response`
--
ALTER TABLE `urbanpiper_response`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=201;

--
-- AUTO_INCREMENT for table `user_locations`
--
ALTER TABLE `user_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=430;

--
-- AUTO_INCREMENT for table `user_modules`
--
ALTER TABLE `user_modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1869;

--
-- AUTO_INCREMENT for table `user_tracks`
--
ALTER TABLE `user_tracks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1000089662;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
