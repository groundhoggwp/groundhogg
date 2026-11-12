<?php

namespace Groundhogg\Abilities\Contacts;

use Groundhogg\Abilities\Ability;
use Groundhogg\Background\Import_Contacts as Import_Contacts_Task;
use Groundhogg\Background_Tasks;
use Groundhogg\Contact_Query;
use WP_Error;
use function Groundhogg\count_csv_rows;
use function Groundhogg\files;
use function Groundhogg\get_csv_delimiter;
use function Groundhogg\get_mappable_fields;
use function Groundhogg\validate_tags;

/**
 * Imports contacts from a CSV, the same as Tools › Import: the same field mapping, the same Import_Contacts task, the
 * same "Import - <date>" tag on every imported contact, and the same three consent choices.
 *
 * The CSV comes as text or from a URL. Text, because an agent can turn a list it was given into CSV reliably (unlike
 * base64, see Upload_Media). A URL, for a file that's online, e.g. a spreadsheet's CSV export link.
 *
 * The consent choices are required inputs with no default, because they're the person's to make: an agent must not
 * guess whether a list agreed to marketing.
 *
 * A file of up to INLINE_ROWS rows is imported straight away, a bigger one by the background task, on WordPress cron.
 */
class Import_Contacts extends Ability {

	protected const NAME       = 'groundhogg/import-contacts';
	protected const CATEGORY   = 'groundhogg-contacts';
	protected const CAPABILITY = 'import_contacts';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = false;
	protected const IDEMPOTENT  = false;

	/**
	 * Files up to this many rows are imported during the call
	 */
	const INLINE_ROWS = 500;

