<?php
namespace Groundhogg;
use Gamajo_Template_Loader;

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Gamajo_Template_Loader' ) ){
    include_once dirname( __FILE__ ) . '/lib/gamajo-template-loader.php';
}

class Template_Loader extends Gamajo_Template_Loader {

    /**
     * Prefix for filter names.
     *
     * @since 1.0.0
     * @type string
     */
    protected $filter_prefix = 'groundhogg';

    /**
     * Directory name where custom templates for this plugin should be found in the theme.
     *
     * @since 1.0.0
     * @type string
     */
    protected $theme_template_directory = 'groundhogg-templates';

    /**
     * Reference to the root directory path of this plugin.
     *
     * @since 1.0.0
     * @type string
     */
    protected $plugin_directory = GROUNDHOGG_PATH;

}