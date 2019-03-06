<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-02-27
 * Time: 11:03 AM
 */

class WPGH_Guided_Setup_Step_Import extends WPGH_Guided_Setup_Step
{

    /**
     * @var WPGH_Bulk_Contact_Manager
     */
    private $importer;

    public function load_dependencies()
    {
        $this->importer = new WPGH_Bulk_Contact_Manager();
    }

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
        <p>
            <input type="file" id="contacts" name="contacts" accept=".csv" >
        </p>
        <p class="description"><a target="_blank" href="https://docs.groundhogg.io/docs/settings/getting-started/import-your-list/"><?php _e( "Learn how to import contacts.", 'groundhogg' ); ?></a></p>
        <hr>
        <?php $tag_args = array();
        $tag_args[ 'id' ] = 'import_tags';
        $tag_args[ 'name' ] = 'import_tags[]'; ?>
        <?php echo WPGH()->html->tag_picker( $tag_args ); ?>
        <div class="import-status-wrapper"><p><strong><span class="import-status"></span></strong></p></div>
        <p class="description"><?php _e( 'These tags will be applied to the contacts upon importing.', 'groundhogg' ); ?></p>
        <p class="submit">
            <button class="import button button-primary" id="import" type="button"><?php _e( 'Import Contacts' ); ?></button>
            <span class="spinner spinner-import" style="float: none"></span>
        </p>
        <hr/>
        <?php
        return ob_get_clean();
    }

    public function save()
    {
        return true;
    }

}