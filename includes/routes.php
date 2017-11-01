<?php

defined('ABSPATH') or die;

return [
  'query' => 'tables',
  'query/(?P<table>[a-zA-Z0-9-_]+)/(?P<field>[a-zA-Z0-9-_]+)/(?P<value>[a-zA-Z0-9-_]+)' => 'entriesByField',
  'query/(?P<table>[a-zA-Z0-9-_]+)/(?P<id>[a-zA-Z0-9-_]+)' => 'entry',
  'query/(?P<table>[a-zA-Z0-9-_]+)' => 'table'
];
