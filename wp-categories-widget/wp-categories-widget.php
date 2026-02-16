<?php
/*
Plugin Name: WP Categories Widget
Description: A simple plugin to display categories as list under website widget sidebar and you have an option to choose any type custom taxonomy to display their categories.
Author: WP-EXPERTS.IN TEAM
Author URI: https://wp-experts.in
Plugin URI: https://www.wp-experts.in/products/wp-categories-widget-addon/
Version: 2.8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Plugin version
 */
define( 'WCW_PLUGIN_VERSION', '2.8' );

/**************************************************************
                START CLASSS WpCategoriesWidget 
**************************************************************/
class WpCategoriesWidget extends WP_Widget {

	/**
	 * Register widget with WP.
	 */
	function __construct() {
		parent::__construct(
			'wpcategorieswidget', // Base ID
			__( 'WP Categories Widget', 'wp-categories-widget' ), // Name
			array( 'description' => esc_html__( 'Display categories list of all taxonomy post type - by WP-Experts.In Team', 'wp-categories-widget' ), ) // Args
		);
		
		add_action('wp_enqueue_scripts',array($this,'wcw_style_func_css'));
		// call ajax 
       add_action( 'wp_ajax_wcw_terms', array( $this, 'wcw_terms_list' ) );

	}
	
	// Add other back-end action hooks here
    public function wcw_terms_list() {
        
		if ( ! wp_doing_ajax() ) {
			wp_die( esc_html__( 'Invalid request', 'wp-categories-widget' ) );
		}

		check_ajax_referer( 'wcw-special-string', 'security' );

		if ( ! current_user_can( 'edit_theme_options' ) ) {
	wp_die( esc_html__( 'Permission denied', 'wp-categories-widget' ) );
}

        
        
$taxonomy = isset($_POST['wcwtaxo']) ? sanitize_text_field( wp_unslash( $_POST['wcwtaxo'] ) ) : '';
$cbid     = isset($_POST['cbid']) ? intval( wp_unslash( $_POST['cbid'] ) ) : '';
$cbname   = isset($_POST['cbname']) ? sanitize_text_field( wp_unslash( $_POST['cbname'] ) ) : '';

		
		
    	if($taxonomy=='') wp_die( esc_html__( 'Direct access denied', 'wp-categories-widget' ) ) ;
    	
    	 $html = '';$j = $i = $k = 0;
    	 $terms = get_terms(array(
    						  'taxonomy' => $taxonomy,
    						  'hide_empty' => false,
    						  'parent' => 0,
    						 ) 
    						);	
    					if ( $terms ) {
    					foreach ( $terms as $term ) {
							
							$html .= '<label for="' . esc_attr( $cbid . '-' . $i ) . '">
<input type="checkbox" id="' . esc_attr( $cbid . '-' . $i ) . '" 
name="' . esc_attr( $cbname ) . '" 
value="' . esc_attr( $term->term_id ) . '"/>'
. esc_html( $term->name );

    						

						$childterms = get_terms(array(
							'taxonomy'   => $taxonomy,
							'child_of'   => $term->term_id,
							'hide_empty' => false,
						));
	
    						
    					if ( $childterms ) {
    					    
    					    
    					    foreach ( $childterms as $childterm ) {
    					        
    						$html .= '<label data-parent="' . esc_attr( $childterm->parent ) . '" for="' . esc_attr( $cbid . '-' . $j ) . '" class="child-term">
<input type="checkbox" id="' . esc_attr( $cbid . '-' . $j ) . '" name="' . esc_attr( $cbname ) . '" value="' . esc_attr( $childterm->term_id ) . '"/>'
. esc_html( $childterm->name ) .
'</label>';


    					
    						$j++;
    					    }
    					    
    						
    					}
    						
    						$html .='</label>';
    						$i++;
    					}
    				    	
    					}
    				echo wp_kses_post($html);
    	wp_die();
    }

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
        echo isset($args['before_widget']) ? wp_kses_post($args['before_widget']) : '';
		//init categories widget
		$title = '';
		$orderby      = !empty($instance['wcw_orderby']) ? $instance['wcw_orderby'] : 'name'; 
		$order        = !empty($instance['wcw_order']) ? $instance['wcw_order'] : 'ASC'; 
		$hide_empty   = !empty($instance['wcw_show_empty']) ? false : true;
		$depth        = !empty($instance['wcw_hide_child']) ? 1 : 0;
		$show_count   = !empty( $instance['wcw_hide_count']) ? false : true;
		$pad_counts   = false;
		$hierarchical = true;
		if ( ! empty( $instance['wcw_title'] ) && ! $instance['wcw_hide_title'] ) {
			$title = '<h3 class="widget-title">' . esc_html( $instance['wcw_title'] ) . '</h3>';
		}

		
		$widgetstyle 	= !empty($instance['wcw_style']) ? $instance['wcw_style'] : 'list';

