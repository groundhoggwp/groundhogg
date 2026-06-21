<?php

namespace Groundhogg\Background;

trait Exportable {

	/**
	 * @var resource
	 */
	protected $filePointer;

	protected string $filePath;

	/**
	 * @return bool
	 */
	public function can_run() {

		$this->maybeOpenFile();

		return user_can( $this->user_id, 'export_contacts' ) && $this->filePointer;
	}

	/**
	 * Open the file
	 *
	 * @return void
	 */
	protected function maybeOpenFile() {
		if ( ! isset( $this->filePointer ) || ! $this->filePointer ) {
			// File path is known, open the file in add mode
			$this->filePointer = fopen( $this->filePath, 'a' );
		}
	}

	/**
	 * Remember to close the file when we're done
	 * vcd vc bnv
	 * @return void
	 */
	public function stop() {
		fclose( $this->filePointer );
	}


}
