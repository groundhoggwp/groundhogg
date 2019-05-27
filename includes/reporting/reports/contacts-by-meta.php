<?php
namespace Groundhogg\Reporting\Reports;

use function Groundhogg\get_db;
use Groundhogg\Plugin;
use Groundhogg\Reporting\Reporting;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-01-03
 * Time: 3:24 PM
 */

abstract class Contacts_By_Meta extends Objects_By_Meta
{
    public function __construct()
    {
        add_filter(  "groundhogg/reporting/reports/{$this->get_id()}/query", [ $this, 'parse_query' ] );
    }

    public function get_db()
    {
        return get_db( 'contactmeta' );
    }

    public function parse_query( $query )
    {
        $query[ 'contact_id' ] = Plugin::$instance->reporting->get_contact_ids_created_within_time_range();
        return $query;
    }
}