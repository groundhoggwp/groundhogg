<?php
namespace Groundhogg;

class Email_Parser
{

    public static function parse_html( $html )
    {
        libxml_use_internal_errors( true );

        $doc = new \DOMDocument();

        $doc->loadHTML( $html );

        libxml_use_internal_errors( false );

        if ( ! class_exists( '\RecursiveDOMIterator' ) ){
            include_once GROUNDHOGG_PATH . 'includes/lib/RecursiveDOMIterator.php';
        }

        /* traverse the HTML to find relevant fields */
        $domTree = new \RecursiveIteratorIterator(
            new \RecursiveDOMIterator( $doc ),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        // Sanitize these things...
        $sanitize_tags = [];
        $sanitize_attributes = [ 'data-grammarly-part' ];
        $sanitize_classes = [];

        $ignore_tags = [ 'html' ];

        /**
         * Iterate recursively over the DOM tree
         *
         * @param $node \DOMElement
         */
        foreach ( $domTree as $node ){

            if ( self::node_has_attribute( $node, $sanitize_attributes ) ){
                $doc->removeChild( $node );
            }

        }

        return $doc->saveHTML();
    }

    /**
     * @param $node \DOMElement
     * @param $attributes array
     * @return bool
     */
    public static function node_has_attribute( $node, $attributes )
    {
        $attributes = ! is_array( $attributes ) ? [ $attributes ] : $attributes;

        foreach ( $attributes as $attribute ){
            if ( $node->nodeType === XML_ELEMENT_NODE && $node->hasAttribute( $attribute ) ){
                return true;
            }
        }

        return false;
    }

}