		// add css 		
		//do_action('wcw_style','wcw_style_func');
		//do_action('wcw_script','wcw_script_func');
		if(!$depth){}
		/** return category list */
		if ( ! empty( $instance['wcw_taxonomy_type'] ) ) {
				$taxonomy     = $instance['wcw_taxonomy_type'];
				$excludeCat   = $instance['wcw_selected_categories'] ? $instance['wcw_selected_categories'] : '';
				$wcw_action_on_cat= $instance['wcw_action_on_cat'] ? $instance['wcw_action_on_cat'] : '';
				$queryargs = array(
				  'echo' => false,
				  'taxonomy'     => $taxonomy,
				  'hide_empty'   => $hide_empty,
				  'orderby'      => $orderby,
				  'order'        => $order,
				  'show_count'   => $show_count,
				  'pad_counts'   => $pad_counts,
				  'hierarchical' => $hierarchical,
				  'depth' => $depth,
				  'hide_title_if_empty' => true,
				  'title_li'     => $title,
				);
				
				if($excludeCat && $wcw_action_on_cat!='')
                $queryargs[$wcw_action_on_cat] = $excludeCat;
				//print_r($queryargs);		
				
				if( $widgetstyle=='list' ) {
				$categories = wp_list_categories($queryargs);
				$cat_html = preg_replace( '~\((\d+)\)(?=\s*+<)~', '<span class="post-count">$1</span>', $categories );
				
				if ( $categories ) {
						printf(
							'<ul id="%s">%s</ul>',
							esc_attr( $args['widget_id'] ),
							wp_kses_post( $cat_html )
						);
				 }
				}else{
				    
				    $parent_terms = get_terms($queryargs); 
					
					 // Get the current term ID if on a taxonomy archive page
    $current_term_id = (is_tax() || is_category() || is_tag()) ? get_queried_object_id() : null;
    
if ( $parent_terms ) {
   echo wp_kses_post($title); 
	
$widget_id = isset( $args['widget_id'] ) ? esc_attr( $args['widget_id'] ) : 'wcwpro-default-00';

echo '<select class="wcwpro-list" id="' . esc_attr($widget_id) . '" onchange="this.options[this.selectedIndex].value && (window.location = this.options[this.selectedIndex].value);">';

    $parentcatclass = '';
	
	if( $parent_terms ) {
		
echo '<option>' . esc_html__( 'Select ', 'wp-categories-widget' ) . esc_html( $instance['wcw_title'] ) . '</option>';
		
		foreach ( $parent_terms as $pterm ) {
			$queryargs['parent'] = $pterm->term_id;
			$terms = get_terms($queryargs);
			echo '<option class="cat-item ' . esc_attr( ($terms && !$depth ? ' cat-have-child ' : '') . $parentcatclass ) . '" 
    id="cat-item-' . esc_attr( $pterm->term_id ) . '" 
    value="' . esc_url( get_term_link( $pterm ) ) . '" ' 
    . selected( $current_term_id, $pterm->term_id, false ) . '>'
    . esc_html( $pterm->name ) . 
'</option>';

						
			//Get the Child terms
			if($terms && !$depth) {
				foreach ( $terms as $term ) {
						echo '<option class="child-cat-item" id="term-' . esc_attr( $term->term_id ) . '" value="' . esc_url( get_term_link( $term ) ) . '" ' . selected( $current_term_id, $term->term_id, false ) . '>' . esc_html( $term->name ) . '</option>';

						
					}
				}
			}

	}
echo '</select>';

}
				}
			
			}	
echo isset($args['after_widget']) ? wp_kses_post( $args['after_widget'] ) : '';
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$wcw_title        = ! empty( $instance['wcw_title'] ) ? $instance['wcw_title'] :'WP Categories';
$wcw_hide_title           = ! empty( $instance['wcw_hide_title'] ) ? $instance['wcw_hide_title'] : '';
$wcw_show_empty           = ! empty( $instance['wcw_show_empty'] ) ? $instance['wcw_show_empty'] : '';
$wcw_hide_child           = ! empty( $instance['wcw_hide_child'] ) ? $instance['wcw_hide_child'] : '';
$wcw_taxonomy_type        = ! empty( $instance['wcw_taxonomy_type'] ) ? $instance['wcw_taxonomy_type'] : 'category';
$wcw_orderby = ! empty( $instance['wcw_orderby'] ) ? $instance['wcw_orderby'] : 'name';
$wcw_order = ! empty( $instance['wcw_order'] ) ? $instance['wcw_order'] : 'asc';
$wcw_selected_categories  = ( ! empty( $instance['wcw_selected_categories'] ) && ! empty( $instance['wcw_action_on_cat'] ) ) ? $instance['wcw_selected_categories'] : '';
$wcw_action_on_cat        = ! empty( $instance['wcw_action_on_cat'] ) ? $instance['wcw_action_on_cat'] : '';
$wcw_hide_count           = ! empty( $instance['wcw_hide_count'] ) ? $instance['wcw_hide_count'] : '';
$wcw_style                = ! empty( $instance['wcw_style'] ) ? $instance['wcw_style'] : '';


