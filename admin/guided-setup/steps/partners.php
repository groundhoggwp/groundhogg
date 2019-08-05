<?php
namespace Groundhogg\Admin\Guided_Setup\Steps;

use function Groundhogg\get_request_var;
use function Groundhogg\html;
use Groundhogg\Plugin;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-02-27
 * Time: 11:03 AM
 */

class Partners extends Step
{

	const PARTNER_DIRECTORY_URL = 'https://www.groundhogg.io/partner/certified-partner-directory/';

    public function get_title()
    {
        return _x( 'Need Help?', 'guided_setup', 'groundhogg' );
    }

    public function get_slug()
    {
        return 'partners';
    }

    public function get_description()
    {
        return _x( 'Think you might want some implementation help? Find yourself a certified partner to help implement your marketing strategy.', 'guided_setup', 'groundhogg' );
    }

    public function get_content()
    {
	    $ip_info = Plugin::$instance->utils->location->ip_info();

    	$partner_search_link = add_query_arg( [
    		'country' => $ip_info[ 'country_code' ]
	    ], self::PARTNER_DIRECTORY_URL );

	    ?>
	    <p class="submit" style="text-align: center">
		    <a class="button button-primary" target="_blank" href="<?php echo esc_url( $partner_search_link ); ?>"><?php _e( 'Find a Partner Near Me!' ) ?></a>
	    </p>
	    <?php
    }

    public function save()
    {
        return true;
    }

}