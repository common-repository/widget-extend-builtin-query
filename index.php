<?php
/*
 * Plugin Name:   Built-in Widgets Query extend
 * Description:   Add support for extending the built-in widgets and adding your query arguments.
 * Text Domain:   widget-extend-builtin-query
 * Domain Path:   /languages
 * Version:       1.09
 * WordPress URI: https://wordpress.org/plugins/widget-extend-builtin-query/
 * Plugin URI:    https://puvox.software/software/wordpress-plugins/?plugin=widget-extend-builtin-query
 * Contributors:  puvoxsoftware,ttodua
 * Author:        Puvox.software
 * Author URI:    https://puvox.software/
 * Donate Link:   https://paypal.me/Puvox
 * License:       GPL-3.0
 * License URI:   https://www.gnu.org/licenses/gpl-3.0.html
*/


namespace WidgetEnhancedBuiltin
{
  if (!defined('ABSPATH')) exit;
  require_once( __DIR__."/library.php" );
  require_once( __DIR__."/library_wp.php" );
  
  class PluginClass extends \Puvox\wp_plugin
  {

	public function declare_settings()
	{
		$this->initial_static_options	= 
		[
			'has_pro_version'        => 0, 
            'show_opts'              => false, 
            'show_rating_message'    => true, 
            'show_donation_footer'   => true, 
            'show_donation_popup'    => true, 
            'menu_pages'             => [
                'first' =>[
                    'title'           => 'Widgets Query extend', 
                    'default_managed' => 'network',            // network | singlesite
                    'required_role'   => 'install_plugins',
                    'level'           => 'submenu', 
                    'page_title'      => 'Built-in Widgets Query extend',
                    'tabs'            => [],
                ],
            ]
		];

		$this->initial_user_options		= 
		[	
			'enable_async_load_in_content'	=> true,
		]; 
	}

	public function __construct_my()
	{
			//$instance = apply_filters( 'widget_form_callback', $instance, $this );
			//...
			//$return = $this->form( $instance );
			//do_action_ref_array( 'in_widget_form', array( &$this, &$return, $instance ) );
		add_action( 'in_widget_form', [$this,'in_widget_form'], 10, 3 );
		
		add_action( 'widget_update_callback', [$this,'widget_update_callback'], 10, 4 );

		// 
		add_action( 'init', function () {      register_post_type( 'movies', [ 'labels' => [ 'name' => __( 'Movies' ), 'singular_name' => __( 'Movie' ) ], 'public' => true, 'has_archive' => true, 'rewrite' => ['slug' => 'movies'], 'show_in_rest' => true, 'supports'=>['author', 'category', 'title', 'editor', 'author', 'thumbnail', 'excerpt'], 'taxonomies'  => [ 'category' ] ] );         });
		
		// $instance = apply_filters( 'widget_display_callback', $instance, $widget_obj, $args );
		add_filter( 'widget_display_callback', [$this,'widget_display_callback'], 10, 3 );
	}

	// ============================================================================================================== //


	/*	
	  example $widget ($this) argument:::
		[ id_base=>'archives', name=>'Archives', option_name=>'widget_archives', alt_option_name=>null, widget_options => [ classname=>'widget_archive', customize_selective_refresh=>true, description=>'A monthly archive ...' ], 'control_options'=>[id_base=>'archives'], number=>__i__, id=>archives-__i__, updated=>false]
	*/

	private $native_widgets = ['archives', 'categories', 'recent-comments', 'recent-posts', 'tag_cloud' ]; 
	// Not yet implemented for: 'search', 'calendar',   	//calendar -> get_calendar(); search: get_search_form;  
	// We dont need: 'text', 'meta', 'custom_html', 'pages', 'nav_menu', 'media_gallery', 'media_audio', 'media_video', 'media_image', 'rss', 

	private $available_functions = ['archives'=>'wp_get_archives', 'recent-comments'=>'get_comments', 'recent-posts'=>'WP_Query', 'tag_cloud'=>'wp_tag_cloud', 'categories1'=>'wp_list_categories', 'categories2'=>'wp_dropdown_categories'];
	
