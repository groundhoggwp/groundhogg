<?php

namespace Groundhogg\DB\Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
class Date_Ranges {
	public const ANY = 'any';
	public const TODAY = 'today';
	public const TOMORROW = 'tomorrow';
	public const YESTERDAY = 'yesterday';
	public const THIS_WEEK = 'this_week';
	public const LAST_WEEK = 'last_week';
	public const THIS_MONTH = 'this_month';
	public const LAST_MONTH = 'last_month';
	public const THIS_YEAR = 'this_year';
	public const LAST_24_HOURS = '24_hours';
	public const LAST_7_DAYS = '7_days';
	public const LAST_14_DAYS = '14_days';
	public const LAST_30_DAYS = '30_days';
	public const LAST_60_DAYS = '60_days';
	public const LAST_90_DAYS = '90_days';
	public const LAST_365_DAYS = '365_days';
	public const X_DAYS = 'x_days';
	public const BEFORE = 'before';
	public const AFTER = 'after';
	public const BETWEEN = 'between';
	public const DAY_OF = 'day_of';
	public const NEXT_24_HOURS = 'next_24_hours';
	public const NEXT_7_DAYS = 'next_7_days';
	public const NEXT_14_DAYS = 'next_14_days';
	public const NEXT_30_DAYS = 'next_30_days';
	public const NEXT_60_DAYS = 'next_60_days';
	public const NEXT_90_DAYS = 'next_90_days';
	public const NEXT_365_DAYS = 'next_365_days';
	public const NEXT_X_DAYS = 'next_x_days';
}
