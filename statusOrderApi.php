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
			"cache-control: no-cache",
			"content-type: application/json"
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
	public function api_log($incrementId,$request,$response){
		$write = Mage::getSingleton('core/resource')->getConnection('core_write');
		$sql= "Insert into torqus_api_log (incrementId,type,request,response,created_at,updated_at)
		values('".$incrementId."','order',
		'".$request."','".$response."',now(),now())";
		$write->query($sql);
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

	public function run1(){
		
		$order=Mage::getModel("sales/order")->loadByIncrementId('500000023');

		$items = $order->getAllVisibleItems();
		$payment = $order->getPayment();
		//print_r($payment->getData('method'));die;
		//print_r($items);die;
		//print_r($order->getData());die;
		
		$shippingAddress = $order->getShippingAddress();
		//print_r($shippingAddress->getData());die;
		$method='online';
		if($payment->getData('method')=='cashondelivery'){
			$method='cod';
		}
		if($order->getStoreId()==1){
			$siteId=Mage::getStoreConfig('saboroapi/torqus/siteId');
		}else{
			$_storeCode = Mage::app()->getStore($order->getStoreId())->getCode();
			$sId= explode('_', $_storeCode);
			$siteId=$sId[1];
		}
		$orderData['orders']= array();
    	$orderData['orders']=  array(
    		"order_id"=>$order->getIncrementId(),
    		"isEdited"=> 0,
    		);
    	$discountArray=array();
        $orderData['orders']['others']=array(  
        	"discount_coupon_array"     => $discountArray,
	        "instructions"=>"deliver fast",
	        "order_date_time"=> $order->getCreatedAt(),
	        "delivery_date_time"=> $order->getCreatedAt(),
	        "order_type"=> "HD"
   		);

        // "discount_coupon_array": [
        //   {
        //     "discount_coupon": "COUPON100",
        //     "discount_coupon_info": "info about coupon",
        //     "discount_amount": 10
        //   }
        // ],


    	$auth= $this->auth();
    	$auth['siteId']=$siteId;

    	$orderData['orders']['customer_info'] = array(
    			"name"=> $order->getCustomerFirstname()." ".$order->getCustomerlastname() ,
                "mobile"=> $shippingAddress->getTelephone());
    	$orderComboDetails=$this->comboProductData($order);
    	//print_r($orderComboDetails);die;
    	$modifiers=array();
    	foreach ($order->getAllItems() as $item) {
    		if($item->getParentItemId()==NULL){
    		 $orderitems[]=array(
                "item_sku"=> $item->getSku(),
                "item_name"=> $item->getName(),
                "quantity"=> $item->getQtyOrdered(),
                "per_item_price"=> (int)$item->getPrice(),
                "total_per_item_price"=>(int)$item->getRowTotal(),
                "per_item_sc"=> 0,
                "per_item_st"=> 0,
                "per_item_vat"=> 0,
                "per_item_discount"=> (int)$item->getDiscountAmount(),
                "instructions"=> "",
                "packaging_charges"=> "0.00",
                "category_sku"=> $this->getCategory($item->getProductId()),
                "discount_coupon"=> $order->getCouponCode(),
                "discount_coupon_info"=> "",
                "discount_amount"=> (int)$item->getDiscountAmount(),
                "modifiers"=>$modifiers,
                "orderComboDetails"=>($item->getProductType()=="bundle")?$orderComboDetails:""
              );
    		}
    		// $items=$item;
    }
   
      $orderData['orders']['items']=$orderitems;

	    $orderData['orders']['costing']=array(
          "sub_total"=> (int)$order->getSubtotal(),
          "delivery_charges"=> (int)$order->getShippingAmount(),
          "payment_type"=> $method,
          "amount_paid"=> 0,
          "total"=> (int)$order->getGrandTotal(),
          "balance"=>(int)$order->getGrandTotal()
        );
      $orderData['orders']['address']=array(
          "line1"=>$shippingAddress->getStreetFull(),
          "line2"=> '',
          "area"=> $shippingAddress->getData('region'),
          "landmark"=> "",
          "city"=> $shippingAddress->getData('city'),
          "pincode"=> $shippingAddress->getData('postcode')
        );
    	$data= $auth;
    	$data['orders']=array($orderData['orders']);
        $apiData= json_encode($data);
        $url=$this->url.'addOnlineOrder';
       // print_r($apiData);die;
        	$res= $this->CallAPI($url , $apiData);
        print_r($res);
        $this->api_log($order->getIncrementId(),$apiData,$res);
    }

    public function comboProductData($order){

    	$orderComboDetails=array();
    	$bundleId='';
    	foreach($order->getAllItems() as $orderitems){
    		if($orderitems->getProductType()=="bundle"){
    			$bundleId=$orderitems->getId();
    		}

    		if($orderitems->getParentItemId() === $bundleId && $orderitems->getProductType()=="simple"){
    			$orderComboDetail=array(
    				"id"=>$orderitems->getParentItemId(),
    				"item_sku"=>$orderitems->getSku(),
    				"item_name"=>$orderitems->getName(),
    				"quantity"=>$orderitems->getQtyOrdered(),
    				"per_item_price"=>(int)$orderitems->getPrice(),
    				"instructions"=>"",
    				"category_sku"=>$this->getCategory($orderitems->getProductId()),
    				"discount_coupon"=> $orderitems->getCouponCode(),
    				"discount_coupon_info"=>"");
    			$orderComboDetails[]= $orderComboDetail;
    		}
    		
       	}
       	return $orderComboDetails;
    }
	public function getCategory($productId){
		$product = Mage::getModel('catalog/product')->load($productId);
		$categoryIds = $product->getCategoryIds();
		$categoryName = '';
		if (isset($categoryIds[0])){
		$category = Mage::getModel('catalog/category')->setStoreId(Mage::app()->getStore()->getId())->load($categoryIds[0]);
		$categoryName = $category->getName();
		}
		return $categoryName;
	}	

	public function run(){
	    $url=$this->url.'getOrderStatus';
      $orderId="";
      $auth= $this->auth();
      $auth["vendorOrderId"]="500000018";//MHNDR-500000018
      //print_r($auth);
      $authData= json_encode($auth);
      print_r($authData);die;
      $res= $this->CallAPI($url , $authData);
      $orderData= json_decode($res);
      foreach($orderData->status as $key=>$order){ 
        $status= $order->orderStatus;
        $statusTime= $order->orderStatusDttm;      
      }
      echo $status.'=='.$statusTime;

      $TorqusStatusArray= array();
      $TorqusStatusArray[1]='STATUS_ORDER_CRATED';
      $TorqusStatusArray[2]='STATUS_ORDER_ACCEPTED';
      $TorqusStatusArray[3]='STATUS_ORDER_REJECTED';
      $TorqusStatusArray[4]='STATUS_KOT_PRINTED';
      $TorqusStatusArray[5]='STATUS_BILL_PRINTED';
      $TorqusStatusArray[6]='STATUS_ORDER_NC';
      $TorqusStatusArray[7]='STATUS_ORDER_CANCELLED';
      $TorqusStatusArray[8]='STATUS_READY_FOR_DELIVERY';
      $TorqusStatusArray[9]='STATUS_OUT_FOR_DELIVERY';
      $TorqusStatusArray[10]='STATUS_PAYMENT_DONE';
      $TorqusStatusArray[11]='STATUS_ORDER_COMPLETED';
      if($status>0){
        $status=2; echo $TorqusStatusArray[$status];
        switch ($TorqusStatusArray[$status]) {
              case "STATUS_ORDER_CRATED":
                  $ostatus= 'created';
                  $ostate='new';
                  break;
              case "STATUS_ORDER_ACCEPTED":
                  $ostatus= 'accepted';
                   $ostate='processing';
                  break;
              case "STATUS_ORDER_REJECTED":
                  $ostatus= 'rejected';
                  $ostate='canceled';
                  break;
              case "STATUS_KOT_PRINTED":
                  $ostatus= '';
                  $ostate='';
                  break;
              case "STATUS_ORDER_NC":
                  $ostatus= ' ';
                   $ostate='canceled';
                  break;
              case "STATUS_ORDER_CANCELLED":
                  $ostatus= 'canceled';
                  $ostate='canceled';
                  break;
              case "STATUS_READY_FOR_DELIVERY":
                  $ostatus= 'ready_for_delivery';
                  $ostate= 'processing';
                  break;
              case "STATUS_OUT_FOR_DELIVERY":
                  $ostatus= 'delivery';
                  $ostate= 'processing';
                  break;
              case "STATUS_PAYMENT_DONE":
                  $ostatus= 'payment';
                  $ostate='processing';
                  break;
              case "STATUS_ORDER_COMPLETED":
                 $ostatus= 'complete';
                  $ostate='completed';
                  break;
               default:        
          }
          echo $ostatus;
         $this->updateStatus( $auth["vendorOrderId"],$ostatus);
      }
      print_r($orderData);
	}

  public function updateStatus($incrementId,$status){
       
        $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
       // $order->setState($status, true)->save();
        $order->setData("state", "complete");
        $order->setStatus("delivered");
        $history = $order->addStatusHistoryComment('Order status update via Torqus.', false);
        $history->setIsCustomerNotified(false);
        $order->save();

        /*  change order status to 'Completed'   */
       // $order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, true)->save();

        /* change order status to 'Pending'     */
       // $order->setState(Mage_Sales_Model_Order::STATE_NEW, true)->save();

        /* change order status to 'Pending Paypal'         */
        //$order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true)->save();

        /*  change order status to 'Processing'        */
        //$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)->save();

        /*  change order status to 'Completed'        */
        //$order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, true)->save();

        /* change order status to 'Closed'        */
       // $order->setState(Mage_Sales_Model_Order::STATE_CLOSED, true)->save();

        /* change order status to 'Canceled'        */
       // $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true)->save();

        /* change order status to 'Holded'        */
       // $order->setState(Mage_Sales_Model_Order::STATE_HOLDED, true)->save();
  }

} 

$obj = new Torqus_Api();
$obj->run();
?>