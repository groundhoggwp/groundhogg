<?php
namespace Groundhogg\Admin\Dashboard\Widgets;

use Groundhogg\Broadcast;
use Groundhogg\Classes\Activity;
use function Groundhogg\html;
use function Groundhogg\key_to_words;
use function Groundhogg\percentage;
use function Groundhogg\words_to_key;

/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:19 AM
 */

class Last_Broadcast_Widget extends Circle_Graph
{

    /**
     * @var array
     */
    protected $stats;

    /**
     * Any additional information needed for the widget.
     *
     * @return void
     */
    protected function extra_widget_info()
    {
        if ( empty( $this->stats ) ){
            return;
        }

        html()->list_table(
            [ 'class' => 'last-broadcast' ],
            [
                __( 'Total Sent', 'groundhogg' ),
                __( 'Opened', 'groundhogg' ),
                __( 'Clicked', 'groundhogg' ),
            ],
            [
                [
                    html()->wrap( $this->stats[ 'sent' ], 'span', [ 'class' => 'number-total' ] ),
                    html()->wrap( sprintf( '%d (%s%%)', $this->stats[ 'opened' ], percentage( $this->stats[ 'sent' ], $this->stats[ 'opened' ] ) ), 'span', [ 'class' => 'number-total' ] ),
                    html()->wrap( sprintf( '%d (%s%%)', $this->stats[ 'clicked' ], percentage( $this->stats[ 'opened' ], $this->stats[ 'clicked' ] ) ), 'span', [ 'class' => 'number-total' ] ),
                ]
            ],
            false
        );
    }

    protected function normalize_data( $stats )
    {
        $this->stats = $stats;

        if ( empty( $stats ) ){
            return $stats;
        }

        /*
        * create array  of data ..
        */
        $dataset = array();

        $dataset[] = array(
            'label' => _x('Opened', 'stats', 'groundhogg'),
            'data' => $stats[ 'opened' ] - $stats[ 'clicked' ],
            'url'  => add_query_arg(
                [ 'activity' => [ 'activity_type' => Activity::EMAIL_OPENED, 'step_id' => $stats[ 'id' ], 'funnel_id' => Broadcast::FUNNEL_ID ] ],
                admin_url( sprintf( 'admin.php?page=gh_contacts' ) )
            )
        );

        $dataset[] = array(
            'label' => _x('Clicked', 'stats', 'groundhogg'),
            'data' => $stats[ 'clicked' ],
            'url'  => add_query_arg(
                [ 'activity' => [ 'activity_type' => Activity::EMAIL_CLICKED, 'step_id' => $stats[ 'id' ], 'funnel_id' => Broadcast::FUNNEL_ID ] ],
                admin_url( sprintf( 'admin.php?page=gh_contacts' ) )
            ),
        );

        $dataset[] = array(
            'label' => _x('Unopened', 'stats', 'groundhogg'),
            'data' => $stats[ 'unopened' ],
            'url'  => '#'
        );

        $this->dataset = $dataset;

        return $dataset;
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
        return [
            'label' => ucwords( key_to_words( $item_key ) ),
            'data' => $item_data,
            'url'  => '#', // TODO add URL to contacts page...
        ];
    }

    /**
     * Ge the report ID...
     *
     * @return string
     */
    protected function get_report_id()
    {
        return 'last_broadcast';
    }
}