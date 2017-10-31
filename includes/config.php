<?php

defined('ABSPATH') or die;

return [
  'secret' => 'wpquery_apikey',
  'namespace' => 'wpquery/v1',
  'routes'=> [
    'query' => 'listTables',
    'query/(?P<table>[a-zA-Z0-9-_]+)/(?P<field>[a-zA-Z0-9-_]+)/(?P<value>[a-zA-Z0-9-_]+)' => 'getEntriesByField',
    'query/(?P<table>[a-zA-Z0-9-_]+)/(?P<id>[a-zA-Z0-9-_]+)' => 'getEntry',
    'query/(?P<table>[a-zA-Z0-9-_]+)' => 'getTable'
  ],
];
