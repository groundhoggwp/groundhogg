<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-02-27
 * Time: 10:56 AM
 */

abstract class WPGH_Guided_Setup_Step
{

    public function __construct()
    {
        if ( isset( $_POST[ 'guided_setup_step_save' ] ) && $this->get_slug() === sanitize_key( $_POST[ 'guided_setup_step_save' ] ) ){
            add_action( 'admin_init', array( $this, 'go_to_next' ) );
        }
    }

    /**
     * @return string
     */
    abstract public function get_title();

    /**
     * @return string
     */
    abstract public function get_slug();

    /**
     * @return string
     */
    abstract public function get_description();

    /**
     * @return string
     */
    abstract public function get_settings();

    /**
     * @return bool
     */
    abstract public function save();

    /**
     * Save the settings, if successful go to next step.
     */
    public function go_to_next()
    {
        if ( ! wp_verify_nonce( $_POST[ '_wpnonce' ] ) ){
            return;
        }

        if ( $this->save() ){
            wp_redirect( sprintf( admin_url( 'admin.php?page=gh_guided_setup&step=%d' ), $this->get_current_step_id() + 1 ) );
            die();
        }

    }

    /**
     * Get the current step progression, or false if none defined.
     *
     * @return int
     */
    public function get_current_step_id()
    {
        if ( isset( $_GET[ 'step' ] ) ){
            return intval( $_GET[ 'step' ] );
        }

        return false;
    }

    /**
     * Returns whether or not the current step is the first step.
     * @return bool
     */
    public function is_first_step()
    {
        return $this->get_current_step_id() === 1;
    }

    /**
     * Returns whether or not the current step is the first step.
     * @return bool
     */
    public function is_last_step()
    {
        $num = WPGH()->menu->guided_setup->get_step_count();
        return $this->get_current_step_id() === $num;
    }

    /**
     * Get default step content.
     *
     * @return false|string
     */
    public function get_content()
    {
        ob_start();
        ?>
        <?php echo WPGH()->html->input(array(
            'type' => 'hidden',
        'name' => 'guided_setup_step_save',
        'value' => $this->get_slug(),
    )); ?>
        <div class="wrap">
            <div style="max-width: 800px;margin: auto;">
                <div class="big-header" style="text-align: center;margin: 1.5em;">
                    <span style="font-size: 40px;line-height: 1.2em;"><b><?php echo $this->get_title(); ?></b></span>
                </div>
                <div id="notices">
                    <?php WPGH()->notices->notices(); ?>
                </div>
                <div class="">
                    <div class="postbox">
                        <div class="inside" style="padding: 10px 30px 20px 30px;">
                            <?php echo $this->get_description(); ?>
                            <hr>
                            <?php echo $this->get_settings(); ?>
                            <p class="submit">
                                <?php if ( ! $this->is_first_step() ): ?>
                                    <a style="float: left" class="button button-primary" href="<?php printf( admin_url( 'admin.php?page=gh_guided_setup&step=%d' ), $this->get_current_step_id() - 1 ) ?>"><?php _ex( '&larr; Back', 'guided_setup', 'groundhogg' ); ?></a>
                                <?php endif; ?>
                                <?php if ( ! $this->is_last_step() ): ?>
                                    <input type="submit" style="float: right" class="button button-primary" value="<?php _ex( 'Save & Continue &rarr;', 'guided_setup', 'groundhogg' ); ?>"/>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

}