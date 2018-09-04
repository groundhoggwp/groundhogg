<?php
/**
 * Created by PhpStorm.
 * User: Adrian
 * Date: 2018-08-30
 * Time: 9:47 PM
 */


class GH_Extension
{
	var $name;
	var $id;
	var $file;
	var $version;
	var $author;
	var $img;
	var $description;

	function __construct(
		$id,
		$name,
		$file,
		$version,
		$author,
		$img,
		$description
	) {

		$this->name = $name;
		$this->id   = $id;
		$this->file = $file;
		$this->version = $version;
		$this->author  = $author;
		$this->img     = $img;
		$this->description = $description;

		add_filter( 'get_gh_extensions', array( $this, 'register' ) );
	}

	function register( $extensions )
	{
		$extensions[ $this->id ] = array(
			'item_name'     => $this->name,
			'item_id'       => $this->id,
			'file'          => $this->file,
			'version'       => $this->version,
			'img_source'    => $this->img,
			'description'   => $this->description
		);

		return $extensions;
	}
}