<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-10-02
 * Time: 12:39 PM
 */

class WPGH_Funnel
{
    /**
     * This ID of the funnel
     *
     * @var int
     */
    public $ID;

    /**
     * The Funnel's title
     *
     * @var string
     */
    public $title;

    /**
     * The funnels steps ordered by their order of appearance
     *
     * @var array
     */
    public $steps;

    /**
     * The status of the funnel inactive/active
     *
     * @var string
     */
    public $status;

    /**
     * @var int the Author ID
     */
    public $author;

    /**
     * @var string the date created
     */
    public $date_created;

    /**
     * @var string the date last updated
     */
    public $last_updated;


    public function __construct( $id )
    {

        $this->ID = $id;



    }

    /**
     * Retrieve the steps from the DB and setup the step array.
     */
    public function setup_steps()
    {

        $steps = WPGH()->steps->get_by( 'funnel_id', $this->ID );

        foreach ( $steps as $step )
        {
            $this->steps[ $step->order ] = $step;
        }

    }

    /**
     * Return whether the funnel is in a state that can run automation
     *
     * @return bool
     */
    public function is_active()
    {
        return $this->status === 'active';
    }


    public function is_contact_active()
    {

    }

    public function export()
    {

    }


}