<?php
require_once '../app/Mage.php';
Mage::app('admin');
ini_set('display_errors', 1);
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
 		$this->url= Mage::getStoreConfig('saboroapi/torqus/apiurl');
 		$this->userName=Mage::getStoreConfig('saboroapi/torqus/userName');
 		$this->password= Mage::getStoreConfig('saboroapi/torqus/password');
 		$this->type=Mage::getStoreConfig('saboroapi/torqus/type');
 		$this->companyId= Mage::getStoreConfig('saboroapi/torqus/companyId');
 		$this->siteId=Mage::getStoreConfig('saboroapi/torqus/siteId');
 		$this->vendorId= Mage::getStoreConfig('saboroapi/torqus/vendorId');
 		
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
 		return  $auth;//$jsonAuth;
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

	    // Optional Authentication:
	    //curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	    //curl_setopt($curl, CURLOPT_USERPWD, "username:password");

	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	    $result = curl_exec($curl);
	    curl_close($curl);
	    return $result;
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

	public function run(){
		// $this->DeleteAllCategory();die();
		$function="getAllFoodCategory";
		$url=$this->url.$function;
		$method="POST";
		$auth= $this->auth();
		$store= $this->getAllStore();
		$category= array();
		foreach($store as $key=>$value){
			$isStore = Mage::getModel('core/store')->load($value, "code");
			if($isStore->getId()>1){
				$storedata= explode("_",$value);
				$auth["siteId"]=$storedata[1];
				$jsonAuth= json_encode($auth);
				//print_r($jsonAuth);
				$result	=  $this->CallAPI($method, $url, $jsonAuth);		
				$category= json_decode($result);
				print_r($category);

			}
		}
			if(!empty($category)){
			foreach($category as $key=>$result){
	    		echo $key ."-".$result->categorySKU; 
		        $data['general']['Id']=0;
		        $data['general']['name'] = $result->foodCategoryName;
		        $data['general']['meta_title'] = "";
		        $data['general']['meta_description'] = "";
		        $data['general']['is_active'] = $result->foodIsActive;
		        $data['general']['url_key'] = $result->foodCategoryName;
		        $data['general']['display_mode'] = "PRODUCTS";
		        $data['general']['is_anchor'] = 0;
		        $data['general']['categorySKU']=$result->categorySKU;
		        $data['general']['imgUrl']=$result->imgUrl;
		        $storeId = 0;
		        $_categorys = Mage::getModel('catalog/category')->getCollection()
		        ->addAttributeToFilter('category_sku', $result->categorySKU);		        
		        if(count($_categorys)){
		        	foreach($_categorys as $_category);
		        		$data['general']['Id']= $_category->getId();
		        		$this->createCategory($data, $storeId);
		        }else{
		        		$this->createCategory($data, $storeId);
		        }
		        unset($data);
        	}
        	//print_r($data);
		}
	}

	public function getAllDishes(){
		$function="getAllDishes";
		$url=$this->url."/".$function;
		$method="POST";
		$data= $this->auth();
		$result=$this->CallAPI($method, $url, $data);

	}

	public function createCategory($data, $storeId=0){
	    $category = Mage::getModel('catalog/category');
	    $category->setStoreId($storeId);
	    $parentId = '2';
    	// Fix must be applied to run script
	    $category = Mage::getModel('catalog/category');
	    $category->setName($data['general']['name']);
	    $category->setUrlKey($data['general']['url_key']);
	    $category->setIsActive($data['general']['is_active']);
	    $category->setDisplayMode(Mage_Catalog_Model_Category::DM_PRODUCT);
	    $category->setIsAnchor($data['general']['is_active']); //for active achor
	    $category->setStoreId(Mage::app()->getStore()->getId());
	    $mediaAttribute= $this->uploadImages($data['general']['imgUrl'],$data['general']['categorySKU']);
	    //$category->addImageToMediaGallery($mediaAttribute['filePath'],$mediaAttribute['image'],true,false);

	    $category->setImage($mediaAttribute['filePath']);
	    $category->setData('category_sku',$data['general']['categorySKU']);
	   // $category->setData('thumbnail',$data['general']['image']);
	    
	    if($data['general']['Id']>0){
	    	$category->setId($data['general']['Id']);
	    }else{
	    	 $parentCategory = Mage::getModel('catalog/category')->load($parentId);
	    $category->setPath($parentCategory->getPath());
	    }
	   try
	    {
	    	$category->save();
	    	echo "Suceeded <br /> ";
	    }
		catch(Exception $e)
	    {
	    	echo $e->getMessage();
	    }
	    return 1;
	}

	public function uploadImages($imgUrl,$sku){
		$image_url  = $imgUrl; //get external image url from csv
		$image_type = substr(strrchr($image_url,"."),1); //find the image extension
		$filename   = md5($image_url . $sku).'.'.$image_type; //give a new name, you can modify as per your requirement
		$filepath   = Mage::getBaseDir('media') . DS . 'catalog'. DS  . 'category'. DS .$filename; //path for temp storage folder: ./media/import/
		file_put_contents($filepath, file_get_contents(trim($image_url))); //store the image from external url to the temp storage folder
		// $mediaAttribute['image'] = array (
		//         'thumbnail',
		//         'small_image',
		//         'image'
		// );
		$mediaAttribute['filePath']=$filename;
		return $mediaAttribute;
	}
	public function DeleteAllCategory(){
		$categoryCollection = Mage::getModel('catalog/category')->getCollection()
		    ->addFieldToFilter('level', array('gteq' => 2)); //greater than root category id

		foreach($categoryCollection as $category) {
		    if ($category->getProductCount() === 0) {
		        $category->delete();
		    }
		}

		echo 'Empty Categories Deleted!';
	}

} 

$obj = new Torqus_Api();

//$obj->getAllSites();
$obj->run();
//$obj->getAllDishes();
?>