		?>
		<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'wcw_title' ) ); ?>"><?php echo esc_html__( 'Title:', 'wp-categories-widget' ); ?></label> 
	<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'wcw_title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'wcw_title' ) ); ?>" type="text" value="<?php echo esc_attr( $wcw_title ); ?>">
</p>
<p>
	<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'wcw_hide_title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'wcw_hide_title' ) ); ?>" type="checkbox" value="1" <?php checked( $wcw_hide_title, 1 ); ?>>
	<label for="<?php echo esc_attr( $this->get_field_id( 'wcw_hide_title' ) ); ?>"><?php echo esc_html__( 'Hide Title:', 'wp-categories-widget' ); ?></label> 
</p>
<hr>
<div class="taxonomysec">
	<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'wcw_taxonomy_type' ) ); ?>"><?php echo esc_html__( 'Taxonomy Type:', 'wp-categories-widget' ); ?></label> 
		<select class="widefat wcwtaxtype" id="<?php echo esc_attr( $this->get_field_id( 'wcw_taxonomy_type' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'wcw_taxonomy_type' ) ); ?>">
			<?php 
			$args = array( 'public' => true, '_builtin' => false ); 
			$taxonomies = get_taxonomies( $args, 'names', 'and' ); 
			array_push( $taxonomies, 'category', 'post_tag' );
			foreach ( $taxonomies as $taxonomy ) {
				printf('<option value="%s" %s>%s</option>', esc_attr( $taxonomy ), selected( $taxonomy, $wcw_taxonomy_type, false ), esc_html( $taxonomy ) );
			}
			?>    
		</select>
	</p>
	<div class="wcwmultiselect">
		<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'wcw_action_on_cat' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'wcw_action_on_cat' ) ); ?>">
			<option value="include" <?php selected( $wcw_action_on_cat, 'include' ); ?>><?php echo esc_html__( 'Show Only Below Selected Categories', 'wp-categories-widget' ); ?></option>
			<option value="exclude" <?php selected( $wcw_action_on_cat, 'exclude' ); ?>><?php echo esc_html__( 'Hide Only Below Selected Categories', 'wp-categories-widget' ); ?></option>
			<option value="" <?php selected( $wcw_action_on_cat, '' ); ?>><?php echo esc_html__( 'Show All Below Categories', 'wp-categories-widget' ); ?></option>
		</select>
		<div class="wcwcheckboxes" id="wcwcb-<?php echo esc_attr( $this->get_field_id( 'wcw_action_on_cat' ) ); ?>">
			<?php 
			$i = $j = 0;
			if ( $wcw_taxonomy_type ) {
				$terms = get_terms( array( 'taxonomy' => $wcw_taxonomy_type, 'hide_empty' => false, 'parent' => 0 ) );
				foreach ( $terms as $term ) {
					$checked = is_array( $wcw_selected_categories ) && in_array( $term->term_id, $wcw_selected_categories );
					echo '<label><input type="checkbox" id="' . esc_attr( $this->get_field_id( 'wcw_action_on_cat' ) . '-' . $i ) . '" name="' . esc_attr( $this->get_field_name( 'wcw_selected_categories' ) ) . '[]" value="' . esc_attr( $term->term_id ) . '" ' . checked( $checked, true, false ) . '> ' . esc_html( $term->name ) . '</label>';

					$childterms = get_terms( array( 'taxonomy' => $wcw_taxonomy_type, 'hide_empty' => false, 'child_of' => $term->term_id ) );
					foreach ( $childterms as $child ) {
						$checked = is_array( $wcw_selected_categories ) && in_array( $child->term_id, $wcw_selected_categories );
						echo '<label class="child-term"><input type="checkbox" id="' . esc_attr( $this->get_field_id( 'wcw_action_on_cat' ) . '-' . $j ) . '" name="' . esc_attr( $this->get_field_name( 'wcw_selected_categories' ) ) . '[]" value="' . esc_attr( $child->term_id ) . '" ' . checked( $checked, true, false ) . '> ' . esc_html( $child->name ) . '</label>';
						$j++;
					}
					$i++;
				}
			}
			?>   
		</div>
	</div>
