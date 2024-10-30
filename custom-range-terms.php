<?php
/*
Plugin Name: Custom range terms
Description: With this plugin you can create range of terms (for example, integer value from 1-10) saving your time.
Version: 1.0
Author: Claudio Alese
License: GPLv2 or later
Text Domain: custom-range-terms
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

Copyright 2005-2015 Automattic, Inc.
*/
defined( 'ABSPATH' ) or die();

// get all post type used in the themes
$args = array( "public" => true );
$all_post_types = get_post_types( $args );


function crt_add_taxonomies_to_pt() {   
    $crt_post_types = get_option( 'crt-custom-post-type' );
    if( is_array( $crt_post_types ) === true ){
        foreach( $crt_post_types as $crt_pt){
            // set post tag to pt
            register_taxonomy_for_object_type( 'post_tag', $crt_pt  );

            // set category to pt
            register_taxonomy_for_object_type( 'category', $crt_pt );
        }
    }
}
add_action ( 'init', 'crt_add_taxonomies_to_pt' ); 

$crt_post_types = get_option( 'crt-custom-post-type' );



function crt_display_form() {
    require_once 'form-post-type.php';
}

function crt_add_admin_menu() {
    add_options_page( 'Create range terms - Admin', 'Custom range terms', 'manage_options', 'custom-range-terms', 'crt_display_form' );    
}
add_action( 'admin_menu','crt_add_admin_menu' );

function crt_save_field_range( $post_id, $post, $update ){
    // nonce
    $crt_range_value_nonce_field = sanitize_text_field( $_POST["crt_range_value_nonce_field"] );
    
    // metabox integer range
    $crt_int_range_value_meta_box = sanitize_text_field ( $_POST["int-range-value"] ); 

    // metabox text categories
    $crt_text_value_meta_box = sanitize_text_field( $_POST["text-range-value"] );
    
    
    if ( !isset( $crt_range_value_nonce_field ) || ! wp_verify_nonce( $crt_range_value_nonce_field , 'crt_range_value_nonce' ) ){      
        return $post_id;        
    }
    
    if( !current_user_can( "edit_post", $post_id) )
        return $post_id;

    if( defined( "DOING_AUTOSAVE" ) && DOING_AUTOSAVE )
        return $post_id;


    $meta_box_text_value = "";
    $meta_box_int_value = "";
    
    // integer range
    if( isset( $crt_int_range_value_meta_box ) ) {  
        if( empty($crt_int_range_value_meta_box) ){
            update_post_meta( $post_id, "int-range-value", '');
        }else{
            //validate
            if ( !preg_match('/^[0-9,-]+$/', $crt_int_range_value_meta_box)) {
                return $post_id;
            }

            $all_range_value = preg_replace_callback('/(\d+)-(\d+)/', function($m) {
                return implode(',', range($m[1], $m[2]));
            }, $crt_int_range_value_meta_box);

            $a_int_values = explode( ",",$all_range_value );   

            update_post_meta( $post_id, "int-range-value", $crt_int_range_value_meta_box );

            // insert array range as terms of category
            wp_set_object_terms( $post_id , $a_int_values , 'category' );     
        }           
    }   
    
    // text categories
    if( isset( $crt_text_value_meta_box ) ) {
        // if is empty update record with nothing
        if( empty($crt_text_value_meta_box) ){
            update_post_meta( $post_id, "text-range-value", '' );
        }else{
            // sanitize
            // prevent array
            if ( is_array( $crt_text_value_meta_box ) ) {
                return $post_id;
            }
            // if is not a string 
            if( !preg_match( '/^[a-zA-Z,]+$/',$crt_text_value_meta_box ) ){
                return $post_id;
            }

            // split each terms by commas
            $a_meta_box_text_values = preg_split("/[\s,]+/", $crt_text_value_meta_box);


            update_post_meta( $post_id, "text-range-value", $crt_text_value_meta_box );

            // insert array range as terms of category
            wp_set_object_terms( $post_id , $a_meta_box_text_values , 'category' );
        }
    }   
    
    if( !isset( $a_int_values ) || empty( $a_int_values ) ){
        $a_int_values = [];
    }
    
    if( !isset( $a_meta_box_text_values ) || empty( $a_meta_box_text_values )  ){
        $a_meta_box_text_values = [];
    }
    
    $a_merge_terms = array_merge( $a_int_values , $a_meta_box_text_values );
    
    // insert array range as terms of category
    wp_set_object_terms( $post_id , $a_merge_terms , 'category' );
}

$crt_post_types = get_option( 'crt-custom-post-type' );

$crt_all_post = ( !empty( $crt_post_types) ? $crt_post_types : [] );

if( in_array("attachment" , $crt_all_post)){
    add_action( 'edit_attachment', 'crt_save_field_range' , 10, 3 );
}
add_action( "save_post", "crt_save_field_range", 10, 3 ); 




// html custom field insert
function crt_html_meta_box( $object ){       
        wp_nonce_field('crt_range_value_nonce','crt_range_value_nonce_field'); ?>
        <div style="margin-bottom:10px;">
            <label for="int-range-value"><b>Integer range</b> (i.e: 5-10,12,15-18) </label>
            <br>
            <input name="int-range-value" type="text" value="<?php echo get_post_meta($object->ID, "int-range-value", true); ?>">
        </div>
        <div>
            <label for="text-range-value"><b>String terms categories</b> (i.e: hello,world) </label>
            <br>
            <input name="text-range-value" type="text" value="<?php echo get_post_meta($object->ID, "text-range-value", true); ?>">
        </div>
    <?php  
}


// create meta box on post type == post
function crt_meta_box_add_in_post() {
    add_meta_box( "crt-meta-box", "Insert your range of terms", "crt_html_meta_box", get_option( 'crt-custom-post-type' ) , "side", "high", null );
}
add_action( "add_meta_boxes", "crt_meta_box_add_in_post" );

register_activation_hook( __FILE__, array( 'Custom Range Terms', 'activation_plugin_crt' ) ); ?>