	protected function get_args(): array {

		$consent = function ( $description ) {
			return [
				'type'        => 'boolean',
				'description' => $description,
			];
		};

		return [
			'label'       => __( 'Import Contacts', 'groundhogg' ),
			'description' => __( 'Import contacts from a CSV, like Tools › Import. Give the CSV as text (with a header row) or the URL of a CSV file, and map its columns to contact fields. Every imported contact gets an "Import - <date>" tag, plus any `tags` you give. Contacts that already exist, matched by email address, are updated. The three consent choices are required: ask the person, never assume them. Up to 500 rows import straight away; a bigger file imports in the background on WordPress cron.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'field_map', 'email_is_confirmed', 'gdpr_consent', 'marketing_consent' ],
				'properties'           => [
					'csv'                => [
						'type'        => 'string',
						'description' => __( 'The CSV itself, with a header row. Use this or `url`.', 'groundhogg' ),
					],
					'url'                => [
						'type'        => 'string',
						'format'      => 'uri',
						'description' => __( 'The public address of a CSV file to download. Use this or `csv`.', 'groundhogg' ),
					],
					'field_map'          => [
						'type'                 => 'object',
						'description'          => __( 'Column header => the contact field it goes in. One column must map to "email". Leave out columns to skip them. Fields: the keys listed in `fields` when the map is wrong, e.g. first_name, last_name, email, primary_phone, mobile_phone, birthday, company_name, street_address_1, city, region, postal_zip, country, tags (the value becomes a tag), notes, meta (custom meta named after the column), or a custom field\'s key (see groundhogg/list-custom-fields).', 'groundhogg' ),
						'additionalProperties' => [ 'type' => 'string' ],
					],
					'tags'               => [
						'type'        => 'array',
						'items'       => [ 'type' => [ 'string', 'integer' ] ],
						'description' => __( 'Tags for every imported contact, by name or ID. Missing names are created if you\'re allowed to.', 'groundhogg' ),
					],
					'email_is_confirmed' => $consent( __( 'Whether these people confirmed their email addresses, e.g. through double opt-in. Marks them confirmed.', 'groundhogg' ) ),
					'gdpr_consent'       => $consent( __( 'Whether these people agreed to their data being processed (GDPR).', 'groundhogg' ) ),
					'marketing_consent'  => $consent( __( 'Whether these people agreed to marketing email. Without it, on a site with strict GDPR, they won\'t be sent marketing email.', 'groundhogg' ) ),
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'rows'          => [ 'type' => 'integer' ],
					'imported'      => [
						'type'        => [ 'integer', 'null' ],
						'description' => __( 'Contacts with the import tag once it finished, or null when it runs in the background or there\'s no import tag.', 'groundhogg' ),
					],
					'background'    => [
						'type'        => 'boolean',
						'description' => __( 'Whether it runs in the background. It needs WordPress cron, see groundhogg/get-cron-status.', 'groundhogg' ),
					],
					'import_tag_id' => [
						'type'        => [ 'integer', 'null' ],
						'description' => __( 'The "Import - <date>" tag every imported contact gets, to find them with groundhogg/search-contacts. Null if you can\'t create tags.', 'groundhogg' ),
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$has_csv = isset( $input['csv'] ) && trim( $input['csv'] ) !== '';
		$has_url = ! empty( $input['url'] );

		if ( $has_csv === $has_url ) {
			return new WP_Error( 'groundhogg_invalid_input', __( 'Give either `csv` or `url`, not both.', 'groundhogg' ) );
		}

		$file = $has_csv ? $this->save_csv( $input['csv'] ) : $this->download_csv( $input['url'] );

		if ( is_wp_error( $file ) ) {
			return $file;
		}

		$map = $this->check_map( $file, (array) $input['field_map'] );

		if ( is_wp_error( $map ) ) {
			wp_delete_file( $file );

			return $map;
		}

		// only made if the person can add tags, like on Tools › Import
		/* translators: %s: the date and time */
		$import_tag = validate_tags( [ sprintf( __( 'Import - %s', 'groundhogg' ), date_i18n( 'Y-m-d H:i:s' ) ) ] )[0] ?? 0;
		$tags       = array_values( array_unique( array_filter( array_merge( [ $import_tag ], validate_tags( (array) ( $input['tags'] ?? [] ) ) ) ) ) );

		$task = new Import_Contacts_Task( basename( $file ), [
			'is_confirmed'      => (bool) $input['email_is_confirmed'],
			'gdpr_consent'      => (bool) $input['gdpr_consent'],
			'marketing_consent' => (bool) $input['marketing_consent'],
			'field_map'         => $map,
			'tags'              => $tags,
		] );

		$rows = count_csv_rows( $file );

		$result = [
			'rows'          => (int) $rows,
			'imported'      => null,
			'background'    => true,
			'import_tag_id' => $import_tag ? (int) $import_tag : null,
		];

		if ( $rows > self::INLINE_ROWS ) {

			$added = Background_Tasks::add( $task );

			return is_wp_error( $added ) ? $added : $result;
		}

		if ( ! $task->can_run() ) {
			return new WP_Error( 'groundhogg_import_failed', __( 'The import could not start.', 'groundhogg' ) );
		}

		// process() does a batch at a time and says when there's nothing left
		while ( ! $task->process() ) {
			continue;
		}

		$task->stop();

		if ( $import_tag ) {
			$query              = new Contact_Query( [ 'tags_include' => [ $import_tag ] ] );
			$result['imported'] = (int) $query->count();
		}

		$result['background'] = false;

		return $result;
	}

	/**
	 * Put a CSV file in the imports folder, where the import task reads it
	 *
	 * @param string $tmp a temporary file, moved into place
	 *
	 * @return string|WP_Error the file's path
	 */
	protected function store( string $tmp ) {

		$file = [
			'name'     => sprintf( 'import-%s.csv', gmdate( 'Y-m-d-His' ) ),
			'tmp_name' => $tmp,
			'size'     => filesize( $tmp ),
			'error'    => 0,
		];

		$stored = files()->safe_file_upload( $file, [ 'csv' => 'text/csv' ], 'imports', true );

		if ( is_wp_error( $stored ) ) {
			wp_delete_file( $tmp );

			return $stored;
		}

		return $stored['file'];
	}

	/**
	 * @param string $csv
	 *
	 * @return string|WP_Error
	 */
	protected function save_csv( string $csv ) {

		$tmp = wp_tempnam( 'import.csv' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- a temporary file
		if ( ! $tmp || file_put_contents( $tmp, $csv ) === false ) {
			return new WP_Error( 'groundhogg_import_failed', __( 'Could not save the CSV.', 'groundhogg' ) );
		}

		return $this->store( $tmp );
	}

	/**
	 * @param string $url
	 *
	 * @return string|WP_Error
	 */
	protected function download_csv( string $url ) {

		require_once ABSPATH . 'wp-admin/includes/file.php';

		$tmp = download_url( esc_url_raw( $url ), 60 );

		if ( is_wp_error( $tmp ) ) {
			return new WP_Error( 'groundhogg_download_failed', sprintf(
			/* translators: %s: why the download failed */
				__( 'Could not download the CSV: %s', 'groundhogg' ),
				$tmp->get_error_message()
			) );
		}

		return $this->store( $tmp );
	}

	/**
	 * Check the map against the file's columns and the fields contacts have
	 *
	 * @param string $file
	 * @param array  $map column header => field
	 *
	 * @return array|WP_Error the map, without columns left empty
	 */
	protected function check_map( string $file, array $map ) {

		$handle = fopen( $file, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$headers = $handle ? fgetcsv( $handle, 0, get_csv_delimiter( $file ) ?: ',' ) : false;

		if ( $handle ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		}

		if ( empty( $headers ) ) {
			return new WP_Error( 'groundhogg_invalid_csv', __( 'The CSV has no header row.', 'groundhogg' ) );
		}

		$fields = array_keys( array_reduce( get_mappable_fields(), 'array_merge', [] ) );
		$map    = array_filter( array_map( 'sanitize_text_field', $map ) );

		$unknown_columns = array_diff( array_keys( $map ), $headers );

		if ( ! empty( $unknown_columns ) ) {
			return new WP_Error( 'groundhogg_invalid_field_map', sprintf(
			/* translators: 1: column names, 2: the CSV's column names */
				__( 'The CSV has no column %1$s. Its columns are: %2$s.', 'groundhogg' ),
				implode( ', ', $unknown_columns ),
				implode( ', ', $headers )
			), [ 'columns' => $headers ] );
		}

		$unknown_fields = array_diff( $map, $fields );

		if ( ! empty( $unknown_fields ) ) {
			return new WP_Error( 'groundhogg_invalid_field_map', sprintf(
			/* translators: %s: field keys */
				__( 'These aren\'t contact fields: %s. See `fields` for the ones there are.', 'groundhogg' ),
				implode( ', ', $unknown_fields )
			), [ 'fields' => $fields ] );
		}

		if ( ! in_array( 'email', $map, true ) ) {
			return new WP_Error( 'groundhogg_invalid_field_map', __( 'One column must map to "email": contacts are matched and created by email address.', 'groundhogg' ) );
		}

		return $map;
	}
}