</div>
<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'wcw_style' ) ); ?>"><?php echo esc_html__( 'Category Style:', 'wp-categories-widget' ); ?></label><br>
	<input type="radio" id="<?php echo esc_attr( $this->get_field_id( 'wcw_style' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'wcw_style' ) ); ?>" value="list" <?php checked( $wcw_style, 'list' ); ?>> <?php echo esc_html__( 'List', 'wp-categories-widget' ); ?>
	<input type="radio" id="<?php echo esc_attr( $this->get_field_id( 'wcw_style' ) ); ?>-1" name="<?php echo esc_attr( $this->get_field_name( 'wcw_style' ) ); ?>" value="dropdown" <?php checked( $wcw_style, 'dropdown' ); ?>> <?php echo esc_html__( 'Dropdown', 'wp-categories-widget' ); ?>
</p>
<p>
	<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'wcw_hide_count' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'wcw_hide_count' ) ); ?>" value="1" <?php checked( $wcw_hide_count, 1 ); ?>>
	<label for="<?php echo esc_attr( $this->get_field_id( 'wcw_hide_count' ) ); ?>"><?php echo esc_html__( 'Hide count', 'wp-categories-widget' ); ?></label>
</p>
<p>
	<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'wcw_hide_child' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'wcw_hide_child' ) ); ?>" value="1" <?php checked( $wcw_hide_child, 1 ); ?>>
	<label for="<?php echo esc_attr( $this->get_field_id( 'wcw_hide_child' ) ); ?>"><?php echo esc_html__( 'Hide Child Categories', 'wp-categories-widget' ); ?></label>
</p>
<p>
	<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'wcw_show_empty' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'wcw_show_empty' ) ); ?>" value="1" <?php checked( $wcw_show_empty, 1 ); ?>>
	<label for="<?php echo esc_attr( $this->get_field_id( 'wcw_show_empty' ) ); ?>"><?php echo esc_html__( 'Show empty categories', 'wp-categories-widget' ); ?></label>
</p>
<hr>
<h3><?php echo esc_html__( 'Need Support?', 'wp-categories-widget' ); ?></h3>
<p><a href="https://www.wp-experts.in/contact-us/" target="_blank"><?php echo esc_html__( 'Contact us', 'wp-categories-widget' ); ?></a> | <a href="https://wordpress.org/support/plugin/wp-categories-widget/reviews/" target="_blank"><?php echo esc_html__( 'I love it :) leave feedback here', 'wp-categories-widget' ); ?></a></p>

		<style> .wcwmultiselect{width:100%;font-size:14px}
