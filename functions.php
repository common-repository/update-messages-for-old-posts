<?php

//postinstall functions
function guaven_updatepusher_load_defaults() {
    if (get_option("guaven_updatepusher_already_installed") === false) {
        update_option("guaven_updatepusher_already_installed", "1");
    }
}

//wp-admin menu functions

function guaven_updatepusher_admin() {
    add_submenu_page('options-general.php', 'Push Update Boxes To Old Content', 'Push Update Boxes To Old Content', 'manage_options', __FILE__, 'guaven_updatepusher_settings');
     //add_menu_page('Guaven', 'Guaven', 5, __FILE__, 'guaven_fp_settings');
    
}

function my_admin_notice() {
    global $post;
    if (!empty($post) and $post->post_type == 'gf_up_push'):
        echo '<div class="updated gf-alert gf-alert-info">';
        if (empty($_GET["post"]) and strpos($_SERVER["REQUEST_URI"], "post-new") === false):
            $gf_message = 'Use <b>Add new</b> button above to create new rule. And click on any existing rule names below to manage them';
        else:
            $gf_message = '
       1. Give any name to your rule.<br>
       2. Place desired content to the editor content.(it will be shown with correspondending posts )<br>
       3. In tag section put tags separated by comma. Remember that it is the most important section, as the plugin will search correspondending posts with those tags. Any post which contains one of those tags will show your update message.<br>
       4. Scroll a little and configure your push message. <br>
       5. Update/publish your rule. <br>
       6. Go to your website front page and look to any of correspondending post to make sure that the message is shown correctly.<br>
        ';
?>
    <?php
        endif;
        _e($gf_message, 'guaven_updatepusher');
        echo ' </div>';
    endif;
}
add_action('admin_notices', 'my_admin_notice');


//CONTENT filter functions

function guaven_updatepusher_content_filter($content) {
    $posttags = wp_get_post_tags($GLOBALS['post']->ID, array('fields' => 'slugs'));
    


    $args=array('post_type' => 'gf_up_push', 
      'tax_query' => array('relation' => 'OR', array('taxonomy' => 'guaven_update_push_tag',
        'field' => 'slug', 
        'terms' => $posttags)));


    $lookforpushpost = get_posts($args);
    if (empty($lookforpushpost[0]->post_content)) return $content;
    
    if ( (get_post_meta($lookforpushpost[0]->ID,'guaven_updatepusher_rule_begdate', true)=='' or 
      strtotime($lookforpushpost[0]->post_date_gmt) >= strtotime(get_post_meta($lookforpushpost[0]->ID, 
      'guaven_updatepusher_rule_begdate', true)) )
      and
      (get_post_meta($lookforpushpost[0]->ID,'guaven_updatepusher_rule_enddate', true)=='' or 
      strtotime($lookforpushpost[0]->post_date_gmt) <= strtotime(get_post_meta($lookforpushpost[0]->ID, 
      'guaven_updatepusher_rule_enddate', true)) )
      ) {

    $added_content_part = '<div class="gf-alert gf-alert-' . get_post_meta($lookforpushpost[0]->ID, 'guaven_updatepusher_rule_style', true) . '"
style="padding:5px;">' . $lookforpushpost[0]->post_content . '</div>';
    
    $before_content = '';
    $after_content = '';
    $whentoshow = get_post_meta($lookforpushpost[0]->ID, 'guaven_updatepusher_rule_whentoshow', true);
    
    if (get_post_meta($lookforpushpost[0]->ID, 'guaven_updatepusher_rule_placement', true) == 'replace') {
        $before_content = $added_content_part;
        $content = '';
    } 
    elseif (get_post_meta($lookforpushpost[0]->ID, 'guaven_updatepusher_rule_placement', true) == 'below') {
        $after_content = $added_content_part;
    } 
    else {
        $before_content = $added_content_part;
    }

if (get_post_meta($lookforpushpost[0]->ID,'guaven_updatepusher_rule_css',true)!='') 
      $content.='<style>'.esc_attr(get_post_meta($lookforpushpost[0]->ID,
        'guaven_updatepusher_rule_css',true)).'</style>';


    if (is_single() or $whentoshow == 'all') return $before_content . $content . $after_content;
}
    return $content;
}

add_filter('the_content', 'guaven_updatepusher_content_filter');

// js and css enqueuer, script inserter

