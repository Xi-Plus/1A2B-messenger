<?php
function checkdiff($arr) {
	return ($arr[0]!=$arr[1]&&$arr[0]!=$arr[2]&&$arr[0]!=$arr[3]&&$arr[1]!=$arr[2]&&$arr[1]!=$arr[3]&&$arr[2]!=$arr[3]);
}
function randomans() {
	$arr=array(0,0,0,0);
	while (!checkdiff($arr)) {
		$arr[0]=rand(0,9);
		$arr[1]=rand(0,9);
		$arr[2]=rand(0,9);
		$arr[3]=rand(0,9);
	}
	return $arr;
}
function checkans($ans, $gue) {
	$A=0;
	$B=0;
	for ($i=0; $i < 4; $i++) { 
		for ($j=0; $j < 4; $j++) {
			if ($ans[$i]==$gue[$j]) {
				if ($i==$j) $A++;
				else $B++;
			}
		}
	}
	return array($A,$B);
}
?>
