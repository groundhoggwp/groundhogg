<?php

use Groundhogg\Contact;
use function Groundhogg\html;

/**
 * @var $contact Contact
 */
?>
<?php $args = array(
	'id'          => 'add-new-note',
	'name'        => 'add_new_note',
	'class'       => 'full-width',
	'placeholder' => __( 'Add a note...', 'groundhogg' ),
	'value'       => '',
	'rows'        => 2,
	'cols'        => false,
	'attributes'  => ''
);
echo html()->textarea( $args );

echo html()->wrap( html()->button( [
	'id'   => 'add-note',
	'name' => 'add_note',
	'text' => __( 'Add Note', 'groundhogg' ),

] ), 'div' );

?>
<div id="gh-notes"><?php

	$notes = $contact->get_all_notes();

	foreach ( $notes as $note ) {
		include __DIR__ . '/../note.php';
	}

	?></div>