.wcwselectBox{position:relative}
.wcwmultiselect select{width:100%;padding:6px 8px;font-weight:600;border:1px solid #ccd0d4;border-radius:4px;background:#fff}
.wcwoverSelect{position:absolute;inset:0;cursor:pointer}
.wcwcheckboxes{margin-top:4px;padding:10px 12px;border:1px solid #ccd0d4;border-radius:0 0 4px 4px;background:#f9fafb;color:#1d2327;max-height:260px;overflow-y:auto;box-shadow:0 4px 12px rgba(0,0,0,.08)}
.wcwcheckboxes label{display:flex;align-items:center;gap:6px;padding:5px 4px;border-radius:3px;cursor:pointer;line-height:1.4}
.wcwcheckboxes label:hover{background:#e9f0f6}
.wcwcheckboxes input[type=checkbox]{margin:0}
.wcwcheckboxes .child-term{margin-left:16px;font-size:13.5px;color:#3c434a}
.wcwcheckboxes .subchild-term{margin-left:32px;font-size:13px;color:#50575e}
</style>
<?php 
		$ajax_nonce = wp_create_nonce( 'wcw-special-string' );
 ?>
<script type="text/javascript">
jQuery(document).ready( function() {

jQuery("#<?php echo esc_attr( $this->get_field_id( 'wcw_taxonomy_type' ) ); ?>").change( function() {
	var val = jQuery(this).val();
	var cbid = "<?php echo esc_attr( $this->get_field_id( 'wcw_action_on_cat' ) );?>";
	var cbname = "<?php echo esc_attr( $this->get_field_name( 'wcw_selected_categories' ) )?>[]";
    var ajxurl = "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>";
	jQuery("#wcwcb-<?php echo esc_attr( $this->get_field_id( 'wcw_action_on_cat' ) ); ?>").html("<i>updating...</i>");
	jQuery.ajax({
		type: "POST",
		dataType: "html",
		url: ajxurl,
		data: {
			"action": 'wcw_terms',
			"wcwtaxo": val,
			"cbname": cbname,
			"security": '<?php echo esc_js($ajax_nonce); ?>',
			"cbid": cbid
		},
		success: function (data) {
			jQuery("#wcwcb-<?php echo esc_attr( $this->get_field_id( 'wcw_action_on_cat' ) ); ?>").html(data);
		}
	});
});

		});
		</script>		
		<?php 
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		//print_r($new_instance);exit;
		$instance = array();
		$instance['wcw_title'] 					= ( ! empty( $new_instance['wcw_title'] ) ) ? wp_strip_all_tags( $new_instance['wcw_title'] ) : '';
		$instance['wcw_hide_title'] 			= ( ! empty( $new_instance['wcw_hide_title'] ) ) ? wp_strip_all_tags( $new_instance['wcw_hide_title'] ) : '';
		$instance['wcw_show_empty'] 			= ( ! empty( $new_instance['wcw_show_empty'] ) ) ? wp_strip_all_tags( $new_instance['wcw_show_empty'] ) : '';
		$instance['wcw_hide_child'] 		    = ( ! empty( $new_instance['wcw_hide_child'] ) ) ? wp_strip_all_tags( $new_instance['wcw_hide_child'] ) : '';
		$instance['wcw_taxonomy_type'] 			= ( ! empty( $new_instance['wcw_taxonomy_type'] ) ) ? wp_strip_all_tags( $new_instance['wcw_taxonomy_type'] ) : '';
		$instance['wcw_selected_categories'] = ! empty( $new_instance['wcw_selected_categories'] )
	? array_map( 'absint', (array) $new_instance['wcw_selected_categories'] )
	: array();
		$instance['wcw_action_on_cat'] 			= ( ! empty( $new_instance['wcw_action_on_cat'] ) ) ? $new_instance['wcw_action_on_cat'] : '';
		$instance['wcw_hide_count'] 			= ( ! empty( $new_instance['wcw_hide_count'] ) ) ? wp_strip_all_tags( $new_instance['wcw_hide_count'] ) : '';
		$instance['wcw_style'] 					= ( ! empty( $new_instance['wcw_style'] ) ) ? wp_strip_all_tags( $new_instance['wcw_style'] ) : '';
		return $instance;
	}
	
	/** plugin CSS **/
	public function wcw_style_func_css() {
		$inlinecss =' .widget_wpcategorieswidget ul.children{display:none;} .widget_wp_categories_widget{background:#fff; position:relative;}.widget_wp_categories_widget h2,.widget_wpcategorieswidget h2{color:#4a5f6d;font-size:20px;font-weight:400;margin:0 0 25px;line-height:24px;text-transform:uppercase}.widget_wp_categories_widget ul li,.widget_wpcategorieswidget ul li{font-size: 16px; margin: 0px; border-bottom: 1px dashed #f0f0f0; position: relative; list-style-type: none; line-height: 35px;}.widget_wp_categories_widget ul li:last-child,.widget_wpcategorieswidget ul li:last-child{border:none;}.widget_wp_categories_widget ul li a,.widget_wpcategorieswidget ul li a{display:inline-block;color:#007acc;transition:all .5s ease;-webkit-transition:all .5s ease;-ms-transition:all .5s ease;-moz-transition:all .5s ease;text-decoration:none;}.widget_wp_categories_widget ul li a:hover,.widget_wp_categories_widget ul li.active-cat a,.widget_wp_categories_widget ul li.active-cat span.post-count,.widget_wpcategorieswidget ul li a:hover,.widget_wpcategorieswidget ul li.active-cat a,.widget_wpcategorieswidget ul li.active-cat span.post-count{color:#ee546c}.widget_wp_categories_widget ul li span.post-count,.widget_wpcategorieswidget ul li span.post-count{height: 30px; min-width: 35px; text-align: center; background: #fff; color: #605f5f; border-radius: 5px; box-shadow: inset 2px 1px 3px rgba(0, 122, 204,.1); top: 0px; float: right; margin-top: 2px;}li.cat-item.cat-have-child > span.post-count{float:inherit;}li.cat-item.cat-item-7.cat-have-child { background: #f8f9fa; }li.cat-item.cat-have-child > span.post-count:before { content: "("; }li.cat-item.cat-have-child > span.post-count:after { content: ")"; }.cat-have-child.open-m-menu ul.children li { border-top: 1px solid #d8d8d8;border-bottom:none;}li.cat-item.cat-have-child:after{ position: absolute; right: 8px; top: 8px; background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASCAYAAABWzo5XAAAABmJLR0QA/wD/AP+gvaeTAAAAoklEQVQ4je3PzQpBURSG4WfknztxGS6BKOIaDQwkSXJTnI7J2rXbhSND3lqTtb/19m3+NGWANVof3LTiZpAWXVxQY4t2A0k7snXcdmGMKpY1dui8kHQik/JVOMAC9+zxlFfO6GFfSDZlaI5bFjpjWEgOhWT9rHYpu2CEPo7Z/v5KklgW37zG5JLlO0liVjTLJaumkmeyj5qUTEP2lSQxiflVHtR5PTMAQTkfAAAAAElFTkSuQmCC); content: ""; width: 18px; height: 18px;transform: rotate(270deg);}ul.children li.cat-item.cat-have-child:after{content:"";background-image: none;}.cat-have-child ul.children {display: none; z-index: 9; width: auto; position: relative; margin: 0px; padding: 0px; margin-top: 0px; padding-top: 10px; padding-bottom: 10px; list-style: none; text-align: left; background:  #f8f9fa; padding-left: 5px;}.widget_wp_categories_widget ul li ul.children li,.widget_wpcategorieswidget ul li ul.children li { border-bottom: 1px solid #fff; padding-right: 5px; }.cat-have-child.open-m-menu ul.children{display:block;}li.cat-item.cat-have-child.open-m-menu:after{transform: rotate(0deg);}.widget_wp_categories_widget > li.product_cat,.widget_wpcategorieswidget > li.product_cat {list-style: none;}.widget_wp_categories_widget > ul,.widget_wpcategorieswidget > ul {padding: 0px;}.widget_wp_categories_widget > ul li ul ,.widget_wpcategorieswidget > ul li ul {padding-left: 15px;} .wcwpro-list{padding: 0 15px;}';
		
		
		 wp_register_style(
		'wcw-inlinecss',
		false,
		array(),
		WCW_PLUGIN_VERSION
	);
		 wp_enqueue_style( 'wcw-inlinecss');
		 wp_add_inline_style( 'wcw-inlinecss', $inlinecss );
		 
		 //control through cookie
		$inlinejs = "jQuery(document).ready(function($){ jQuery('li.cat-item:has(ul.children)').addClass('cat-have-child'); jQuery('.cat-have-child').removeClass('open-m-menu');jQuery('li.cat-have-child > a').click(function(){window.location.href=jQuery(this).attr('href');return false;});jQuery('li.cat-have-child').click(function(){

		var li_parentdiv = jQuery(this).parent().parent().parent().attr('class');
			if(jQuery(this).hasClass('open-m-menu')){jQuery('.cat-have-child').removeClass('open-m-menu');}else{jQuery('.cat-have-child').removeClass('open-m-menu');jQuery(this).addClass('open-m-menu');}});});";
		   
		    wp_register_script(
				'wcw-inline-js',
				false,
				array( 'jquery' ),
				WCW_PLUGIN_VERSION,
				true
			);
			wp_enqueue_script( 'wcw-inline-js' );
			wp_add_inline_script( 'wcw-inline-js', $inlinejs );
			

	}
}// class WpCategoriesWidget



