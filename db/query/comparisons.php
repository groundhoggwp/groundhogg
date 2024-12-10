<?php

namespace Groundhogg\DB\Query;

class Comparisons {
	public const EQUALS = 'equals';
	public const NOT_EQUALS = 'not_equals';
	public const CONTAINS = 'contains';
	public const NOT_CONTAINS = 'not_contains';
	public const STARTS_WITH = 'starts_with';
	public const ENDS_WITH = 'ends_with';
	public const DOES_NOT_START_WITH = 'does_not_start_with';
	public const DOES_NOT_END_WITH = 'does_not_end_with';
	public const LESS_THAN = 'less_than';
	public const LESS_THAN_OR_EQUAL_TO = 'less_than_or_equal_to';
	public const GREATER_THAN = 'greater_than';
	public const GREATER_THAN_OR_EQUAL_TO = 'greater_than_or_equal_to';
	public const EMPTY = 'empty';
	public const NOT_EMPTY = 'not_empty';
}
