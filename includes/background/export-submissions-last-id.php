<?php

namespace Groundhogg\background;

use Groundhogg\Contact_Query;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Form\Form_v2;
use Groundhogg\Submission;
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

class Export_Submissions_Last_Id extends Task {

	use Exportable;

	protected array $query;
	protected int $last_id;
	protected int $step_id;
	protected int $user_id;
	protected int $batch;
	protected int $items;
	protected string $filePath;

	const BATCH_LIMIT = 100;

	public function __construct( array $query, string $fileName, int $step_id, int $batch = 0 ) {

		unset( $query['order'] );
		unset( $query['orderby'] );

		$this->user_id  = get_current_user_id();
		$this->filePath = files()->get_csv_exports_dir( $fileName );
		$this->step_id  = $step_id;
		$this->batch    = $batch;
		$this->query    = $query;

		$query = new Table_Query( 'submissions' );
		$query->set_query_params( $this->query );
		$this->items = $query->count();
	}

	public function get_progress() {
		return percentage( $this->items, $this->batch * self::BATCH_LIMIT );
	}

	public function get_batches_remaining() {
		return floor( $this->items / self::BATCH_LIMIT ) - $this->batch;
	}

	/**
	 * Title of the task
	 *
	 * @return string
	 */
	public function get_title() {

		$fileName = bold_it( basename( $this->filePath ) );

		if ( $this->get_progress() >= 100 ) {
			$fileName = html()->e( 'a', [
				'href' => file_access_url( '/exports/' . basename( $this->filePath ), true )
			], $fileName );
		}

		return sprintf( 'Export %s submissions to %s', _nf( $this->items ), $fileName );
	}


	/**
	 * Export the contacts
	 *
	 * @return bool true if no more contacts, false otherwise
	 */
	public function process(): bool {

		$this->maybeOpenFile();

		$query = new Table_Query( 'submissions' );
		$query->set_query_params( $this->query )
			  ->setOrderby( [ 'ID', 'ASC' ] )
		      ->setFoundRows( false )
		      ->setLimit( self::BATCH_LIMIT )
		      ->where()
		      ->greaterThan( 'ID', $this->last_id );

		$submissions = $query->get_objects( Submission::class );

		if ( empty( $submissions ) ) {

			/* translators: %s: the name of the export file */
			$message = sprintf( __( 'Your submissions export %s is ready for download!', 'groundhogg' ), html()->e( 'a', [
//				'class' => 'gh-button primary',
				'href' => file_access_url( '/exports/' . basename( $this->filePath ), true )
			], bold_it( esc_html( basename( $this->filePath ) ) ) ) );

			notices()->add_user_notice( $message, 'success', true, $this->user_id );

			/* translators: %s: site/brand name */
			$subject = sprintf( __( "[%s] Submissions export ready!", 'groundhogg' ), white_labeled_name() );

			wp_mail( get_userdata( $this->user_id )->user_email, $subject, wpautop( $message ), [
				'Content-Type: text/html'
			] );

			return true;
		}

		// get the columns, and build meta from that

		/**
		 * @var Submission $submission
		 */
		foreach ( $submissions as $submission ) {
			$this->last_id = $submission->ID;
			$line          = [];

			if ( ! user_can( $this->user_id, 'view_contact', $submission->get_contact_id() ) ) {
				continue;
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
			'step_id'  => $this->step_id,
			'items'    => $this->items,
			'last_id'  => $this->last_id,
		];
	}

	public function __unserialize( array $data ): void {
		parent::__unserialize( $data );

		// Backup in case items was not saved originally
		if ( ! isset( $data['items'] ) ) {
			$query = new Table_Query( 'submissions' );
			$query->set_query_params( $this->query );
			$this->items = $query->count();
		}
	}
}
