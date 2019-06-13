<?php
namespace Groundhogg\Blocks;

class Blocks
{

    public function __construct()
    {
        $this->init_gutenberg();

//        add_action( '', [ $this, 'init_gutenberg' ] );
    }

    public function init_gutenberg()
    {
        include dirname( __FILE__ ) . '/gutenberg/gutenberg.php';
    }

}