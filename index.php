<?php
/*
Plugin Name: Aplayer For Wordpress
Plugin URI: 插件的介绍或更新地址
Description: Aplayer是一款HTML5的音乐播放器，界面仿照网易云音乐外链播放器。
Version: 1.0
Author: 刘荣焕
Author URI: http://liuronghuan.com/
License: A "Slug" license name e.g. GPL2
*/
function music_post_type_movie() {
  $labels = array(
    'name'               => '音乐',
    'singular_name'      => '音乐',
    'add_new'            => '添加音乐',
    'add_new_item'       => '添加音乐信息',
    'edit_item'          => '编辑音乐信息',
    'new_item'           => '新音乐',
    'all_items'          => '所有音乐',
    'view_item'          => '查看音乐',
    'search_items'       => '搜索音乐',
    'not_found'          => '没找到音乐资料',
    'not_found_in_trash' => '回收站里没找到音乐信息', 
    'menu_name'          => '音乐'
  );
  $args = array(
    'public'        => true,
    'labels'        => $labels,
    'menu_position' => 5,
    'taxonomies'    => array(),
    'supports'      => array( 'title', 'thumbnail'),
    'has_archive'   => true,
    'rewrite'       => array( 'slug'  => 'music', 'with_front'  => false ),
   );
  register_post_type( 'music', $args );
}
add_action( 'init', 'music_post_type_movie' );
function music_updated_messages( $messages ) {
  global $post, $post_ID;
  $messages['music'] = array(
    0 => '', 
    1 => sprintf( __('音乐已更新，<a href="%s">点击查看</a>'), esc_url( get_permalink($post_ID) ) ),
    2 => __('自定义字段已更新。', 'iwilling'),
    3 => __('自定义字段已删除。', 'iwilling'),
    4 => __('音乐已更新。', 'iwilling'), 
    5 => isset($_GET['revision']) ? sprintf( __('音乐恢复到了 %s 这个修订版本。'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
    6 => sprintf( __('音乐已发布，<a href="%s">点击查看</a>'), esc_url( get_permalink($post_ID) ) ),
    7 => __('音乐已保存', 'iwilling'),
    8 => sprintf( __('音乐已提交， <a target="_blank" href="%s">点击预览</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
    9 => sprintf( __('音乐发布于：<strong>%1$s</strong>， <a target="_blank" href="%2$s">点击预览</a>'),
      date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
    10 => sprintf( __('音乐草稿已更新，<a target="_blank" href="%s">点击预览</a>', 'iwilling'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
  );
  return $messages;
}
add_filter( 'post_updated_messages', 'music_updated_messages' );
$new_meta_boxes =
array(
  "aplayer_author" => array(
    "name" => "aplayer_author",
    "std" => "请填写作者姓名：",
    "title" => "作者:"),
  "aplayer_cover" => array(
    "name" => "aplayer_cover",
    "std" => "请填写音乐封面地址",
    "title" => "音乐封面:"),
  "aplayer_mp3" => array(
    "name" => "aplayer_mp3",
    "std" => "请填写音乐文件地址：",
    "title" => "音乐地址:"),
  "aplayer_lyric" => array(
    "name" => "aplayer_lyric",
    "std" => "",
    "title" => "歌词:")
);
function new_meta_boxes() {
  global $post, $new_meta_boxes;
  foreach($new_meta_boxes as $meta_box) {
    $meta_box_value = get_post_meta($post->ID, $meta_box['name'].'_value', true);
    if($meta_box_value == "")
      $meta_box_value = $meta_box['std'];
    echo'<h4>'.$meta_box['title'].'</h4>';
    echo '<div class="music_textarea"><textarea rows="2" name="'.$meta_box['name'].'_value">'.$meta_box_value.'</textarea></div>';
  }
  echo '<input type="hidden" name="ludou_metaboxes_nonce" id="ludou_metaboxes_nonce" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />';
}
function create_meta_box() {
  global $theme_name;

  if ( function_exists('add_meta_box') ) {
    add_meta_box( 'new-meta-boxes', '音乐信息', 'new_meta_boxes', 'music', 'normal', 'high' );
  }
}
function save_postdata( $post_id ) {
  global $new_meta_boxes;
  if ( !wp_verify_nonce( $_POST['ludou_metaboxes_nonce'], plugin_basename(__FILE__) ))
    return;
  if ( !current_user_can( 'edit_posts', $post_id ))
    return;       
  foreach($new_meta_boxes as $meta_box) {
    $data = $_POST[$meta_box['name'].'_value'];
    if($data == "")
      delete_post_meta($post_id, $meta_box['name'].'_value', get_post_meta($post_id, $meta_box['name'].'_value', true));
    else
      update_post_meta($post_id, $meta_box['name'].'_value', $data);
   }
}
add_action('admin_menu', 'create_meta_box');
add_action('save_post', 'save_postdata');
function aplayer_input_style(){
    echo'<style type="text/css">
     .music_textarea textarea{width:100%}
    </style>';
    }
add_action('admin_head', 'aplayer_input_style');
if( ! defined( 'APLAYER_URL' ) )define( 'APLAYER_URL', plugin_dir_url( __FILE__ ) );
function aplayer_css_to_head() {
    echo "<link href='".APLAYER_URL."src/APlayer.css?ver=1.0.0' rel='stylesheet' type='text/css'>";
    echo "<script type='text/javascript' src='".APLAYER_URL."src/APlayer.js'></script>";
}
add_action( 'wp_head', 'aplayer_css_to_head' );
function aplayer_shortcode($atts, $content=null) {
    extract(shortcode_atts(array("apid" => ''), $atts));
    $author  = get_post_meta($apid, 'aplayer_author_value', TRUE);
    $cover  = get_post_meta($apid, 'aplayer_cover_value', TRUE);
    $dirth  = get_post_meta($apid, 'aplayer_mp3_value', TRUE);
    $lyric  = get_post_meta($apid, 'aplayer_lyric_value', TRUE);
    $return  = '<div id="player'.$apid.'" class="aplayer">';
    if(!empty($lyric)) {
        $return .= '<pre class="aplayer-lrc-content">'.$lyric.'</pre>';
    }
    $return .= '</div>';
    $return .="
    <script>
        var ap = new APlayer({
            element: document.getElementById('player".$apid."'),
            narrow: false,
            autoplay:false,";
     if(!empty($lyric)) {
         $return .="showlrc: true,";
     }else {
         $return .="showlrc: false,";
     }
     $return .=" 
            music: {
                title: '".get_post($apid)->post_title."',
                author: '".$author."',
                url: '".$dirth."',
                pic: '".$cover."'
            }
        });
        ap.init();
    </script>
    ";
    return $return;
}
add_shortcode('aplayer' , 'aplayer_shortcode' );
add_filter('manage_music_posts_columns', 'add_new_music_columns');   
function add_new_music_columns($book_columns) {   
    $new_columns['cb'] = '<input type="checkbox" />';//不能忘记的选择框
    $new_columns['title'] = __('Title'); 
    $new_columns['id'] = __('ID');   
    $new_columns['shortcode'] = '短代码';          
    return $new_columns;   
}
add_action('manage_music_posts_custom_column', 'manage_music_columns', 10, 2);   
function manage_music_columns($column_name, $id) {   
    global $wpdb;   
    switch ($column_name) {   
    case 'id':   
        echo $id;   
        break;   
    
    case 'shortcode':     
        echo '[aplayer apid="'.$id.'"][/aplayer]';    
        break;   
    default:   
        break;   
    }   
}  
?>