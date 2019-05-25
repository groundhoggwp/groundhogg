<?php
namespace Groundhogg\Reporting\Reports;


use Groundhogg\DB\DB;
use Groundhogg\DB\Meta_DB;
use function Groundhogg\get_db;
use Groundhogg\Plugin;
use Groundhogg\Reporting\Reporting;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-01-03
 * Time: 3:24 PM
 */

abstract class Contacts_By_Data extends Objects_By_Data
{
    public function __construct()
    {
        add_filter(  "groundhogg/reporting/reports/{$this->get_id()}/query", [ $this, 'parse_query' ] );
    }

    public function parse_query( $query )
    {
        $query[ 'date_query' ] = [
            'after' => date( 'Y-m-d H:i:s', $this->get_start_time() ),
            'before' =>date( 'Y-m-d H:i:s', $this->get_end_time() ),
        ];

        return $query;
    }
    /**
     * Get the DB
     *
     * @return DB
     */
    public function get_db(){
        return get_db( 'contacts' );
    }
}