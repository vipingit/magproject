<?php
class util{
	function calculate($string){
		$str_array = count_chars($string, 1);
		$data= array();
		foreach ($str_array as $ascii => $occurences) {
			$data[chr($ascii)]=$occurences ;
		}
		foreach ($data as $key => $value) {
			if($key=="A"){
				$looping = number_format($value/4,0);
				$aCount=0;
				$aGroup=4;
				for($a=1;$a<=$looping;$a++){
					$aCount+=$aGroup;
					$sum+=7;
				}
				if($value-$aCount){
					$sum+=($value-$aCount)*2;
				}
			}
			if($key=="B"){
				$sum+=$value*12;
			}
			if($key=="C"){
				$looping = number_format($value/6,0);
				$cCount=0;
				$cGroup=6;
				for($c=1;$c<=$looping;$c++){
					$cCount+=$cGroup;
					$sum+=$cGroup*1;
				}
				if($value-$cCount){
					$sum+=($value-$cCount)*1.25;
				}
			}
			if($key=="D"){
				$sum+=$value*0.15;
			}
		}
		return number_format($sum,2);
	}
}



$clas = new util();
$dataArrays = array('ABCDABAA','ABCD','CCCCCCC');

foreach($dataArrays as $dataArray){
	$sums = $clas->calculate($dataArray);
	echo $dataArray." <strong> : </strong> ".$sums."<br><br>";
}


?>
