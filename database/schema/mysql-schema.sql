/*M!999999\- enable the sandbox mode */ 
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `abilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `abilities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `entity_id` bigint(20) unsigned DEFAULT NULL,
  `entity_type` varchar(255) DEFAULT NULL,
  `only_owned` tinyint(1) NOT NULL DEFAULT 0,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `scope` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `abilities_scope_index` (`scope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `opening_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `currency_code` varchar(3) NOT NULL,
  `notes` text DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `plaid_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accounts_name_index` (`name`),
  KEY `accounts_company_id_index` (`company_id`),
  KEY `accounts_currency_code_index` (`currency_code`),
  KEY `accounts_type_index` (`type`),
  KEY `accounts_company_id_type_index` (`company_id`,`type`),
  KEY `accounts_archived_at_index` (`archived_at`),
  CONSTRAINT `accounts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_depreciations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_depreciations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `asset_id` bigint(20) unsigned NOT NULL,
  `purchase_cost` decimal(12,2) NOT NULL,
  `residual_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `useful_life_years` int(11) NOT NULL,
  `method` enum('straight_line','declining_balance','sum_of_years') NOT NULL DEFAULT 'straight_line',
  `annual_depreciation` decimal(12,2) NOT NULL,
  `accumulated_depreciation` decimal(12,2) NOT NULL DEFAULT 0.00,
  `current_book_value` decimal(12,2) NOT NULL,
  `depreciation_start_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asset_depreciations_asset_id_foreign` (`asset_id`),
  KEY `asset_depreciations_company_id_asset_id_index` (`company_id`,`asset_id`),
  CONSTRAINT `asset_depreciations_asset_id_foreign` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asset_depreciations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_maintenance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_maintenance` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `asset_id` bigint(20) unsigned NOT NULL,
  `maintenance_type` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `scheduled_date` datetime NOT NULL,
  `completed_date` datetime DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `technician_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asset_maintenance_asset_id_foreign` (`asset_id`),
  KEY `asset_maintenance_technician_id_foreign` (`technician_id`),
  KEY `asset_maintenance_company_id_asset_id_index` (`company_id`,`asset_id`),
  KEY `asset_maintenance_company_id_scheduled_date_index` (`company_id`,`scheduled_date`),
  KEY `asset_maintenance_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `asset_maintenance_asset_id_foreign` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asset_maintenance_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asset_maintenance_technician_id_foreign` FOREIGN KEY (`technician_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_warranties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_warranties` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `asset_id` bigint(20) unsigned NOT NULL,
  `warranty_provider` varchar(255) NOT NULL,
  `warranty_number` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `type` enum('manufacturer','extended','service_contract') NOT NULL DEFAULT 'manufacturer',
  `coverage_details` text DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `status` enum('active','expired','claimed') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asset_warranties_asset_id_foreign` (`asset_id`),
  KEY `asset_warranties_company_id_asset_id_index` (`company_id`,`asset_id`),
  KEY `asset_warranties_company_id_end_date_index` (`company_id`,`end_date`),
  KEY `asset_warranties_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `asset_warranties_asset_id_foreign` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asset_warranties_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `asset_tag` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `make` varchar(255) NOT NULL,
  `model` varchar(255) DEFAULT NULL,
  `serial` varchar(255) DEFAULT NULL,
  `os` varchar(255) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `nat_ip` varchar(255) DEFAULT NULL,
  `mac` varchar(17) DEFAULT NULL,
  `uri` varchar(500) DEFAULT NULL,
  `uri_2` varchar(500) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `warranty_expire` date DEFAULT NULL,
  `install_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `accessed_at` timestamp NULL DEFAULT NULL,
  `vendor_id` bigint(20) unsigned DEFAULT NULL,
  `location_id` bigint(20) unsigned DEFAULT NULL,
  `contact_id` bigint(20) unsigned DEFAULT NULL,
  `network_id` bigint(20) unsigned DEFAULT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `rmm_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assets_name_index` (`name`),
  KEY `assets_type_index` (`type`),
  KEY `assets_client_id_index` (`client_id`),
  KEY `assets_company_id_index` (`company_id`),
  KEY `assets_status_index` (`status`),
  KEY `assets_ip_index` (`ip`),
  KEY `assets_mac_index` (`mac`),
  KEY `assets_serial_index` (`serial`),
  KEY `assets_client_id_type_index` (`client_id`,`type`),
  KEY `assets_client_id_status_index` (`client_id`,`status`),
  KEY `assets_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `assets_warranty_expire_index` (`warranty_expire`),
  KEY `assets_archived_at_index` (`archived_at`),
  KEY `assets_vendor_id_foreign` (`vendor_id`),
  KEY `assets_location_id_foreign` (`location_id`),
  KEY `assets_contact_id_foreign` (`contact_id`),
  KEY `assets_network_id_foreign` (`network_id`),
  CONSTRAINT `assets_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assets_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assets_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `assets_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `assets_network_id_foreign` FOREIGN KEY (`network_id`) REFERENCES `networks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `assets_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `event_type` varchar(50) NOT NULL,
  `model_type` varchar(255) DEFAULT NULL,
  `model_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `request_method` varchar(10) DEFAULT NULL,
  `request_url` varchar(255) DEFAULT NULL,
  `request_headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`request_headers`)),
  `request_body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`request_body`)),
  `response_status` int(11) DEFAULT NULL,
  `execution_time` decimal(10,3) DEFAULT NULL,
  `severity` varchar(20) NOT NULL DEFAULT 'info',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_index` (`user_id`),
  KEY `audit_logs_company_id_index` (`company_id`),
  KEY `audit_logs_event_type_index` (`event_type`),
  KEY `audit_logs_model_type_index` (`model_type`),
  KEY `audit_logs_model_id_index` (`model_id`),
  KEY `audit_logs_created_at_index` (`created_at`),
  KEY `audit_logs_ip_address_index` (`ip_address`),
  KEY `audit_logs_severity_index` (`severity`),
  KEY `audit_logs_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `audit_logs_company_id_event_type_index` (`company_id`,`event_type`),
  CONSTRAINT `audit_logs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bouncer_abilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bouncer_abilities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `entity_id` bigint(20) unsigned DEFAULT NULL,
  `entity_type` varchar(255) DEFAULT NULL,
  `only_owned` tinyint(1) NOT NULL DEFAULT 0,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `scope` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bouncer_abilities_scope_index` (`scope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bouncer_assigned_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bouncer_assigned_roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) unsigned NOT NULL,
  `entity_id` bigint(20) unsigned NOT NULL,
  `entity_type` varchar(255) NOT NULL,
  `restricted_to_id` bigint(20) unsigned DEFAULT NULL,
  `restricted_to_type` varchar(255) DEFAULT NULL,
  `scope` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assigned_roles_entity_index` (`entity_id`,`entity_type`,`scope`),
  KEY `bouncer_assigned_roles_role_id_index` (`role_id`),
  KEY `bouncer_assigned_roles_scope_index` (`scope`),
  CONSTRAINT `bouncer_assigned_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `bouncer_roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bouncer_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bouncer_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ability_id` bigint(20) unsigned NOT NULL,
  `entity_id` bigint(20) unsigned DEFAULT NULL,
  `entity_type` varchar(255) DEFAULT NULL,
  `forbidden` tinyint(1) NOT NULL DEFAULT 0,
  `scope` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `permissions_entity_index` (`entity_id`,`entity_type`,`scope`),
  KEY `bouncer_permissions_ability_id_index` (`ability_id`),
  KEY `bouncer_permissions_scope_index` (`scope`),
  CONSTRAINT `bouncer_permissions_ability_id_foreign` FOREIGN KEY (`ability_id`) REFERENCES `bouncer_abilities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bouncer_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bouncer_roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `scope` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`,`scope`),
  KEY `bouncer_roles_scope_index` (`scope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `color` varchar(255) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `categories_name_index` (`name`),
  KEY `categories_type_index` (`type`),
  KEY `categories_parent_id_index` (`parent_id`),
  KEY `categories_company_id_index` (`company_id`),
  KEY `categories_type_parent_id_index` (`type`,`parent_id`),
  KEY `categories_company_id_type_index` (`company_id`,`type`),
  KEY `categories_archived_at_index` (`archived_at`),
  KEY `categories_type_idx` (`type`),
  KEY `categories_name_idx` (`name`),
  CONSTRAINT `categories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_addresses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `type` enum('billing','shipping','service','other') NOT NULL DEFAULT 'billing',
  `address` varchar(255) NOT NULL,
  `address2` varchar(255) DEFAULT NULL,
  `city` varchar(255) NOT NULL,
  `state` varchar(255) NOT NULL,
  `zip` varchar(255) NOT NULL,
  `country` varchar(2) NOT NULL DEFAULT 'US',
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_addresses_client_id_foreign` (`client_id`),
  KEY `client_addresses_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `client_addresses_company_id_type_index` (`company_id`,`type`),
  CONSTRAINT `client_addresses_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `client_addresses_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_calendar_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_calendar_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `all_day` tinyint(1) NOT NULL DEFAULT 0,
  `type` enum('maintenance','meeting','project','other') NOT NULL DEFAULT 'other',
  `attendees` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attendees`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_calendar_events_client_id_foreign` (`client_id`),
  KEY `client_calendar_events_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `client_calendar_events_company_id_start_time_index` (`company_id`,`start_time`),
  KEY `client_calendar_events_company_id_type_index` (`company_id`,`type`),
  CONSTRAINT `client_calendar_events_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `client_calendar_events_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_certificates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_certificates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `domain` varchar(255) NOT NULL,
  `issuer` varchar(255) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date NOT NULL,
  `type` enum('ssl','wildcard','ev','dv','ov') NOT NULL DEFAULT 'ssl',
  `status` enum('active','expired','pending','revoked') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_certificates_client_id_foreign` (`client_id`),
  KEY `client_certificates_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `client_certificates_company_id_expiry_date_index` (`company_id`,`expiry_date`),
  KEY `client_certificates_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `client_certificates_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `client_certificates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_contacts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `mobile` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_billing` tinyint(1) NOT NULL DEFAULT 0,
  `is_technical` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_contacts_client_id_foreign` (`client_id`),
  KEY `client_contacts_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `client_contacts_company_id_email_index` (`company_id`,`email`),
  KEY `client_contacts_company_id_is_primary_index` (`company_id`,`is_primary`),
  CONSTRAINT `client_contacts_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `client_contacts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_credentials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_credentials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` text NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `additional_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_fields`)),
  `is_shared` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_credentials_client_id_foreign` (`client_id`),
  KEY `client_credentials_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `client_credentials_company_id_service_name_index` (`company_id`,`service_name`),
  CONSTRAINT `client_credentials_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `client_credentials_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `is_confidential` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_documents_client_id_foreign` (`client_id`),
  KEY `client_documents_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `client_documents_company_id_type_index` (`company_id`,`type`),
  CONSTRAINT `client_documents_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `client_documents_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_domains`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_domains` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `domain` varchar(255) NOT NULL,
  `registrar` varchar(255) DEFAULT NULL,
  `registration_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `auto_renew` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','expired','pending','suspended') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `dns_records` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dns_records`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_domains_client_id_foreign` (`client_id`),
  KEY `client_domains_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `client_domains_company_id_expiry_date_index` (`company_id`,`expiry_date`),
  KEY `client_domains_company_id_domain_index` (`company_id`,`domain`),
  CONSTRAINT `client_domains_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `client_domains_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_files` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `mime_type` varchar(255) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_files_client_id_foreign` (`client_id`),
  KEY `client_files_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `client_files_company_id_category_index` (`company_id`,`category`),
  CONSTRAINT `client_files_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `client_files_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_it_documentation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_it_documentation` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned DEFAULT NULL,
  `authored_by` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `it_category` varchar(255) DEFAULT NULL,
  `system_references` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`system_references`)),
  `ip_addresses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ip_addresses`)),
  `software_versions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`software_versions`)),
  `compliance_requirements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`compliance_requirements`)),
  `review_schedule` varchar(255) DEFAULT NULL,
  `last_reviewed_at` timestamp NULL DEFAULT NULL,
  `next_review_at` timestamp NULL DEFAULT NULL,
  `access_level` varchar(255) DEFAULT NULL,
  `procedure_steps` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`procedure_steps`)),
  `network_diagram` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`network_diagram`)),
  `related_entities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`related_entities`)),
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `version` int(11) NOT NULL DEFAULT 1,
  `parent_document_id` bigint(20) unsigned DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `original_filename` varchar(255) DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `file_hash` varchar(255) DEFAULT NULL,
  `last_accessed_at` timestamp NULL DEFAULT NULL,
  `access_count` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `enabled_tabs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`enabled_tabs`)),
  `tab_configuration` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tab_configuration`)),
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `effective_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `template_used` varchar(255) DEFAULT NULL,
  `ports` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ports`)),
  `api_endpoints` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`api_endpoints`)),
  `ssl_certificates` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ssl_certificates`)),
  `dns_entries` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dns_entries`)),
  `firewall_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`firewall_rules`)),
  `vpn_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`vpn_settings`)),
  `hardware_references` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`hardware_references`)),
  `environment_variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`environment_variables`)),
  `procedure_diagram` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`procedure_diagram`)),
  `rollback_procedures` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`rollback_procedures`)),
  `prerequisites` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`prerequisites`)),
  `data_classification` varchar(255) DEFAULT NULL,
  `encryption_required` tinyint(1) NOT NULL DEFAULT 0,
  `audit_requirements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`audit_requirements`)),
  `security_controls` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`security_controls`)),
  `external_resources` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`external_resources`)),
  `vendor_contacts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`vendor_contacts`)),
  `support_contracts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`support_contracts`)),
  `test_cases` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`test_cases`)),
  `validation_checklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`validation_checklist`)),
  `performance_benchmarks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`performance_benchmarks`)),
  `health_checks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`health_checks`)),
  `automation_scripts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`automation_scripts`)),
  `integrations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`integrations`)),
  `webhooks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`webhooks`)),
  `scheduled_tasks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`scheduled_tasks`)),
  `uptime_requirement` decimal(5,2) DEFAULT NULL,
  `rto` int(11) DEFAULT NULL,
  `rpo` int(11) DEFAULT NULL,
  `performance_metrics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`performance_metrics`)),
  `alert_thresholds` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`alert_thresholds`)),
  `escalation_paths` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`escalation_paths`)),
  `change_summary` text DEFAULT NULL,
  `change_log` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`change_log`)),
  `requires_technical_review` tinyint(1) NOT NULL DEFAULT 0,
  `requires_management_approval` tinyint(1) NOT NULL DEFAULT 0,
  `approval_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`approval_history`)),
  `review_comments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`review_comments`)),
  `custom_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_fields`)),
  `documentation_completeness` int(11) NOT NULL DEFAULT 0,
  `is_template` tinyint(1) NOT NULL DEFAULT 0,
  `template_category` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_it_documentation_client_id_foreign` (`client_id`),
  KEY `client_it_documentation_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `client_it_documentation_company_id_it_category_index` (`company_id`,`it_category`),
  KEY `client_it_documentation_company_id_status_index` (`company_id`,`status`),
  KEY `client_it_documentation_company_id_is_active_index` (`company_id`,`is_active`),
  KEY `client_it_documentation_company_id_is_template_index` (`company_id`,`is_template`),
  KEY `client_it_documentation_authored_by_index` (`authored_by`),
  KEY `client_it_documentation_parent_document_id_index` (`parent_document_id`),
  KEY `client_it_documentation_last_reviewed_at_index` (`last_reviewed_at`),
  KEY `client_it_documentation_next_review_at_index` (`next_review_at`),
  CONSTRAINT `client_it_documentation_authored_by_foreign` FOREIGN KEY (`authored_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `client_it_documentation_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `client_it_documentation_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `client_it_documentation_parent_document_id_foreign` FOREIGN KEY (`parent_document_id`) REFERENCES `client_it_documentation` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_licenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_licenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `software_name` varchar(255) NOT NULL,
  `license_key` varchar(255) NOT NULL,
  `version` varchar(255) DEFAULT NULL,
  `seats` int(11) NOT NULL DEFAULT 1,
  `purchase_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `status` enum('active','expired','cancelled') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_licenses_client_id_foreign` (`client_id`),
  KEY `client_licenses_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `client_licenses_company_id_expiry_date_index` (`company_id`,`expiry_date`),
  KEY `client_licenses_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `client_licenses_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `client_licenses_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_networks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_networks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `network_address` varchar(255) NOT NULL,
  `subnet_mask` varchar(255) NOT NULL,
  `gateway` varchar(255) DEFAULT NULL,
  `dns_primary` varchar(255) DEFAULT NULL,
  `dns_secondary` varchar(255) DEFAULT NULL,
  `vlan_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `type` enum('lan','wan','dmz','guest','management') NOT NULL DEFAULT 'lan',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_networks_client_id_foreign` (`client_id`),
  KEY `client_networks_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `client_networks_company_id_type_index` (`company_id`,`type`),
  CONSTRAINT `client_networks_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `client_networks_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_quotes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_quotes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `quote_number` varchar(255) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `quote_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('draft','sent','accepted','rejected','expired') NOT NULL DEFAULT 'draft',
  `line_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`line_items`)),
  `notes` text DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_quotes_client_id_foreign` (`client_id`),
  KEY `client_quotes_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `client_quotes_company_id_quote_date_index` (`company_id`,`quote_date`),
  KEY `client_quotes_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `client_quotes_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `client_quotes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_racks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_racks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `units` int(11) NOT NULL DEFAULT 42,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive','maintenance') NOT NULL DEFAULT 'active',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_racks_client_id_foreign` (`client_id`),
  KEY `client_racks_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `client_racks_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `client_racks_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `client_racks_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_recurring_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_recurring_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `invoice_number` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `frequency` enum('monthly','quarterly','semi-annually','annually') NOT NULL DEFAULT 'monthly',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `next_invoice_date` date NOT NULL,
  `status` enum('active','paused','cancelled') NOT NULL DEFAULT 'active',
  `line_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`line_items`)),
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_recurring_invoices_client_id_foreign` (`client_id`),
  KEY `client_recurring_invoices_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `client_recurring_invoices_company_id_next_invoice_date_index` (`company_id`,`next_invoice_date`),
  KEY `client_recurring_invoices_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `client_recurring_invoices_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `client_recurring_invoices_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_services` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('managed','monitoring','backup','security','support','other') NOT NULL DEFAULT 'other',
  `monthly_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `configuration` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`configuration`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_services_client_id_foreign` (`client_id`),
  KEY `client_services_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `client_services_company_id_type_index` (`company_id`,`type`),
  KEY `client_services_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `client_services_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `client_services_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `tag_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_tags_client_id_tag_id_unique` (`client_id`,`tag_id`),
  KEY `client_tags_tag_id_foreign` (`tag_id`),
  KEY `client_tags_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `client_tags_company_id_tag_id_index` (`company_id`,`tag_id`),
  CONSTRAINT `client_tags_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `client_tags_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `client_tags_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_trips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_trips` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `mileage` decimal(8,2) DEFAULT NULL,
  `expense_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('planned','in_progress','completed','cancelled') NOT NULL DEFAULT 'planned',
  `expenses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`expenses`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_trips_client_id_foreign` (`client_id`),
  KEY `client_trips_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `client_trips_company_id_start_time_index` (`company_id`,`start_time`),
  KEY `client_trips_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `client_trips_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `client_trips_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_vendors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_vendors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `relationship` enum('vendor','supplier','partner','contractor') NOT NULL DEFAULT 'vendor',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_vendors_client_id_foreign` (`client_id`),
  KEY `client_vendors_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `client_vendors_company_id_relationship_index` (`company_id`,`relationship`),
  CONSTRAINT `client_vendors_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `client_vendors_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `clients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `lead` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `zip_code` varchar(255) DEFAULT NULL,
  `country` varchar(255) NOT NULL DEFAULT 'US',
  `website` varchar(255) DEFAULT NULL,
  `referral` varchar(255) DEFAULT NULL,
  `rate` decimal(15,2) DEFAULT NULL,
  `currency_code` varchar(3) NOT NULL DEFAULT 'USD',
  `net_terms` int(11) NOT NULL DEFAULT 30,
  `tax_id_number` varchar(255) DEFAULT NULL,
  `rmm_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `sla_id` bigint(20) unsigned DEFAULT NULL,
  `hourly_rate` decimal(8,2) DEFAULT NULL,
  `billing_contact` varchar(255) DEFAULT NULL,
  `technical_contact` varchar(255) DEFAULT NULL,
  `custom_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_fields`)),
  `contract_start_date` timestamp NULL DEFAULT NULL,
  `contract_end_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `accessed_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clients_email_unique` (`email`),
  KEY `clients_status_created_at_index` (`status`,`created_at`),
  KEY `clients_company_name_index` (`company_name`),
  KEY `clients_company_id_index` (`company_id`),
  KEY `clients_company_id_status_index` (`company_id`,`status`),
  KEY `clients_lead_index` (`lead`),
  KEY `clients_type_index` (`type`),
  KEY `clients_accessed_at_index` (`accessed_at`),
  KEY `clients_company_id_lead_index` (`company_id`,`lead`),
  KEY `clients_company_id_archived_at_index` (`company_id`,`archived_at`),
  KEY `clients_sla_id_foreign` (`sla_id`),
  KEY `clients_company_id_sla_id_index` (`company_id`,`sla_id`),
  KEY `clients_company_name_idx` (`company_id`,`name`),
  KEY `clients_company_status_idx` (`company_id`,`status`),
  FULLTEXT KEY `clients_name_fulltext` (`name`,`company_name`),
  CONSTRAINT `clients_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clients_sla_id_foreign` FOREIGN KEY (`sla_id`) REFERENCES `slas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `zip` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `locale` varchar(255) DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `companies_name_index` (`name`),
  KEY `companies_email_index` (`email`),
  KEY `companies_currency_index` (`currency`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `company_customizations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_customizations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `customizations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`customizations`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_customizations_company_id_unique` (`company_id`),
  KEY `company_customizations_company_id_index` (`company_id`),
  CONSTRAINT `company_customizations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contacts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `extension` varchar(255) DEFAULT NULL,
  `mobile` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `pin` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `auth_method` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `token_expire` timestamp NULL DEFAULT NULL,
  `primary` tinyint(1) NOT NULL DEFAULT 0,
  `important` tinyint(1) NOT NULL DEFAULT 0,
  `billing` tinyint(1) NOT NULL DEFAULT 0,
  `technical` tinyint(1) NOT NULL DEFAULT 0,
  `has_portal_access` tinyint(1) NOT NULL DEFAULT 0,
  `portal_permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`portal_permissions`)),
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `login_count` int(11) NOT NULL DEFAULT 0,
  `failed_login_count` int(11) NOT NULL DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `password_changed_at` timestamp NULL DEFAULT NULL,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `session_timeout_minutes` int(11) NOT NULL DEFAULT 30,
  `allowed_ip_addresses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_ip_addresses`)),
  `department` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `accessed_at` timestamp NULL DEFAULT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `location_id` bigint(20) unsigned DEFAULT NULL,
  `vendor_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contacts_name_index` (`name`),
  KEY `contacts_email_index` (`email`),
  KEY `contacts_client_id_index` (`client_id`),
  KEY `contacts_company_id_index` (`company_id`),
  KEY `contacts_primary_index` (`primary`),
  KEY `contacts_important_index` (`important`),
  KEY `contacts_client_id_primary_index` (`client_id`,`primary`),
  KEY `contacts_client_id_billing_index` (`client_id`,`billing`),
  KEY `contacts_client_id_technical_index` (`client_id`,`technical`),
  KEY `contacts_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `contacts_archived_at_index` (`archived_at`),
  KEY `idx_portal_access` (`company_id`,`email`,`has_portal_access`),
  KEY `idx_client_portal` (`company_id`,`client_id`,`has_portal_access`),
  KEY `contacts_location_id_foreign` (`location_id`),
  KEY `contacts_vendor_id_foreign` (`vendor_id`),
  CONSTRAINT `contacts_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contacts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contacts_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `contacts_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contract_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contract_approvals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `contract_id` bigint(20) unsigned NOT NULL,
  `approval_type` varchar(255) NOT NULL,
  `approval_level` varchar(255) DEFAULT NULL,
  `approval_order` int(10) unsigned NOT NULL DEFAULT 1,
  `approver_id` bigint(20) unsigned NOT NULL,
  `approver_role` varchar(255) DEFAULT NULL,
  `delegated_to_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `requested_at` timestamp NOT NULL,
  `due_date` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `conditions` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `can_resubmit` tinyint(1) NOT NULL DEFAULT 1,
  `amount_limit` decimal(10,2) DEFAULT NULL,
  `amount_exceeded` tinyint(1) NOT NULL DEFAULT 0,
  `notification_sent_at` timestamp NULL DEFAULT NULL,
  `reminder_sent_at` timestamp NULL DEFAULT NULL,
  `reminder_count` int(10) unsigned NOT NULL DEFAULT 0,
  `escalated_at` timestamp NULL DEFAULT NULL,
  `escalated_to_id` bigint(20) unsigned DEFAULT NULL,
  `required_documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`required_documents`)),
  `all_documents_received` tinyint(1) NOT NULL DEFAULT 0,
  `checklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`checklist`)),
  `approval_method` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `audit_trail` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`audit_trail`)),
  `depends_on_approval_id` bigint(20) unsigned DEFAULT NULL,
  `can_approve_parallel` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contract_approvals_delegated_to_id_foreign` (`delegated_to_id`),
  KEY `contract_approvals_escalated_to_id_foreign` (`escalated_to_id`),
  KEY `contract_approvals_depends_on_approval_id_foreign` (`depends_on_approval_id`),
  KEY `contract_approvals_company_id_contract_id_index` (`company_id`,`contract_id`),
  KEY `contract_approvals_company_id_status_index` (`company_id`,`status`),
  KEY `contract_approvals_contract_id_approval_order_index` (`contract_id`,`approval_order`),
  KEY `contract_approvals_approver_id_status_index` (`approver_id`,`status`),
  KEY `contract_approvals_company_id_due_date_index` (`company_id`,`due_date`),
  KEY `contract_approvals_company_id_index` (`company_id`),
  KEY `contract_approvals_contract_id_index` (`contract_id`),
  KEY `contract_approvals_approver_id_index` (`approver_id`),
  KEY `contract_approvals_status_index` (`status`),
  CONSTRAINT `contract_approvals_approver_id_foreign` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contract_approvals_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contract_approvals_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contract_approvals_delegated_to_id_foreign` FOREIGN KEY (`delegated_to_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `contract_approvals_depends_on_approval_id_foreign` FOREIGN KEY (`depends_on_approval_id`) REFERENCES `contract_approvals` (`id`) ON DELETE SET NULL,
  CONSTRAINT `contract_approvals_escalated_to_id_foreign` FOREIGN KEY (`escalated_to_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contract_invoice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contract_invoice` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `contract_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `invoice_type` varchar(255) DEFAULT NULL,
  `invoiced_amount` decimal(10,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `milestone_id` bigint(20) unsigned DEFAULT NULL,
  `billing_period_start` date DEFAULT NULL,
  `billing_period_end` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contract_invoice_contract_id_invoice_id_unique` (`contract_id`,`invoice_id`),
  KEY `contract_invoice_milestone_id_foreign` (`milestone_id`),
  KEY `contract_invoice_company_id_contract_id_index` (`company_id`,`contract_id`),
  KEY `contract_invoice_company_id_invoice_id_index` (`company_id`,`invoice_id`),
  KEY `contract_invoice_company_id_index` (`company_id`),
  KEY `contract_invoice_contract_id_index` (`contract_id`),
  KEY `contract_invoice_invoice_id_index` (`invoice_id`),
  CONSTRAINT `contract_invoice_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contract_invoice_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contract_invoice_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contract_invoice_milestone_id_foreign` FOREIGN KEY (`milestone_id`) REFERENCES `contract_milestones` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contract_milestones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contract_milestones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `contract_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date NOT NULL,
  `completed_date` date DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_billable` tinyint(1) NOT NULL DEFAULT 1,
  `is_invoiced` tinyint(1) NOT NULL DEFAULT 0,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `deliverables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`deliverables`)),
  `requires_approval` tinyint(1) NOT NULL DEFAULT 0,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approval_notes` text DEFAULT NULL,
  `progress_percentage` int(10) unsigned NOT NULL DEFAULT 0,
  `progress_notes` text DEFAULT NULL,
  `depends_on_milestone_id` bigint(20) unsigned DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `send_reminder` tinyint(1) NOT NULL DEFAULT 1,
  `reminder_days_before` int(10) unsigned NOT NULL DEFAULT 7,
  `reminder_sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contract_milestones_approved_by_foreign` (`approved_by`),
  KEY `contract_milestones_depends_on_milestone_id_foreign` (`depends_on_milestone_id`),
  KEY `contract_milestones_company_id_contract_id_index` (`company_id`,`contract_id`),
  KEY `contract_milestones_company_id_status_index` (`company_id`,`status`),
  KEY `contract_milestones_company_id_due_date_index` (`company_id`,`due_date`),
  KEY `contract_milestones_contract_id_sort_order_index` (`contract_id`,`sort_order`),
  KEY `contract_milestones_company_id_index` (`company_id`),
  KEY `contract_milestones_contract_id_index` (`contract_id`),
  KEY `contract_milestones_status_index` (`status`),
  KEY `contract_milestones_invoice_id_index` (`invoice_id`),
  CONSTRAINT `contract_milestones_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `contract_milestones_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contract_milestones_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contract_milestones_depends_on_milestone_id_foreign` FOREIGN KEY (`depends_on_milestone_id`) REFERENCES `contract_milestones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `contract_milestones_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contract_signatures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contract_signatures` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `contract_id` bigint(20) unsigned NOT NULL,
  `signer_type` varchar(255) NOT NULL,
  `signer_role` varchar(255) DEFAULT NULL,
  `signer_name` varchar(255) NOT NULL,
  `signer_email` varchar(255) NOT NULL,
  `signer_title` varchar(255) DEFAULT NULL,
  `signer_company` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `signed_at` timestamp NULL DEFAULT NULL,
  `signature_method` varchar(255) DEFAULT NULL,
  `signature_data` text DEFAULT NULL,
  `signature_hash` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `verification_code` varchar(255) DEFAULT NULL,
  `verification_sent_at` timestamp NULL DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `document_version` varchar(255) DEFAULT NULL,
  `document_hash` varchar(255) DEFAULT NULL,
  `consent_to_electronic_signature` tinyint(1) NOT NULL DEFAULT 0,
  `consent_given_at` timestamp NULL DEFAULT NULL,
  `additional_terms_accepted` text DEFAULT NULL,
  `invitation_sent_at` timestamp NULL DEFAULT NULL,
  `last_reminder_sent_at` timestamp NULL DEFAULT NULL,
  `reminder_count` int(10) unsigned NOT NULL DEFAULT 0,
  `expires_at` timestamp NULL DEFAULT NULL,
  `decline_reason` text DEFAULT NULL,
  `declined_at` timestamp NULL DEFAULT NULL,
  `audit_trail` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`audit_trail`)),
  `signing_order` int(10) unsigned NOT NULL DEFAULT 1,
  `requires_previous_signatures` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contract_signatures_contract_id_signer_email_signer_type_unique` (`contract_id`,`signer_email`,`signer_type`),
  KEY `contract_signatures_company_id_contract_id_index` (`company_id`,`contract_id`),
  KEY `contract_signatures_contract_id_signer_type_index` (`contract_id`,`signer_type`),
  KEY `contract_signatures_contract_id_status_index` (`contract_id`,`status`),
  KEY `contract_signatures_company_id_status_index` (`company_id`,`status`),
  KEY `contract_signatures_contract_id_signing_order_index` (`contract_id`,`signing_order`),
  KEY `contract_signatures_company_id_index` (`company_id`),
  KEY `contract_signatures_contract_id_index` (`contract_id`),
  KEY `contract_signatures_status_index` (`status`),
  CONSTRAINT `contract_signatures_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contract_signatures_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contracts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `contract_number` varchar(255) NOT NULL,
  `contract_type` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `client_id` bigint(20) unsigned NOT NULL,
  `contract_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `currency_code` varchar(3) NOT NULL DEFAULT 'USD',
  `payment_terms` varchar(255) DEFAULT NULL,
  `discount_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `term_months` int(10) unsigned DEFAULT NULL,
  `signed_date` date DEFAULT NULL,
  `terms_and_conditions` text DEFAULT NULL,
  `scope_of_work` text DEFAULT NULL,
  `deliverables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`deliverables`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `auto_renew` tinyint(1) NOT NULL DEFAULT 0,
  `renewal_notice_days` int(10) unsigned DEFAULT NULL,
  `renewal_date` date DEFAULT NULL,
  `quote_id` bigint(20) unsigned DEFAULT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `template_used` varchar(255) DEFAULT NULL,
  `requires_approval` tinyint(1) NOT NULL DEFAULT 0,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approval_notes` text DEFAULT NULL,
  `sla_terms` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sla_terms`)),
  `performance_metrics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`performance_metrics`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contracts_company_id_contract_number_unique` (`company_id`,`contract_number`),
  UNIQUE KEY `contracts_contract_number_unique` (`contract_number`),
  KEY `contracts_created_by_foreign` (`created_by`),
  KEY `contracts_approved_by_foreign` (`approved_by`),
  KEY `contracts_company_id_status_index` (`company_id`,`status`),
  KEY `contracts_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `contracts_company_id_contract_type_index` (`company_id`,`contract_type`),
  KEY `contracts_start_date_end_date_index` (`start_date`,`end_date`),
  KEY `contracts_company_id_archived_at_index` (`company_id`,`archived_at`),
  KEY `contracts_company_id_index` (`company_id`),
  KEY `contracts_status_index` (`status`),
  KEY `contracts_client_id_index` (`client_id`),
  KEY `contracts_quote_id_index` (`quote_id`),
  KEY `contracts_project_id_index` (`project_id`),
  KEY `contracts_archived_at_index` (`archived_at`),
  CONSTRAINT `contracts_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `contracts_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contracts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contracts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `contracts_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `contracts_quote_id_foreign` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dashboard_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dashboard_activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dashboard_activity_logs_company_id_foreign` (`company_id`),
  KEY `dashboard_activity_logs_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `dashboard_activity_logs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dashboard_activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dashboard_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dashboard_metrics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `metric_key` varchar(255) NOT NULL,
  `value` decimal(20,4) NOT NULL,
  `previous_value` decimal(20,4) DEFAULT NULL,
  `change_percentage` decimal(8,2) DEFAULT NULL,
  `trend` varchar(255) DEFAULT NULL,
  `breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`breakdown`)),
  `calculated_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dashboard_metrics_company_id_metric_key_calculated_at_index` (`company_id`,`metric_key`,`calculated_at`),
  CONSTRAINT `dashboard_metrics_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dashboard_presets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dashboard_presets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `role` varchar(255) DEFAULT NULL,
  `layout` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`layout`)),
  `widgets` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`widgets`)),
  `default_preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`default_preferences`)),
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `usage_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dashboard_presets_slug_unique` (`slug`),
  KEY `dashboard_presets_company_id_role_index` (`company_id`,`role`),
  CONSTRAINT `dashboard_presets_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dashboard_widgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dashboard_widgets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `widget_id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `default_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`default_config`)),
  `available_sizes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`available_sizes`)),
  `data_source` varchar(255) NOT NULL,
  `min_refresh_interval` int(11) NOT NULL DEFAULT 30,
  `required_permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`required_permissions`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `icon` varchar(255) DEFAULT NULL,
  `color_scheme` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dashboard_widgets_widget_id_unique` (`widget_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `expense_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `expense_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `expense_categories_company_id_name_unique` (`company_id`,`name`),
  KEY `expense_categories_company_id_is_active_index` (`company_id`,`is_active`),
  CONSTRAINT `expense_categories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `expense_date` date NOT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_billable` tinyint(1) NOT NULL DEFAULT 0,
  `client_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expenses_category_id_foreign` (`category_id`),
  KEY `expenses_user_id_foreign` (`user_id`),
  KEY `expenses_client_id_foreign` (`client_id`),
  KEY `expenses_company_id_expense_date_index` (`company_id`,`expense_date`),
  KEY `expenses_company_id_category_id_index` (`company_id`,`category_id`),
  KEY `expenses_company_id_user_id_index` (`company_id`,`user_id`),
  KEY `expenses_company_id_is_billable_index` (`company_id`,`is_billable`),
  CONSTRAINT `expenses_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`),
  CONSTRAINT `expenses_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `expenses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` decimal(15,2) NOT NULL DEFAULT 0.00,
  `price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tax_breakdown`)),
  `service_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`service_data`)),
  `tax_rate` decimal(8,4) DEFAULT NULL,
  `service_type` varchar(50) DEFAULT NULL,
  `tax_jurisdiction_id` bigint(20) unsigned DEFAULT NULL,
  `total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `tax_id` bigint(20) unsigned DEFAULT NULL,
  `quote_id` bigint(20) unsigned DEFAULT NULL,
  `recurring_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_items_invoice_id_index` (`invoice_id`),
  KEY `invoice_items_company_id_index` (`company_id`),
  KEY `invoice_items_order_index` (`order`),
  KEY `invoice_items_company_id_invoice_id_index` (`company_id`,`invoice_id`),
  KEY `invoice_items_archived_at_index` (`archived_at`),
  KEY `invoice_items_tax_id_foreign` (`tax_id`),
  KEY `invoice_items_recurring_id_foreign` (`recurring_id`),
  KEY `invoice_items_category_id_foreign` (`category_id`),
  KEY `invoice_items_service_type_index` (`service_type`),
  KEY `invoice_items_tax_jurisdiction_id_index` (`tax_jurisdiction_id`),
  KEY `invoice_items_quote_order_idx` (`quote_id`,`order`),
  KEY `invoice_items_product_idx` (`product_id`),
  KEY `invoice_items_price_idx` (`price`),
  KEY `invoice_items_quantity_idx` (`quantity`),
  KEY `invoice_items_subtotal_idx` (`subtotal`),
  CONSTRAINT `invoice_items_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoice_items_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoice_items_quote_id_foreign` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoice_items_recurring_id_foreign` FOREIGN KEY (`recurring_id`) REFERENCES `recurring` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoice_items_tax_id_foreign` FOREIGN KEY (`tax_id`) REFERENCES `taxes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoice_items_tax_jurisdiction_id_foreign` FOREIGN KEY (`tax_jurisdiction_id`) REFERENCES `tax_jurisdictions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `prefix` varchar(255) DEFAULT NULL,
  `number` int(11) NOT NULL,
  `scope` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `due_date` date NOT NULL,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `currency_code` varchar(3) NOT NULL,
  `note` text DEFAULT NULL,
  `url_key` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_prefix_number_unique` (`prefix`,`number`),
  KEY `invoices_number_index` (`number`),
  KEY `invoices_status_index` (`status`),
  KEY `invoices_client_id_index` (`client_id`),
  KEY `invoices_company_id_index` (`company_id`),
  KEY `invoices_date_index` (`date`),
  KEY `invoices_due_index` (`due_date`),
  KEY `invoices_client_id_status_index` (`client_id`,`status`),
  KEY `invoices_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `invoices_url_key_index` (`url_key`),
  KEY `invoices_archived_at_index` (`archived_at`),
  KEY `invoices_category_id_foreign` (`category_id`),
  CONSTRAINT `invoices_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kpi_targets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `kpi_targets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `kpi_name` varchar(255) NOT NULL,
  `kpi_type` varchar(255) NOT NULL,
  `target_value` decimal(15,4) NOT NULL,
  `comparison_operator` enum('>','<','>=','<=','=') NOT NULL DEFAULT '>=',
  `period` enum('daily','weekly','monthly','quarterly','yearly') NOT NULL DEFAULT 'monthly',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `kpi_targets_company_id_kpi_name_index` (`company_id`,`kpi_name`),
  KEY `kpi_targets_company_id_period_index` (`company_id`,`period`),
  KEY `kpi_targets_company_id_is_active_index` (`company_id`,`is_active`),
  CONSTRAINT `kpi_targets_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `locations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `zip` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `hours` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `primary` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `accessed_at` timestamp NULL DEFAULT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `contact_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `locations_name_index` (`name`),
  KEY `locations_client_id_index` (`client_id`),
  KEY `locations_company_id_index` (`company_id`),
  KEY `locations_primary_index` (`primary`),
  KEY `locations_client_id_primary_index` (`client_id`,`primary`),
  KEY `locations_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `locations_archived_at_index` (`archived_at`),
  KEY `locations_contact_id_foreign` (`contact_id`),
  CONSTRAINT `locations_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `locations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `locations_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  `uuid` char(36) DEFAULT NULL,
  `collection_name` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `disk` varchar(255) NOT NULL,
  `conversions_disk` varchar(255) DEFAULT NULL,
  `size` bigint(20) unsigned NOT NULL,
  `manipulations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`manipulations`)),
  `custom_properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`custom_properties`)),
  `generated_conversions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`generated_conversions`)),
  `responsive_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`responsive_images`)),
  `order_column` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_uuid_unique` (`uuid`),
  KEY `media_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `media_order_column_index` (`order_column`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `networks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `networks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `vlan` int(11) DEFAULT NULL,
  `network` varchar(255) NOT NULL,
  `gateway` varchar(255) NOT NULL,
  `dhcp_range` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `accessed_at` timestamp NULL DEFAULT NULL,
  `location_id` bigint(20) unsigned DEFAULT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `networks_name_index` (`name`),
  KEY `networks_client_id_index` (`client_id`),
  KEY `networks_company_id_index` (`company_id`),
  KEY `networks_location_id_index` (`location_id`),
  KEY `networks_vlan_index` (`vlan`),
  KEY `networks_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `networks_archived_at_index` (`archived_at`),
  CONSTRAINT `networks_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `networks_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `networks_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `processed_by` bigint(20) unsigned DEFAULT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_reference` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `gateway` varchar(50) NOT NULL DEFAULT 'manual',
  `gateway_transaction_id` varchar(255) DEFAULT NULL,
  `gateway_fee` decimal(8,2) DEFAULT NULL,
  `status` enum('pending','processing','completed','failed','cancelled','refunded','partial_refund','chargeback') NOT NULL DEFAULT 'pending',
  `payment_date` timestamp NOT NULL,
  `notes` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `refund_amount` decimal(10,2) DEFAULT NULL,
  `refund_reason` text DEFAULT NULL,
  `refunded_at` timestamp NULL DEFAULT NULL,
  `chargeback_amount` decimal(10,2) DEFAULT NULL,
  `chargeback_reason` text DEFAULT NULL,
  `chargeback_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_company_id_status_index` (`company_id`,`status`),
  KEY `payments_client_id_status_index` (`client_id`,`status`),
  KEY `payments_invoice_id_index` (`invoice_id`),
  KEY `payments_payment_date_company_id_index` (`payment_date`,`company_id`),
  KEY `payments_gateway_gateway_transaction_id_index` (`gateway`,`gateway_transaction_id`),
  KEY `payments_payment_reference_index` (`payment_reference`),
  KEY `payments_processed_by_index` (`processed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permission_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permission_groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permission_groups_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `domain` varchar(255) NOT NULL,
  `action` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `group_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_unique` (`name`),
  UNIQUE KEY `permissions_slug_unique` (`slug`),
  KEY `permissions_domain_action_index` (`domain`,`action`),
  KEY `permissions_domain_index` (`domain`),
  KEY `permissions_group_id_foreign` (`group_id`),
  CONSTRAINT `permissions_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `permission_groups` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `portal_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `portal_notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `priority` enum('low','normal','high','urgent','critical') NOT NULL DEFAULT 'normal',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `description` text DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `icon` varchar(255) DEFAULT NULL,
  `color` varchar(255) DEFAULT NULL,
  `action_url` varchar(255) DEFAULT NULL,
  `action_text` varchar(255) DEFAULT NULL,
  `show_in_portal` tinyint(1) NOT NULL DEFAULT 1,
  `send_email` tinyint(1) NOT NULL DEFAULT 0,
  `send_sms` tinyint(1) NOT NULL DEFAULT 0,
  `send_push` tinyint(1) NOT NULL DEFAULT 0,
  `delivery_channels` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`delivery_channels`)),
  `email_subject` varchar(255) DEFAULT NULL,
  `email_body` text DEFAULT NULL,
  `email_template` varchar(255) DEFAULT NULL,
  `email_sent_at` timestamp NULL DEFAULT NULL,
  `email_delivered` tinyint(1) DEFAULT NULL,
  `email_error` varchar(255) DEFAULT NULL,
  `sms_message` varchar(255) DEFAULT NULL,
  `sms_sent_at` timestamp NULL DEFAULT NULL,
  `sms_delivered` tinyint(1) DEFAULT NULL,
  `sms_error` varchar(255) DEFAULT NULL,
  `push_title` varchar(255) DEFAULT NULL,
  `push_body` text DEFAULT NULL,
  `push_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`push_data`)),
  `push_sent_at` timestamp NULL DEFAULT NULL,
  `push_delivered` tinyint(1) DEFAULT NULL,
  `push_error` varchar(255) DEFAULT NULL,
  `status` enum('pending','sent','delivered','read','failed','cancelled') NOT NULL DEFAULT 'pending',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `is_dismissed` tinyint(1) NOT NULL DEFAULT 0,
  `dismissed_at` timestamp NULL DEFAULT NULL,
  `requires_action` tinyint(1) NOT NULL DEFAULT 0,
  `action_completed` tinyint(1) NOT NULL DEFAULT 0,
  `action_completed_at` timestamp NULL DEFAULT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT 0,
  `recurring_pattern` varchar(255) DEFAULT NULL,
  `next_occurrence` timestamp NULL DEFAULT NULL,
  `target_conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_conditions`)),
  `personalization_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`personalization_data`)),
  `language` varchar(10) NOT NULL DEFAULT 'en',
  `timezone` varchar(255) DEFAULT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `ticket_id` bigint(20) unsigned DEFAULT NULL,
  `contract_id` bigint(20) unsigned DEFAULT NULL,
  `related_model_type` varchar(255) DEFAULT NULL,
  `related_model_id` bigint(20) unsigned DEFAULT NULL,
  `group_key` varchar(255) DEFAULT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `thread_position` int(11) DEFAULT NULL,
  `is_summary` tinyint(1) NOT NULL DEFAULT 0,
  `tracking_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tracking_data`)),
  `view_count` int(11) NOT NULL DEFAULT 0,
  `first_viewed_at` timestamp NULL DEFAULT NULL,
  `last_viewed_at` timestamp NULL DEFAULT NULL,
  `click_count` int(11) NOT NULL DEFAULT 0,
  `first_clicked_at` timestamp NULL DEFAULT NULL,
  `last_clicked_at` timestamp NULL DEFAULT NULL,
  `variant` varchar(255) DEFAULT NULL,
  `campaign_id` varchar(255) DEFAULT NULL,
  `experiment_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`experiment_data`)),
  `requires_acknowledgment` tinyint(1) NOT NULL DEFAULT 0,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `acknowledgment_method` varchar(255) DEFAULT NULL,
  `audit_trail` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`audit_trail`)),
  `respects_do_not_disturb` tinyint(1) NOT NULL DEFAULT 1,
  `client_preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`client_preferences`)),
  `can_be_disabled` tinyint(1) NOT NULL DEFAULT 1,
  `frequency_limit` varchar(255) DEFAULT NULL,
  `source_system` varchar(255) DEFAULT NULL,
  `external_id` varchar(255) DEFAULT NULL,
  `webhook_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`webhook_data`)),
  `trigger_webhooks` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `custom_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_fields`)),
  `internal_notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `portal_notifications_company_id_client_id_index` (`company_id`,`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pricing_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pricing_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `pricing_model` enum('fixed','tiered','volume','usage','package','custom') NOT NULL DEFAULT 'fixed',
  `discount_type` enum('percentage','fixed','override') DEFAULT NULL,
  `discount_value` decimal(10,2) DEFAULT NULL,
  `price_override` decimal(10,2) DEFAULT NULL,
  `min_quantity` int(11) DEFAULT NULL,
  `max_quantity` int(11) DEFAULT NULL,
  `quantity_increment` int(11) NOT NULL DEFAULT 1,
  `valid_from` datetime DEFAULT NULL,
  `valid_until` datetime DEFAULT NULL,
  `applicable_days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applicable_days`)),
  `applicable_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applicable_hours`)),
  `is_promotional` tinyint(1) NOT NULL DEFAULT 0,
  `promo_code` varchar(255) DEFAULT NULL,
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`conditions`)),
  `priority` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_combinable` tinyint(1) NOT NULL DEFAULT 0,
  `max_uses` int(11) DEFAULT NULL,
  `uses_count` int(11) NOT NULL DEFAULT 0,
  `max_uses_per_client` int(11) DEFAULT NULL,
  `requires_approval` tinyint(1) NOT NULL DEFAULT 0,
  `approval_threshold` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pricing_rules_product_id_foreign` (`product_id`),
  KEY `pricing_rules_client_id_foreign` (`client_id`),
  KEY `pricing_rules_company_id_product_id_is_active_index` (`company_id`,`product_id`,`is_active`),
  KEY `pricing_rules_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `pricing_rules_valid_from_valid_until_index` (`valid_from`,`valid_until`),
  KEY `pricing_rules_promo_code_index` (`promo_code`),
  CONSTRAINT `pricing_rules_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pricing_rules_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pricing_rules_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_bundle_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_bundle_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `bundle_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 1,
  `discount_type` enum('percentage','fixed','none') NOT NULL DEFAULT 'none',
  `discount_value` decimal(10,2) DEFAULT NULL,
  `price_override` decimal(10,2) DEFAULT NULL,
  `min_quantity` int(11) NOT NULL DEFAULT 0,
  `max_quantity` int(11) DEFAULT NULL,
  `allowed_variants` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_variants`)),
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_bundle_items_bundle_id_product_id_unique` (`bundle_id`,`product_id`),
  KEY `product_bundle_items_product_id_foreign` (`product_id`),
  CONSTRAINT `product_bundle_items_bundle_id_foreign` FOREIGN KEY (`bundle_id`) REFERENCES `product_bundles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_bundle_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_bundles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_bundles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `bundle_type` enum('fixed','configurable','dynamic') NOT NULL DEFAULT 'fixed',
  `pricing_type` enum('sum','fixed','percentage_discount') NOT NULL DEFAULT 'sum',
  `fixed_price` decimal(10,2) DEFAULT NULL,
  `discount_percentage` decimal(5,2) DEFAULT NULL,
  `min_value` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `available_from` datetime DEFAULT NULL,
  `available_until` datetime DEFAULT NULL,
  `max_quantity` int(11) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `show_items_separately` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_bundles_company_id_foreign` (`company_id`),
  CONSTRAINT `product_bundles_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_tax_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_tax_data` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `tax_profile_id` bigint(20) unsigned DEFAULT NULL,
  `tax_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`tax_data`)),
  `calculated_taxes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`calculated_taxes`)),
  `jurisdiction_id` bigint(20) unsigned DEFAULT NULL,
  `effective_tax_rate` decimal(5,2) DEFAULT NULL,
  `total_tax_amount` decimal(10,2) DEFAULT NULL,
  `last_calculated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_tax_data_company_id_product_id_unique` (`company_id`,`product_id`),
  KEY `product_tax_data_company_id_index` (`company_id`),
  KEY `product_tax_data_product_id_index` (`product_id`),
  KEY `product_tax_data_tax_profile_id_index` (`tax_profile_id`),
  CONSTRAINT `product_tax_data_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_tax_data_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_tax_data_tax_profile_id_foreign` FOREIGN KEY (`tax_profile_id`) REFERENCES `tax_profiles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `type` enum('product','service') NOT NULL DEFAULT 'product',
  `base_price` decimal(15,2) NOT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `currency_code` varchar(3) NOT NULL,
  `tax_inclusive` tinyint(1) NOT NULL DEFAULT 0,
  `tax_rate` decimal(5,2) DEFAULT NULL,
  `tax_profile_id` bigint(20) unsigned DEFAULT NULL,
  `tax_specific_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tax_specific_data`)),
  `unit_type` enum('hours','units','days','weeks','months','years','fixed','subscription') NOT NULL DEFAULT 'units',
  `billing_model` enum('one_time','subscription','usage_based','hybrid') NOT NULL DEFAULT 'one_time',
  `billing_cycle` enum('one_time','hourly','daily','weekly','monthly','quarterly','semi_annually','annually') NOT NULL DEFAULT 'one_time',
  `billing_interval` int(11) NOT NULL DEFAULT 1,
  `track_inventory` tinyint(1) NOT NULL DEFAULT 0,
  `current_stock` int(11) NOT NULL DEFAULT 0,
  `reserved_stock` int(11) NOT NULL DEFAULT 0,
  `min_stock_level` int(11) NOT NULL DEFAULT 0,
  `max_quantity_per_order` int(11) DEFAULT NULL,
  `reorder_level` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_taxable` tinyint(1) NOT NULL DEFAULT 1,
  `allow_discounts` tinyint(1) NOT NULL DEFAULT 1,
  `requires_approval` tinyint(1) NOT NULL DEFAULT 0,
  `pricing_model` enum('fixed','tiered','volume','usage','value','custom') NOT NULL DEFAULT 'fixed',
  `pricing_tiers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pricing_tiers`)),
  `discount_percentage` decimal(5,2) DEFAULT NULL,
  `usage_rate` decimal(10,4) DEFAULT NULL,
  `usage_included` int(11) DEFAULT NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `custom_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_fields`)),
  `image_url` varchar(255) DEFAULT NULL,
  `gallery_urls` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gallery_urls`)),
  `sales_count` int(11) NOT NULL DEFAULT 0,
  `total_revenue` decimal(12,2) NOT NULL DEFAULT 0.00,
  `average_rating` decimal(3,2) DEFAULT NULL,
  `rating_count` int(11) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `short_description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `tax_id` bigint(20) unsigned DEFAULT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `subcategory_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `products_name_index` (`name`),
  KEY `products_category_id_index` (`category_id`),
  KEY `products_company_id_index` (`company_id`),
  KEY `products_price_index` (`base_price`),
  KEY `products_company_id_category_id_index` (`company_id`,`category_id`),
  KEY `products_tax_id_foreign` (`tax_id`),
  KEY `products_company_id_is_active_index` (`company_id`),
  KEY `products_company_id_type_index` (`company_id`),
  KEY `products_sku_index` (`sku`),
  KEY `products_billing_model_index` (`billing_model`),
  KEY `products_tax_profile_id_index` (`tax_profile_id`),
  KEY `products_company_category_idx` (`company_id`,`category_id`),
  KEY `products_company_featured_idx` (`company_id`,`is_featured`),
  KEY `products_price_idx` (`base_price`),
  KEY `products_sku_idx` (`sku`),
  FULLTEXT KEY `products_search_fulltext` (`name`,`description`,`sku`),
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `products_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `products_tax_id_foreign` FOREIGN KEY (`tax_id`) REFERENCES `taxes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_tax_profile_id_foreign` FOREIGN KEY (`tax_profile_id`) REFERENCES `tax_profiles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_members` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `project_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `role` enum('manager','member','viewer') NOT NULL DEFAULT 'member',
  `hourly_rate` decimal(10,2) DEFAULT NULL,
  `can_log_time` tinyint(1) NOT NULL DEFAULT 1,
  `can_edit_tasks` tinyint(1) NOT NULL DEFAULT 0,
  `joined_at` datetime NOT NULL,
  `left_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_members_project_id_user_id_unique` (`project_id`,`user_id`),
  KEY `project_members_user_id_foreign` (`user_id`),
  KEY `project_members_company_id_project_id_index` (`company_id`,`project_id`),
  KEY `project_members_company_id_user_id_index` (`company_id`,`user_id`),
  CONSTRAINT `project_members_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_members_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_members_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_milestones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_milestones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `project_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date NOT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','in_progress','completed','overdue') NOT NULL DEFAULT 'pending',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `deliverables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`deliverables`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_milestones_project_id_foreign` (`project_id`),
  KEY `project_milestones_company_id_project_id_index` (`company_id`,`project_id`),
  KEY `project_milestones_company_id_due_date_index` (`company_id`,`due_date`),
  KEY `project_milestones_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `project_milestones_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_milestones_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `project_id` bigint(20) unsigned NOT NULL,
  `milestone_id` bigint(20) unsigned DEFAULT NULL,
  `parent_task_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `due_date` datetime DEFAULT NULL,
  `completed_date` datetime DEFAULT NULL,
  `estimated_hours` int(11) DEFAULT NULL,
  `actual_hours` int(11) DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `status` enum('not_started','in_progress','completed','on_hold','cancelled') NOT NULL DEFAULT 'not_started',
  `completion_percentage` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_tasks_project_id_foreign` (`project_id`),
  KEY `project_tasks_milestone_id_foreign` (`milestone_id`),
  KEY `project_tasks_parent_task_id_foreign` (`parent_task_id`),
  KEY `project_tasks_company_id_project_id_index` (`company_id`,`project_id`),
  KEY `project_tasks_company_id_milestone_id_index` (`company_id`,`milestone_id`),
  KEY `project_tasks_company_id_status_index` (`company_id`,`status`),
  KEY `project_tasks_company_id_due_date_index` (`company_id`,`due_date`),
  CONSTRAINT `project_tasks_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_tasks_milestone_id_foreign` FOREIGN KEY (`milestone_id`) REFERENCES `project_milestones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_tasks_parent_task_id_foreign` FOREIGN KEY (`parent_task_id`) REFERENCES `project_tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_tasks_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `default_milestones` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`default_milestones`)),
  `default_tasks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`default_tasks`)),
  `estimated_duration_days` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_templates_company_id_category_index` (`company_id`,`category`),
  KEY `project_templates_company_id_is_active_index` (`company_id`,`is_active`),
  CONSTRAINT `project_templates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `projects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `prefix` varchar(255) DEFAULT NULL,
  `number` int(11) NOT NULL DEFAULT 1,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due` date DEFAULT NULL,
  `manager_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `projects_prefix_number_unique` (`prefix`,`number`),
  KEY `projects_name_index` (`name`),
  KEY `projects_client_id_index` (`client_id`),
  KEY `projects_company_id_index` (`company_id`),
  KEY `projects_manager_id_index` (`manager_id`),
  KEY `projects_due_index` (`due`),
  KEY `projects_completed_at_index` (`completed_at`),
  KEY `projects_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `projects_archived_at_index` (`archived_at`),
  CONSTRAINT `projects_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `projects_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `projects_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quote_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quote_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('basic','standard','premium','enterprise','custom','equipment','maintenance','professional','managed') NOT NULL,
  `template_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`template_items`)),
  `service_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`service_config`)),
  `pricing_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pricing_config`)),
  `tax_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tax_config`)),
  `terms_conditions` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quote_templates_company_id_name_unique` (`company_id`,`name`),
  KEY `quote_templates_company_id_is_active_index` (`company_id`,`is_active`),
  KEY `quote_templates_company_id_category_index` (`company_id`,`category`),
  KEY `quote_templates_name_index` (`name`),
  KEY `quote_templates_category_index` (`category`),
  KEY `quote_templates_created_by_index` (`created_by`),
  KEY `templates_company_active_idx` (`company_id`,`is_active`),
  KEY `templates_company_category_idx` (`company_id`,`category`),
  FULLTEXT KEY `templates_content_fulltext` (`name`,`description`),
  CONSTRAINT `quote_templates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quote_templates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quote_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quote_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `quote_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `version_number` int(11) NOT NULL,
  `quote_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`quote_data`)),
  `changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`changes`)),
  `change_reason` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quote_versions_quote_id_version_number_unique` (`quote_id`,`version_number`),
  KEY `quote_versions_quote_id_version_number_index` (`quote_id`,`version_number`),
  KEY `quote_versions_company_id_created_at_index` (`company_id`,`created_at`),
  KEY `quote_versions_created_by_index` (`created_by`),
  CONSTRAINT `quote_versions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quote_versions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quote_versions_quote_id_foreign` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quotes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quotes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `prefix` varchar(255) DEFAULT NULL,
  `number` int(11) NOT NULL,
  `scope` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `approval_status` enum('pending','manager_approved','executive_approved','rejected','not_required') NOT NULL DEFAULT 'not_required',
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `date` date NOT NULL,
  `expire` date DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `currency_code` varchar(3) NOT NULL,
  `note` text DEFAULT NULL,
  `url_key` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quotes_prefix_number_unique` (`prefix`,`number`),
  KEY `quotes_number_index` (`number`),
  KEY `quotes_status_index` (`status`),
  KEY `quotes_client_id_index` (`client_id`),
  KEY `quotes_company_id_index` (`company_id`),
  KEY `quotes_date_index` (`date`),
  KEY `quotes_expire_index` (`expire`),
  KEY `quotes_client_id_status_index` (`client_id`,`status`),
  KEY `quotes_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `quotes_url_key_index` (`url_key`),
  KEY `quotes_archived_at_index` (`archived_at`),
  KEY `quotes_category_id_foreign` (`category_id`),
  KEY `quotes_company_status_idx` (`company_id`,`status`),
  KEY `quotes_company_client_idx` (`company_id`,`client_id`),
  KEY `quotes_company_created_idx` (`company_id`,`created_at`),
  KEY `quotes_company_date_idx` (`company_id`,`date`),
  KEY `quotes_company_expire_idx` (`company_id`,`expire`),
  KEY `quotes_number_idx` (`number`),
  KEY `quotes_status_idx` (`status`),
  KEY `quotes_total_idx` (`amount`),
  KEY `quotes_status_expire_idx` (`status`,`expire`),
  KEY `quotes_approval_status_index` (`approval_status`),
  CONSTRAINT `quotes_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quotes_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quotes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `recurring`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recurring` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `prefix` varchar(255) DEFAULT NULL,
  `number` int(11) NOT NULL,
  `scope` varchar(255) DEFAULT NULL,
  `frequency` varchar(255) NOT NULL,
  `last_sent` date DEFAULT NULL,
  `next_date` date NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `currency_code` varchar(3) NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `recurring_prefix_number_unique` (`prefix`,`number`),
  KEY `recurring_number_index` (`number`),
  KEY `recurring_status_index` (`status`),
  KEY `recurring_client_id_index` (`client_id`),
  KEY `recurring_company_id_index` (`company_id`),
  KEY `recurring_frequency_index` (`frequency`),
  KEY `recurring_next_date_index` (`next_date`),
  KEY `recurring_client_id_status_index` (`client_id`,`status`),
  KEY `recurring_status_next_date_index` (`status`,`next_date`),
  KEY `recurring_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `recurring_archived_at_index` (`archived_at`),
  KEY `recurring_category_id_foreign` (`category_id`),
  CONSTRAINT `recurring_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recurring_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recurring_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `recurring_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recurring_tickets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `template_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `frequency` enum('daily','weekly','monthly','quarterly','annually') NOT NULL DEFAULT 'monthly',
  `interval_value` int(11) NOT NULL DEFAULT 1,
  `frequency_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`frequency_config`)),
  `next_run` datetime NOT NULL,
  `next_run_date` date DEFAULT NULL,
  `last_run` datetime DEFAULT NULL,
  `last_run_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `max_occurrences` int(11) DEFAULT NULL,
  `occurrences_count` int(11) NOT NULL DEFAULT 0,
  `template_overrides` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`template_overrides`)),
  `status` enum('active','paused','completed') NOT NULL DEFAULT 'active',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `configuration` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`configuration`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `recurring_tickets_client_id_foreign` (`client_id`),
  KEY `recurring_tickets_template_id_foreign` (`template_id`),
  KEY `recurring_tickets_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `recurring_tickets_company_id_next_run_index` (`company_id`,`next_run`),
  KEY `recurring_tickets_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `recurring_tickets_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recurring_tickets_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recurring_tickets_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `ticket_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `report_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `color` varchar(7) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `report_categories_company_id_is_active_index` (`company_id`,`is_active`),
  KEY `report_categories_company_id_sort_order_index` (`company_id`,`sort_order`),
  CONSTRAINT `report_categories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `report_exports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_exports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `report_name` varchar(255) NOT NULL,
  `format` enum('pdf','excel','csv') NOT NULL DEFAULT 'pdf',
  `file_path` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `status` enum('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
  `generated_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`parameters`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `report_exports_user_id_foreign` (`user_id`),
  KEY `report_exports_company_id_user_id_index` (`company_id`,`user_id`),
  KEY `report_exports_company_id_status_index` (`company_id`,`status`),
  KEY `report_exports_company_id_expires_at_index` (`company_id`,`expires_at`),
  CONSTRAINT `report_exports_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `report_exports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `report_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_metrics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `metric_name` varchar(255) NOT NULL,
  `metric_type` varchar(255) NOT NULL,
  `value` decimal(15,4) NOT NULL,
  `dimensions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dimensions`)),
  `metric_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `report_metrics_company_id_metric_name_index` (`company_id`,`metric_name`),
  KEY `report_metrics_company_id_metric_date_index` (`company_id`,`metric_date`),
  KEY `report_metrics_company_id_metric_type_index` (`company_id`,`metric_type`),
  CONSTRAINT `report_metrics_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `report_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `template_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `frequency` enum('daily','weekly','monthly','quarterly') NOT NULL DEFAULT 'monthly',
  `recipients` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`recipients`)),
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`filters`)),
  `format` enum('pdf','excel','csv') NOT NULL DEFAULT 'pdf',
  `delivery_time` time NOT NULL DEFAULT '09:00:00',
  `next_run` datetime NOT NULL,
  `last_run` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `report_subscriptions_template_id_foreign` (`template_id`),
  KEY `report_subscriptions_user_id_foreign` (`user_id`),
  KEY `report_subscriptions_company_id_template_id_index` (`company_id`,`template_id`),
  KEY `report_subscriptions_company_id_next_run_index` (`company_id`,`next_run`),
  KEY `report_subscriptions_company_id_is_active_index` (`company_id`,`is_active`),
  CONSTRAINT `report_subscriptions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `report_subscriptions_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `report_templates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `report_subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `report_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `configuration` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`configuration`)),
  `default_filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`default_filters`)),
  `type` enum('table','chart','summary','dashboard') NOT NULL DEFAULT 'table',
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `report_templates_category_id_foreign` (`category_id`),
  KEY `report_templates_company_id_category_id_index` (`company_id`,`category_id`),
  KEY `report_templates_company_id_type_index` (`company_id`,`type`),
  KEY `report_templates_company_id_is_active_index` (`company_id`,`is_active`),
  CONSTRAINT `report_templates_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `report_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `report_templates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) unsigned NOT NULL,
  `permission_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_permissions_role_id_permission_id_unique` (`role_id`,`permission_id`),
  KEY `role_permissions_permission_id_foreign` (`permission_id`),
  CONSTRAINT `role_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `level` int(11) NOT NULL DEFAULT 1,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `saved_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `saved_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `template_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`filters`)),
  `configuration` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`configuration`)),
  `is_shared` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `saved_reports_template_id_foreign` (`template_id`),
  KEY `saved_reports_user_id_foreign` (`user_id`),
  KEY `saved_reports_company_id_template_id_index` (`company_id`,`template_id`),
  KEY `saved_reports_company_id_user_id_index` (`company_id`,`user_id`),
  CONSTRAINT `saved_reports_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `saved_reports_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `report_templates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `saved_reports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `service_tax_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_tax_rates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `tax_jurisdiction_id` bigint(20) unsigned NOT NULL,
  `tax_category_id` bigint(20) unsigned DEFAULT NULL,
  `service_type` varchar(50) NOT NULL,
  `tax_type` varchar(50) NOT NULL,
  `tax_name` varchar(255) NOT NULL,
  `authority_name` varchar(255) NOT NULL,
  `tax_code` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `regulatory_code` varchar(50) DEFAULT NULL,
  `rate_type` enum('percentage','fixed','tiered','per_line','per_minute','per_unit') NOT NULL,
  `percentage_rate` decimal(8,4) DEFAULT NULL,
  `fixed_amount` decimal(10,4) DEFAULT NULL,
  `minimum_threshold` decimal(10,4) DEFAULT NULL,
  `maximum_amount` decimal(10,4) DEFAULT NULL,
  `calculation_method` enum('standard','compound','additive','inclusive','exclusive') NOT NULL DEFAULT 'standard',
  `service_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`service_types`)),
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`conditions`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_recoverable` tinyint(1) NOT NULL DEFAULT 0,
  `is_compound` tinyint(1) NOT NULL DEFAULT 0,
  `priority` smallint(5) unsigned NOT NULL DEFAULT 100,
  `effective_date` datetime NOT NULL,
  `expiry_date` datetime DEFAULT NULL,
  `external_id` varchar(255) DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL,
  `last_updated_from_source` datetime DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `service_tax_rates_company_id_is_active_index` (`company_id`,`is_active`),
  KEY `service_tax_rates_service_type_is_active_index` (`service_type`,`is_active`),
  KEY `service_tax_rates_tax_jurisdiction_id_is_active_index` (`tax_jurisdiction_id`,`is_active`),
  KEY `service_tax_rates_tax_type_is_active_index` (`tax_type`,`is_active`),
  KEY `service_tax_rates_regulatory_code_is_active_index` (`regulatory_code`,`is_active`),
  KEY `service_tax_rates_priority_index` (`priority`),
  KEY `service_tax_rates_effective_date_expiry_date_index` (`effective_date`,`expiry_date`),
  CONSTRAINT `service_tax_rates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `service_tax_rates_tax_jurisdiction_id_foreign` FOREIGN KEY (`tax_jurisdiction_id`) REFERENCES `tax_jurisdictions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `services` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `service_type` enum('consulting','support','maintenance','development','training','implementation','custom') NOT NULL DEFAULT 'custom',
  `estimated_hours` decimal(8,2) DEFAULT NULL,
  `sla_days` int(11) DEFAULT NULL,
  `response_time_hours` int(11) DEFAULT NULL,
  `resolution_time_hours` int(11) DEFAULT NULL,
  `deliverables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`deliverables`)),
  `dependencies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dependencies`)),
  `requirements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`requirements`)),
  `requires_scheduling` tinyint(1) NOT NULL DEFAULT 0,
  `min_notice_hours` int(11) NOT NULL DEFAULT 24,
  `duration_minutes` int(11) DEFAULT NULL,
  `availability_schedule` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`availability_schedule`)),
  `default_assignee_id` bigint(20) unsigned DEFAULT NULL,
  `required_skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`required_skills`)),
  `required_resources` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`required_resources`)),
  `has_setup_fee` tinyint(1) NOT NULL DEFAULT 0,
  `setup_fee` decimal(10,2) DEFAULT NULL,
  `has_cancellation_fee` tinyint(1) NOT NULL DEFAULT 0,
  `cancellation_fee` decimal(10,2) DEFAULT NULL,
  `cancellation_notice_hours` int(11) NOT NULL DEFAULT 24,
  `minimum_commitment_months` int(11) DEFAULT NULL,
  `maximum_duration_months` int(11) DEFAULT NULL,
  `auto_renew` tinyint(1) NOT NULL DEFAULT 0,
  `renewal_notice_days` int(11) NOT NULL DEFAULT 30,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `services_product_idx` (`product_id`),
  KEY `services_type_idx` (`service_type`),
  CONSTRAINT `services_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `company_logo` varchar(255) DEFAULT NULL,
  `company_colors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`company_colors`)),
  `company_address` varchar(255) DEFAULT NULL,
  `company_city` varchar(255) DEFAULT NULL,
  `company_state` varchar(255) DEFAULT NULL,
  `company_zip` varchar(255) DEFAULT NULL,
  `company_country` varchar(255) NOT NULL DEFAULT 'US',
  `company_phone` varchar(255) DEFAULT NULL,
  `company_website` varchar(255) DEFAULT NULL,
  `company_tax_id` varchar(255) DEFAULT NULL,
  `business_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`business_hours`)),
  `company_holidays` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`company_holidays`)),
  `company_language` varchar(255) NOT NULL DEFAULT 'en',
  `company_currency` varchar(255) NOT NULL DEFAULT 'USD',
  `custom_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_fields`)),
  `localization_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`localization_settings`)),
  `current_database_version` varchar(10) NOT NULL,
  `start_page` varchar(255) NOT NULL DEFAULT 'clients.php',
  `smtp_host` varchar(255) DEFAULT NULL,
  `smtp_port` int(11) DEFAULT NULL,
  `smtp_encryption` varchar(255) DEFAULT NULL,
  `smtp_username` varchar(255) DEFAULT NULL,
  `smtp_password` varchar(255) DEFAULT NULL,
  `mail_from_email` varchar(255) DEFAULT NULL,
  `mail_from_name` varchar(255) DEFAULT NULL,
  `imap_host` varchar(255) DEFAULT NULL,
  `imap_port` int(11) DEFAULT NULL,
  `imap_encryption` varchar(255) DEFAULT NULL,
  `imap_username` varchar(255) DEFAULT NULL,
  `imap_password` varchar(255) DEFAULT NULL,
  `smtp_auth_required` tinyint(1) NOT NULL DEFAULT 1,
  `smtp_use_tls` tinyint(1) NOT NULL DEFAULT 1,
  `smtp_timeout` int(11) NOT NULL DEFAULT 30,
  `email_retry_attempts` int(11) NOT NULL DEFAULT 3,
  `email_templates` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`email_templates`)),
  `email_signatures` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`email_signatures`)),
  `email_tracking_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `sms_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sms_settings`)),
  `voice_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`voice_settings`)),
  `slack_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`slack_settings`)),
  `teams_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`teams_settings`)),
  `discord_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`discord_settings`)),
  `video_conferencing_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`video_conferencing_settings`)),
  `communication_preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`communication_preferences`)),
  `quiet_hours_start` time DEFAULT NULL,
  `quiet_hours_end` time DEFAULT NULL,
  `default_transfer_from_account` int(11) DEFAULT NULL,
  `default_transfer_to_account` int(11) DEFAULT NULL,
  `default_payment_account` int(11) DEFAULT NULL,
  `default_expense_account` int(11) DEFAULT NULL,
  `default_payment_method` varchar(255) DEFAULT NULL,
  `default_expense_payment_method` varchar(255) DEFAULT NULL,
  `default_calendar` int(11) DEFAULT NULL,
  `default_net_terms` int(11) DEFAULT NULL,
  `default_hourly_rate` decimal(15,2) NOT NULL DEFAULT 0.00,
  `multi_currency_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `supported_currencies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`supported_currencies`)),
  `exchange_rate_provider` varchar(255) DEFAULT NULL,
  `auto_update_exchange_rates` tinyint(1) NOT NULL DEFAULT 1,
  `tax_calculation_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tax_calculation_settings`)),
  `tax_engine_provider` varchar(255) DEFAULT NULL,
  `payment_gateway_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_gateway_settings`)),
  `stripe_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`stripe_settings`)),
  `square_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`square_settings`)),
  `paypal_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`paypal_settings`)),
  `authorize_net_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`authorize_net_settings`)),
  `ach_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ach_settings`)),
  `recurring_billing_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `recurring_billing_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`recurring_billing_settings`)),
  `late_fee_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`late_fee_settings`)),
  `collection_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`collection_settings`)),
  `accounting_integration_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`accounting_integration_settings`)),
  `quickbooks_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`quickbooks_settings`)),
  `xero_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`xero_settings`)),
  `sage_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sage_settings`)),
  `revenue_recognition_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `revenue_recognition_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`revenue_recognition_settings`)),
  `purchase_order_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`purchase_order_settings`)),
  `expense_approval_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`expense_approval_settings`)),
  `connectwise_automate_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`connectwise_automate_settings`)),
  `datto_rmm_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datto_rmm_settings`)),
  `ninja_rmm_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ninja_rmm_settings`)),
  `kaseya_vsa_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`kaseya_vsa_settings`)),
  `auvik_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`auvik_settings`)),
  `prtg_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`prtg_settings`)),
  `solarwinds_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`solarwinds_settings`)),
  `monitoring_alert_thresholds` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`monitoring_alert_thresholds`)),
  `escalation_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`escalation_rules`)),
  `asset_discovery_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`asset_discovery_settings`)),
  `patch_management_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`patch_management_settings`)),
  `remote_access_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`remote_access_settings`)),
  `auto_create_tickets_from_alerts` tinyint(1) NOT NULL DEFAULT 0,
  `alert_to_ticket_mapping` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`alert_to_ticket_mapping`)),
  `invoice_prefix` varchar(255) DEFAULT NULL,
  `invoice_next_number` int(11) DEFAULT NULL,
  `invoice_footer` text DEFAULT NULL,
  `invoice_from_name` varchar(255) DEFAULT NULL,
  `invoice_from_email` varchar(255) DEFAULT NULL,
  `invoice_late_fee_enable` tinyint(1) NOT NULL DEFAULT 0,
  `invoice_late_fee_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `quote_prefix` varchar(255) DEFAULT NULL,
  `quote_next_number` int(11) DEFAULT NULL,
  `quote_footer` text DEFAULT NULL,
  `quote_from_name` varchar(255) DEFAULT NULL,
  `quote_from_email` varchar(255) DEFAULT NULL,
  `ticket_prefix` varchar(255) DEFAULT NULL,
  `ticket_next_number` int(11) DEFAULT NULL,
  `ticket_from_name` varchar(255) DEFAULT NULL,
  `ticket_from_email` varchar(255) DEFAULT NULL,
  `ticket_email_parse` tinyint(1) NOT NULL DEFAULT 0,
  `ticket_client_general_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `ticket_autoclose` tinyint(1) NOT NULL DEFAULT 0,
  `ticket_autoclose_hours` int(11) NOT NULL DEFAULT 72,
  `ticket_new_ticket_notification_email` varchar(255) DEFAULT NULL,
  `ticket_categorization_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ticket_categorization_rules`)),
  `ticket_priority_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ticket_priority_rules`)),
  `auto_assignment_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`auto_assignment_rules`)),
  `routing_logic` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`routing_logic`)),
  `approval_workflows` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`approval_workflows`)),
  `time_tracking_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `time_tracking_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`time_tracking_settings`)),
  `customer_satisfaction_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `csat_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`csat_settings`)),
  `ticket_templates` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ticket_templates`)),
  `ticket_automation_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ticket_automation_rules`)),
  `multichannel_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`multichannel_settings`)),
  `queue_management_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`queue_management_settings`)),
  `project_templates` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`project_templates`)),
  `project_standardization_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`project_standardization_settings`)),
  `resource_allocation_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`resource_allocation_settings`)),
  `capacity_planning_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`capacity_planning_settings`)),
  `project_time_tracking_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `project_billing_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`project_billing_settings`)),
  `milestone_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`milestone_settings`)),
  `deliverable_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`deliverable_settings`)),
  `gantt_chart_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gantt_chart_settings`)),
  `budget_management_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`budget_management_settings`)),
  `profitability_tracking_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`profitability_tracking_settings`)),
  `change_request_workflows` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`change_request_workflows`)),
  `project_collaboration_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`project_collaboration_settings`)),
  `document_management_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`document_management_settings`)),
  `asset_discovery_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`asset_discovery_rules`)),
  `asset_lifecycle_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`asset_lifecycle_settings`)),
  `software_license_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`software_license_settings`)),
  `hardware_warranty_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`hardware_warranty_settings`)),
  `procurement_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`procurement_settings`)),
  `vendor_management_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`vendor_management_settings`)),
  `asset_depreciation_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`asset_depreciation_settings`)),
  `asset_tracking_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`asset_tracking_settings`)),
  `barcode_scanning_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `barcode_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`barcode_settings`)),
  `mobile_asset_management_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `asset_relationship_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`asset_relationship_settings`)),
  `asset_compliance_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`asset_compliance_settings`)),
  `enable_cron` tinyint(1) NOT NULL DEFAULT 0,
  `cron_key` varchar(255) DEFAULT NULL,
  `recurring_auto_send_invoice` tinyint(1) NOT NULL DEFAULT 1,
  `enable_alert_domain_expire` tinyint(1) NOT NULL DEFAULT 1,
  `send_invoice_reminders` tinyint(1) NOT NULL DEFAULT 1,
  `invoice_overdue_reminders` varchar(255) DEFAULT NULL,
  `theme` varchar(255) NOT NULL DEFAULT 'blue',
  `telemetry` tinyint(1) NOT NULL DEFAULT 0,
  `timezone` varchar(255) NOT NULL DEFAULT 'America/New_York',
  `date_format` varchar(20) NOT NULL DEFAULT 'Y-m-d',
  `destructive_deletes_enable` tinyint(1) NOT NULL DEFAULT 0,
  `module_enable_itdoc` tinyint(1) NOT NULL DEFAULT 1,
  `module_enable_accounting` tinyint(1) NOT NULL DEFAULT 1,
  `module_enable_ticketing` tinyint(1) NOT NULL DEFAULT 1,
  `client_portal_enable` tinyint(1) NOT NULL DEFAULT 1,
  `portal_branding_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`portal_branding_settings`)),
  `portal_customization_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`portal_customization_settings`)),
  `portal_access_controls` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`portal_access_controls`)),
  `portal_feature_toggles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`portal_feature_toggles`)),
  `portal_self_service_tickets` tinyint(1) NOT NULL DEFAULT 1,
  `portal_knowledge_base_access` tinyint(1) NOT NULL DEFAULT 1,
  `portal_invoice_access` tinyint(1) NOT NULL DEFAULT 1,
  `portal_payment_processing` tinyint(1) NOT NULL DEFAULT 0,
  `portal_asset_visibility` tinyint(1) NOT NULL DEFAULT 0,
  `portal_sso_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`portal_sso_settings`)),
  `portal_mobile_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`portal_mobile_settings`)),
  `portal_dashboard_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`portal_dashboard_settings`)),
  `business_rule_engine_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`business_rule_engine_settings`)),
  `workflow_automation_templates` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`workflow_automation_templates`)),
  `rpa_bot_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`rpa_bot_settings`)),
  `event_driven_automation` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`event_driven_automation`)),
  `custom_script_policies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_script_policies`)),
  `integration_middleware_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`integration_middleware_settings`)),
  `data_synchronization_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data_synchronization_rules`)),
  `notification_automation_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notification_automation_settings`)),
  `approval_process_automation` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`approval_process_automation`)),
  `document_generation_automation` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`document_generation_automation`)),
  `soc2_compliance_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `soc2_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`soc2_settings`)),
  `hipaa_compliance_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `hipaa_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`hipaa_settings`)),
  `pci_compliance_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `pci_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pci_settings`)),
  `gdpr_compliance_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `gdpr_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gdpr_settings`)),
  `industry_compliance_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`industry_compliance_settings`)),
  `data_retention_policies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data_retention_policies`)),
  `data_destruction_policies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data_destruction_policies`)),
  `risk_assessment_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`risk_assessment_settings`)),
  `vendor_compliance_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`vendor_compliance_settings`)),
  `incident_response_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`incident_response_settings`)),
  `backup_policies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`backup_policies`)),
  `backup_schedules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`backup_schedules`)),
  `recovery_time_objective` int(11) DEFAULT NULL,
  `recovery_point_objective` int(11) DEFAULT NULL,
  `disaster_recovery_procedures` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`disaster_recovery_procedures`)),
  `data_replication_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data_replication_settings`)),
  `business_continuity_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`business_continuity_settings`)),
  `testing_validation_schedules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`testing_validation_schedules`)),
  `cloud_backup_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`cloud_backup_settings`)),
  `ransomware_protection_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ransomware_protection_settings`)),
  `recovery_documentation_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`recovery_documentation_settings`)),
  `system_resource_monitoring` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`system_resource_monitoring`)),
  `performance_tuning_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`performance_tuning_settings`)),
  `caching_strategies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`caching_strategies`)),
  `database_optimization_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`database_optimization_settings`)),
  `cdn_load_balancing_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`cdn_load_balancing_settings`)),
  `api_performance_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`api_performance_settings`)),
  `login_message` text DEFAULT NULL,
  `login_key_required` tinyint(1) NOT NULL DEFAULT 0,
  `login_key_secret` varchar(255) DEFAULT NULL,
  `password_min_length` int(11) NOT NULL DEFAULT 8,
  `password_require_special` tinyint(1) NOT NULL DEFAULT 1,
  `password_require_numbers` tinyint(1) NOT NULL DEFAULT 1,
  `password_require_uppercase` tinyint(1) NOT NULL DEFAULT 1,
  `password_require_lowercase` tinyint(1) NOT NULL DEFAULT 1,
  `password_require_number` tinyint(1) NOT NULL DEFAULT 1,
  `password_expiry_days` int(11) NOT NULL DEFAULT 90,
  `password_history_count` int(11) NOT NULL DEFAULT 5,
  `session_lifetime` int(11) NOT NULL DEFAULT 120,
  `idle_timeout` int(11) NOT NULL DEFAULT 15,
  `single_session_per_user` tinyint(1) NOT NULL DEFAULT 0,
  `logout_on_browser_close` tinyint(1) NOT NULL DEFAULT 0,
  `ip_whitelist_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `whitelisted_ips` text DEFAULT NULL,
  `oauth_google_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `oauth_google_client_id` varchar(255) DEFAULT NULL,
  `oauth_google_client_secret` text DEFAULT NULL,
  `oauth_microsoft_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `oauth_microsoft_client_id` varchar(255) DEFAULT NULL,
  `oauth_microsoft_client_secret` text DEFAULT NULL,
  `saml_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `saml_entity_id` varchar(255) DEFAULT NULL,
  `saml_sso_url` varchar(255) DEFAULT NULL,
  `saml_certificate` text DEFAULT NULL,
  `audit_login_attempts` tinyint(1) NOT NULL DEFAULT 1,
  `audit_password_changes` tinyint(1) NOT NULL DEFAULT 1,
  `audit_permission_changes` tinyint(1) NOT NULL DEFAULT 1,
  `audit_data_access` tinyint(1) NOT NULL DEFAULT 0,
  `audit_log_retention_days` int(11) NOT NULL DEFAULT 365,
  `alert_suspicious_activity` tinyint(1) NOT NULL DEFAULT 0,
  `alert_multiple_failed_logins` tinyint(1) NOT NULL DEFAULT 1,
  `security_alert_email` varchar(255) DEFAULT NULL,
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `remember_me_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `email_verification_required` tinyint(1) NOT NULL DEFAULT 0,
  `api_authentication_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `two_factor_methods` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`two_factor_methods`)),
  `session_timeout_minutes` int(11) NOT NULL DEFAULT 480,
  `force_single_session` tinyint(1) NOT NULL DEFAULT 0,
  `max_login_attempts` int(11) NOT NULL DEFAULT 5,
  `login_lockout_duration` int(11) NOT NULL DEFAULT 15,
  `lockout_duration_minutes` int(11) NOT NULL DEFAULT 15,
  `allowed_ip_ranges` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_ip_ranges`)),
  `blocked_ip_ranges` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`blocked_ip_ranges`)),
  `geo_blocking_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `allowed_countries` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_countries`)),
  `sso_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sso_settings`)),
  `audit_logging_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `audit_retention_days` int(11) NOT NULL DEFAULT 365,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_company_id_unique` (`company_id`),
  KEY `settings_company_id_index` (`company_id`),
  CONSTRAINT `settings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `slas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `slas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `critical_response_minutes` int(11) NOT NULL DEFAULT 60,
  `high_response_minutes` int(11) NOT NULL DEFAULT 240,
  `medium_response_minutes` int(11) NOT NULL DEFAULT 480,
  `low_response_minutes` int(11) NOT NULL DEFAULT 1440,
  `critical_resolution_minutes` int(11) NOT NULL DEFAULT 240,
  `high_resolution_minutes` int(11) NOT NULL DEFAULT 1440,
  `medium_resolution_minutes` int(11) NOT NULL DEFAULT 4320,
  `low_resolution_minutes` int(11) NOT NULL DEFAULT 10080,
  `business_hours_start` time NOT NULL DEFAULT '09:00:00',
  `business_hours_end` time NOT NULL DEFAULT '17:00:00',
  `business_days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '["monday","tuesday","wednesday","thursday","friday"]' CHECK (json_valid(`business_days`)),
  `timezone` varchar(255) NOT NULL DEFAULT 'UTC',
  `coverage_type` enum('24/7','business_hours','custom') NOT NULL DEFAULT 'business_hours',
  `holiday_coverage` tinyint(1) NOT NULL DEFAULT 0,
  `exclude_weekends` tinyint(1) NOT NULL DEFAULT 1,
  `escalation_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `escalation_levels` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`escalation_levels`)),
  `breach_warning_percentage` int(11) NOT NULL DEFAULT 80,
  `uptime_percentage` decimal(5,2) NOT NULL DEFAULT 99.50,
  `first_call_resolution_target` decimal(5,2) NOT NULL DEFAULT 75.00,
  `customer_satisfaction_target` decimal(5,2) NOT NULL DEFAULT 90.00,
  `notify_on_breach` tinyint(1) NOT NULL DEFAULT 1,
  `notify_on_warning` tinyint(1) NOT NULL DEFAULT 1,
  `notification_emails` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notification_emails`)),
  `effective_from` date NOT NULL DEFAULT '2025-08-13',
  `effective_to` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `slas_company_id_is_default_index` (`company_id`,`is_default`),
  KEY `slas_company_id_is_active_index` (`company_id`,`is_active`),
  KEY `slas_effective_from_effective_to_index` (`effective_from`,`effective_to`),
  CONSTRAINT `slas_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscription_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscription_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `stripe_price_id` varchar(255) NOT NULL,
  `price_monthly` decimal(10,2) NOT NULL,
  `user_limit` int(11) DEFAULT NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscription_plans_stripe_price_id_unique` (`stripe_price_id`),
  KEY `subscription_plans_is_active_index` (`is_active`),
  KEY `subscription_plans_is_active_sort_order_index` (`is_active`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` int(11) NOT NULL DEFAULT 1,
  `color` varchar(7) DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tags_company_id_name_unique` (`company_id`,`name`),
  KEY `tags_company_id_name_index` (`company_id`,`name`),
  KEY `tags_type_index` (`type`),
  KEY `tags_archived_at_index` (`archived_at`),
  CONSTRAINT `tags_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `task_checklist_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_checklist_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `task_id` bigint(20) unsigned NOT NULL,
  `description` varchar(255) NOT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `completed_at` datetime DEFAULT NULL,
  `completed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `task_checklist_items_task_id_foreign` (`task_id`),
  KEY `task_checklist_items_completed_by_foreign` (`completed_by`),
  KEY `task_checklist_items_company_id_task_id_index` (`company_id`,`task_id`),
  KEY `task_checklist_items_company_id_is_completed_index` (`company_id`,`is_completed`),
  CONSTRAINT `task_checklist_items_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_checklist_items_completed_by_foreign` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `task_checklist_items_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `project_tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `task_dependencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_dependencies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `task_id` bigint(20) unsigned NOT NULL,
  `depends_on_task_id` bigint(20) unsigned NOT NULL,
  `dependency_type` enum('finish_to_start','start_to_start','finish_to_finish','start_to_finish') NOT NULL DEFAULT 'finish_to_start',
  `lag_days` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_dependencies_task_id_depends_on_task_id_unique` (`task_id`,`depends_on_task_id`),
  KEY `task_dependencies_depends_on_task_id_foreign` (`depends_on_task_id`),
  KEY `task_dependencies_company_id_task_id_index` (`company_id`,`task_id`),
  KEY `task_dependencies_company_id_depends_on_task_id_index` (`company_id`,`depends_on_task_id`),
  CONSTRAINT `task_dependencies_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_dependencies_depends_on_task_id_foreign` FOREIGN KEY (`depends_on_task_id`) REFERENCES `project_tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_dependencies_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `project_tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `task_watchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_watchers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `task_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `email_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_watchers_task_id_user_id_unique` (`task_id`,`user_id`),
  KEY `task_watchers_user_id_foreign` (`user_id`),
  KEY `task_watchers_company_id_task_id_index` (`company_id`,`task_id`),
  KEY `task_watchers_company_id_user_id_index` (`company_id`,`user_id`),
  CONSTRAINT `task_watchers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_watchers_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `project_tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_watchers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tax_jurisdictions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_jurisdictions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `jurisdiction_type` enum('federal','state','county','city','municipality','special_district','zip_code') NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(10) NOT NULL,
  `fips_code` varchar(10) DEFAULT NULL,
  `state_code` varchar(2) DEFAULT NULL,
  `county_code` varchar(10) DEFAULT NULL,
  `city_code` varchar(10) DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `zip_codes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`zip_codes`)),
  `boundaries` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`boundaries`)),
  `parent_jurisdiction_id` bigint(20) unsigned DEFAULT NULL,
  `authority_name` varchar(255) NOT NULL,
  `authority_contact` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `filing_requirements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filing_requirements`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `priority` smallint(5) unsigned NOT NULL DEFAULT 100,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tax_jurisdictions_code_unique` (`code`),
  KEY `tax_jurisdictions_company_id_is_active_index` (`company_id`,`is_active`),
  KEY `tax_jurisdictions_jurisdiction_type_is_active_index` (`jurisdiction_type`,`is_active`),
  KEY `tax_jurisdictions_state_code_is_active_index` (`state_code`,`is_active`),
  KEY `tax_jurisdictions_fips_code_index` (`fips_code`),
  KEY `tax_jurisdictions_zip_code_index` (`zip_code`),
  KEY `tax_jurisdictions_parent_jurisdiction_id_index` (`parent_jurisdiction_id`),
  KEY `tax_jurisdictions_priority_index` (`priority`),
  CONSTRAINT `tax_jurisdictions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tax_jurisdictions_parent_jurisdiction_id_foreign` FOREIGN KEY (`parent_jurisdiction_id`) REFERENCES `tax_jurisdictions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tax_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `tax_category_id` bigint(20) unsigned DEFAULT NULL,
  `profile_type` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `required_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`required_fields`)),
  `tax_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`tax_types`)),
  `calculation_engine` varchar(100) NOT NULL,
  `field_definitions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`field_definitions`)),
  `validation_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`validation_rules`)),
  `default_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`default_values`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `priority` int(11) NOT NULL DEFAULT 100,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tax_profiles_company_id_index` (`company_id`),
  KEY `tax_profiles_category_id_index` (`category_id`),
  KEY `tax_profiles_tax_category_id_index` (`tax_category_id`),
  KEY `tax_profiles_profile_type_index` (`profile_type`),
  KEY `tax_profiles_company_id_is_active_index` (`company_id`,`is_active`),
  KEY `tax_profiles_company_id_priority_index` (`company_id`,`priority`),
  CONSTRAINT `tax_profiles_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tax_profiles_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `taxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `taxes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `percent` double NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `taxes_name_index` (`name`),
  KEY `taxes_company_id_index` (`company_id`),
  KEY `taxes_percent_index` (`percent`),
  KEY `taxes_company_id_name_index` (`company_id`,`name`),
  KEY `taxes_archived_at_index` (`archived_at`),
  CONSTRAINT `taxes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ticket_calendar_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_calendar_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `ticket_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `all_day` tinyint(1) NOT NULL DEFAULT 0,
  `attendees` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attendees`)),
  `location` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_calendar_events_ticket_id_foreign` (`ticket_id`),
  KEY `ticket_calendar_events_company_id_ticket_id_index` (`company_id`,`ticket_id`),
  KEY `ticket_calendar_events_company_id_start_time_index` (`company_id`,`start_time`),
  CONSTRAINT `ticket_calendar_events_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_calendar_events_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ticket_priority_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_priority_queue` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `ticket_id` bigint(20) unsigned NOT NULL,
  `priority_score` int(11) NOT NULL,
  `queue_time` datetime NOT NULL,
  `scoring_factors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`scoring_factors`)),
  `is_escalated` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_priority_queue_company_id_ticket_id_unique` (`company_id`,`ticket_id`),
  KEY `ticket_priority_queue_ticket_id_foreign` (`ticket_id`),
  KEY `ticket_priority_queue_company_id_priority_score_index` (`company_id`,`priority_score`),
  KEY `ticket_priority_queue_company_id_queue_time_index` (`company_id`,`queue_time`),
  CONSTRAINT `ticket_priority_queue_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_priority_queue_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ticket_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_replies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `reply` longtext NOT NULL,
  `type` varchar(10) NOT NULL,
  `time_worked` time DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `replied_by` bigint(20) unsigned NOT NULL,
  `ticket_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_replies_ticket_id_index` (`ticket_id`),
  KEY `ticket_replies_company_id_index` (`company_id`),
  KEY `ticket_replies_replied_by_index` (`replied_by`),
  KEY `ticket_replies_type_index` (`type`),
  KEY `ticket_replies_ticket_id_type_index` (`ticket_id`,`type`),
  KEY `ticket_replies_company_id_ticket_id_index` (`company_id`,`ticket_id`),
  KEY `ticket_replies_time_worked_index` (`time_worked`),
  KEY `ticket_replies_archived_at_index` (`archived_at`),
  CONSTRAINT `ticket_replies_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_replies_replied_by_foreign` FOREIGN KEY (`replied_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_replies_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ticket_status_transitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_status_transitions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `from_status` varchar(255) NOT NULL,
  `to_status` varchar(255) NOT NULL,
  `transition_name` varchar(255) NOT NULL,
  `requires_approval` tinyint(1) NOT NULL DEFAULT 0,
  `allowed_roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_roles`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_status_unique` (`company_id`,`from_status`,`to_status`),
  KEY `ticket_status_transitions_company_id_from_status_index` (`company_id`,`from_status`),
  CONSTRAINT `ticket_status_transitions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ticket_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `default_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`default_fields`)),
  `instructions` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_templates_company_id_is_active_index` (`company_id`,`is_active`),
  KEY `ticket_templates_company_id_category_index` (`company_id`,`category`),
  CONSTRAINT `ticket_templates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ticket_time_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_time_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `ticket_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `work_date` date DEFAULT NULL,
  `description` text NOT NULL,
  `work_performed` text DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `minutes` int(11) DEFAULT NULL,
  `hours` decimal(8,2) DEFAULT NULL,
  `hours_worked` decimal(8,2) DEFAULT NULL,
  `minutes_worked` int(11) DEFAULT NULL,
  `hours_billed` decimal(8,2) DEFAULT NULL,
  `billable` tinyint(1) NOT NULL DEFAULT 1,
  `hourly_rate` decimal(10,2) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `entry_type` varchar(255) NOT NULL DEFAULT 'manual',
  `work_type` varchar(255) DEFAULT NULL,
  `rate_type` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `submitted_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approval_notes` text DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `rejected_by` bigint(20) unsigned DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `invoiced_at` timestamp NULL DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `is_billable` tinyint(1) NOT NULL DEFAULT 1,
  `is_billed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_time_entries_ticket_id_foreign` (`ticket_id`),
  KEY `ticket_time_entries_user_id_foreign` (`user_id`),
  KEY `ticket_time_entries_company_id_ticket_id_index` (`company_id`,`ticket_id`),
  KEY `ticket_time_entries_company_id_user_id_index` (`company_id`,`user_id`),
  KEY `ticket_time_entries_company_id_is_billable_index` (`company_id`,`is_billable`),
  CONSTRAINT `ticket_time_entries_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_time_entries_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_time_entries_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ticket_workflows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_workflows` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`conditions`)),
  `actions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`actions`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_workflows_company_id_is_active_index` (`company_id`,`is_active`),
  KEY `ticket_workflows_company_id_sort_order_index` (`company_id`,`sort_order`),
  CONSTRAINT `ticket_workflows_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tickets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `prefix` varchar(255) DEFAULT NULL,
  `number` int(11) NOT NULL,
  `source` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `details` longtext NOT NULL,
  `priority` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `billable` tinyint(1) NOT NULL DEFAULT 0,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `onsite` tinyint(1) NOT NULL DEFAULT 0,
  `vendor_ticket_number` varchar(255) DEFAULT NULL,
  `feedback` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `closed_by` bigint(20) unsigned DEFAULT NULL,
  `vendor_id` bigint(20) unsigned DEFAULT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `contact_id` bigint(20) unsigned DEFAULT NULL,
  `location_id` bigint(20) unsigned DEFAULT NULL,
  `asset_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tickets_prefix_number_unique` (`prefix`,`number`),
  KEY `tickets_number_index` (`number`),
  KEY `tickets_status_index` (`status`),
  KEY `tickets_priority_index` (`priority`),
  KEY `tickets_client_id_index` (`client_id`),
  KEY `tickets_company_id_index` (`company_id`),
  KEY `tickets_assigned_to_index` (`assigned_to`),
  KEY `tickets_created_by_index` (`created_by`),
  KEY `tickets_client_id_status_index` (`client_id`,`status`),
  KEY `tickets_assigned_to_status_index` (`assigned_to`,`status`),
  KEY `tickets_company_id_client_id_index` (`company_id`,`client_id`),
  KEY `tickets_billable_index` (`billable`),
  KEY `tickets_schedule_index` (`scheduled_at`),
  KEY `tickets_closed_at_index` (`closed_at`),
  KEY `tickets_archived_at_index` (`archived_at`),
  KEY `tickets_closed_by_foreign` (`closed_by`),
  KEY `tickets_vendor_id_foreign` (`vendor_id`),
  KEY `tickets_contact_id_foreign` (`contact_id`),
  KEY `tickets_location_id_foreign` (`location_id`),
  KEY `tickets_asset_id_foreign` (`asset_id`),
  KEY `tickets_project_id_foreign` (`project_id`),
  KEY `tickets_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `tickets_asset_id_foreign` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tickets_closed_by_foreign` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tickets_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tickets_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_dashboard_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_dashboard_configs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `dashboard_name` varchar(255) NOT NULL DEFAULT 'main',
  `layout` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`layout`)),
  `widgets` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`widgets`)),
  `preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`preferences`)),
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_shared` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_dashboard_configs_user_id_dashboard_name_unique` (`user_id`,`dashboard_name`),
  KEY `user_dashboard_configs_company_id_is_shared_index` (`company_id`,`is_shared`),
  CONSTRAINT `user_dashboard_configs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_dashboard_configs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `permission_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `granted` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_permissions_user_id_permission_id_company_id_unique` (`user_id`,`permission_id`,`company_id`),
  KEY `user_permissions_permission_id_foreign` (`permission_id`),
  KEY `user_permissions_company_id_foreign` (`company_id`),
  KEY `user_permissions_user_id_company_id_index` (`user_id`,`company_id`),
  CONSTRAINT `user_permissions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_permissions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_roles_user_id_role_id_company_id_unique` (`user_id`,`role_id`,`company_id`),
  KEY `user_roles_role_id_foreign` (`role_id`),
  KEY `user_roles_company_id_foreign` (`company_id`),
  KEY `user_roles_user_id_company_id_index` (`user_id`,`company_id`),
  CONSTRAINT `user_roles_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_roles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `role` int(11) NOT NULL DEFAULT 1,
  `remember_me_token` varchar(255) DEFAULT NULL,
  `force_mfa` tinyint(1) NOT NULL DEFAULT 0,
  `records_per_page` int(11) NOT NULL DEFAULT 10,
  `dashboard_financial_enable` tinyint(1) NOT NULL DEFAULT 0,
  `dashboard_technical_enable` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_settings_user_id_index` (`user_id`),
  KEY `user_settings_company_id_index` (`company_id`),
  KEY `user_settings_role_index` (`role`),
  KEY `user_settings_user_id_role_index` (`user_id`,`role`),
  KEY `user_settings_company_id_role_index` (`company_id`,`role`),
  CONSTRAINT `user_settings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_widget_instances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_widget_instances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_dashboard_config_id` bigint(20) unsigned NOT NULL,
  `dashboard_widget_id` bigint(20) unsigned NOT NULL,
  `instance_id` varchar(255) NOT NULL,
  `position_x` int(11) NOT NULL,
  `position_y` int(11) NOT NULL,
  `width` int(11) NOT NULL,
  `height` int(11) NOT NULL,
  `custom_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_config`)),
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
  `refresh_interval` int(11) DEFAULT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `is_collapsed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_widget_instances_instance_id_unique` (`instance_id`),
  KEY `user_widget_instances_user_dashboard_config_id_foreign` (`user_dashboard_config_id`),
  KEY `user_widget_instances_dashboard_widget_id_foreign` (`dashboard_widget_id`),
  KEY `user_widget_instances_instance_id_index` (`instance_id`),
  CONSTRAINT `user_widget_instances_dashboard_widget_id_foreign` FOREIGN KEY (`dashboard_widget_id`) REFERENCES `dashboard_widgets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_widget_instances_user_dashboard_config_id_foreign` FOREIGN KEY (`user_dashboard_config_id`) REFERENCES `user_dashboard_configs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `token` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `specific_encryption_ciphertext` varchar(255) DEFAULT NULL,
  `php_session` varchar(255) DEFAULT NULL,
  `extension_key` varchar(18) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_email_index` (`email`),
  KEY `users_status_index` (`status`),
  KEY `users_company_id_index` (`company_id`),
  KEY `users_email_status_index` (`email`,`status`),
  KEY `users_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `users_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vendors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `contact_name` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `extension` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `hours` varchar(255) DEFAULT NULL,
  `sla` varchar(255) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `template` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `accessed_at` timestamp NULL DEFAULT NULL,
  `client_id` bigint(20) unsigned DEFAULT NULL,
  `template_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vendors_name_index` (`name`),
  KEY `vendors_email_index` (`email`),
  KEY `vendors_client_id_index` (`client_id`),
  KEY `vendors_company_id_index` (`company_id`),
  KEY `vendors_template_index` (`template`),
  KEY `vendors_company_id_template_index` (`company_id`,`template`),
  KEY `vendors_archived_at_index` (`archived_at`),
  KEY `vendors_template_id_foreign` (`template_id`),
  CONSTRAINT `vendors_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vendors_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vendors_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `widget_data_cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `widget_data_cache` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `widget_type` varchar(255) NOT NULL,
  `cache_key` varchar(255) NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `widget_data_cache_cache_key_unique` (`cache_key`),
  KEY `widget_data_cache_company_id_widget_type_index` (`company_id`,`widget_type`),
  KEY `widget_data_cache_expires_at_index` (`expires_at`),
  CONSTRAINT `widget_data_cache_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

