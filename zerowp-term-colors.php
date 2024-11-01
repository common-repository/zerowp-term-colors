<?php
/* 
 * Plugin Name: Custom Term Colors
 * Plugin URI:  http://zerowp.com/custom-term-colors
 * Description: Easy user interface to assign colors to any term from any taxonomy.
 * Author:      Andrei Surdu
 * Author URI:  http://zerowp.com/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: zerowp-term-colors
 * Domain Path: /languages
 *
 * Version:     1.0.8.2
 * 
 */

/* No direct access allowed!
---------------------------------*/
if (!defined('ABSPATH')) {
    exit;
}

/* Plugin configuration
----------------------------*/
function ztcolors_config($key = false)
{
    $settings = apply_filters('ztcolors:config_args', [

        // Plugin data
        'version'          => '1.0',
        'min_php_version'  => '5.3',
        'required_plugins' => [],
        'priority'         => 10,
        'action_name'      => 'init',

        // Plugin branding
        'plugin_name'      => __('ZeroWP Term Colors', 'zerowp-term-colors'),
        'id'               => 'zerowp-term-colors',
        'namespace'        => 'ZTColors',
        'uppercase_prefix' => 'ZTCOLORS',
        'lowercase_prefix' => 'ztcolors',

        // Access to plugin directory
        'file'             => __FILE__,
        'lang_path'        => plugin_dir_path(__FILE__) . 'languages',
        'basename'         => plugin_basename(__FILE__),
        'path'             => plugin_dir_path(__FILE__),
        'url'              => plugin_dir_url(__FILE__),
        'uri'              => plugin_dir_url(__FILE__),//Alias

    ]);

    // Make sure that PHP version is set to 5.3+
    if (version_compare($settings['min_php_version'], '5.3', '<')) {
        $settings['min_php_version'] = '5.3';
    }

    // Get the value by key
    if (!empty($key)) {
        if (array_key_exists($key, $settings)) {
            return $settings[$key];
        }
        else {
            return false;
        }
    }

    // Get settings
    else {
        return $settings;
    }
}

/* Define the current version of this plugin.
-----------------------------------------------------------------------------*/
define('ZTCOLORS_VERSION', ztcolors_config('version'));

/* Plugin constants
------------------------*/
define('ZTCOLORS_PLUGIN_FILE', ztcolors_config('file'));
define('ZTCOLORS_PLUGIN_BASENAME', ztcolors_config('basename'));

define('ZTCOLORS_PATH', ztcolors_config('path'));
define('ZTCOLORS_URL', ztcolors_config('url'));
define('ZTCOLORS_URI', ztcolors_config('url')); // Alias

/* Minimum PHP version required
------------------------------------*/
define('ZTCOLORS_MIN_PHP_VERSION', ztcolors_config('min_php_version'));

/* Plugin Init
----------------------*/

final class ZTCOLORS_Plugin_Init
{

    public function __construct()
    {
        $required_plugins = ztcolors_config('required_plugins');
        $missed_plugins   = $this->missedPlugins();

        /* The installed PHP version is lower than required.
        ---------------------------------------------------------*/
        if (version_compare(PHP_VERSION, ZTCOLORS_MIN_PHP_VERSION, '<')) {
            require_once ZTCOLORS_PATH . 'warnings/php-warning.php';
            new ZTCOLORS_PHP_Warning;
        }

        /* Required plugins are not installed/activated
        ----------------------------------------------------*/
        else if (!empty($required_plugins) && !empty($missed_plugins)) {
            require_once ZTCOLORS_PATH . 'warnings/noplugin-warning.php';
            new ZTCOLORS_NoPlugin_Warning($missed_plugins);
        }

        /* We require some plugins and all of them are activated
        -------------------------------------------------------------*/
        else if (!empty($required_plugins) && empty($missed_plugins)) {
            add_action(
                'plugins_loaded',
                [$this, 'getSource'],
                ztcolors_config('priority')
            );
        }

        /* We don't require any plugins. Include the source directly
        ----------------------------------------------------------------*/
        else {
            $this->getSource();
        }
    }

    //------------------------------------//--------------------------------------//

    /**
     * Get plugin source
     *
     * @return void
     */
    public function getSource()
    {
        require_once ZTCOLORS_PATH . 'plugin.php';

        $components = glob(ZTCOLORS_PATH . 'components/*', GLOB_ONLYDIR);
        foreach ($components as $component_path) {
            require_once trailingslashit($component_path) . 'component.php';
        }
    }

    //------------------------------------//--------------------------------------//

    /**
     * Missed plugins
     *
     * Get an array of missed plugins
     *
     * @return array
     */
    public function missedPlugins()
    {
        $required = ztcolors_config('required_plugins');
        $active   = $this->activePlugins();
        $diff     = array_diff_key($required, $active);

        return $diff;
    }

    //------------------------------------//--------------------------------------//

    /**
     * Active plugins
     *
     * Get an array of active plugins
     *
     * @return array
     */
    public function activePlugins()
    {
        $active = get_option('active_plugins');
        $slugs  = [];

        if (!empty($active)) {
            $slugs = array_flip(array_map([$this, '_filterPlugins'], (array)$active));
        }

        return $slugs;
    }

    //------------------------------------//--------------------------------------//

    /**
     * Filter plugins callback
     *
     * @return string
     */
    protected function _filterPlugins($value)
    {
        $plugin = explode('/', $value);

        return $plugin[0];
    }

}

new ZTCOLORS_Plugin_Init;