// register WpCategoriesWidget widget
function register_wp_categories_widget() {
    register_widget( 'WpCategoriesWidget' );
}
add_action( 'widgets_init', 'register_wp_categories_widget'); 
/**************************************************************
                END CLASSS WpCategoriesWidget 
**************************************************************/
/*
* WPCategoryOption Page
* @hooks
* @backend
*/

if(!class_exists('WpcEditor'))
{
    class WpcEditor
    {
        /**
         * Construct the plugin object
         */
        public function __construct() {
            // register actions
			add_action('admin_init', array(&$this, 'wcw_admin_init'));
			add_action('admin_menu', array(&$this, 'wcw_add_menu'));
			
			$wcw_disable_block_editor = get_option('wcw_disable_block_editor');
		    
		    if( $wcw_disable_block_editor ) {
		        
		        add_action( 'after_setup_theme', array(&$this,'disable_widget_block_editor') );
		        
		    }
			
			
        } // END public function __construct
		
		/**
		 * hook into WP's admin_init action hook
		 */
		public function wcw_admin_init() {
		    
		    
			// Set up the settings for this plugin
			$this->wcw_init_settings();
			// Possibly do additional admin_init tasks
		} // END public static function activate
		
	    public function disable_widget_block_editor() {
             remove_theme_support( 'widgets-block-editor' );
             // Disables the block editor from managing widgets in the Gutenberg plugin.
	            add_filter( 'gutenberg_use_widgets_block_editor', '__return_false' );
            // Disables the block editor from managing widgets.
                add_filter( 'use_widgets_block_editor', '__return_false' );
            }
		/**
		 * Initialize some custom settings
		 */     
		public function wcw_init_settings() {
	// register the settings for this plugin with proper sanitization
	register_setting(
		'wcw-group',                     // Option group
		'wcw_disable_block_editor',      // Option name
		array(
			'type'              => 'boolean',
			'sanitize_callback' => array( $this, 'wcw_sanitize_checkbox' ),
			'default'           => false,
		)
	);
}
// END public function init_custom_settings()
public function wcw_sanitize_checkbox( $input ) {
	return $input == 1 ? 1 : 0;
}

		/**
		 * add a menu
		 */     
		public function wcw_add_menu() {
			add_options_page('WP Category Settings', 'WP Category Widget', 'manage_options', 'wcw-page', array(&$this, 'wcw_settings_page'));
		} // END public function add_menu()
		/**
		 * Menu Callback
		 */     
		public function wcw_settings_page()	{
		    
			if(!current_user_can('manage_options'))
			{
              wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-categories-widget' ) );
			}

			// Render the settings template
			include(sprintf("%s/lib/settings.php", dirname(__FILE__)));
			// Admin CSS
			wp_register_style(
				'wcw_admin_style',
				plugins_url( '/assets/wcw-admin.css', __FILE__ ),
				array(),
				WCW_PLUGIN_VERSION
			);
			wp_enqueue_style( 'wcw_admin_style' );

			// Admin JS
			wp_register_script(
				'wcw_admin_script',
				plugins_url( '/assets/wcw-admin.js', __FILE__ ),
				array( 'jquery' ),
				WCW_PLUGIN_VERSION,
				true
			);
			wp_enqueue_script( 'wcw_admin_script' );
		} // END public function plugin_settings_page()
    } // END class WpcEditor
} // END if(!class_exists('WpcEditor'))

add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	function ( $links ) {
		$settings_link = '<a href="widgets.php">' .
			esc_html__( 'Settings Widget', 'wp-categories-widget' ) .
			'</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
);


if( class_exists('WpcEditor') ) {
    if( is_admin() ) {
    // instantiate the plugin class
    $wcw_plugin_template = new WpcEditor();

    }
}