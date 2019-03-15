<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:19 AM
 */

class WPGH_Last_Broadcast_Report extends WPGH_Circle_Graph_Report
{
    /**
     * @var int
     */
    private $last_broadcast_id;

    public function __construct()
    {
        $this->wid = 'last_broadcast_report';
        $this->name = _x( 'Last Broadcast Report', 'widget_name', 'groundhogg' );

        parent::__construct();
    }

    /**
     * get the last broadcast
     *
     * @return bool|int
     */
    public function get_last_broadcast()
    {
        $broadcasts = WPGH()->broadcasts->get_broadcasts( [ 'status' => 'sent' ] );

        if ( ! $broadcasts ){
            return false;
        }

        $broadcast = array_shift( $broadcasts );

        $this->last_broadcast_id = $broadcast->ID;

        return $this->last_broadcast_id;
    }

    public function get_data()
    {
        $dataset  =  array();

        if ( ! $this->get_last_broadcast() ){
            return [];
        }

        $last_broadcast_id = $this->last_broadcast_id;

        $contact_sum = WPGH()->events->count( array(
            'funnel_id'     => WPGH_BROADCAST,
            'step_id'       => $last_broadcast_id
        ) );

        $opens = WPGH()->activity->count( array(
            'funnel_id'     => WPGH_BROADCAST,
            'step_id'       => $last_broadcast_id,
            'activity_type' => 'email_opened'
        ) );

        $clicks = WPGH()->activity->count( array(
            'funnel_id'     => WPGH_BROADCAST,
            'step_id'       => $last_broadcast_id,
            'activity_type' => 'email_link_click'
        ) );

        $dataset[] = array(
            'label' => _x('Opened', 'stats', 'groundhogg'),
            'data' => $opens - $clicks,
            'url'  => admin_url( sprintf( 'admin.php?page=gh_contacts&view=activity&funnel=%s&step=%s&activity_type=%s&start=%s&end=%s', WPGH_BROADCAST, $last_broadcast_id, 'email_opened', 0, time() ) ),
        ) ;
        $dataset[] = array(
            'label' => _x('Clicked', 'stats', 'groundhogg'),
            'data' => $clicks,
            'url'  => admin_url( sprintf( 'admin.php?page=gh_contacts&view=activity&funnel=%s&step=%s&activity_type=%s&start=%s&end=%s', WPGH_BROADCAST, $last_broadcast_id, 'email_link_click', 0, time() ) )
        ) ;
        $dataset[] = array(
            'label' => _x('Unopened', 'stats', 'groundhogg'),
            'data' => $contact_sum - $opens,
            'url'  => '#'

        ) ;

        usort( $dataset , array( $this, 'sort' ) );

        return array_values( $dataset );
    }

    public function sort( $a, $b )
    {
        return $b[ 'data' ] - $a[ 'data' ];
    }

    /**
     * Show extra info
     *
     * @return string
     */
    protected function extra_widget_info()
    {

        $data = $this->get_data();

        if ( empty( $data ) ){
            return __( 'No broadcast data available.', 'groundhogg' );
        }

        ?>
        <hr>
        <table class="chart-summary">
        <thead>
        <tr>
            <th><?php _ex( 'Stat', 'column_title','groundhogg' ); ?></th>
            <th><?php _ex( 'Count', 'column_title', 'groundhogg' ); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php

        foreach ( $data as $dataset ):
            ?>
        <tr>
            <td><a href="<?php echo $dataset[ 'url' ]; ?>"><?php echo $dataset[ 'label' ] ?></a></td>
            <td class="summary-total"><?php echo $dataset[ 'data' ] ?></td>
        </tr>
        <?php
        endforeach;

        ?></tbody>
        </table>
        <hr>
        <?php

        $activity = WPGH()->activity->get_activity( array(
            'funnel_id'     => WPGH_BROADCAST,
            'step_id'       => $this->last_broadcast_id,
            'activity_type' => 'email_link_click'
        ) );


        $links = array();

        foreach ( $activity as $event ){

            if ( isset( $links[ $event->referer ] ) ){
                $links[ $event->referer ] += 1;
            } else {
                $links[ $event->referer ] = 1;
            }

        }

        ?>
        <table class="chart-summary">
        <thead>
        <tr>
            <th><?php _ex( 'Link', 'column_label', 'groudhogg' ); ?></th>
            <th><?php _ex( 'Clicks', 'column_label', 'groudhogg' ); ?></th>
        </tr>
        </thead><tbody><?php

        if ( empty( $links ) ){

            ?>
            <tr>
                <td colspan="2"><?php _ex( 'No Links Clicked...', 'notice', 'groundhogg' ); ?></td>
            </tr>
            <?php

        }

        foreach ( $links as $link => $clicks ):
            ?>
            <tr>
                <td><?php echo sprintf( "<a href='%s' target='_blank' >%s</a>", $link, $link ) ?></td>
                <td><?php echo sprintf( "<a href='%s' target='_blank' >%d</a>", admin_url( sprintf( 'admin.php?page=gh_contacts&view=activity&funnel=%s&step=%s&activity_type=%s&referer=%s&start=%s&end=%s', WPGH_BROADCAST, $this->last_broadcast_id, 'email_link_click', $link, 0, time() ) ), $clicks ); ?></td>
            </tr>
        <?php
        endforeach;
        ?>
        </tbody></table><?php

        $this->export_button();

        return '';

    }

    /**
     * Return export info in friendly format
     *
     * @return array
     */
    protected function get_export_data()
    {

        $export = [];

        $data = $this->get_data();

        foreach ( $data as $data_set ){

            $export[] = [
                _x( 'Country', 'column_title', 'groundhogg' ) => $data_set[ 'label' ],
                _x( 'Contacts', 'column_title', 'groundhogg' ) => $data_set[ 'data' ]
            ];

        }

        return $export;

    }
}