function guaven_updatepusher_enqueue_scripts() {
    global $post;
    if (!empty($post->post_type) and $post->post_type == 'gf_up_push'):
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-datepicker');
      endif;
}
function guaven_updatepusher_enqueue_main_style() {
    wp_enqueue_style('guaven_updatepusher_main_style', plugins_url('gf_up_push_style.css', __FILE__));
}
add_action('admin_enqueue_scripts', 'guaven_updatepusher_enqueue_scripts');
add_action('admin_enqueue_scripts', 'guaven_updatepusher_enqueue_main_style');
add_action('wp_enqueue_scripts', 'guaven_updatepusher_enqueue_main_style');

function guaven_updatepusher_isJson($string) {
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}

add_action('init', 'guaven_updatepusher_register_post');
function guaven_updatepusher_register_post() {
    register_taxonomy('guaven_update_push_tag', 'termin');
    register_post_type('gf_up_push', array('labels' => array('name' => __('Update push for old posts'), 'singular_name' => __('Update pushes for old posts')),
    
    // 'rewrite'            => array( 'slug' => 'gf_up_push' ),
    'public' => true, 'taxonomies' => array('guaven_update_push_tag'), 'supports' => array('title', 'editor', 'postmeta'), 'register_meta_box_cb' => 'guaven_updatepusher_metabox_area'));
}

add_action('admin_footer', 'guaven_updatepusher_admin_front');
function guaven_updatepusher_admin_front() {
    global $post;
?>
<style>#menu-posts-gf_up_push{display:none}
.ui-datepicker {z-index:100 !important;}
</style>
<?php
    if (!empty($post->post_type) and $post->post_type == 'gf_up_push'):
?> 
<script>
    jQuery(document).ready(function($){
    
    jQuery( "#guaven_updatepusher_rule_begdate" ).datepicker({
      defaultDate: "+1w",
      changeMonth: true,
      numberOfMonths: 1,
      onClose: function( selectedDate ) {
        $( "#guaven_updatepusher_rule_enddate" ).datepicker( "option", "minDate", selectedDate );
      }
    });
    jQuery( "#guaven_updatepusher_rule_enddate" ).datepicker({
      defaultDate: "+1w",
      changeMonth: true,
      numberOfMonths: 1,
      onClose: function( selectedDate ) {
        $( "#guaven_updatepusher_rule_begdate" ).datepicker( "option", "maxDate", selectedDate );
      }
    });
});
</script>
<?php
    endif;
}

// metabox for editor
function guaven_updatepusher_metabox_area() {
    add_meta_box('gf_up_push_metabox', 'Configure your push message', 'gf_up_push_metabox', 'gf_up_push', 'advanced', 'default');
}

