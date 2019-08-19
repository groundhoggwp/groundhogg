<?php
namespace Groundhogg\Admin\Guided_Setup\Steps;

use Groundhogg\Extension;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use Groundhogg\License_Manager;
use Groundhogg\Plugin;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-02-27
 * Time: 11:03 AM
 */

class Extensions extends Step
{

	const STORE_URL = 'https://www.groundhogg.io/downloads/';

    public function get_title()
    {
        return _x( 'Premium Extensions', 'guided_setup', 'groundhogg' );
    }

    public function get_slug()
    {
        return 'extensions';
    }

    public function get_description()
    {
        return _x( 'Integrate popular tools and add functionality with our premium extensions!', 'guided_setup', 'groundhogg' );
    }

    public function get_content()
    {
	    ?>
	    <style>
		    .masonry {
			    columns: 2;
			    column-gap: 1.5em;
		    }
		    .postbox {
			    display: inline-block;
			    vertical-align: top;
		    }

            .postbox .inside {
                padding: 0;
                margin: 0 !important;
            }

            .article-description {
                padding: 10px;
            }
	    </style>
	    <div class="masonry">
		    <?php
		    foreach ( License_Manager::get_extensions(6 ) as $extension ):
			    License_Manager::extension_to_html( $extension );
		    endforeach;?>
	    </div>
	    <?php

	    ?>
	    <p class="submit" style="text-align: center">
		    <a class="button button-primary" target="_blank" href="<?php echo esc_url( self::STORE_URL ); ?>"><?php _e( 'See All Premium Extensions!' ); ?></a>
	    </p>
	    <?php
	}

    public function save()
    {

        return true;
    }

}