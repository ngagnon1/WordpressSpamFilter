<?php
/*
Plugin Name: NCG Spam filter
Description: Adds a extra validation script to prevent spam
Version:     1
Author:      Nathan Gagnon
*/


function add_form_spam_protection(){
  $encrypt = openssl_encrypt( date('Y-m-d H:i:s'), "aes128", "NCGTESTKEY" );
  $encrypt = preg_replace( "/=/", "", $encrypt);
  $encrypt = preg_replace( "/a/", "aa", $encrypt);
  $encrypt = preg_replace( "/\+/", "ab", $encrypt);
  $encrypt = preg_replace( "/\\//", "ac", $encrypt);

  $str = <<<END
<input type="hidden" id="ncg_part1" name="ncg_part1" value="$encrypt" />
<input type="hidden" id="ncg_part2" name="ncg_part2" value="" />
<script>
setTimeout(function(){
  var myval = document.getElementById('ncg_part1').value;
  document.getElementById('ncg_part2').value = myval.substring(8)+myval.substring(0,8);
}, 5000 );
</script>
END;
  echo $str;
}

function check_form_spam( $comment_id, $comment_object ){
  $encrypt_orig = $_REQUEST['ncg_part2'];
  $encrypt = substr($encrypt_orig,-8).substr($encrypt_orig,0,strlen($encrypt_orig)-8);
  $encrypt = preg_replace( "/ab/", "+", $encrypt );
  $encrypt = preg_replace( "/ac/", "\\/", $encrypt );
  $encrypt = preg_replace( "/aa/", "a", $encrypt );
  $decrypt = openssl_decrypt( $encrypt, "aes128", "NCGTESTKEY" );
  if( !$decrypt || strtotime($decrypt) < strtotime('-4 hours') || strtotime($decrypt) > strtotime('-20 second') ){
    $comment_arr = array(
      "comment_ID" => $comment_object->comment_ID,
      "comment_approved" => "spam",
    );
    wp_update_comment( $comment_arr );
  }
}

add_filter('comment_form', 'add_form_spam_protection', 2);
add_action('wp_insert_comment','check_form_spam',99,2);

