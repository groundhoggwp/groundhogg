<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-01-29
 * Time: 1:01 PM
 */
class WPGH_Visual_Composer_Blocks
{
    public function __construct()
    {
        add_action('vcv:api',function ($api) {
            $elementsToRegister = ['groundhoggForms'];
            /** @var \VisualComposer\Modules\Elements\ApiController $elementsApi */
            $elementsApi = $api->elements;
            foreach ($elementsToRegister as $tag) {
                $manifestPath =  WPGH_PLUGIN_DIR. 'blocks/visual-composer/steps/' . $tag . '/manifest.json';
                $elementBaseUrl = WPGH_PLUGIN_BASE_DIR . '/blocks/visual-composer/steps/' . $tag;
                $elementsApi->add($manifestPath, $elementBaseUrl);
            }
        });
    }
}