/*M!999999\- enable the sandbox mode */ 
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2024_01_01_000001_create_all_tables_v1',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2024_01_01_000002_create_permissions_system',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2024_01_01_000003_create_subscription_plans',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2024_01_01_000004_create_missing_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2024_01_01_000005_create_advanced_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2024_01_01_000006_create_enterprise_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2025_08_11_203138_rename_due_to_due_date_in_invoices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2025_08_11_203208_rename_schedule_to_scheduled_at_in_tickets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2025_08_11_210533_fix_recurring_tickets_table_columns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2025_08_11_210842_add_soft_deletes_to_recurring_tickets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2025_08_11_211414_fix_missing_columns_in_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2025_08_12_100000_create_dashboard_widgets_tables',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2025_08_12_021918_add_date_format_to_settings_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2025_01_01_000002_create_services_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2025_01_01_000003_create_pricing_rules_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2025_01_01_000004_create_product_bundles_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2025_08_12_100001_add_comprehensive_settings_fields',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2025_08_12_043048_extend_products_table_for_advanced_features',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2025_08_12_175908_add_security_fields_to_settings_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2025_08_12_215605_add_contract_permissions',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2025_08_12_225212_create_tax_profiles_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2025_08_12_225233_create_product_tax_data_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2025_08_12_225255_add_tax_profile_to_products_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2025_08_13_000520_create_slas_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2025_08_13_000643_add_sla_id_to_clients_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2025_08_13_001011_migrate_sla_data_from_settings',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2025_08_13_001953_remove_sla_fields_from_settings_table',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2025_08_13_000001_create_contracts_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2025_08_13_000002_create_contract_milestones_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2025_08_13_000003_create_contract_signatures_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2025_08_13_000004_create_contract_approvals_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2025_08_13_000005_create_contract_invoice_pivot_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2025_08_13_051221_create_bouncer_tables',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2025_08_13_051424_migrate_to_bouncer_permissions',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2025_08_13_195251_create_tax_jurisdictions_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2025_08_13_195252_add_tax_engine_fields_to_invoice_items_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2025_08_13_195410_create_service_tax_rates_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2025_08_13_051009_add_database_indexes_for_quotes',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2025_08_13_235346_create_quote_versions_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2025_08_14_001559_add_approval_status_to_quotes_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2025_08_14_024454_create_company_customizations_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2025_08_14_040135_change_cost_column_to_decimal_in_products_table',23);
