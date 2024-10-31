<?php
/**
 * Plugin Name: PlugIntel
 * Plugin URI: http://www.charlestonsw.com/product/plugintel/
 * Description: Filter out some of the WordPress Plugin Directory listings.
 * Version: 0.5
 * Author: Charleston Software Associates
 * Author URI: http://charlestonsw.com/
 * Requires at least: 3.3
 * Test up to : 3.9
 *
 * Text Domain: csa-plugintel
 * Domain Path: /languages/
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Main PlugIntel Class
 *
 * @package PlugIntel
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2013-2014 Charleston Software Associates, LLC
 */
class PlugIntel {

    //-------------------------------------
    // Properties
    //-------------------------------------


    /**
     * The directory we live in.
     *
     * @var string $dir
     */
    private $dir;

    /**
     * The plugin settings page hook.
     * 
     * @var string $menuHook
     */
    private $menuHook;

    /**
     * Our plugin options.
     * 
     * @var string[]
     */
    private $options = array(
        'filter_active'     => '1' ,
        'min_rating'        => '65' ,
        'max_rating'        => '100',
        'min_num_ratings'   => '2'  ,
        'min_tested_version'=> '3.3',
    );

    /**
     * Option meta data stdClass objects.
     * 
     * @var \stdClass[] $optionMeta
     */
    private $optionMeta;

    /**
     * Our slug.
     *
     * @var string $slug
     */
    private $slug                   = null;

    /**
     * The admin style handle.
     * 
     * @var string $styleHandle
     */
    private $styleHandle            = 'plugintelAdminCSS';

    /**
     * The url to this plugin admin features.
     *
     * @var string $url
     */
    private $url;

    //------------------------------------------------------
    // METHODS
    //------------------------------------------------------

