<?php
namespace Groundhogg\Form\Fields;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-09
 * Time: 4:25 PM
 */

/**
 * TODO Support for file types....
 *
 * Class File
 * @package Groundhogg\Form\Fields
 */
class File extends Input
{

    public function get_default_args()
    {
        return [
            'type'          => 'file',
            'label'         => _x( 'File *', 'form_default', 'groundhogg' ),
            'name'          => '',
            'id'            => '',
            'class'         => 'gh-file-uploader',
            'max_file_size' => wp_max_upload_size(),
            'file_types'    => implode(',', $this->get_default_file_types() ),
            'required'      => false,
            'attributes'    => '',
        ];
    }

    /**
     * Get the name of the shortcode
     *
     * @return string
     */
    public function get_shortcode_name()
    {
        return 'file';
    }

    private function get_default_file_types()
    {
        return [
            '.pdf',
            '.txt',
            '.text',
            '.png',
            '.jpg',
            '.jpeg',
            '.doc',
            '.docx',
        ];
    }

    public function get_file_types()
    {
        return $this->get_att( 'file_types', $this->get_default_file_types() );
    }

    public function render()
    {
        return sprintf(
            '<label class="gh-input-label">%1$s <input type="%2$s" name="%3$s" id="%4$s" class="gh-input %5$s" value="%6$s" placeholder="%7$s" title="%8$s" accept="%9$s" %10$s %11$s></label>',
            $this->get_label(),
            $this->get_type(),
            $this->get_name(),
            $this->get_id(),
            $this->get_classes(),
            $this->get_value(),
            $this->get_placeholder(),
            $this->get_title(),
            $this->get_file_types(),
            $this->get_attributes(),
            $this->is_required() ? 'required' : ''
        );
    }
}