<?php defined('ABSPATH') or die;?>
<!-- Begin WPQuery Admin template -->
<div class="wrap">
    <h1>WPQuery settings</h1>
    <form method="POST">
        <input type="hidden" name="wpquery_options" value="1">
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="wpquery_apikey">API Key</label>
                    </th>
                    <td>
                        <input name="wpquery_apikey" type="text" id="wpquery_apikey" value="<?=$apikey?>" readonly class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wpquery_regenerate">Regenerate</label>
                    </th>
                    <td>
                        <input type="checkbox" name="wpquery_regenerate" value="1" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wpquery_write">Read</label>
                    </th>
                    <td>
                        <input type="checkbox" name="wpquery_read" value="1"<?php checked( 1 == $read ); ?> />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wpquery_write">Write</label>
                    </th>
                    <td>
                        <input type="checkbox" name="wpquery_write" value="1"<?php checked( 1 == $write ); ?> />
                    </td>
                </tr>
            </tbody>
        </table>
        <br>
        <button type="submit" name="submit" id="submit" class="button button-primary">Update</button>
    </form>
</div>
<!-- End WPQuery Admin template -->