    /**
     * Invoke the plugin.
     *
     * This ensures a singleton of this plugin.
     *
     * @static
     */
    public static function init() {
        static $instance = false;
        if ( !$instance ) {
            load_plugin_textdomain( 'csa-plugintel', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
            $instance = new PlugIntel();
        }
        return $instance;
    }

    /**
     * Constructor
     *
     * Anything in here runs ALL THE TIME. Front End and Admin UI.
     */
    function PlugIntel() {

        // Since we only run in the admin UI world, this should probably be the
        // only thing in here.
        //
        add_action('admin_menu'         ,array($this,'init_for_AdminUI') );
    }

    /**
     * Stuff we want available when in the Admin UI.
     *
     * @return null
     */
    function init_for_AdminUI() {

        // Set properties for this plugin.
        //
        $this->url  = plugins_url('',__FILE__);
        $this->dir  = plugin_dir_path(__FILE__);
        $this->slug = plugin_basename(__FILE__);
        
        // Initialize the options meta data
        //
        $this->initOptions();

        // Plugin Menu Hook
        //
        $this->menuHook = add_plugins_page('Intel','Intel','install_plugins','plugintel',array($this,'render_SettingsPage'));

        // Admin CSS
        // attach to the Intel settings page.
        //
        if (file_exists($this->dir.'/admin.css')) {
            wp_register_style($this->styleHandle, $this->url .'/admin.css');
        }
        add_action('admin_enqueue_scripts',array($this,'enqueue_admin_stylesheet'));

        // Admin Init
        //
        add_action('admin_init',array($this,'register_Settings'));

        // Admin Only Filters
        //
        add_filter('plugins_api_result' ,array($this,'filter_WPPluginDirectory_Results'         ),10,3);
        add_filter('plugins_api_args'   ,array($this,'filter_Tweak_WPPluginDirectory_SearchArgs'),10,2);

        // Put a header on the base plugin page (dashboard) and search results page (search)
        //
        add_action('install_plugins_dashboard', array($this,'action_PluginPageHeader'));
        add_action('install_plugins_search',    array($this,'action_PluginPageHeader'));
    }

    /**
     * Enqueue the admin stylesheet when needed.
     *
     * Currently only on the plugin-install.php pages.
     *
     * @var string $hook
     */
    function enqueue_admin_stylesheet($hook) {
        $this->render_ToDebugBar('plugintel.main','msg','enqueue_admin_stylesheet('.$hook.')');
        switch ($hook) {
            case 'plugin-install.php':
            case 'plugins_page_plugintel':
                wp_enqueue_style($this->styleHandle);
                break;

            default:
                break;
        }
    }

    /**
     * Setup the options meta data.
     */
    function initOptions() {
        $this->optionMeta['filter_active'] =
            $this->create_OptionMeta(
                    'filter_active',
                    __('Filter','csa-plugintel'),
                    __('Turn PlugIntel filtering of the plugin list on/off.', 'csa-plugintel'),
                    'slider'
                    );

        $this->optionMeta['min_rating'] =
            $this->create_OptionMeta(
                    'min_rating',
                    __('Minimum Rating','csa-plugintel'),
                    __('Do not show plugins with ratings below this value.  60 = 3 stars, 100 = 5 stars.', 'csa-plugintel')
                    );

        $this->optionMeta['max_rating'] =
            $this->create_OptionMeta(
                    'max_rating',
                    __('Maximum Rating','csa-plugintel'),
                    __('Do not show plugins with ratings above this value.  60 = 3 stars, 100 = 5 stars.','csa-plugintel')
                );

        $this->optionMeta['min_num_ratings'] =
            $this->create_OptionMeta(
                'min_num_ratings',
                __('Min Number of Ratings','csa-plugintel'),
                __('Do not show plugins with fewer than this number of ratings. Default: 2.','csa-plugintel')
                );

        $this->optionMeta['min_tested_version'] =
            $this->create_OptionMeta(
                    'min_tested_version',
                    __('Minimum Tested Version','csa-plugintel'),
                    __('Do not show plugins that were not tested on this version of WordPress or  higher. Default: 3.3.','csa-plugintel')
                    );
    }

    /**
     * Create the Debug My Plugin Panels
     * 
     * @return null
     */
    static function create_DMPPanels() {
        if (!isset($GLOBALS['DebugMyPlugin'])) { return; }
        if (class_exists('DMPPanelPlugintel') == false) {
            require_once(plugin_dir_path(__FILE__).'include/class.dmppanels.php');
        }
        $GLOBALS['DebugMyPlugin']->panels['plugintel.main'] = new DMPPanelPlugintel();
    }

    /**
     * Create a new option meta object.
     *
     * $type can be
     *    'text'   - simple text input
     *    'slider' - checkbox rendered as a slider
     *
     * @param string $slug
     * @param string $label
     * @param string $desc
     * @param string $type
     * @param int    $order
     * @return \stdClass
     */
    function create_OptionMeta($slug,$label,$desc,$type='text',$order=10) {
        $optionMeta = new stdClass();
        $optionMeta->slug           = $slug;
        $optionMeta->label          = $label;
        $optionMeta->description    = $desc;
        $optionMeta->type           = $type;
        $optionMeta->order          = $order;
        return $optionMeta;
    }

    /**
     *
     */
    function action_PluginPageHeader() {
        print '<p>'.
              sprintf(
                      __('PlugIntelligence is %s.','csa-plugintel'),
                      ($this->options['filter_active']==1)?__('active','csa-plugintel'):__('NOT active','csa-plugintel')
                     )
              ;
        print '<br/>';
        if ($this->options['filter_active']==1) {
            print "<span class='option_label'>Rating: </span>" .
                  "<span class='option_value'>{$this->options['min_rating']} - {$this->options['max_rating']}</span>"
                  ;
            print "<span class='option_label'>Rating Count: </span>" .
                  "<span class='option_value'>{$this->options['min_num_ratings']}</span>"
                  ;
            print "<span class='option_label'>WP Version: </span>" .
                  "<span class='option_value'>{$this->options['min_tested_version']}</span>"
                  ;
        }

        print '</p>';
    }

    /**
     * Tell WordPress to give us 5x as many back (150 v. 30).
     *
     * @param \stdClass $args
     * @param string $action
     * @return \stdClass
     */
    function filter_Tweak_WPPluginDirectory_SearchArgs($args,$action) {
        $newArgs = new stdClass();
        $newArgs = $args;
        $newArgs->per_page   = 150;
        return $newArgs;
    }

    /**
     *  Manipulate the results that came back from the WP Plugin Directory.
     *
     * @param \stdClass $Results
     * @param string $action
     * @param \stdClass $args
     * @return \stdClass
     */
    function filter_WPPluginDirectory_Results($Results, $action, $args) {
        if ($action !== 'query_plugins') { return $Results; }
        if ($this->options['filter_active']==0) { return $Results; }

        $filteredResults = new stdClass();
        $filteredResults->info = $Results->info;

        // Setup the PlugIntel Filters
        //
        add_filter('plugintel_showmeif', array($this,'filter_ShowMeIf_RatingOK'     ),10,2);
        add_filter('plugintel_showmeif', array($this,'filter_ShowMeIf_NumRatingsOK' ),20,2);
        add_filter('plugintel_showmeif', array($this,'filter_ShowMeIf_VersionOK'    ),30,2);

        // First element of Results is the page info
        // Second element is the plugin array
        //
        $goodPlugins = array();
        foreach ($Results->plugins as $plugin) {

            // FILTER: plugintel_showmeif
            // 
            // Run the filters and add the plugin to the stack if they all return true.
            // The filter hooks should return true or false and preferably short if they get a false.
            //
            if (apply_filters('plugintel_showmeif',true,$plugin)) {
                $goodPlugins[] = $plugin;
                //print '<pre>'.print_r($plugin,true).'</pre>';
            }
        }
        $filteredResults->plugins = $goodPlugins;

        return $filteredResults;
    }

    /**
     * Filter out plugins that do not meet our number of ratings threshold.
     *
     * @param boolean $ok
     * @param \stdClass $plugin
     * @return boolean true if plugin is good
     */
    function filter_ShowMeIf_NumRatingsOK($ok,$plugin) {
        if (!$ok) { return $ok; } // short if already !ok
        return ($plugin->rating >= $this->options['min_num_ratings']);
    }

    /**
     * Filter out plugins that do not meet our rating threshold.
     * 
     * @param boolean $ok
     * @param \stdClass $plugin
     * @return boolean true if plugin is good
     */
    function filter_ShowMeIf_RatingOK($ok,$plugin) {
        if (!$ok) { return $ok; } // short if already !ok
        return (
                ($plugin->rating >= $this->options['min_rating']) &&
                ($plugin->rating <= $this->options['max_rating'])
        );
    }

    /**
     * Filter out plugins that do not meet our tested version threshhold.
     *
     * @param boolean $ok
     * @param \stdClass $plugin
     * @return boolean true if plugin is good
     */
    function filter_ShowMeIf_VersionOK($ok,$plugin) {
        if (!$ok) { return $ok; } // short if already !ok
        return (!version_compare($plugin->tested, $this->options['min_tested_version'], '<'));
    }

    /**
     * Render the settings page.
     */
    function render_SettingsPage() {
        print
            '<div class="wrap">' .
                screen_icon() .
                '<h2>PlugIntel '.__('Settings','csa-plugintel').'</h2>'.
                '<form method="post" action="options.php">'
                ;
        settings_fields('plugintel_options');
        do_settings_sections('plugintel');
        submit_button();
        print
                '</form>'.
            '</div>'
            ;
    }

    /**
     * Register the settings.
     *
     */
    function register_Settings() {

        // Load options from WPDB, default values to the array at the top of this class.
        //
        // loading the options this way (2 steps with array_merge) ensures that the serialized data
        // from the database does not obliterate defaults when new options are added.  Those new options
        // would be blank in the database.   Using get_option('plugintel_options',$this->options) does
        // not have the desired effect with serialized data as it is loaded as a single blob, thus the
        // original parameter will have data and the second parameter is ignored.
        //
        $optionsFromDB = get_option('plugintel_options',array());
        $this->options = array_merge($this->options,$optionsFromDB);
        $this->render_ToDebugBar('plugintel.main','pr','register_Settings set options to:',$this->options);

        register_setting('plugintel_options','plugintel_options',array($this,'validate_Options'));

        // Main Settings Section
        //
        add_settings_section('plugintel_main',__('Settings','csa-plugintel'),array($this,'render_MainSettings') ,'plugintel'        );

        // Show all options from the option meta array.
        //
        foreach ($this->optionMeta as $option) {
            add_settings_field($option->slug ,
                    $option->label,
                    array($this,'render_Input')    ,'plugintel', 'plugintel_main',
                    array(
                        'id'            => $option->slug,
                        'description'   => $option->description,
                        'type'          => $option->type,
                        )
                    );
        }
    }

    /**
     * Render the main settings panel inputs.
     */
    function render_MainSettings() {
        print '<p>'.
              __('Use these settings to filter out the plugins that are returned on the Add New plugin page.','csa-plugintel').
             '</p>';
    }

    /**
     * Figure out which type of input to render.
     *
     * @param mixed[] $args
     */
    function render_Input($args) {
        switch ($args['type']) {
            case 'text':
                $this->render_TextInput($args);
                break;
            case 'slider':
                $this->render_SliderInput($args);
                break;
            default:
                break;
        }
    }

    /**
     * Render the slider input.
     *
     * @param mixed[] $args
     */
    function render_SliderInput($args) {
        $checked = (($this->options[$args['id']]==1)?'checked':'');
        $onClick = 'onClick="'.
            "jQuery('input[id={$args['id']}]').prop('checked',".
                "!jQuery('input[id={$args['id']}]').prop('checked')" .
                ");".
            '" ';

        echo
            "<input type='checkbox' id='{$args['id']}' name='plugintel_options[{$args['id']}]' value='1' style='display:none;' $checked>" .
            "<div id='{$args['id']}_div' class='onoffswitch-block'>" .
            "<div class='onoffswitch'>" .
            "<input type='checkbox' name='onoffswitch' class='onoffswitch-checkbox' value='1' id='{$args['id']}-checkbox' $checked>" .
            "<label class='onoffswitch-label' for='{$args['id']}-checkbox'  $onClick>" .
            '<div class="onoffswitch-inner"></div>'.
            "<div class='onoffswitch-switch'></div>".
            '</label>'.
            '</div>' .
            '</div>'
            ;

        if (!empty($args['description'])) {
            print "<p class='description'>{$args['description']}</p>";
        }
    }

    /**
     * Throw debugging output into Debug My Plugin (3rd party plugin)
     *
     * @param string $panel the panel name, default is 'main'
     * @param string $type the type 'pr' or 'msg'
     * @param string $hdr the message header
     * @param mixed $msg the variable to dump ('pr') or print ('msg')
     * @param string $file __FILE__ from calling location
     * @param string $line __LINE__ from calling location
     * @return null
     */
    function render_ToDebugBar($panel='main', $type='msg',$hdr='',$msg='',$file=null,$line=null) {
        if (!isset($GLOBALS['DebugMyPlugin'])) { return; }
        switch ($type) {
            case 'pr':
                $GLOBALS['DebugMyPlugin']->panels[$panel]->addPR($hdr,$msg,$file,$line);
                break;
            case 'msg':
                $GLOBALS['DebugMyPlugin']->panels[$panel]->addMessage($hdr,$msg,$file,$line);
                break;
            default:
                break;
        }
    }

    /**
     * Render the text input for a settings field.
     *
     * @param mixed[] $args
     */
    function render_TextInput($args) {
        print "<input ".
                "id='plugintel_options[{$args['id']}]' ".
                "name='plugintel_options[{$args['id']}]' ".
                "size='20' ".
                "type='text' " .
                "value='{$this->options[$args['id']]}' ".
               "/>"
               ;
        if (!empty($args['description'])) {
            print "<p class='description'>{$args['description']}</p>";
        }
    }

    /**
     * Validate the options we get.
     *
     * @param mixed[] $option
     */
    function validate_Options($optionsRcvd) {
        if (!is_array($optionsRcvd)) { return; }

        $validOptions = array();
        foreach ($optionsRcvd as $optionName=>$optionValue) {

            // Option exists in our properties array, let it in.
            //
            if (isset($this->options[$optionName])) {
                $validOptions[$optionName]=$optionValue;
            }
        }

        // Check for empty checkboxes
        //
        foreach ($this->optionMeta as $option) {
            if (isset($validOptions[$option->slug])) { continue; }
            if (($option->type == 'checkbox') || ($option->type == 'slider')) {
                $validOptions[$option->slug] = '0';
            }
        }

        return $validOptions;
    }

}

add_action( 'init', array( 'PlugIntel', 'init' ) );
add_action('dmp_addpanel'   ,array('PlugIntel','create_DMPPanels'   ));