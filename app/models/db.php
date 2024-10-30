<?php

namespace Bundler\Models;

if (!defined('ABSPATH')) exit;

class DB
{

    public static $offer_table_name                    = "wbdl_offer";
    public static $offer_products_table_name           = "wbdl_offer_products";
    public static $offer_collections_table_name        = "wbdl_offer_collections";
    public static $volume_discount_table_name          = "wbdl_volume_discount";
    public static $bundle_table_name                   = "wbdl_bundle";
    public static $volume_discount_settings_table_name = "wbdl_volume_discount_settings";

    // current database version
    public static $current_db_version = '4.0';


    /**
     * Get the list of all custom tables starting with `wbdl_*`.
     *
     * @since 1.0.0
     *
     * @return array List of table names.
     */
    public static function get_existing_tables()
    {
        global $wpdb;

        $tables = $wpdb->get_results("SHOW TABLES LIKE '" . $wpdb->prefix . "wbdl_%'", 'ARRAY_N'); // phpcs:ignore

        return !empty($tables) ? wp_list_pluck($tables, 0) : [];
    }


    /**
     * Create tables
     */
    public static function create_tables()
    {
        global $wpdb;

        $installed_db_version = get_option('woobundles_db_version');
        $existing_tables = self::get_existing_tables();

        if (count($existing_tables) > 0) { // existing tables => upgrade the db structure
            if ($installed_db_version && ($installed_db_version != self::$current_db_version)) { // only upgrade if the current version of the db is different than installed version
                // db version >= 2.0
                self::create_offer_table($wpdb);
                if (floatval($installed_db_version) < 3.0) self::db_update_3_0($wpdb);
                self::create_volume_discount_table($wpdb);
                if (floatval($installed_db_version) < 2.1) self::db_update_2_1($wpdb);
                if (floatval($installed_db_version) < 4.0) self::db_update_4_0($wpdb);
                self::create_volume_discount_settings_table($wpdb);
                update_option('woobundles_db_version', self::$current_db_version);
            } else { // same db version
                self::create_offer_table($wpdb);
                self::create_offer_products_table($wpdb);
                self::create_volume_discount_table($wpdb);
                self::create_volume_discount_settings_table($wpdb);
            }
        } else { // no db
            self::create_offer_table($wpdb);
            self::create_offer_products_table($wpdb);
            self::create_volume_discount_table($wpdb);
            self::create_volume_discount_settings_table($wpdb);
            if ($installed_db_version) {
                update_option('woobundles_db_version', self::$current_db_version);
            } else {
                add_option('woobundles_db_version', self::$current_db_version);
            }
        }
    }

