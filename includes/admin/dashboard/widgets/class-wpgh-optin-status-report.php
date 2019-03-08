<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:19 AM
 */

class WPGH_Optin_Status_Report extends WPGH_Circle_Graph_Report
{
    public function __construct()
    {
        $this->wid = 'optin_status_report';
        $this->name = _x( 'Optin Status Report', 'widget_name', 'groundhogg' );

        parent::__construct();
    }

    public function get_data()
    {

        $dataset = [];

        $dataset[] = [
            'label' => _x( 'Unconfirmed', 'view', 'groundhogg' ),
            'data' => WPGH()->contacts->count( [ 'optin_status' => WPGH_UNCONFIRMED ] ),
            'url'  => admin_url( 'admin.php?page=gh_contacts&view=optin_status&optin_status=unconfirmed' )
        ];
        $dataset[] = [
            'label' => _x( 'Confirmed', 'view', 'groundhogg' ),
            'data' => WPGH()->contacts->count( [ 'optin_status' => WPGH_CONFIRMED ] ),
            'url'  => admin_url( 'admin.php?page=gh_contacts&view=optin_status&optin_status=confirmed' )
        ];
        $dataset[] = [
            'label' => _x( 'Unsubscribed', 'view', 'groundhogg' ),
            'data' => WPGH()->contacts->count( [ 'optin_status' => WPGH_UNSUBSCRIBED ] ),
            'url'  => admin_url( 'admin.php?page=gh_contacts&view=optin_status&optin_status=opted_out' )
        ];
        $dataset[] = [
            'label' => _x( 'Spam', 'view', 'groundhogg' ),
            'data' => WPGH()->contacts->count( [ 'optin_status' => WPGH_SPAM ] ),
            'url'  => admin_url( 'admin.php?page=gh_contacts&view=optin_status&optin_status=spam' )
        ];
        $dataset[] = [
            'label' => _x( 'Bounced', 'view', 'groundhogg' ),
            'data' => WPGH()->contacts->count( [ 'optin_status' => WPGH_HARD_BOUNCE ] ),
            'url'  => admin_url( 'admin.php?page=gh_contacts&view=optin_status&optin_status=bounce' )
        ];

        $dataset = array_values( $dataset );
        usort( $dataset , array( $this, 'sort' ) );

        return array_values( $dataset );
    }

    public function sort( $a, $b )
    {
        return $b[ 'data' ] - $a[ 'data' ];
    }

    /**
     * Show extr info
     *
     * @return string
     */
    protected function extra_widget_info()
    {

        $data = $this->get_data();

        ?>
        <hr>
        <table class="chart-summary">
        <thead>
        <tr>
            <th><?php _ex( 'Status', 'column_title','groundhogg' ); ?></th>
            <th><?php _ex( 'Contacts', 'column_title', 'groundhogg' ); ?></th>
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
        </table><?php

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
			    _x( 'Optin Status', 'column_title', 'groundhogg' ) => $data_set[ 'label' ],
			    _x( 'Contacts', 'column_title', 'groundhogg' ) => $data_set[ 'data' ]
		    ];

	    }

	    return $export;

    }
}