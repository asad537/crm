<?php

namespace App;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class Page extends Model {

   public static function insertData($insertData,$product_id){
        // print_r($product_id);
        // die();
      $value=DB::table('variations')->where('product_id', $product_id['product_id'])->get();
    //   echo '<pre>';
    //   print_r($value);
    //   die();
      if($value->count() == 0){
         DB::table('variations')->insert($insertData);
      }
      else{
         DB::table('variations')->insert($insertData); 
      }
   }

}