<?php
/**
 * 
 */
require_once '../app/Mage.php';
Mage::app('admin');
//require_once '../shell/abstract.php';
 class Torqus_Api
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
 		$this->url= Mage::getStoreConfig('saboroapi/torqus/apiurl');
 		$this->userName=Mage::getStoreConfig('saboroapi/torqus/userName');
 		$this->password= Mage::getStoreConfig('saboroapi/torqus/password');
 		$this->type=Mage::getStoreConfig('saboroapi/torqus/type');
 		$this->companyId= Mage::getStoreConfig('saboroapi/torqus/companyId');
 		$this->siteId=Mage::getStoreConfig('saboroapi/torqus/siteId');
 		$this->vendorId= Mage::getStoreConfig('saboroapi/torqus/vendorId');
 	}
 	
 	function auth()
	{
 		$auth=array(
 		"userName"	=>	$this->userName,
 		"password"	=>	$this->password,
 		"type"		=>	$this->type,
 		"companyId"	=>	$this->companyId,
 		// "siteId"	=>	$this->siteId,
 		// "vendorId"	=>	$this->vendorId);
 		);
 		$jsonAuth	= json_encode($auth);
 		return $jsonAuth;
 	}

 	function CallAPI($url, $authData)
	{
	    $curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_PORT => "8081",
		  CURLOPT_URL => $url,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => $authData,
		  CURLOPT_HTTPHEADER => array(
			"cache-control: no-cache"
		  ),
		));
		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);
		if ($err) {
		  return false;
		} else {
		  return $response;
		}
		
	}

	public function getAllWebsite(){
		$url=$this->url."getAllSites";
		$responseData = $this->CallAPI($url,$this->auth());
		if($responseData){
			$storeData = json_decode($responseData);
			foreach($storeData as $key=>$stores){
				$isStore = Mage::getModel('core/store')->load("saboro_".$stores->siteId, "code");
				if(!$isStore->getId()){
					$storeGroup = Mage::getModel('core/store_group');
					$storeGroup->setWebsiteId(1)
						->setName($stores->siteName)
						->setRootCategoryId(2)
						->save();
					$store = Mage::getModel('core/store');
					$store->setCode("saboro_".$stores->siteId)
						->setWebsiteId($storeGroup->getWebsiteId())
						->setGroupId($storeGroup->getId())
						->setName($stores->siteName)
						->setIsActive(1)
						->save();
				}
			}
		}
	}

}
ini_set('display_errors', 1);
$obj = new Torqus_Api();
$obj->getAllWebsite();
echo base64_decode("MjJTKWVkLlJyKH1N");
?>