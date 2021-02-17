<?php

use Groundhogg\Contact;
use function Groundhogg\action_input;
use function Groundhogg\admin_page_url;
use function Groundhogg\html;

/**
 * @var $contact Contact
 */
?>
<form method="post" enctype="multipart/form-data">
	<?php action_input( 'upload_file' ); ?>
	<?php wp_nonce_field( 'upload_file' ); ?>
    <input class="gh-file-uploader" type="file" name="files[]" multiple>
	<?php echo html()->wrap( html()->button( [
		'id'   => 'upload-file',
		'name' => 'upload_file',
		'type' => 'submit',
		'text' => __( 'Upload', 'groundhogg' ),
	] ), 'div' ); ?>
</form>
<ul class="info-list">
	<?php

	$files = $contact->get_files();

	foreach ( $files as $key => $item ) :

		$info = pathinfo( $item['file_path'] );

		?>
        <li class="file">
        <span class="data">
		<?php printf( "<a href='%s' class='file-download' target='_blank'>%s</a>", esc_url( $item['file_url'] ), esc_html( $info['basename'] ) ); ?>
        </span>
        <span class="light-text subdata">
		<?php
		printf( "%s | %s", size_format( filesize( $item['file_path'] ) ), strtoupper( $info['extension'] ) );
//		printf( "%s", $info['extension'] );
		echo html()->e( 'span', [ 'class' => 'file-actions' ], [
			" <span class='light-text'>|</span> ",
			html()->e( 'span', [ 'class' => 'delete' ],
				html()->e( 'a', [
					'class' => 'delete',
					'href'  => admin_page_url( 'gh_contacts', [
						'action'   => 'remove_file',
						'file'     => $info['basename'],
						'contact'  => $contact->get_id(),
						'_wpnonce' => wp_create_nonce( 'remove_file' )
					] )
				], __( 'Delete' ) ) ),
		] ) ?>
        </span><?php


//		esc_html( size_format( filesize( $item['file_path'] ) ) ),
//		esc_html( $info['extension'] ),
//		html()->e( 'span', [ 'class' => 'row-actions' ], [
//			html()->e( 'span', [ 'class' => 'delete' ],
//				html()->e( 'a', [
//					'class' => 'delete',
//					'href'  => admin_page_url( 'gh_contacts', [
//						'action'   => 'remove_file',
//						'file'     => $info['basename'],
//						'contact'  => $contact->get_id(),
//						'_wpnonce' => wp_create_nonce( 'remove_file' )
//					] )
//				], __( 'Delete' ) ) ),
//		] )
//	];
		?></li><?php
	endforeach; ?>
</ul>
