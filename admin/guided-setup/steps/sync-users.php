<?php
namespace Groundhogg\Admin\Guided_Setup\Steps;

use Groundhogg\Plugin;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-02-27
 * Time: 11:03 AM
 */

class Sync_Users extends Step
{

    public function get_title()
    {
        return _x( 'Sync Users With Contact Records', 'guided_setup', 'groundhogg' );
    }

    public function get_slug()
    {
        return 'sync_users';
    }

    public function get_description()
    {
        return _x( 'Sync your existing users with the your contact lists.', 'guided_setup', 'groundhogg' );
    }

    public function get_content()
    {
        ?>
        <p class="submit" style="text-align: center">
            <a class="button button-primary" target="_blank" href="<?php echo Plugin::$instance->bulk_jobs->sync_contacts->get_start_url(); ?>"><?php _e( 'Click Here Sync!' ) ?></a>
        </p>
        <?php
    }

    public function save()
    {
        return true;
    }

}