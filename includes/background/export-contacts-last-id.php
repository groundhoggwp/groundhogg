<?php

namespace Groundhogg\background;

use Groundhogg\Contact_Query;
use function Groundhogg\bold_it;
use function Groundhogg\export_field;
use function Groundhogg\file_access_url;
use function Groundhogg\html;
use function Groundhogg\is_a_contact;
use function Groundhogg\notices;
use function Groundhogg\percentage;
use function Groundhogg\white_labeled_name;

class Export_Contacts_Last_Id extends Export_Contacts {

	protected int $last_id;

	/**
	 * @var resource
	 */
	protected $filePointer;

	const BATCH_LIMIT = 100;

	public function __construct( array $query, string $fileName, array $columns, int $batch = 0 ) {
		unset( $query['order'] );
		unset( $query['orderby'] );

		$this->last_id = 0;
		parent::__construct( $query, $fileName, $columns, $batch );
	}

	public function get_progress() {
		return percentage( $this->items, $this->batch * self::BATCH_LIMIT );
	}

	public function get_batches_remaining() {
		return floor( $this->items / self::BATCH_LIMIT ) - $this->batch;
	}

	/**
	 * Export the contacts
	 *
	 * @return bool true if no more contacts, false otherwise
	 */
	public function process(): bool {

		$this->maybeOpenFile();

		$query = new Contact_Query( $this->query );
		$query->setOrderby( [ 'ID', 'ASC' ] )
		      ->setFoundRows( false )
		      ->setLimit( self::BATCH_LIMIT );
		$query->where()->greaterThan( 'ID', $this->last_id );
		$contacts = $query->query( null, true );

		if ( empty( $contacts ) ) {

			/* translators: %s: the name of the export file */
			$message = sprintf( __( 'Your contacts export %s is ready for download!', 'groundhogg' ), html()->e( 'a', [
//				'class' => 'gh-button primary',
				'href' => file_access_url( '/exports/' . basename( $this->filePath ), true )
			], bold_it( esc_html( basename( $this->filePath ) ) ) ) );

			notices()->add_user_notice( $message, 'success', true, $this->user_id );

			/* translators: %s: site/brand name */
			$subject = sprintf( __( "[%s] Export ready!", 'groundhogg' ), white_labeled_name() );

			wp_mail( get_userdata( $this->user_id )->user_email, $subject, wpautop( $message ), [
				'Content-Type: text/html'
			] );

			return true;
		}

		foreach ( $contacts as $contact ) {
			$this->last_id = $contact->ID;
			$line          = [];

			if ( ! is_a_contact( $contact ) || ! user_can( $this->user_id, 'view_contact', $contact ) ) {
				continue;
			}

			foreach ( $this->columns as $column ) {
				$line[] = export_field( $contact, $column );
			}

			fputcsv( $this->filePointer, $line );
		}

		$this->batch ++;

		return false;
	}

	public function __serialize(): array {
		return [
			'batch'    => $this->batch,
			'user_id'  => $this->user_id,
			'filePath' => $this->filePath,
			'query'    => $this->query,
			'columns'  => $this->columns,
			'items'    => $this->items,
			'last_id'  => $this->last_id,
		];
	}
}