function gf_up_push_metabox() {
    global $post;
    wp_nonce_field('meta_box_nonce_action', 'meta_box_nonce_field');
?>
<h4>Where to place it?</h4>
<p>
<select name="guaven_updatepusher_rule_placement">
<option value="above" <?php
    echo (get_post_meta($post->ID, 'guaven_updatepusher_rule_placement', true) == 'above' ? 'selected' : ''); ?>>Above the content</option>
<option value="below" <?php
    echo (get_post_meta($post->ID, 'guaven_updatepusher_rule_placement', true) == 'below' ? 'selected' : ''); ?>>Below the content</option>
<option value="replace" <?php
    echo (get_post_meta($post->ID, 'guaven_updatepusher_rule_placement', true) == 'replace' ? 'selected' : ''); ?>>Replace content</option>
</select>
</p>
<hr>

<h4>When to show it?</h4>
<p>
<select name="guaven_updatepusher_rule_whentoshow">
<option value="single" <?php
    echo (get_post_meta($post->ID, 'guaven_updatepusher_rule_whentoshow', true) == 'single' ? 'selected' : ''); ?>>Only at single page</option>
<option value="all" <?php
    echo (get_post_meta($post->ID, 'guaven_updatepusher_rule_whentoshow', true) == 'all' ? 'selected' : ''); ?>>Everytime when correspondending content shown</option>
</select>
</p>
<hr>


<h4>Which posts should show this rule's message?</h4>
<p>
<p>
<?php
    $guaven_updatepusher_rule_tags_andor = get_post_meta($post->ID, 'guaven_updatepusher_rule_tags_andor', true);
?>
<input type="radio" name="guaven_updatepusher_rule_tags_andor" id="guaven_updatepusher_rule_tags_andor_1"  value="and"
        <?php
    if ($guaven_updatepusher_rule_tags_andor == 'and') echo 'checked'; ?> >
        All posts which <b>tags</b> contains <b>all tags</b> from this rule<br>

<input type="radio" name="guaven_updatepusher_rule_tags_andor" id="guaven_updatepusher_rule_tags_andor_2"  value="or"
        <?php
    if ($guaven_updatepusher_rule_tags_andor == 'or') echo 'checked'; ?> >
        All posts which <b>tags</b> contains <b>one of the tags</b> from this rule <br>

<input type="radio" name="guaven_updatepusher_rule_tags_andor" id="guaven_updatepusher_rule_tags_andor_4"  value="orsearch"
        <?php
    if ($guaven_updatepusher_rule_tags_andor == 'orsearch' or $guaven_updatepusher_rule_tags_andor == '') echo 'checked'; ?> >
        All posts which <b>tags or post content</b> contain <b>one of the tags</b> from this rule <br>


<input type="radio" name="guaven_updatepusher_rule_tags_andor" id="guaven_updatepusher_rule_tags_andor_5"  value="search"
        <?php
    if ($guaven_updatepusher_rule_tags_andor == 'search') echo 'checked'; ?> >
        All posts which <b>post content</b> contain <b>all tags</b> from this rule.


</p>
<hr>

<h4>Start post date: (leave it empty if you don't want to make any interval)</h4>
<p>

<input type="text" name="guaven_updatepusher_rule_begdate" id="guaven_updatepusher_rule_begdate" value="<?php
    echo get_post_meta($post->ID, 'guaven_updatepusher_rule_begdate', true); ?>">
</p>

<h4>End post date: (leave empty if you don't want to make any interval)</h4>
<p>
<input type="text" name="guaven_updatepusher_rule_enddate" id="guaven_updatepusher_rule_enddate"  value="<?php
    echo get_post_meta($post->ID, 'guaven_updatepusher_rule_enddate', true); ?>">
</p>
<hr>




<h4>Choose any style for message div </h4>
<p>
<?php
    $guaven_updatepusher_rule_style = get_post_meta($post->ID, 'guaven_updatepusher_rule_style', true);
?>
<table>
    <tr>
        <td><input type="radio" name="guaven_updatepusher_rule_style" id="guaven_updatepusher_rule_style_1"  value="warning"
        <?php
    if ($guaven_updatepusher_rule_style == 'warning') echo 'checked'; ?> ></td>
        <td> <div class="gf-alert gf-alert-warning"> Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book</div>  </td>
    </tr>
     <tr>
        <td><input type="radio" name="guaven_updatepusher_rule_style" id="guaven_updatepusher_rule_style_1"  value="success"
        <?php
    if ($guaven_updatepusher_rule_style == 'success') echo 'checked'; ?>></td>
        <td> <div class="gf-alert gf-alert-success"> Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book</div>  </td>
    </tr>

     <tr>
        <td><input type="radio" name="guaven_updatepusher_rule_style" id="guaven_updatepusher_rule_style_1"  value="info"
<?php
    if ($guaven_updatepusher_rule_style == 'info' or $guaven_updatepusher_rule_style == '') echo 'checked'; ?>
        ></td>
        <td> <div class="gf-alert gf-alert-info"> Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book</div>  </td>
    </tr>

     <tr>
        <td><input type="radio" name="guaven_updatepusher_rule_style" id="guaven_updatepusher_rule_style_1"  value="danger"
<?php
    if ($guaven_updatepusher_rule_style == 'danger') echo 'checked'; ?>
        ></td>
        <td> <div class="gf-alert gf-alert-danger"> Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book</div>  </td>
    </tr>
</table>


</p>
<hr>




<h4>Custom css for message div (the name of container class is .gf-alert. f.e.   .gf-alert {background: red !important} ) </h4>
<p>
<textarea name="guaven_updatepusher_rule_css" id="guaven_updatepusher_rule_css" style="width:100%"><?php
    echo (get_post_meta($post->ID, 'guaven_updatepusher_rule_css', true) != '' ? get_post_meta($post->ID, 'guaven_updatepusher_rule_css', true) : ''); ?>
</textarea> 
</p>
<hr>
<?php
}

function gf_up_push_save_metabox_area($post_id, $post) {
    
    if (!isset($_POST['meta_box_nonce_field']) or !wp_verify_nonce($_POST['meta_box_nonce_field'], 'meta_box_nonce_action')) {
        return $post->ID;
    }
    $fields = array("guaven_updatepusher_rule_enddate","guaven_updatepusher_rule_css", "guaven_updatepusher_rule_begdate", "guaven_updatepusher_rule_style", "guaven_updatepusher_rule_placement", "guaven_updatepusher_rule_whentoshow", "guaven_updatepusher_rule_tags_andor");
    foreach ($fields as $key => $value) {
        update_post_meta($post->ID, $value, esc_attr($_POST[$value]));
    }
}
add_action('save_post', 'gf_up_push_save_metabox_area', 1, 2);
 // save the custom fields


?>
