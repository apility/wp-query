<?php

defined('ABSPATH') or die;

class WPQueryPlugin
{

    private $db;
    private $table;
    private $config;
    private $cache = [];

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        
        $this->config = (object) require(WPQUERY_ROOT . 'config.php');

        $this->addMenu();
        $this->registerRoutes();
        $this->flashNotification();
    }

    /**
     * Performs first time installation of Plugin
     * Generates API key if not already exists
     *
     * @return void
     */
    public function install()
    {
        $secret = $this->config->secret;

        if (!$this->getKey($secret)) {
            $apiKey = $this->generateKey();
            $this->setKey($secret, $apiKey);
            $this->setKey('wpquery_notification', json_encode([
                'title' => 'API Key generated: ',
                'message' => $apiKey,
                'type' => 'success'
            ]));
        }
    }

    /**
     * Uninstalls plugin
     *
     * @return void
     */
    public function uninstall()
    {
        $this->deleteKey($this->$config->$secret);
    }

    /**
     * Registers REST routes if correct API key is provided
     *
     * @return void
     */
    public function registerRoutes()
    {
        if (isset($_GET['apikey']) && $_GET['apikey'] == $this->getKey($this->config->secret)) {
            add_action('rest_api_init', function () {
                foreach ($this->config->routes as $route => $handler) {
                    register_rest_route(
                            $this->config->namespace,
                            $route, [
                                'methods' => 'GET',
                                'callback' => [$this, $handler]
                            ]
                        );
                }
            });
        }
    }

    /**
     * Generates a unique API key
     *
     * @return string
     */
    private function generateKey()
    {
        return bin2hex(openssl_random_pseudo_bytes(16));
    }

    /**
     * Deletes a config key
     *
     * @param string $configKey
     * @return void
     */
    private function deleteKey($configKey)
    {
        delete_option($configKey);
    }

    /**
     * Returns a config key value
     *
     * @param string $configKey
     * @return string
     */
    private function getKey($configKey)
    {
        return get_option($configKey);
    }

    /**
     * Sets a config key value
     *
     * @param string $configKey
     * @param string $configVal
     */
    private function setKey($configKey, $configVal)
    {
        update_option($configKey, $configVal);
    }

    /**
     * Lists all DB tables for this WP instance
     * Strips the db prefix and hides WPQuery table
     *
     * @return array
     */
    public function listTables()
    {
        if (count($this->cache)) {
            return array_map(function ($table, $fields) {
                return $table;
            }, array_keys($this->cache), $this->cache);
        }
        $tables = array_map(function ($table) {
            return str_replace($this->db->prefix, '', $table->table_name);
        }, array_filter(
            $this->query(
                "SELECT table_name FROM information_schema.tables WHERE table_name LIKE %s",
                $this->db->prefix . '%'
            ),
            function ($table) {
                $name = str_replace($this->db->prefix, '', $table->table_name);
                return $name != $this->config->table && !empty($name);
            }
        ));
        $list = [];
        foreach ($tables as $key => $table) {
            $list[] = $table;
        }
        $this->cache = $list;
        return $list;
    }

    /**
     * Returns field names for a given DB table
     *
     * @return array
     */
    private function getFields($table)
    {
        in_array($table, $this->listTables()) or die;
        $table = $this->db->prefix . $table;
        $columns = $this->query("SHOW COLUMNS FROM ${table}");
        return array_map(function ($column) {
            return $column->Field;
        }, $columns);
    }

    /**
     * Finds $request['table'] rows by $request['field'] == $request['value']
     *
     * @param array $request
     * @return array
     */
    public function getEntriesByField($request)
    {
        in_array($request['table'], $this->listTables()) || die;
        $table = $this->db->prefix . $request['table'];
        $field = $request['field'];
        in_array($field, $this->getFields($request['table'])) or $this->trigger404();
        $entries = $this->query(
            "SELECT * FROM ${table} WHERE ${field} = %s",
            $request['value']
        );
        count($entries) or $this->trigger404();
        return $entries;
    }

    /**
     * Triggers a 404 and stops excecution
     *
     * @return void
     */
    private function trigger404()
    {
        header('HTTP/1.0 404 Not Found');
        die();
    }

    /**
     * Retrieves all rows for a given $request['table']
     *
     * @param array $request
     * @return array
     */
    public function getTable($request)
    {
        in_array($request['table'], $this->listTables()) or $this->trigger404();
        $table = $this->db->prefix . $request['table'];
        return $this->query("SELECT * FROM ${table}");
    }

    /**
     * Retrieves a row from $request['table'] by the
     * tables PRIMARY key value $request['id']
     *
     * @return object
     */
    public function getEntry($request)
    {
        in_array($request['table'], $this->listTables()) or die;
        $table = $this->db->prefix . $request['table'];
        $field = $this->query(
            "SHOW KEYS FROM ${table} WHERE Key_name = 'PRIMARY'"
        );
        count($field) or $this->trigger404();
        $field = $field[0]->Column_name;
        $entry = $this->query(
            "SELECT * FROM ${table} WHERE ${field} = %s",
            $request['id']
        );
        count($entry) or $this->trigger404();
        return $entry[0];
    }

    /**
     * Performs a prepared DB query and returns the result
     *
     * @return array
     */
    private function query($query, ...$args)
    {
        $prepared = $this->db->prepare($query, $args);
        return $this->db->get_results($prepared);
    }

    /**
     * Generates an admin_notices notification
     *
     * @param string $title
     * @param string $message
     * @param string $type = 'success'
     * @return void
     */
    public function notification($title, $message, $type = 'success')
    {
        add_action('admin_notices', function () use ($title, $message, $type) {
            include(WPQUERY_ROOT . 'templates/notification.php');
        });
    }

    /**
     * Checks options for a 'wpquery_notification' key,
     * and generates an admin_notices notification.
     * The key is then deleted from the table
     *
     * @return void
     */
    public function flashNotification()
    {
        add_action('admin_notices', function () {
            $notification = $this->getKey('wpquery_notification');
            if ($notification) {
                $notification = json_decode($notification);
                $this->deleteKey('wpquery_notification');
                $type = $notification->type;
                $title = $notification->title;
                $message = $notification->message;
                include(WPQUERY_ROOT . 'templates/notification.php');
            }
        });
    }

    /**
     * Adds a menu entry to the dashboard
     *
     * @return void
     */
    public function addMenu()
    {
        $this->handlePost();
        add_action('admin_menu', function () {
            add_menu_page(
                'WPQuery Settings',
                'WPQuery',
                'manage_options',
                'WPQuery',
                function () {
                    $secretKey = $this->config->secret;
                    $secretVal = $this->getKey($secretKey);
                    include(WPQUERY_ROOT . 'templates/admin.php');
                },
                'dashicons-cloud'
            );
        });
    }

    /**
     * Handle POST in dashboard menu
     * Generates a new API key and shows a notification
     *
     * @return void
     */
    public function handlePost()
    {
        $secretKey = $this->config->secret;
        if (!empty($_POST) && isset($_POST[$secretKey])) {
            $this->setKey($secretKey, $this->generateKey());
            $this->notification("API key change ", "Success");
        }
    }
}
