<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-02-27
 * Time: 11:03 AM
 */

class WPGH_Guided_Setup_Step_Other extends WPGH_Guided_Setup_Step
{

    public function get_title()
    {
        return _x( 'Other Stuff', 'guided_setup', 'groundhogg' );
    }

    public function get_slug()
    {
        return 'other_stuff';
    }

    public function get_description()
    {
        return _x( 'Tune Groundhogg even further by using these settings below.', 'guided_setup', 'groundhogg' );
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