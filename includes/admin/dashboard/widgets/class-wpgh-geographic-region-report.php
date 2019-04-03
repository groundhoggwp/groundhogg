<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:19 AM
 */

class WPGH_Geographic_Region_Report extends WPGH_Circle_Graph_Report
{
    public function __construct()
    {
        $this->wid = 'geographic_region_report';
        $this->name = _x( 'Geographic Report: State/Province/Region', 'widget_name', 'groundhogg' );

        parent::__construct();
    }

    public function get_data()
    {

        if ( ! empty( $this->data ) ){
            return $this->data;
        }

        $country_code = strtoupper( $this->get_url_var( 'country_code', $this->get_option( 'country_code' ) ) );

        if ( ! $country_code ){
            $country_code = 'US';
        }

        $this->update_options(
            array( 'country_code' => $country_code )
        );

        global $wpdb;

        $dataset  =  array();

        $table_name = WPGH()->contact_meta->table_name;

        $contact_ids = wp_parse_id_list( wp_list_pluck( $wpdb->get_results( $wpdb->prepare( "SELECT contact_id FROM $table_name WHERE meta_key = %s AND meta_value = %s", 'country', $country_code ) ), 'contact_id' ) );
        $ids = implode( ',', $contact_ids );
        $regions = $wpdb->get_results( $wpdb->prepare( "SELECT meta_value FROM $table_name WHERE meta_key = %s AND contact_id IN ( $ids )", 'region' ) );

        foreach ( $regions as $result ){

            $result->meta_value = ucwords( strtolower( $result->meta_value ) );
            if ( key_exists( $result->meta_value, $dataset ) ){
                $dataset[ $result->meta_value ][ 'data' ]++;
            } else {

                $label = 'unknown';
                if ( $result->meta_value ){
                    $label = $result->meta_value;
                }

                $dataset[ $result->meta_value ] = [
                    'label' => $label,
                    'data' => 1,
                    'url'  => admin_url( sprintf( 'admin.php?page=gh_contacts&meta_key=region&meta_value=%s', $result->meta_value ) )
                ];

            }


        }

        if ( empty( $dataset ) ){
            $dataset[] = [
                'label' => 'No Data',
                'data' => 1,
                'url'  => '#'
            ];
        }

        $dataset = array_values( $dataset );
        usort( $dataset , array( $this, 'sort' ) );

        /* Pair down the results to largest 10 */
        if ( count( $dataset ) > 10 ){

            $other_dataset = [
                'label' => __( 'Other' ),
                'data' => 0,
                'url'  => '#'
            ];

            $other_countries = array_slice( $dataset, 10 );
            $dataset = array_slice( $dataset, 0, 10);

            foreach ( $other_countries as $c_data ){
                $other_dataset[ 'data' ] += $c_data[ 'data' ];
            }

            $dataset[] = $other_dataset;

        }

        usort( $dataset , array( $this, 'sort' ) );
        $this->data = array_values( $dataset );
        return $this->data;
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
?>
        <form method="get" action="" style="margin-top: 10px;">
            <?php

            $this->form_reporting_inputs();

            ?><div style="display:inline-block;width: 200px;margin-right: 5px"><?php
            $args = array(
                'name'      => 'country_code',
                'id'        => 'country_code',
                'data'   => wpgh_get_countries_list(),
                'selected' => $this->get_option( 'country_code' ),
            ); echo WPGH()->html->select2( $args );
                ?></div><?php
            submit_button( __( 'Update' ), 'secondary', 'change_country_code', false );

            ?>
        </form>
<?php

        $data = $this->get_data();

        ?>
        <hr>
        <table class="chart-summary">
        <thead>
        <tr>
            <th><?php _ex( 'Region', 'column_title','groundhogg' ); ?></th>
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
			    _x( 'Region', 'column_title', 'groundhogg' ) => $data_set[ 'label' ],
			    _x( 'Contacts', 'column_title', 'groundhogg' ) => $data_set[ 'data' ]
		    ];

	    }

	    return $export;
    }
}