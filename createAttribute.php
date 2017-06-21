<?php  
  
require_once('../app/Mage.php');

 Mage::app()->setCurrentStore(Mage::getModel('core/store')->load(Mage_Core_Model_App::ADMIN_STORE_ID));

$installer = new Mage_Eav_Model_Entity_Setup('core_setup');
$installer->startSetup();                   

// $installer->addAttribute(Mage_Catalog_Model_Category::ENTITY, 'custom_attribute', array(
//     'group'         => 'General Information',
//     'input'         => 'select',
//     'type'          => 'int',
//     'label'         => 'Show Images On Front',
//     'backend'       => '',
//     'visible'       => true,
//     'required'      => false,
//     'visible_on_front' => true,
//     'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
// ));

/*$installer->addAttribute(Mage_Catalog_Model_Category::ENTITY, 'custom_attribute', array(
    'group'                => 'General Information',
    'type'              => 'int',//can be int, varchar, decimal, text, datetime
    'backend'           => '',
   // 'frontend_input'    => '',
   // 'frontend'          => '',
    'label'             => 'Show Images On Front',
    'input'             => 'select', //text, textarea, select, file, image, multilselect
    //'default' => array(0),
    //'class'             => '',
    'source'            => 'eav/entity_attribute_source_boolean',//this is necessary for select and multilelect, for the rest leave it blank
    'global'             => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,//scope can be SCOPE_STORE or SCOPE_GLOBAL or SCOPE_WEBSITE
    'visible'           => true,
    'visible_on_front' => true,
    //'frontend_class'     => '',
    'required'          => false,//or true
    //'user_defined'      => true,
    //'default'           => '0',
    //'position'            => 100,//any number will do
));*/
$installer->addAttribute(Mage_Catalog_Model_Category::ENTITY, 'category_sku',  array(
        'type'     => 'text',
        'backend'  => '',
        'frontend' => '',
        'label'    => 'Category Sku',
        'input'    => 'text',
        'class'    => '',
        'source'   => '',
        'global'   => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
        'visible'  => true,
        'required' => false,
        'user_defined'  => false,
        'default' => '',
        'searchable' => false,
        'filterable' => false,
        'comparable' => false,
        'group'         => 'General Information',
        'visible_on_front'  => true,
        'unique'     => false,
        ));
//$installer->removeAttribute(Mage_Catalog_Model_Category::ENTITY, 'binding');
$installer->endSetup();

?>