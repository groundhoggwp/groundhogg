<?php
namespace Groundhogg\Classes;

use Groundhogg\Base_Object;
use Groundhogg\DB\DB;
use function Groundhogg\get_db;

class Activity extends Base_Object
{
    const EMAIL_OPENED = 'email_opened';
    const EMAIL_CLICKED = 'email_link_click';
    const FORM_IMPRESSION = 'form_impression';
    const FORM_SUBMISSION = 'form_submission';
    const PAGE_VIEW = 'page_view';

    /**
     * Do any post setup actions.
     *
     * @return void
     */
    protected function post_setup()
    {
        // TODO: Implement post_setup() method.
    }

    /**
     * Return the DB instance that is associated with items of this type.
     *
     * @return DB
     */
    protected function get_db()
    {
        return get_db( 'activity' );
    }
}