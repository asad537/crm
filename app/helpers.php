<?php  

function product_actual_and_original_price($product_id){
	$result = DB::table("variations")->where("product_id", $product_id)->get();
	$product_detail_by_id = DB::table("products")->where("id", $product_id)->get();
	?>
	<span class="original-price"><?php if(!empty($product_detail_by_id[0]->actual_price)){echo "$".$product_detail_by_id[0]->actual_price;} ?></span><span style="color: #148a74; font-weight: bold; font-size: 18px;"><?php if(!empty($product_detail_by_id[0]->actual_price)){echo " Save ";} ?></span><span style="font-weight: bold; font-size: 18px;"><?php if(!empty($product_detail_by_id[0]->actual_price) AND !empty($result[0]->price)){echo "$"; echo $product_detail_by_id[0]->actual_price-$result[0]->price;} ?></span><br>
	<span class="sale-price">$<?php if(count($result)>0){echo $result[0]->price;} ?></span>
	<?php
}
function social_media(){
 $social_media=DB::table('socials_media')->get();


}
function product_star_rating($product_id){
	$result = DB::table("product_star_rating")->where("product_id", $product_id)->avg("star_rating");
	$rating_value = round($result);
	if($rating_value==1){
	?>
		<i class="fa fa-star" aria-hidden="true"></i>
		<i class="fa fa-star-o" aria-hidden="true"></i>
		<i class="fa fa-star-o" aria-hidden="true"></i>
		<i class="fa fa-star-o" aria-hidden="true"></i>
		<i class="fa fa-star-o" aria-hidden="true"></i>
	<?php }elseif($rating_value==2){ ?>
		<i class="fa fa-star" aria-hidden="true"></i>
		<i class="fa fa-star" aria-hidden="true"></i>
		<i class="fa fa-star-o" aria-hidden="true"></i>
		<i class="fa fa-star-o" aria-hidden="true"></i>
		<i class="fa fa-star-o" aria-hidden="true"></i>
	<?php }elseif($rating_value==3){ ?>
		<i class="fa fa-star" aria-hidden="true"></i>
		<i class="fa fa-star" aria-hidden="true"></i>
		<i class="fa fa-star" aria-hidden="true"></i>
		<i class="fa fa-star-o" aria-hidden="true"></i>
		<i class="fa fa-star-o" aria-hidden="true"></i>
	<?php }elseif($rating_value==4){ ?>
		<i class="fa fa-star" aria-hidden="true"></i>
		<i class="fa fa-star" aria-hidden="true"></i>
		<i class="fa fa-star" aria-hidden="true"></i>
		<i class="fa fa-star" aria-hidden="true"></i>
		<i class="fa fa-star-o" aria-hidden="true"></i>
	<?php }elseif($rating_value==5){ ?>
		<i class="fa fa-star" aria-hidden="true"></i>
		<i class="fa fa-star" aria-hidden="true"></i>
		<i class="fa fa-star" aria-hidden="true"></i>
		<i class="fa fa-star" aria-hidden="true"></i>
		<i class="fa fa-star" aria-hidden="true"></i>
	<?php }else{ ?>
		<i class="fa fa-star" aria-hidden="true"></i>
		<i class="fa fa-star" aria-hidden="true"></i>
		<i class="fa fa-star" aria-hidden="true"></i>
		<i class="fa fa-star" aria-hidden="true"></i>
		<i class="fa fa-star" aria-hidden="true"></i>
	<?php
	}
}

?>