    /**
     * Create offer table
     * 
     * @param mixed $wpdb
     * 
     * @return void
     */
    public static function create_offer_table($wpdb)
    {

        $charset_collate = $wpdb->get_charset_collate();

        $offer_table = $wpdb->prefix . self::$offer_table_name;

        $sql = "CREATE TABLE $offer_table ( 
            id mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(255) DEFAULT '' NOT NULL,
            header varchar(255) DEFAULT '' NOT NULL,
            type varchar(255) DEFAULT '' NOT NULL,
            status varchar(10) DEFAULT 'on' NOT NULL,
            priority int(10) DEFAULT 1 NOT NULL,
            apply_on varchar(20) DEFAULT 'specific_products' NOT NULL,
            use_custom_variations varchar(5) DEFAULT 'off' NOT NULL,
            wmc varchar(10) DEFAULT 'off',
            PRIMARY KEY  (id)) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create offer products table
     * 
     * @param mixed $wpdb
     * 
     * @return void
     */
    public static function create_offer_products_table($wpdb)
    {
        $charset_collate = $wpdb->get_charset_collate();

        $offer_products_table = $wpdb->prefix . self::$offer_products_table_name;

        $sql = "CREATE TABLE $offer_products_table ( 
            id mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
            offer_id mediumint(8) unsigned NOT NULL,
            product_id mediumint(8) unsigned NOT NULL,
            PRIMARY KEY  (id),
            KEY offer_id (offer_id),
            KEY product_id (product_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create offer collections table
     * 
     * @param mixed $wpdb
     * 
     * @return void
     */
    public static function create_offer_collections_table($wpdb)
    {
        $charset_collate = $wpdb->get_charset_collate();

        $offer_collections_table = $wpdb->prefix . self::$offer_collections_table_name;

        $sql = "CREATE TABLE $offer_collections_table ( 
            id mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
            offer_id mediumint(8) unsigned NOT NULL,
            collection_id mediumint(8) unsigned NOT NULL,
            PRIMARY KEY  (id),
            KEY offer_id (offer_id),
            KEY collection_id (collection_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create Volume Discount table
     * 
     * @param mixed $wpdb
     * 
     * @return void
     */
    public static function create_volume_discount_table($wpdb)
    {
        $charset_collate = $wpdb->get_charset_collate();

        $volume_discount_table = $wpdb->prefix . self::$volume_discount_table_name;

        $sql = "CREATE TABLE $volume_discount_table ( 
            id mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
            offer_id INT(11) DEFAULT NULL,
            title varchar(255) DEFAULT '' NOT NULL,
            image_url varchar(2048) DEFAULT '',
            number_of_products varchar(4) DEFAULT '1' NOT NULL,
            number_of_products_max varchar(4) DEFAULT '1' NOT NULL,
            sale_price varchar(36) DEFAULT '' NOT NULL,
            regular_price varchar(36) DEFAULT '',
            wmc varchar(10) DEFAULT 'off',
            wmc_sale_price varchar(255) DEFAULT '',
            wmc_regular_price varchar(255) DEFAULT '',
            discount_type varchar(15) DEFAULT 'fixed_price',
            discount_value varchar(8) DEFAULT NULL,
            discount_method varchar(15) DEFAULT NULL,
            use_custom_variations varchar(10) DEFAULT 'off',
            option1_name varchar(255) DEFAULT '',
            option1_value varchar(255) DEFAULT '',
            option2_name varchar(255) DEFAULT '',
            option2_value varchar(255) DEFAULT '',
            add_messages varchar(10) DEFAULT 'off',
            message varchar(255) DEFAULT '',
            discount_rule varchar(255) DEFAULT '',
            preselected_offer varchar(10) DEFAULT 'off',
            message_effect varchar(10) DEFAULT '',
            show_price_per_unit varchar(10) DEFAULT 'off',
            price_per_unit_text varchar(50) DEFAULT '/ unit',
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * create volume discount settings table
     * 
     * @param mixed $wpdb
     * 
     * @return void
     */
    public static function create_volume_discount_settings_table($wpdb)
    {
        $charset_collate = $wpdb->get_charset_collate();

        $volume_discount_settings_table = $wpdb->prefix . self::$volume_discount_settings_table_name;

        $sql = "CREATE TABLE $volume_discount_settings_table ( 
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `design` varchar(20) DEFAULT 'classic',
            `design_mobile` varchar(20) DEFAULT 'classic',
            `header_text` varchar(220) DEFAULT '',
            `header_show` varchar(10) DEFAULT 'off',
            `qty_selector` varchar(10) DEFAULT 'off',
            `var_form` varchar(10) DEFAULT 'off',
            `cart_redirect` varchar(10) DEFAULT 'off',
            `checkout_redirect` varchar(10) DEFAULT 'off',
            `atc_price_text` varchar(10) DEFAULT 'off',
            `background_color` varchar(20) DEFAULT '#ffffff',
            `background_color_active` varchar(20) DEFAULT '#f2f2fe',
            `border_color` varchar(20) DEFAULT '#dbdbdb',
            `border_color_active` varchar(20) DEFAULT '#565add',
            `title_color` varchar(20) DEFAULT '#000000',
            `sale_price_color` varchar(20) DEFAULT '#565add',
            `regular_price_color` varchar(20) DEFAULT '#afafaf',
            `message1_color` varchar(20) DEFAULT '#565add',
            `message2_color` varchar(20) DEFAULT '#ffffff',
            `message2_background_color` varchar(20) DEFAULT '#565add',
            `attribute_name_color` varchar(20) DEFAULT '#000000',
            `dropdown_text_color` varchar(20) DEFAULT '#000000',
            `dropdown_background_color` varchar(20) DEFAULT '#ffffff',
            `radio_show` varchar(3) DEFAULT 'on',
            `header_font` varchar(250) DEFAULT NULL,
            `title_font` varchar(250) DEFAULT NULL,
            `price_font` varchar(250) DEFAULT NULL,
            `message1_font` varchar(250) DEFAULT NULL,
            `message2_font` varchar(250) DEFAULT NULL,
            `attribute_name_font` varchar(250) DEFAULT NULL,
            `dropdown_font` varchar(250) DEFAULT NULL,
            `atc_button_font` varchar(250) DEFAULT NULL,
            `atc_button_background_color` varchar(20) DEFAULT NULL,
            `atc_button_border_color` varchar(20) DEFAULT NULL,
            `atc_button_text_color` varchar(20) DEFAULT NULL,
            `atc_button_show_custom_text` varchar(20) DEFAULT NULL,
            `atc_button_custom_text` varchar(250) DEFAULT NULL,
            PRIMARY KEY  (id)) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Initialise settings with default values
     * 
     * @param mixed $wpdb
     * 
     * @return void
     */
    public static function initialize_volume_discount_settings($wpdb)
    {
        $volume_discount_settings_table = $wpdb->prefix . self::$volume_discount_settings_table_name;
        $wpdb->insert(
            $volume_discount_settings_table,
            array(
                'id'                                => '1',
                'design'                            => 'classic',
                'design_mobile'                     => 'classic',
                'bundles_title'                     => 'Select your offer',
                'title_show'                        => 'off',
                'qty_selector'                      => 'off',
                'cart_redirect'                     => 'off',
                'checkout_redirect'                 => 'off',
                'atc_price_text'                    => 'off',
                'background_color'                  => '#ffffff00',
                'background_color_active'           => '#fafbff',
                'border_color'                      => '#c6c6c6',
                'border_color_active'               => '#565add',
                'heading_color'                     => '#000000',
                'sale_price_color'                  => '#565add',
                'regular_price_color'               => '#afafaf',
                'message1_color'                    => '#565add',
                'message2_color'                    => '#000000',
                'message2_background_color'         => '#565add',
                'attribute_name_color'              => '#000000',
                'attribute_values_text_color'       => '#000000',
                'attribute_values_background_color' => '#ffffff00',

            )
        );
    }

    /**
     * DB migration to the version 2.0 which separates the offers from the discount options and renames a few tables
     * 
     * @param mixed $wpdb
     * 
     * @return void
     */
    public static function db_update_2_0($wpdb)
    {
        $volume_discount_table_old = $wpdb->prefix . 'wbdl_bundle_data';
        $settings_table_old = $wpdb->prefix . 'wbdl_bundle_settings';

        $volume_discount_table = $wpdb->prefix . self::$volume_discount_table_name;
        $settings_table = $wpdb->prefix . self::$volume_discount_settings_table_name;
        $offers_table = $wpdb->prefix . self::$offer_table_name;

        $wpdb->query('START TRANSACTION'); // Start a transaction

        try {
            // Step 0: Rename the tables
            $query = "ALTER TABLE $volume_discount_table_old RENAME TO $volume_discount_table";
            $wpdb->query($query);
            $query = "ALTER TABLE $settings_table_old RENAME TO $settings_table";
            $wpdb->query($query);

            // Step 1: Add the offer_id column to the "Bundles" table
            $query = "ALTER TABLE $volume_discount_table ADD COLUMN offer_id INT(11) DEFAULT NULL AFTER id";
            $wpdb->query($query);

            // Step 2: Create offers in the "Offers" table for each bundle
            $query = "INSERT INTO $offers_table (title, product_id, type, status) SELECT CONCAT('Offer for product ', product_id), product_id, 'Bundle', 'on' FROM $volume_discount_table GROUP BY product_id";
            $wpdb->query($query);

            // Step 3: Set priority = offer id
            $query = "UPDATE $offers_table SET $offers_table.priority = $offers_table.id";
            $wpdb->query($query);

            // Step 4: Update the offer_id column in the "Bundles" table
            $query = "UPDATE $volume_discount_table INNER JOIN $offers_table ON $volume_discount_table.product_id = $offers_table.product_id SET $volume_discount_table.offer_id = $offers_table.id";
            $wpdb->query($query);

            // Step 5: Remove the product_id column from the "Bundles" table
            $query = "ALTER TABLE $volume_discount_table DROP COLUMN product_id";
            $wpdb->query($query);
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK'); // Roll back the transaction if there's an error
        }
    }


    /**
     * DB migration to the version 2.1 updates only the columns names in the settings table
     * 
     * @param mixed $wpdb
     * 
     * @return void
     */
    public static function db_update_2_1($wpdb)
    {
        $settings_table = $wpdb->prefix . self::$volume_discount_settings_table_name;

        $queries = array(
            "ALTER TABLE $settings_table MODIFY COLUMN design_mobile varchar(20) AFTER design;",
            "ALTER TABLE $settings_table MODIFY COLUMN background_color varchar(20) AFTER atc_price_text;",
            "ALTER TABLE $settings_table MODIFY COLUMN background_color_active varchar(20) AFTER background_color;",
            "ALTER TABLE $settings_table MODIFY COLUMN border_color varchar(20) AFTER background_color_active;",
            "ALTER TABLE $settings_table MODIFY COLUMN border_color_active varchar(20) AFTER border_color;",
            "ALTER TABLE $settings_table MODIFY COLUMN title_color varchar(20) AFTER border_color_active;",
            "ALTER TABLE $settings_table MODIFY COLUMN sale_price_color varchar(20) AFTER title_color;",
            "ALTER TABLE $settings_table MODIFY COLUMN regular_price_color varchar(20) AFTER sale_price_color;",
            "ALTER TABLE $settings_table MODIFY COLUMN message1_color varchar(20) AFTER regular_price_color;",
            "ALTER TABLE $settings_table MODIFY COLUMN message2_color varchar(20) AFTER message1_color;"
        );

        foreach ($queries as $query) {
            $wpdb->query($query);
        }
    }

    /**
     * DB migration to the version 3.0 = renaming the volume discount table
     * 
     * @param mixed $wpdb
     * 
     * @return void
     */
    public static function db_update_3_0($wpdb)
    {
        $offer_table_name = $wpdb->prefix . self::$offer_table_name;

        $volume_discount_table_old = $wpdb->prefix . 'wbdl_bundle';
        $volume_discount_table_new = $wpdb->prefix . self::$volume_discount_table_name;

        $volume_discount_settings_table_old = $wpdb->prefix . 'wbdl_settings';
        $volume_discount_settings_table_new = $wpdb->prefix . self::$volume_discount_settings_table_name;

        // check if the volume discount table name already exists. If not, rename the old table.
        $table_exists = $wpdb->get_results("SHOW TABLES LIKE '$volume_discount_table_new'");
        if (!$table_exists) {
            $query = "RENAME TABLE $volume_discount_table_old TO $volume_discount_table_new";
            $wpdb->query($query);
        }

        // check if the volume discount settings table name already exists. If not, rename the old table.
        $table_exists = $wpdb->get_results("SHOW TABLES LIKE '$volume_discount_settings_table_new'");
        if (!$table_exists) {
            $query = "RENAME TABLE $volume_discount_settings_table_old TO $volume_discount_settings_table_new";
            $wpdb->query($query);
        }

        // step 2: rename some columns in tables

        $queries = array(
            "ALTER TABLE $offer_table_name MODIFY COLUMN header varchar(255) AFTER type;",
            // "ALTER TABLE $offer_table_name MODIFY COLUMN product_id2 varchar(255) AFTER type;",
            // "ALTER TABLE $volume_discount_table_new MODIFY COLUMN number_of_products_max varchar(4) AFTER number_of_products;",
            "ALTER TABLE $volume_discount_table_new MODIFY COLUMN wmc_sale_price varchar(255) AFTER regular_price;",
            "ALTER TABLE $volume_discount_table_new MODIFY COLUMN wmc_regular_price varchar(255) AFTER wmc_sale_price;",
            // "ALTER TABLE $volume_discount_table_new MODIFY COLUMN discount_method varchar(8) AFTER wmc_regular_price;",
            // "ALTER TABLE $volume_discount_table_new MODIFY COLUMN discount_value varchar(8) AFTER wmc_regular_price;",
            "ALTER TABLE $volume_discount_table_new MODIFY COLUMN add_messages varchar(10) AFTER option2_value;",
        );

        // Check if the new column already exists
        // $query = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
        // WHERE TABLE_NAME = '$volume_discount_table_new' 
        // AND COLUMN_NAME = 'bundles_title';";
        // $title_column_exists = (bool)$wpdb->get_var($query);

        // if ($title_column_exists)
        //     array_push($queries, "ALTER TABLE $volume_discount_table_new CHANGE bundles_title title varchar(255);");

        foreach ($queries as $query) {
            $wpdb->query($query);
        }

        // step 3: Update the type column value in the offer table : rename qty_discount and bundle to volume_discount.
        $wpdb->update(
            $offer_table_name,
            array('type' => 'volume_discount'), // New value
            array('type' => 'bundle'), // Old value
            array('%s'),
            array('%s')
        );

        $wpdb->update(
            $offer_table_name,
            array('type' => 'volume_discount'), // New value
            array('type' => 'qty_discount'), // Old value
            array('%s'),
            array('%s')
        );
    }

    public static function db_update_4_0($wpdb)
    {
        $offer_table                    = $wpdb->prefix . DB::$offer_table_name;
        $offer_products_table           = $wpdb->prefix . DB::$offer_products_table_name;
        $volume_discount_settings_table = $wpdb->prefix . self::$volume_discount_settings_table_name;

        // Step 0: Rename columns in the settings page
        $wpdb->query("ALTER TABLE $volume_discount_settings_table CHANGE `bundles_title` `header_text` varchar(220) DEFAULT NULL;");
        $wpdb->query("ALTER TABLE $volume_discount_settings_table CHANGE `title_show` `header_show` varchar(10) DEFAULT 'off';");

        // Step 1: Create offer_products table if it doesn't exist
        self::create_offer_products_table($wpdb);

        // Step 2: Migrate product_id to offer_products table
        $offers = $wpdb->get_results("SELECT id, product_id FROM $offer_table WHERE product_id IS NOT NULL");

        foreach ($offers as $offer) {
            $wpdb->insert(
                $offer_products_table,
                array(
                    'offer_id' => $offer->id,
                    'product_id' => $offer->product_id
                ),
                array(
                    '%d',
                    '%d'
                )
            );
        }

        // Step 3: Remove product_id and product_id2 columns and update schema
        $wpdb->query("ALTER TABLE $offer_table DROP COLUMN product_id");
        $wpdb->query("ALTER TABLE $offer_table DROP COLUMN product_id2");
    }
}
