<?php

defined('ABSPATH') or die;

return [
    'query' => 'list',
    'query/(?P<table>[a-zA-Z0-9-_]+)/(?P<field>[a-zA-Z0-9-_]+)/(?P<value>[a-zA-Z0-9-_]+)' => 'getEntryByField',
    'query/(?P<table>[a-zA-Z0-9-_]+)/(?P<id>[a-zA-Z0-9-_]+)' => 'getEntry',
    'query/(?P<table>[a-zA-Z0-9-_]+)' => 'listTable'
];
