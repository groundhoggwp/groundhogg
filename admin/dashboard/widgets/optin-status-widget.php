<?php
namespace Groundhogg\Admin\Dashboard\Widgets;

use function Groundhogg\percentage;
use Groundhogg\Plugin;
use Groundhogg\Preferences;

/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:19 AM
 */

class Optin_Status_Widget extends Circle_Graph
{

    /**
     * Any additional information needed for the widget.
     *
     * @return void
     */
    protected function extra_widget_info()
    {
        $html = Plugin::$instance->utils->html;

        $statuses = wp_list_pluck( $this->dataset, 'label' );
        $totals = wp_list_pluck( $this->dataset, 'data' );
        $urls = wp_list_pluck( $this->dataset, 'url' );
        $total = array_sum( $totals );

        $rows = [];

        for ( $i=0; $i<count( $statuses );$i++ ){
            $rows[] = [
                $statuses[ $i ],
                $html->wrap( $html->wrap( $totals[ $i ], 'span', [ 'class' => 'number-total' ] ), 'a', [ 'href' => $urls[ $i ] ] ),
                $html->wrap( percentage( $total, $totals[$i] ), 'span', [ 'class' => 'number-total' ] ) . '%',
            ];
        }

        $html->striped_table(
            [ 'class' => $this->get_report_id() ],
            [
                __( 'Status', 'groundhogg' ),
                __( 'Total', 'groundhogg' ),
                __( 'Percentage (%)', 'groundhogg' ),
            ],
            $rows
            ,
            false
        );
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
        switch ( $item_key ){
            default:
            case Preferences::UNCONFIRMED:
                $label = __( 'Unconfirmed', 'groundhogg' );
                break;
            case Preferences::CONFIRMED:
                $label = __( 'Confirmed', 'groundhogg' );
                break;
            case Preferences::HARD_BOUNCE:
                $label = __( 'Bounced', 'groundhogg' );
                break;
            case Preferences::SPAM:
                $label = __( 'Spam', 'groundhogg' );
                break;
            case Preferences::UNSUBSCRIBED:
                $label = __( 'Unsubscribed', 'groundhogg' );
                break;
        }

        return [
            'label' => $label,
            'data' => $item_data,
            'url'  => admin_url( 'admin.php?page=gh_contacts&optin_status=' . $item_key )
        ];
    }

    /**
     * Ge the report ID...
     *
     * @return string
     */
    protected function get_report_id()
    {
        return 'contacts_by_optin_status';
    }
}