<?php

defined('ABSPATH') or die;

class WPQuery
{
    private $wpDB;
    private $called = false;
    private $tables;
    
    public function __construct()
    {
        global $wpdb;
        global $wp_query;
        $this->db = $wpdb;
    }

    private function trigger404()
    {
        header('HTTP/1.0 404 Not Found');
        die();
    }

    private function getTables()
    {
        $tables = $this->query(
            "SELECT table_name FROM information_schema.tables WHERE table_name LIKE %s",
            $this->db->prefix . '%'
        );
        return array_map(function ($table) {
            return str_replace($this->db->prefix, '', $table->table_name);
        }, $tables);
    }

    private function getColumns($table)
    {
        $table = $table;
        $columns = $this->query("SHOW COLUMNS FROM ${table}");
        return array_map(function ($column) {
            return $column->Field;
        }, $columns);
    }

    private function getPrimaryColumn($table)
    {
        return $this->query("SHOW KEYS FROM {$table} WHERE Key_name = 'PRIMARY'");
    }

    private function query($query, ...$args)
    {
        $prepared = $this->db->prepare($query, $args);
        return $this->db->get_results($prepared);
    }

    public function list()
    {
        return $this->getTables();
    }

    public function listTable($request)
    {
        in_array($request['table'], $this->getTables()) or $this->trigger404();
        $table = $this->db->prefix . $request['table'];
        return $this->query("SELECT * FROM {$table}");
    }

    public function getEntry($request)
    {
        in_array($request['table'], $this->getTables()) or die;
        $table = $this->db->prefix . $request['table'];
        $field = $this->getPrimaryColumn($table);
        count($field) or die;
        $field = $field[0]->Column_name;
        $entry = $this->query("SELECT * FROM {$table} WHERE {$field} = %s", $request['id']);
        count($entry) or $this->trigger404();
        return $entry[0];
    }

    public function getEntryByField($request)
    {
        in_array($request['table'], $this->getTables()) or die;
        $table = $this->db->prefix . $request['table'];
        $field = $request['field'];
        in_array($field, $this->getColumns($table)) or $this->trigger404();
        return $this->query("SELECT * FROM {$table} WHERE {$field} = %s", $request['value']);
    }
}
