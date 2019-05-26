<?php
namespace Groundhogg\Admin\Dashboard\Widgets;

use function Groundhogg\percentage;
use Groundhogg\Plugin;

class Country_Widget extends Circle_Graph
{

    /**
     * Any additional information needed for the widget.
     *
     * @return void
     */
    protected function extra_widget_info()
    {
        $html = Plugin::$instance->utils->html;

        $countries = wp_list_pluck( $this->dataset, 'label' );
        $totals = wp_list_pluck( $this->dataset, 'data' );
        $urls = wp_list_pluck( $this->dataset, 'url' );
        $total = array_sum( $totals );

        $rows = [];

        for ( $i=0; $i<count( $countries );$i++ ){
            $rows[] = [
                $countries[ $i ],
                $html->wrap( $html->wrap( $totals[ $i ], 'span', [ 'class' => 'number-total' ] ), 'a', [ 'href' => $urls[ $i ] ] ),
                $html->wrap( percentage( $total, $totals[$i] ), 'span', [ 'class' => 'number-total' ] ) . '%',
            ];
        }

        $html->list_table(
            [ 'class' => $this->get_report_id() ],
            [
                __( 'Country', 'groundhogg' ),
                __( 'Total', 'groundhogg' ),
                __( 'Percentage (%)', 'groundhogg' ),
            ],
            $rows
            ,
            false
        );
    }

    /**
     * Ge the report ID...
     *
     * @return string
     */
    protected function get_report_id()
    {
        return 'contacts_by_country';
    }

    /**
     * Normalize a datum
     *
     * @param $item_key
     * @param $item_data
     * @return array
     */
    protected function normalize_datum($item_key, $item_data)
    {

        $label = ! empty( $item_key ) ? Plugin::$instance->utils->location->get_countries_list( $item_key ): __( 'Unknown' );
        $data  = $item_data;
        $url   = ! empty( $item_key ) ? admin_url( sprintf( 'admin.php?page=gh_contacts&meta_key=country&meta_value=%s', $item_key ) ) : '#';

        return [
            'label' => $label,
            'data' => $data,
            'url'  => $url
        ];
    }
}