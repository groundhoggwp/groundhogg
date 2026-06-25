<?php

namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) exit;

class DraftException extends \Exception {}
class QueueException extends \Exception {}
class SchedulingException extends \Exception {}
class NoItemsException extends \Exception {}
class NoContactsException extends \Exception {}
class InvalidFiltersException extends \Exception {}
class InvalidContactException extends \Exception {}
class InvalidEventException extends \Exception {}
