<?php
namespace Groundhogg;

class Files
{
    /**
     * Get the base path.
     *
     * @param string $type
     * @return string
     */
    public function get_base( $type = 'basedir' )
    {
        $base = 'groundhogg';

        $upload_dir = wp_get_upload_dir();

        $base = $upload_dir[ 'basedir' ] . DIRECTORY_SEPARATOR . $base;

        if ( is_multisite() && ! Plugin::$instance->settings->is_global_multisite() ){
            $base .= '/' . get_current_blog_id();
        }

        return wp_normalize_path( apply_filters( "groundhogg/files/uploads/{$type}", $base ) );
    }


    /**
     * Get the base uploads path.
     *
     * @return string
     */
    public function get_base_uploads_dir()
    {
        return $this->get_base( 'basedir' );
    }

    /**
     * Get the base uploads path.
     *
     * @return string
     */
    public function get_base_uploads_url()
    {
        return $this->get_base( 'baseurl' );
    }

    /**
     * Generic function for mapping to uploads folder.
     *
     * @param string $subdir
     * @param string $file_path
     * @param bool $create_folders
     * @return string
     */
    public function get_uploads_dir( $subdir='uploads', $file_path='', $create_folders=false )
    {
        $path = untrailingslashit( wp_normalize_path( sprintf( "%s/%s/%s",  $this->get_base_uploads_dir(), $subdir, $file_path ) ) );

        if ( $create_folders ){
            wp_mkdir_p( dirname( $path ) );
        }

        return $path;
    }

    /**
     * Generic function for mapping to uploads folder.
     *
     * @param string $subdir
     * @param string $file_path
     * @return string
     */
    public function get_uploads_url( $subdir='uploads', $file_path='' )
    {
        $path = untrailingslashit( sprintf( "%s/%s/%s",  $this->get_base_uploads_url(), $subdir, $file_path ) );
        return $path;
    }

    /**
     * @return string Get the CSV import URL.
     */
    public function get_csv_imports_dir( $file_path='', $create_folders=false ){
        return  $this->get_uploads_dir( 'imports', $file_path, $create_folders );
    }

    /**
     * @return string Get the CSV import URL.
     */
    public function get_csv_imports_url( $file_path='' ){
        return  $this->get_uploads_url( 'imports', $file_path );
    }

    /**
     * @return string Get the CSV import URL.
     */
    public function get_contact_uploads_dir( $file_path='', $create_folders=false ){
        return  $this->get_uploads_dir( 'uploads', $file_path, $create_folders );
    }

    /**
     * @return string Get the CSV import URL.
     */
    public function get_contact_uploads_url( $file_path='' ){
        return  $this->get_uploads_url( 'uploads', $file_path );
    }

    /**
     * @return string Get the CSV export URL.
     */
    public function get_csv_exports_dir( $file_path='', $create_folders=false ){
        return  $this->get_uploads_dir( 'exports', $file_path, $create_folders );
    }

    /**
     * @return string Get the CSV export URL.
     */
    public function get_csv_exports_url( $file_path='' ){
        return  $this->get_uploads_url( 'exports', $file_path );
    }

}