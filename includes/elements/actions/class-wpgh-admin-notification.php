<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-10-04
 * Time: 5:42 PM
 */

class WPGH_Admin_Notification extends WPGH_Funnel_Step
{

    /**
     * @var string
     */
    public $type    = 'admin_notification';

    /**
     * @var string
     */
    public $group   = 'action';

    /**
     * @var string
     */
    public $icon    = 'admin-notification.png';

    /**
     * @var string
     */
    public $name    = 'Admin Notification';

    /**
     * @param WPGH_Step
     */
    public function settings( $step )
    {

        $note = $step->get_meta( 'note_text' );
        $send_to = $step->get_meta( 'send_to' );
        $subject = $step->get_meta( 'subject' );

        if ( ! $note )
            $note = __( "Follow up with {first} {last} tomorrow.", 'groundhogg' );

        if ( ! $send_to )
            $send_to = get_bloginfo( 'admin_email' );

        if ( ! $subject )
            $subject = __( "Admin Notification for {first}", 'groundhogg' );

        ?>
        <table class="form-table">
            <tbody>
                <tr>
                    <th>
                        <?php echo esc_html__( 'Send To:', 'groundhogg' ); ?>
                    </th>
                    <td>
                        <?php $args = array(
                            'id'    => $step->prefix( 'send_to' ),
                            'name'  => $step->prefix( 'send_to' ),
                            'value' => $send_to
                        ); ?>
                        <?php WPGH()->html->input( $args ) ?>
                        <p class="description">
                            <?php _e( 'Use any email address or the {owner_email} replacement code.', 'groundhogg' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php echo esc_html__( 'Subject:', 'groundhogg' ); ?>
                    </th>
                    <td>
                        <?php $args = array(
                            'id'    => $step->prefix( 'subject' ),
                            'name'  => $step->prefix( 'subject' ),
                            'value' => $subject
                        ); ?>
                        <?php WPGH()->html->input( $args ) ?>
                        <p class="description">
                            <?php _e( 'Use any valid replacement codes.', 'groundhogg' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php echo esc_html__( 'Notification Text:', 'groundhogg' ); ?>
                    </th>
                    <?php $args = array(
                        'id'    => $step->prefix( 'note_text' ),
                        'name'  => $step->prefix( 'note_text' ),
                        'value' => $note,
                        'cols'  => 64,
                        'rows'  => 4
                    ); ?>
                    <td>
                        <?php echo WPGH()->html->textarea( $args ) ?>
                        <p class="description">
                            <?php _e( 'Use any valid replacement codes', 'groundhogg' ); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <?php
    }

    /**
     * Save the step settings
     *
     * @param $step WPGH_Step
     */
    public function save( $step )
    {

        if ( isset( $_POST[ $step->prefix( 'send_to' ) ] ) ){

            $send_to = sanitize_text_field( $_POST[ $step->prefix( 'send_to' ) ] );

            if ( strpos( $send_to, ',' ) !== false ){
                $emails = array_map( 'trim', explode( ',', $send_to ) );

                $sanitized_emails = array();

                foreach ( $emails as $email ){
                    $sanitized_emails[] = ( $email === '{owner_email}' )? '{owner_email}' : sanitize_email( $email );
                }

                $send_to = implode( ',', $sanitized_emails );

            } else {

                $send_to = ( $send_to === '{owner_email}' )? '{owner_email}' : sanitize_email( $send_to );

            }

            $step->update_meta( 'send_to', $send_to );
        }

        if ( isset( $_POST[ $step->prefix( 'subject' ) ] ) ){

            $step->update_meta( 'subject', sanitize_text_field( $_POST[  $step->prefix( 'subject' ) ] ) );

        }

        if ( isset( $_POST[ $step->prefix( 'note_text' ) ] ) ){

            $step->update_meta( 'note_text', sanitize_textarea_field( $_POST[  $step->prefix( 'note_text' ) ] ) );

        }

    }

    /**
     * Process the apply note step...
     *
     * @param $contact WPGH_Contact
     * @param $event WPGH_Event
     *
     * @return bool
     */
    public function run( $contact, $event )
    {

        $note = $event->step->get_meta( 'note_text' );

        $finished_note = sanitize_textarea_field( WPGH()->replacements->process( $note, $event->contact->ID ) );

        $finished_note .= sprintf( "\n\n%s: %s", __( 'Manage Contact', 'groundhogg' ), admin_url( 'admin.php?page=gh_contacts&action=edit&contact=' . $event->contact->ID  ) );

        $subject = $event->step->get_meta('subject' );
        $subject = sanitize_text_field(  WPGH()->replacements->process( $subject, $event->contact->ID ) );

        $send_to = $event->step->get_meta( 'send_to' );

        if ( ! is_email( $send_to ) ){

            $send_to = WPGH()->replacements->process( $send_to, $event->contact->ID );

        }

        if ( ! $send_to )
        {
            return false;
        }

        return wp_mail( $send_to, $subject, $finished_note );

    }


}