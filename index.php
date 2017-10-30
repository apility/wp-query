<?php
/*
    Plugin Name: WPQuery
    Plugin URI: http://github.com/apility/wp-query
    Description: Expose raw DB tables as a JSON api.
    Version: 1.0.0
    Author: Thomas Alrek
    Author URI: http://github.com/thomas-alrek
    License: MIT

    Copyright 2017 Apility AS

    Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

defined('ABSPATH') or die;

require_once($PLUGIN_PATH . 'setup.php');

register_activation_hook(__FILE__, 'WPQuerySetup' );
add_action( 'admin_notices', 'WPQuerySetupNotification' );

add_action('admin_menu', 'WPQueryAddMenu');

function WPQueryAddMenu()
{
    WPQueryHandlePost();
    add_menu_page('WPQuery Settings', 'WPQuery', 'manage_options', 'WPQuery', 'WPQueryShowMenu');
}

function WPQueryHandlePost()
{
    if (!empty($_POST) && isset($_POST['WPQUERY_secret']) && get_key(WPQUERY_SECRET) != $_POST['WPQUERY_secret']) {
        set_key(WPQUERY_SECRET, $_POST['WPQUERY_secret']);
?>
    <div class="wrap">
        <div id="message" class="updated notice is-dismissible">
            <p>API key <strong>changed</strong>.</p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text">Dismiss this notice.</span>
            </button>
        </div>
    </div>
<?php
    }
}

function WPQueryShowMenu()
{
?>
    <div class="wrap">
    <h1>WPQuery settings</h1>
    <form method="POST">
        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row"><label for="blogname">API Key</label></th>
                <td><input name="WPQUERY_secret" type="text" id="WPQUERY_secret" value="<?php echo get_key(WPQUERY_SECRET);?>" class="regular-text" style="background-image: url(&quot;data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAABHklEQVQ4EaVTO26DQBD1ohQWaS2lg9JybZ+AK7hNwx2oIoVf4UPQ0Lj1FdKktevIpel8AKNUkDcWMxpgSaIEaTVv3sx7uztiTdu2s/98DywOw3Dued4Who/M2aIx5lZV1aEsy0+qiwHELyi+Ytl0PQ69SxAxkWIA4RMRTdNsKE59juMcuZd6xIAFeZ6fGCdJ8kY4y7KAuTRNGd7jyEBXsdOPE3a0QGPsniOnnYMO67LgSQN9T41F2QGrQRRFCwyzoIF2qyBuKKbcOgPXdVeY9rMWgNsjf9ccYesJhk3f5dYT1HX9gR0LLQR30TnjkUEcx2uIuS4RnI+aj6sJR0AM8AaumPaM/rRehyWhXqbFAA9kh3/8/NvHxAYGAsZ/il8IalkCLBfNVAAAAABJRU5ErkJggg==&quot;); background-repeat: no-repeat; background-attachment: scroll; background-size: 16px 18px; background-position: 98% 50%; cursor: auto;"></td>
            </tr>
            </tbody>
        </table>
        <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
    </form>
    </div>
<?
}

const PLUGIN_NAMESPACE = 'wpquery/v1';
$PLUGIN_PATH = plugin_dir_path( __FILE__ );
$routes = include($PLUGIN_PATH . 'routes.php');

require_once($PLUGIN_PATH . 'WPQuery.php');

$dumper = new WPQuery();

if (isset($_GET['apikey']) && $_GET['apikey'] == get_key(WPQUERY_SECRET)) {
    add_action( 'rest_api_init', function () use ($dumper, $routes) {
        foreach ($routes as $route => $handler) {
            register_rest_route( PLUGIN_NAMESPACE, $route, [
                'methods' => 'GET',
                'callback' => [$dumper, $handler]
            ]);
        }
    });    
}