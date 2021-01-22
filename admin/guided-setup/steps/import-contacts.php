<?php
namespace Groundhogg\Admin\Guided_Setup\Steps;

use function Groundhogg\dashicon_e;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-02-27
 * Time: 11:03 AM
 */

class Import_Contacts extends Step
{

    public function get_title()
    {
        return _x( 'Import Contacts', 'guided_setup', 'groundhogg' );
    }

    public function get_slug()
    {
        return 'import_contacts';
    }

    public function get_description()
    {
        return _x( 'Import your contacts so you can start sending emails and marketing your business.', 'guided_setup', 'groundhogg' );
    }

    public function get_content()
    {
        ?>
        <p class="submit" style="text-align: center">
            <a class="button button-primary big-button" target="_blank" href="<?php echo admin_url( 'admin.php?page=gh_tools&action=add&tab=import' );?>"><?php  dashicon_e( 'upload' ); _e( 'Click Here To Import Your List!' ) ?></a>
        </p>
        <?php
    }

    public function save()
    {
        return true;
    }

}