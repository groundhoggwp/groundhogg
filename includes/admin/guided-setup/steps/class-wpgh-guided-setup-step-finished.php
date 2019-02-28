<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-02-27
 * Time: 11:03 AM
 */

class WPGH_Guided_Setup_Step_Finished extends WPGH_Guided_Setup_Step
{

    public function get_title()
    {
        return _x( 'Finished', 'guided_setup', 'groundhogg' );
    }

    public function get_slug()
    {
        return 'finished';
    }

    public function get_description()
    {
        return _x( 'Groundhogg has been successfully setup!', 'guided_setup', 'groundhogg' );
    }

    public function get_settings()
    {
        ob_start();
        ?>
        <table class="form-table">

        </table>
        <?php
        return ob_get_clean();
    }

    public function save()
    {
        // TODO: Implement save() method.
    }

}