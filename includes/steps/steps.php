<?php
namespace Groundhogg\Steps;

use Groundhogg\Funnel;
use function Groundhogg\isset_not_empty;
use Groundhogg\Steps\Actions\Admin_Notification;
use Groundhogg\Steps\Actions\Apply_Note;
use Groundhogg\Steps\Actions\Apply_Owner;
use Groundhogg\Steps\Actions\Apply_Tag;
use Groundhogg\Steps\Actions\Create_User;
use Groundhogg\Steps\Actions\Date_Timer;
use Groundhogg\Steps\Actions\Delay_Timer;
use Groundhogg\Steps\Actions\Edit_Meta;
use Groundhogg\Steps\Actions\Field_Timer;
use Groundhogg\Steps\Actions\HTTP_Post;
use Groundhogg\Steps\Actions\Remove_Tag;
use Groundhogg\Steps\Actions\Send_Email;
use Groundhogg\Steps\Actions\Send_SMS;
use Groundhogg\Steps\Benchmarks\Account_Created;
use Groundhogg\Steps\Benchmarks\Email_Confirmed;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Elements
 *
 * This exists solely to init the basic steps. Do not look here for adding you own steps. Extending the WPGH_Funnel_Step class is enough.
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
class Steps
{

    /**
     * Storage for the instances of the steps
     *
     * @var array
     */
    private $steps = [];

    function __construct()
    {
        $this->init_steps();
    }

    public function init_steps()
    {
        // Actions
        $this->send_email           = new Send_Email();
        $this->send_sms             = new Send_SMS();
        $this->admin_notification   = new Admin_Notification();
        $this->apply_note           = new Apply_Note();
        $this->apply_tag            = new Apply_Tag();
        $this->remove_tag           = new Remove_Tag();
        $this->date_timer           = new Date_Timer();
        $this->field_timer          = new Field_Timer();
        $this->delay_timer          = new Delay_Timer();
        $this->apply_owner          = new Apply_Owner();
        $this->create_user          = new Create_User();
        $this->edit_meta            = new Edit_Meta();
        $this->http_post            = new HTTP_Post();

        // Benchmarks
        $this->account_created      = new Account_Created();
//        $this->steps[] = new Role_Changed();
//        $this->steps[] = new Login_Status_Changed();
//        $this->steps[] = new Form_Filled();
//        $this->steps[] = new Tag_Applied();
//        $this->steps[] = new Tag_Removed();
//        $this->steps[] = new Page_Visited();
//        $this->steps[] = new Link_Clicked();
        $this->email_confirmed      = new Email_Confirmed();

        do_action( 'groundhogg/steps/init', $this );
    }


    /**
     * Set the data to the given value
     *
     * @param $key string
     * @return Funnel_Step
     */
    public function get_step( $key ){
        return $this->$key;
    }

    /**
     * Magic get method
     *
     * @param $key string
     * @return bool|Funnel_Step
     */
    public function __get( $key )
    {
        if ( isset_not_empty( $this->steps, $key ) ){
            return $this->steps[ $key ];
        }

        return false;
    }


    /**
     * Set the data to the given value
     *
     * @param $key string
     * @param $value Funnel_Step
     */
    public function __set( $key, $value )
    {
        $this->steps[ $key ] = $value;
    }

    /**
     * Return an array of benchmarks
     *
     * @return Funnel_Step[]
     */
    public function get_benchmarks()
    {
        return apply_filters( "groundhogg/steps/benchmarks", [] );
    }

    /**
     * Return an array of actions
     *
     * @return Funnel_Step[]
     */
    public function get_actions()
    {
        return apply_filters( 'groundhogg/steps/actions', [] );
    }

}