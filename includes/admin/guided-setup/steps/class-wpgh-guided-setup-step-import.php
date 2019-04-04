<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-02-27
 * Time: 11:03 AM
 */

class WPGH_Guided_Setup_Step_Import extends WPGH_Guided_Setup_Step
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

    public function get_settings()
    {
        ob_start();
        ?>
        <p class="submit" style="text-align: center">
            <a class="button button-primary" target="_blank" href="<?php echo admin_url( 'admin.php?page=gh_tools&action=add&tab=import' );?>"><?php _e( 'Click Here To Import Your List!' ) ?></a>
        </p>
        <?php
        return ob_get_clean();
    }

    public function save()
    {
        return true;
    }

}