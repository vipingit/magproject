<?php
/**
 * 
 */

require_once '../app/Mage.php';
Mage::app('admin');

require_once '../shell/abstract.php';
 class Torqus_Api extends Mage_Shell_Abstract
 {
 	protected $userName;
 	protected $password;
 	protected $type;
 	protected $companyId;
 	protected $siteId;
 	protected $vendorId;
 	protected $url;
 	function __construct()
 	{
 		# code... 	
 		$this->url 		= 	Mage::getStoreConfig('saboroapi/torqus/apiurl');
 		$this->userName =	Mage::getStoreConfig('saboroapi/torqus/userName');
 		$this->password = 	Mage::getStoreConfig('saboroapi/torqus/password');
 		$this->type 	=	Mage::getStoreConfig('saboroapi/torqus/type');
 		$this->companyId= 	Mage::getStoreConfig('saboroapi/torqus/companyId');
 		$this->siteId 	=	Mage::getStoreConfig('saboroapi/torqus/siteId');
 		$this->vendorId = 	Mage::getStoreConfig('saboroapi/torqus/vendorId');
 	}
 	
 	function auth(){
 		$auth=array(
 		"userName"=>$this->userName,
 		"password"=>$this->password,
 		"type"=>$this->type,
 		"companyId"=>$this->companyId,
 		//"siteId"=>$this->siteId,
 		"vendorId"=>$this->vendorId);
 		//$jsonAuth= json_encode($auth);
 		return $auth;
 	}

 	public function getAllStore(){
		$allStores = Mage::app()->getStores();
		$store = array();
		foreach ($allStores as $_eachStoreId => $val) 
		{
			$_storeCode = Mage::app()->getStore($_eachStoreId)->getCode();
			$_storeName = Mage::app()->getStore($_eachStoreId)->getName();
			$_storeId = Mage::app()->getStore($_eachStoreId)->getId();
			$store[$_storeId]= $_storeCode;
			//echo $_storeId;			echo $_storeCode;			echo $_storeName;
		}
		return $store;
	}

 	function CallAPI($method, $url, $data = false)
	{
	    $curl = curl_init();
	    switch ($method)
	    {
	        case "POST":
	            curl_setopt($curl, CURLOPT_POST, 1);

	            if ($data)
	                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
	            break;
	        case "PUT":
	            curl_setopt($curl, CURLOPT_PUT, 1);
	            break;
	        default:
	            if ($data)
	                $url = sprintf("%s?%s", $url, http_build_query($data));
	    }

	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	    $result = curl_exec($curl);
	    // echo 'Curl error: ' . curl_error($curl);
	    curl_close($curl);
	    return $result;
	}

	public function run(){
		$function="getAllDishes";
		$url=$this->url.$function;
		$method="POST";
		$auth= $this->auth();
		$store= $this->getAllStore();

		foreach($store as $key=>$value){
			$isStore = Mage::getModel('core/store')->load($value, "code");
			if($isStore->getId()>1){
				$storedata= explode("_",$value);
				$auth['siteId']=$storedata[1];
				$jsonAuth= json_encode($auth);
				//print_r($jsonAuth);
				$result	=  $this->CallAPI($method, $url, $jsonAuth);		
				$products= json_decode($result);

			}
		}

		if(!empty($products)){ $i=0;
			foreach($products as $key=>$result){
				if($result->isCombo==0 ){
					$data['productId']= 0;
					if($pId=$this->checkProductExist($result->dishSKU)){
						$data['productId']= $pId;
					}
					$data['setTypeId']='simple';
					$data['setSku']=$result->dishSKU;
					$data['setName']=$result->dishName;
					$data['setStatus']=$result->dishActive;
					$data['setWeight']='0.00';
					$data['setTaxClassId']='';
					$data['setPrice']=$result->dishPrice;
					$data['setDescription']=$result->dishName;
					$data['setShortDescription']=$result->dishName;
					$data['categoryId']= $this->GetCategoryId($result->categorySKU);
					$data['imgUrl']=$result->imgUrl;
					$productData[$i]=$this->createProduct($data);
					
				} 
				$i++;
				 unset($data);
			}
			foreach($products as $key=>$result){
				if($result->isCombo > 1){
						 $this->createBundleProduct($result);
					}
			}
			//	$this->handleProductImage($productData);
		}
	}

	public function createProduct($data){	
		
		$product = Mage::getModel('catalog/product');
			if($data['productId']>0){
				$product->setId($data['productId']);
			}
			//$mediaAttribute=$this->uploadImages($data['imgUrl'],$data['setSku']);
			$product
		    ->setStoreId(Mage::app()->getStore()->getId()) //you can set data in store scope
		    ->setWebsiteIds(array(1)) //website ID the product is assigned to, as an array
		    ->setAttributeSetId(4) //ID of a attribute set named 'default'
		    ->setTypeId($data['setTypeId']) //product type
		    ->setCreatedAt(strtotime('now')) //product creation time
			->setUpdatedAt(strtotime('now')) //product update time
		 
		    ->setSku($data['setSku']) //SKU
		    ->setName($data['setName']) //product name
		    ->setWeight($data['setWeight'])
		    ->setStatus($data['setStatus']) //product status (1 - enabled, 2 - disabled)
		    ->setTaxClassId(4) //tax class (0 - none, 1 - default, 2 - taxable, 4 - shipping)
		    ->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH) //catalog and search visibility
		    // ->setManufacturer(28) //manufacturer id
		    // ->setColor(24)
		    // ->setNewsFromDate('06/26/2014') //product set as new from
		    // ->setNewsToDate('06/30/2014') //product set as new to
		    // ->setCountryOfManufacture('AF') //country of manufacture (2-letter country code)
		 
		    ->setPrice($data['setPrice']) //price in form 11.22
		    //->setCost(22.33) //price in form 11.22
		    //->setSpecialPrice(00.44) //special price in form 11.22
		    //->setSpecialFromDate('06/1/2014') //special price from (MM-DD-YYYY)
		    //->setSpecialToDate('06/30/2014') //special price to (MM-DD-YYYY)
		    //->setMsrpEnabled(1) //enable MAP
		    //->setMsrpDisplayActualPriceType(1) //display actual price (1 - on gesture, 2 - in cart, 3 - before order confirmation, 4 - use config)
		    //->setMsrp(99.99) //Manufacturer's Suggested Retail Price
		 
		    // ->setMetaTitle('test meta title 2')
		    // ->setMetaKeyword('test meta keyword 2')
		    // ->setMetaDescription('test meta description 2')
		 
		    ->setDescription($data['setDescription'])
		    ->setShortDescription($data['setShortDescription'])
		    ->setData('online_image_url',$data['imgUrl'])
		 	// ->addImageToMediaGallery($mediaAttribute['filePath'],array('image','thumbnail','small_image'),true,false)
		    //->setMediaGallery (array('images'=>array (), 'values'=>array ())) //media gallery initialization
		    //->addImageToMediaGallery($mediaAttribute['filePath'],array('image','thumbnail','small_image'),true,false) 
		    // ->setStockData(array(
		    //                    'use_config_manage_stock' => 1, //'Use config settings'
		    //                    // 'manage_stock'=>0, //manage stock
		    //                    // 'min_sale_qty'=>1, //Minimum Qty Allowed in Shopping Cart
		    //                       'use_config_min_sale_qty'=>1,
		    //                       'use_config_max_sale_qty'=>1,
		    //                       'use_config_enable_qty_increments'=>1
		    //                    // 'max_sale_qty'=>0, //Maximum Qty Allowed in Shopping Cart
		    //                    // 'is_in_stock' => 1, //Stock Availability
		    //                    // 'qty' => 999 //qty
		    //                )
		    // 	)
		 
		    ->setCategoryIds(array($data['categoryId'])); //assign product to categories
		try{
			//$product->save();
			$product->getResource()->save($product);
			$productData=array('productId'=>$product->getId(),'sku'=>$product->getSku(),'imgUrl'=>$data['imgUrl']);
		}catch(Exception $e){
			echo $e->getMessage();
		} 
		return $productData;
	}

	public function handleProductImage($productData){
		foreach($productData as $key=>$values){
			$mediaAttribute=$this->uploadImages($values['imgUrl'],$values['sku']);
			$product = Mage::getModel('catalog/product')->load($values['productId']);
			$product->addImageToMediaGallery($mediaAttribute['filePath'],array('image','thumbnail','small_image'),true,false) ;
			$product->save();
		}
	}
	public function uploadImages($imgUrl,$sku){
		
		$image_url  = $imgUrl; //get external image url from csv
		$image_type = substr(strrchr($image_url,"."),1); //find the image extension
		$filename   = md5($image_url . $sku).'.'.$image_type; //give a new name, you can modify as per your requirement
		//$url1= Mage::getBaseDir('media') . DS . 'import/';
		$filepath   = Mage::getBaseDir('media') . DS . 'import'. DS .$filename; //path for temp storage folder: ./media/import/
		//file_put_contents($filepath, file_get_contents(trim($image_url))); //store the image from external url to the temp storage folder
		copy($image_url,$filepath);	
		$mediaAttribute['filePath']=$filepath;
		return $mediaAttribute;
	}
	public function checkProductExist($sku){
		
		$pId= Mage::getModel('catalog/product')->getIdBySku($sku);

		if(false === $id){
			$pId= 0;
		}
		return $pId;
	}

	public function GetCategoryId($categoryName){
		$catId=2;
		$_categorys = Mage::getModel('catalog/category')->getCollection()
		        ->addAttributeToFilter('category_sku', $categoryName);		        
		    if(count($_categorys)){
		      	foreach($_categorys as $_category);
		      		$catId= $_category->getId();
		   	}
		return $catId;
	}

	public function existOptionBundle($sku){
		$results=array();
		$resource = Mage::getSingleton('core/resource');
		$readConnection = $resource->getConnection('core_read');
		
		$pId = $this->checkProductExist($sku);
		if($pId){
			$query= 'Select cpbov.title FROM  `catalog_product_bundle_selection` cpbs 
					 inner join catalog_product_bundle_option_value cpbov  on cpbov.option_id=cpbs.option_id
					 WHERE cpbs.parent_product_id ='.$pId .' GROUP BY cpbov.value_id ' ;
			$results =  $readConnection->fetchAll($query);
		}
		return $results;
	}

	public function createBundleProduct($product){
		try{
		$bundleOptions = array();
		$bundleSelections = array();
		$i=0;
		$c = json_decode(json_encode($product->comboDishDetails), true);
		$optionArray= $this->existOptionBundle($product->dishSKU);
		foreach($c as $key=>$value){ $i=0;
			foreach ($value as $k=>$v){
				if (count($optionArray) > 0 && array_search($k, array_column($optionArray, 'title'))>=0) 
					{ 
						$bundleOptions= array(); 
					} else { 
						$bundleOptions[$i]=array(
				            'title' => $k, 
				            'option_id' => '115',
				            'delete' => '',
				            'type' => 'checkbox', 
				            'required' => '1', 
				            'position' => $i,
				        );
					}
		      foreach ($v as $con=>$items){
		      	$bundleSelections[$i][$con] = array(
	                'product_id' => $this->getProductId($items,$items['dishSKU']), //if of a product in selection
	                'delete' => '',
	                'selection_price_value' => $items['dishPrice'],
	                'selection_price_type' => 0,
	                'selection_qty' => 1,
	                'selection_can_change_qty' => 0,
	                'position' => $con,
	                'is_default' => 1,
	            );        
		      }
		      $i++;
			}
		     	
		    
		}
 // echo '<pre>';
  //print_r($bundleOptions);
			$bundleProduct = Mage::getModel('catalog/product');
			if($pId=$this->checkProductExist($product->dishSKU)){						
					$bundleProduct->load($data['productId']);
			}

		    $bundleProduct
		    ->setStoreId(Mage::app()->getStore()->getId()) //you can set data in store scope
		        ->setWebsiteIds(array(1)) //website ID the product is assigned to, as an array
		        ->setAttributeSetId(4) //ID of a attribute set named 'default'
		        ->setTypeId('bundle') //product type
		        ->setCreatedAt(strtotime('now')) //product creation time
			    ->setUpdatedAt(strtotime('now')) //product update time
		        ->setSkuType(1) //SKU type (0 - dynamic, 1 - fixed)
		        ->setSku($product->dishSKU) //SKU
		        ->setName($product->dishName) //product name
		        ->setWeightType(0) //weight type (0 - dynamic, 1 - fixed)
		//        ->setWeight(4.0000)
		        ->setShipmentType(0) //shipment type (0 - together, 1 - separately)
		        ->setStatus(1) //product status (1 - enabled, 2 - disabled)
		        ->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH) //catalog and search visibility
		        ->setPriceType(0) //price type (0 - dynamic, 1 - fixed)
		        ->setPriceView(0) //price view (0 - price range, 1 - as low as)
		        /*only available if price type is 'fixed'*/
		//        ->setPrice(11.22) //price, works only if price type is fixed
		//        ->setCost(22.33) //price in form 11.22
		//        ->setMsrpEnabled(1) //enable MAP
		//        ->setMsrpDisplayActualPriceType(1) //display actual price (1 - on gesture, 2 - in cart, 3 - before order confirmation, 4 - use config)
		//        ->setMsrp(99.99) //Manufacturer's Suggested Retail Price
		        ->setTaxClassId(4) //tax class (0 - none, 1 - default, 2 - taxable, 4 - shipping)
		        ->setDescription($product->dishName)
		        ->setShortDescription($product->dishName)
		        ->setData('online_image_url',$product->imgUrl)
		        ->setCategoryIds($this->GetCategoryId($product->categorySKU)); //assign product to categories
		 
		
		    //flags for saving custom options/selections
		    $bundleProduct->setCanSaveCustomOptions(true);
		    $bundleProduct->setCanSaveBundleSelections(true);
		    $bundleProduct->setAffectBundleProductSelections(true);
		 
		    //registering a product because of Mage_Bundle_Model_Selection::_beforeSave
		    //Mage::register('product'.$i, $bundleProduct);
		 
		    //setting the bundle options and selection data
		    $bundleProduct->setBundleOptionsData($bundleOptions);
		    $bundleProduct->setBundleSelectionsData($bundleSelections);

		    $bundleProduct->save();
		    
		    echo 'success';
		} catch (Exception $e) {
		    Mage::log($e->getMessage(),null,'product.log');
		    echo $e->getMessage();
		}
	}

	public function getProductId($items,$sku){
		$productId= Mage::getModel('catalog/product')->getIdBySku($sku);
		if($productId>0){
			return $productId;
		} else { 
			$data=array();

			$data['setTypeId']='simple';
			$data['setSku']=$items['dishSKU'];
			$data['setName']=$items['dishName'];
			$data['setStatus']=1;
			$data['setWeight']='0.00';
			$data['setTaxClassId']='';
			$data['setPrice']=$items['dishPrice'];
			$data['setDescription']=$items['dishName'];
			$data['setShortDescription']=$items['dishName'];
			$data['categoryId']= $this->GetCategoryId($items['categorySKU']);
			$data['imgUrl']=$items['imgUrl'];
			$productData[$i]=$this->createProduct($data);
			return $productData[$i]['productId'];
		}
	}
	public function DeleteAllProduct(){
		//Mage::getModel('catalog/product')->getCollection()->delete();
		$productCollection = Mage::getModel('catalog/product')
           ->getCollection();
			foreach($productCollection as $_product)
			{
			    $productID = $_product->getId();

			    try {
			        $mediaApi = Mage::getModel("catalog/product_attribute_media_api");
			        $items = $mediaApi->items($productID);
			        foreach($items as $item)
			        {
			            $mediaApi->remove($productID, $item['file']);   // this will remove images 
			        }
			        Mage::getModel("catalog/product")->load($productID)->delete(); // this will delete product
			    } catch (Exception $exception){
			        var_dump($exception);
			        die('Exception Thrown');
			    }
		echo 'Empty Categories Deleted!';
		}
	}

	public function fixStockAction()
	{
	    // Set store defaults for Magento
	    $storeId = Mage::app()->getStore()->getId();
	   // Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
	 
	    $pModel = Mage::getModel('catalog/product');
	 
	    $products = $pModel->getCollection();
	 
	    foreach ($products as $product) {
	        $stockData = $product->getStockData();
	 
	        if (!$stockData) {
	            $product = $product->load($product->getId());
	            $stockData = array(
		                        'use_config_manage_stock' => 0, //'Use config settings' checkbox
		                        'manage_stock'=>0, //manage stock
		                        'min_sale_qty'=>0, //Minimum Qty Allowed in Shopping Cart
		                        'max_sale_qty'=>0, //Maximum Qty Allowed in Shopping Cart
		                        'is_in_stock' => 1, //Stock Availability
		                        'qty' => 999,
		                        'use_config_min_sale_qty'=>1,
		                        'use_config_max_sale_qty'=>1,
		                        'use_config_enable_qty_increments'=>1
		                    );
	            $product->setStockData($stockData);
	 
	            try {
	                $product->save();
	            } catch (Exception $e) {
	                echo $e->getMessage();
	            }
	        }
	    }
	}

} 
ini_set('display_errors', 1);
$obj = new Torqus_Api();
$obj->fixStockAction();
//$obj->getAllSites();
//$obj->run();
//$obj->getAllDishes();




?>