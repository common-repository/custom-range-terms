<?php
defined( 'ABSPATH' ) or die();

if ( is_admin() ){ 
    add_action( 'admin_init', 'crt_register');
}

function crt_register() { 
    register_setting( 'crt-group', 'crt-custom-post-type' );	
}

// get all post types checked
$all_post_types_selected = stripslashes_deep( $_POST );

if ( !empty( $all_post_types_selected) ){
    // nonce 
    $crt_nonce_field = sanitize_text_field ( $_POST['crt_nonce_field'] );
}


function crt_check_field_form( $all_post_types_selected, $crt_nonce_field  ){
    // check submit
    if( !is_string( $all_post_types_selected['submit-crt'] ) ){
        return false;
    }
    
    // prevent XSS
    if ( empty( $crt_nonce_field ) || ! wp_verify_nonce( $crt_nonce_field , 'crt_assign_to_cpt' ) ) {
        return false;
    }
    
    // in case of success
    return true;
}


if( isset($all_post_types_selected['submit-crt']) && crt_check_field_form( $all_post_types_selected, $crt_nonce_field ) ){
    // update field
    update_option( 'crt-custom-post-type', $all_post_types_selected['crt-custom-post-type'] );    
}



function crt_cpt_options($all_post_types){
    $crt_post_types = get_option ( 'crt-custom-post-type' ); 
    $crt_post_types = ( !empty( $crt_post_types ) ? $crt_post_types : array() ); ?>
    <ul>
        <?php
        foreach( $all_post_types as $post_type ){
            $checked = ( (in_array( $post_type , $crt_post_types ) ) ? ' checked' : ''); ?>
            <li><input type="checkbox" name="crt-custom-post-type[]" value="<?php esc_html_e( $post_type , 'custom-range-terms'); ?>"<?php echo $checked; ?>><?php echo $post_type; ?></li>
        <?php
            $checked = "";
        }?>
    </ul>
<?php
}

function crt_html_choose_type_form(){?>
    <h1><?php esc_html_e( 'Custom range terms - Options','custom-range-terms'); ?></h1>
    <p><?php esc_html_e( 'Select all custom post type where you want insert Meta Box Custom Range Terms','custom-range-terms' ); ?></p>
    <form method="POST">
        <?php wp_nonce_field('crt_assign_to_cpt','crt_nonce_field'); ?>
        <?php settings_fields( 'crt-group' ); ?>
        <label for="crt-custom-post-type">Post Type</label>
        <?php
        $args = array( "public" => true );    
        $all_post_types = get_post_types( $args );

        // create list of all post types 
        crt_cpt_options( $all_post_types ); ?>
        <input type="submit" name="submit-crt" value="<?php esc_html_e( 'Save changes','custom-range-terms' ); ?>" class="button button-primary button-large">
    </form>
<?php
}
echo crt_html_choose_type_form();