	private	$widgets_filters = ['archives1'=>'widget_archives_dropdown_args', 'archives2'=>'widget_archives_args',  'recent-comments'=>'widget_comments_args', 'recent-posts'=>'widget_posts_args', 'tag_cloud'=>'widget_tag_cloud_args', 'categories1'=>'widget_categories_args', 'categories2'=>'widget_categories_dropdown_args'];
		
		
	public function allowedWidget($widget_slug)
	{
		return ( in_array($widget_slug, $this->native_widgets) ); 
	}


	public $slugs = ['query_fields'=>'query_fields_PUVOX'];
	public function in_widget_form( $widget, $return, $instance  )  //$widget>Widget array; $return>null;  $instance-->[] empty array
	{
		if ($this->allowedWidget($widget->id_base))
		{
			$nm = $this->slugs['query_fields'];
			$post_types_str = isset( $instance[$nm] ) ? $this->fieldData($instance) : "";
			?>
			<p style="background:#e7e7e7; padding: 2px;">
				<label for="<?php echo $widget->get_field_id($nm); ?>"><?php _e( 'Query string:'); ?></label>
				<input class="large-text" id="<?php echo $widget->get_field_id($nm); ?>" name="<?php echo $widget->get_field_name($nm); ?>" type="text" value="<?php echo $post_types_str; ?>" /> 
				<span class="desription" style="font-style:italic;">
				<?php _e( sprintf('Leave blank to ignore this field. Otherwise, you should enter your standard query string (<a href="%s">example</a>)', 'javascript:alert(\'Find out query arguments at wordpress.org, depending your chosen widget ('. implode(', ', $this->available_functions).'). \n\nExample for WP_Query: \npost_type[]=post&post_type[]=movie&posts_per_page=15\n\nExample for wp_list_categories/wp_get_archives:\nhide_empty=0&taxonomy=myBookGenres&exclude=3,82,17\');' ) );?>
				</span>
			</p>
			<?php
		}
	}

	private function fieldData($instance, $sanitize=true){ 
		$str = $instance[$this->slugs['query_fields']]; 
		return ( $sanitize ? sanitize_text_field($str) : $str );
	}
	
	public function widget_update_callback( $instance, $new_instance, $old_instance, $widget ) 
	{	
		if ($this->allowedWidget($widget->id_base))
		{
			$instance[$this->slugs['query_fields']] = $this->fieldData($new_instance);
		}
		return $instance;
	}
	
	public function widget_display_callback( $instance_settings, $widget_obj, $sidebar_args ) 
	{
		if ($this->allowedWidget($widget_obj->id_base)) 
			$this->addRemoveFilters(true); 
		return $instance_settings; 
	}
	
 
	public function widget_args($args, $instance)
	{
		if(isset($instance[$this->slugs['query_fields']]))
		{
			parse_str( $this->fieldData($instance), $new_array);
			$args = array_merge($args, $new_array);
		}
		$this->addRemoveFilters(false); 
		return $args;
	}

	public function addRemoveFilters($add=true)
	{
		if($add) { foreach ($this->widgets_filters as $each) add_filter($each, [$this, 'widget_args'], 10, 2); }
		else 	 { foreach ($this->widgets_filters as $each) remove_filter($each, [$this, 'widget_args'], 10, 2); }
	}
	
	
	/*
	//if ( false === $instance ) do_action( 'the_widget', $widget, $instance, $args );
	//add_filter( 'the_widget', [$this,'the_widget_func'], 10, 3 );
	public function the_widget_func( $widget, $instance, $args ) 
	{
		return $widget;
	}
	*/
	

	
 
	// =================================== Options page ================================ //
	public function opts_page_output()
	{ 
		return;
		$this->settings_page_part("start", 'first');
		?>

		<style> 
		</style>

		<?php if ($this->active_tab=="Options") 
		{ 
			//if form updated
			if( $this->checkSubmission() )
			{ 
				//$this->opts['enable_async_load_in_content']	= !empty($_POST[ $this->plugin_slug ]['enable_async_load_in_content']) ;  
				$this->update_opts();  
			}
			?>
 
			<form class="mainForm" method="post" action="">

			<table class="form-table">
				
			</table>
			
			<?php $this->nonceSubmit(); ?>

			</form> 
		<?php 
		}
		

		$this->settings_page_part("end", '');
	} 





  } // End Of Class

  $GLOBALS[__NAMESPACE__] = new PluginClass();

 
} // End Of NameSpace

?>