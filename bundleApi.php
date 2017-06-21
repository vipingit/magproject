<?php
require_once '../app/Mage.php';
Mage::app("admin");
ini_set('display_errors', 1);

try{
$bundleProduct = Mage::getModel('catalog/product');
    $bundleProduct->setStoreId(Mage_Core_Model_App::ADMIN_STORE_ID) //you can set data in store scope
        ->setWebsiteIds(array(1)) //website ID the product is assigned to, as an array
        ->setAttributeSetId(4) //ID of a attribute set named 'default'
        ->setTypeId('bundle') //product type
        ->setCreatedAt(strtotime('now')) //product creation time
//    ->setUpdatedAt(strtotime('now')) //product update time
        ->setSkuType(0) //SKU type (0 - dynamic, 1 - fixed)
        ->setSku('bundlexx1') //SKU
        ->setName('test bundle product96') //product name
        ->setWeightType(0) //weight type (0 - dynamic, 1 - fixed)
//        ->setWeight(4.0000)
        ->setShipmentType(0) //shipment type (0 - together, 1 - separately)
        ->setStatus(1) //product status (1 - enabled, 2 - disabled)
        ->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH) //catalog and search visibility
        //->setManufacturer(28) //manufacturer id
        //->setColor(24)
        //->setNewsFromDate('06/26/2014') //product set as new from
        //->setNewsToDate('06/30/2014') //product set as new to
        ->setCountryOfManufacture('IN') //country of manufacture (2-letter country code)
        ->setPriceType(1) //price type (0 - dynamic, 1 - fixed)
        ->setPriceView(1) //price view (0 - price range, 1 - as low as)
        //->setSpecialPrice(00.44) //special price in form 11.22
        //->setSpecialFromDate('06/1/2014') //special price from (MM-DD-YYYY)
        //->setSpecialToDate('06/30/2014') //special price to (MM-DD-YYYY)
        /*only available if price type is 'fixed'*/
        ->setPrice(11.22) //price, works only if price type is fixed
//        ->setCost(22.33) //price in form 11.22
//        ->setMsrpEnabled(1) //enable MAP
//        ->setMsrpDisplayActualPriceType(1) //display actual price (1 - on gesture, 2 - in cart, 3 - before order confirmation, 4 - use config)
//        ->setMsrp(99.99) //Manufacturer's Suggested Retail Price
        ->setTaxClassId(0) //tax class (0 - none, 1 - default, 2 - taxable, 4 - shipping)
        /*only available if price type is 'fixed'*/
        ->setMetaTitle('test meta title 2')
        ->setMetaKeyword('test meta keyword 2')
        ->setMetaDescription('test meta description 2')
        ->setDescription('This is a long description')
        ->setShortDescription('This is a short description')
        ->setMediaGallery(array('images' => array(), 'values' => array())) //media gallery initialization
        ->setStockData(array(
                'use_config_manage_stock' => 1, //'Use config settings' checkbox
                'manage_stock' => 0, //manage stock
                //'is_in_stock' => 1, //Stock Availability
            )
        )
        ->setCategoryIds(array(3)); //assign product to categories
 
    $bundleOptions = array();
    $bundleOptions = array(
        '0' => array( //option id (0, 1, 2, etc)
            'title' => 'item01', //option title
            'option_id' => '',
            'delete' => '',
            'type' => 'checkbox', //option type
            'required' => '0', //is option required
            'position' => '1' //option position
        ),
        '1' => array(
            'title' => 'item02',
            'option_id' => '',
            'delete' => '',
            'type' => 'checkbox',
            'required' => '0',
            'position' => '2'
        )
    );
 
    $bundleSelections = array();
    $bundleSelections = array(
        '0' => array( //option ID
            '0' => array( //selection ID of the option (first product under this option (option ID) would have ID of 0, second an ID of 1, etc)
                'product_id' => '92', //if of a product in selection
                'delete' => '',
                'selection_price_value' => '10',
                'selection_price_type' => 0,
                'selection_qty' => 1,
                'selection_can_change_qty' => 0,
                'position' => 0,
                'is_default' => 1
            ),
 
            '1' => array(
                'product_id' => '93',
                'delete' => '',
                'selection_price_value' => '10',
                'selection_price_type' => 0,
                'selection_qty' => 1,
                'selection_can_change_qty' => 0,
                'position' => 0,
                'is_default' => 1
            )
        ),
        '1' => array( //option ID
            '0' => array(
                'product_id' => '94',
                'delete' => '',
                'selection_price_value' => '10',
                'selection_price_type' => 0,
                'selection_qty' => 1,
                'selection_can_change_qty' => 0,
                'position' => 0,
                'is_default' => 1
            ),
 
            '1' => array(
                'product_id' => '95',
                'delete' => '',
                'selection_price_value' => '10',
                'selection_price_type' => 0,
                'selection_qty' => 1,
                'selection_can_change_qty' => 0,
                'position' => 0,
                'is_default' => 1
            )
        )
    );
    //flags for saving custom options/selections
    $bundleProduct->setCanSaveCustomOptions(true);
    $bundleProduct->setCanSaveBundleSelections(true);
    $bundleProduct->setAffectBundleProductSelections(true);
 
    //registering a product because of Mage_Bundle_Model_Selection::_beforeSave
    Mage::register('product', $bundleProduct);
 
    //setting the bundle options and selection data
    $bundleProduct->setBundleOptionsData($bundleOptions);
    $bundleProduct->setBundleSelectionsData($bundleSelections);
 
    $bundleProduct->save();
    echo 'success';
} catch (Exception $e) {
    //Mage::log($e->getMessage());
    echo $e->getMessage();
}

?>