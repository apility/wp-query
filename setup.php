<?php

defined('ABSPATH') or die;

const WPQUERY_TABLE_NAME = "jsondumper";
const WPQUERY_PRIMARY = "conf_key";
const WPQUERY_VALUE = "conf_val";
const WPQUERY_SECRET = "SECRET_KEY";

$plugin_installed = false;

function on_install_notificaiton()
{
    ?>
    <div class="notice notice-success is-dismissible">
        <p>Generated API Key: <b><?php echo get_key(WPQUERY_SECRET);?></b></p>
    </div>
    <?php
}

function setup_jsondumper()
{
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    $table = get_table();
    $conf_key = getPrimaryKey();
    $conf_val = getValueKey();
    
    $query = "CREATE TABLE ${table} (
      conf_key varchar(255) NOT NULL UNIQUE,
      conf_val varchar(255) NOT NULL,
      PRIMARY KEY  (conf_key)
    ) ${charset};";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($query);
    if (!get_key(WPQUERY_SECRET)) {
        $SECRET_KEY = bin2hex(openssl_random_pseudo_bytes(16));
        set_key(WPQUERY_SECRET, $SECRET_KEY);
    }
}

function getPrimaryKey()
{
    return WPQUERY_PRIMARY;
}

function getValueKey()
{
    return WPQUERY_VALUE;
}

function get_table()
{
    global $wpdb;
    return $wpdb->prefix . WPQUERY_TABLE_NAME;
}

function get_key($key)
{
    global $wpdb;
    $table = get_table();
    $conf_key = getPrimaryKey();
    $conf_val = getValueKey();
    $values = $wpdb->get_results($wpdb->prepare("SELECT ${conf_val} FROM ${table} WHERE ${conf_key} = %s", $key));
    if (!count($values)) {
        return null;
    }
    return $values[0]->${conf_val};
}

function set_key($key, $value)
{
    global $wpdb;
    $table = get_table();
    $conf_key = getPrimaryKey();
    $conf_val = getValueKey();
    if (get_key($key)) {
        $values = $wpdb->get_results($wpdb->prepare("UPDATE ${table} SET ${conf_val} = %s WHERE ${conf_key} = %s", $value, $key));
    } else {
        $wpdb->get_results($wpdb->prepare("INSERT INTO ${table} (${conf_key}, ${conf_val}) VALUES (%s, %s)", $key, $value));
    }
}
