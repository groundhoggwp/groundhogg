<?php

namespace Groundhogg\Background;

use Groundhogg\Contact_Query;
use function Groundhogg\_nf;
use function Groundhogg\bold_it;
use function Groundhogg\export_field;
use function Groundhogg\file_access_url;
use function Groundhogg\files;
use function Groundhogg\html;
use function Groundhogg\is_a_contact;
use function Groundhogg\notices;
use function Groundhogg\percentage;
use function Groundhogg\white_labeled_name;

class Export_Contacts extends Task {

	protected array $query;
	protected array $columns;
	protected int $user_id;
	protected int $batch;
	protected int $contacts;
	protected string $filePath;

	/**
	 * @var resource
	 */
	protected $filePointer;

	const BATCH_LIMIT = 100;

	public function __construct( array $query, string $fileName, array $columns, int $batch = 0 ) {
		$this->query    = $query;
		$this->user_id  = get_current_user_id();
		$this->filePath = files()->get_csv_exports_dir( $fileName );
		$this->columns  = $columns;
		$this->batch    = $batch;

		$query = new Contact_Query( $this->query );
		$this->contacts = $query->count();
	}

	public function get_progress(){
		return percentage( $this->contacts, $this->batch * self::BATCH_LIMIT );
	}

	/**
	 * Title of the task
	 *
	 * @return string
	 */
	public function get_title(){

		$fileName = bold_it( basename( $this->filePath ) );

		if ( $this->get_progress() >= 100 ){
			$fileName = html()->e('a', [
				'href' => file_access_url( '/exports/' . basename( $this->filePath ), true )
			], $fileName );
		}

		return sprintf( 'Export %s contacts to %s', _nf( $this->contacts ), $fileName );
	}

	/**
	 * Open the file
	 *
	 * @return void
	 */
	protected function openFile() {
		// File path is known, open the file in add mode
		$this->filePointer = fopen( $this->filePath, 'a' );
	}

	/**
	 * @return bool
	 */
	public function can_run() {

		if ( ! isset( $this->filePointer ) ) {
			$this->openFile();
		}

		return user_can( $this->user_id, 'export_contacts' ) && $this->filePointer;
	}

	/**
	 * Export the contacts
	 *
	 * @return bool true if no more contacts, false otherwise
	 */
	public function process(): bool {

		$query_args = array_merge( [
			'limit'      => self::BATCH_LIMIT,
			'offset'     => $this->batch * self::BATCH_LIMIT,
			'found_rows' => true,
		], $this->query );

		$query    = new Contact_Query( $query_args );
		$contacts = $query->query( null, true );

		if ( empty( $contacts ) ) {

			$message = sprintf( __( 'Your contacts export %s is ready for download!', 'groundhogg' ), html()->e( 'a', [
//				'class' => 'gh-button primary',
				'href' => file_access_url( '/exports/' . basename( $this->filePath ), true )
			], __( bold_it( basename( $this->filePath ) ), 'groundhogg' ) ) );

			notices()->add_user_notice( $message, 'success', true, $this->user_id );

			$subject = sprintf( __( "[%s] Export ready!" ), white_labeled_name() );

			wp_mail( get_userdata( $this->user_id )->user_email, $subject, wpautop( $message ), [
				'Content-Type: text/html'
			] );

			return true;
		}

		foreach ( $contacts as $contact ) {
			$line = [];

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

	/**
	 * Remember to close the file when we're done
	 * vcd vc bnv
	 * @return void
	 */
	public function stop() {
		fclose( $this->filePointer );
	}

	public function __serialize(): array {
		return [
			'user_id'  => $this->user_id,
			'filePath' => $this->filePath,
			'query'    => $this->query,
			'columns'  => $this->columns,
			'batch'    => $this->batch,
			'contacts' => $this->contacts,
		];
	}

	public function __unserialize( array $data ): void {
		parent::__unserialize( $data );

		// Backup in case contacts was not saved originally
		if ( ! isset( $data[ 'contacts' ] ) ) {
			$query = new Contact_Query( $this->query );
			$this->contacts = $query->count();
		}
	}
}
