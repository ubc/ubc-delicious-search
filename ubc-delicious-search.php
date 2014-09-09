<?php
/**
 * Plugin Name: UBC Delicious Search
 * Plugin URI:
 * Description: Allows you to filter and search on your Delicious account
 * Version: 0.1
 * Author: ctlt-dev, loongchan
 * Author URI: 
 * License: GPL2
 *
 * 
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License as published by the Free Software Foundation; either version 2 of the License,
 * or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * You should have received a copy of the GNU General Public License along with this program; if not, write
 * to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 *
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

class UBC_Delicious_Search {
	private $ubc_delicious_attributes;
	
	function __construct() {
		add_action('init', array($this, 'register_shortcodes' ));
		add_action('wp_enqueue_scripts',  array($this, 'register_scripts'));
	}
	
	public function register_scripts() {
		wp_register_script('ubc-delicious-search', plugin_dir_url(__FILE__).'/js/ubc_delicious_search.js', array('jquery'));
		wp_register_style('ubc-delicious-search', plugin_dir_url(__FILE__).'/css/ubc_delicious_search.css');
	}
	
	/**
	 * register_shortcode function.
	 *
	 * @access public
	 * @return void
	 */
	public function register_shortcodes() {
		/* don't do anything if the shortcode exists already */
		$this->add_shortcode( 'ubc_delicious_results', 'ubc_delicious_results' );
		$this->add_shortcode( 'ubc_delicious_search', 'ubc_delicious_search' );
		$this->add_shortcode( 'ubc_delicious_dropdown', 'ubc_delicious_dropdown' );
		$this->add_shortcode( 'ubc_delicious_checkbox', 'ubc_delicious_checkbox' );
		$this->add_shortcode( 'ubc_delicious_results_once', 'ubc_delicious_results_once' );
	}
	
	/**
	 * has_shortcode function.
	 *
	 * @access public
	 * @param mixed $shortcode
	 * @return void
	 */
	function has_shortcode( $shortcode ) {
		global $shortcode_tags;
	
		return ( in_array( $shortcode, array_keys ($shortcode_tags ) ) ? true : false);
	}
	
	/**
	 * add_shortcode function.
	 *
	 * @access public
	 * @param mixed $shortcode
	 * @param mixed $shortcode_function
	 * @return void
	 */
	function add_shortcode( $shortcode, $shortcode_function ) {
		if( !$this->has_shortcode( $shortcode ) )
			add_shortcode( $shortcode, array( $this, $shortcode_function ) );
	}
	

	
	/**
	 * creates a search box
	 * @param unknown $atts
	 * @param string $content
	 */
	function ubc_delicious_search($atts, $content = null) {
		//enqueue script/css
		wp_enqueue_script('ubc-delicious-search');
		wp_enqueue_style('ubc-delicious-search');
		
		$return_val = '';
		
		$this->ubc_delicious_attributes['search'] = shortcode_atts(array(
				'placeholder'	=> "Search Words",	//input placeholder 
				'submittext'	=> "Submit",		//submit button text
				'resettext' 	=> "Reset",			//reset button text, used ONLY if usereset is not false
				'searchtitle'	=> "Search",		//search title text
				'extraclasses'	=> '',
				'buttonclasses'	=> '',
				'usereset'		=> 'true'
		), $atts );

		//escaping stuff
		$placeholder = esc_attr(trim($this->ubc_delicious_attributes['search']['placeholder']));
		$submittext = esc_html(trim($this->ubc_delicious_attributes['search']['submittext']));
		$resettext = esc_html(trim($this->ubc_delicious_attributes['search']['resettext']));
		$searchtitle = esc_html(trim($this->ubc_delicious_attributes['search']['searchtitle']));
		$extraclasses = esc_attr(trim($this->ubc_delicious_attributes['search']['extraclasses']));
		$buttonclasses = esc_attr(trim($this->ubc_delicious_attributes['search']['buttonclasses']));
		$usereset = esc_attr(trim($this->ubc_delicious_attributes['search']['usereset']));
		
		ob_start();
		?>
		<div class="ubc-delicious-search-area-container">
			<div class="ubc-dellicious-search-area <?php echo $extraclasses;?>">
				<label class="ubc-delicious-search-title"><span class="ubc-delicious-label-title"><?php echo $searchtitle;?></span>
					<input type="text" id="ubc-delicious-search-term" class="ubc-delicious-input" name="ubc-delicious-search-term" placeholder="<?php echo $placeholder;?>">
				</label>
			</div>
		<?php 
			$return_val .= ob_get_clean();
			 if (!is_null($content)) {
				$return_val .= do_shortcode($content);
			}
			ob_start();
		?>
		<div class="ubc-delicious-search-submit-area">
				<?php 
					if ($usereset != false && !empty($usereset)) {
				?>
					<button <?php echo !empty($buttonclasses) ? 'class="'.$buttonclasses.'"' : '';?> type="reset" id="ubc-delicious-reset" ><?php if (!empty($resettext)) { echo $resettext;}?></button>
				<?php 
					}
				?>
				<button <?php echo !empty($buttonclasses) ? 'class="'.$buttonclasses.'"' : '';?> type="submit" id="ubc-delicious-submit" ><?php if (!empty($submittext)) { echo $submittext;}?></button>
			</div>
		</div><!-- end of ubc-delicious-search-area-container -->
		<?php 
		$return_val .= ob_get_clean();
		return $return_val;
	}
	
	/**
	 * creates options for the dropdown.
	 * 
	 * eg1: [ubc_delicious_dropdown optionlist="value::label, value2"]
	 * @param unknown $atts
	 * @param string $content
	 * @return string
	 */
	function ubc_delicious_dropdown($atts, $content = null) {
		//enqueue script/css
		wp_enqueue_script('ubc-delicious-search');
		wp_enqueue_style('ubc-delicious-search');

		$this->ubc_delicious_attributes['dropdown'] = shortcode_atts(array(
			'useshowall' => 'Show All',		//if false or empty, then don't use show all option, else show the text
			'optionslist' => '',			//list of options
			'defaultoption' => '',			//selected VALUE of option to make default
			'optiontitle' => '',			//label for the dropdown
			'extraclasses' => ''			//extra classes!
		), $atts);

		//escaping values 
		$optiontitle = esc_html(trim($this->ubc_delicious_attributes['dropdown']['optiontitle']));
		$extraclasses = esc_attr(trim($this->ubc_delicious_attributes['dropdown']['extraclasses']));
		
		//output for the function
		$return_val = '';

		//figure out and create options for the select
		if (!empty($this->ubc_delicious_attributes['dropdown']['optionslist'])) {
			$raw_parsed = explode(',', $this->ubc_delicious_attributes['dropdown']['optionslist']);
			$dropdown_options = '';

			//first see if "Show All" is wanted
			$trimmed_useshowall = trim($this->ubc_delicious_attributes['dropdown']['useshowall']);
			if ($this->ubc_delicious_attributes['dropdown']['useshowall'] != 'false' && $trimmed_useshowall != "") {
				$dropdown_options .= '<option value="Show All">'.esc_html($trimmed_useshowall).'</option>';
			}
			
			//add rest of options
			foreach ($raw_parsed as $single_option) {
				$dropdown_raw = explode('::', $single_option);
				$dropdown_extra = '';	//variable to hold extra stuff, like disabled.

				if ($dropdown_raw === false) {
					continue;
				} else if (count($dropdown_raw) == 1) {
					$dropdown_raw[] = $dropdown_raw[0];
				} else if (count($dropdown_raw) == 3) {
					$dropdown_extra = $dropdown_raw[2];
				}
				//if option value matches optiontitle, then make it default
				$is_selected = trim($dropdown_raw[0]) == trim($this->ubc_delicious_attributes['dropdown']['defaultoption']);
				$dropdown_options .= '<option '.($is_selected? 'selected="selected"':'').' value="'.esc_attr(trim($dropdown_raw[0])).'" '.esc_attr($dropdown_extra).'>'.esc_html(trim($dropdown_raw[1])).'</option>';
			}
			ob_start();
			?>
			<div class="ubc-delicious-dropdown-area <?php echo $extraclasses;?>">
				<label class="ubc-delicious-dropdown-label"><span class="ubc-delicious-label-title"><?php echo $optiontitle;?></span>
					<select class="ubc-delicious-dropdown ubc-delicious-input">
						<?php echo $dropdown_options;?>
					</select>
				</label>
			</div>
			<?php 
			$return_val = ob_get_clean();
		}		

		return $return_val;
	}
	
	/**
	 * creates checkboxes for tags
	 *
	 * eg1: [ubc_delicious_checkbox optionlist="value::label, value2"]
	 * @param unknown $atts
	 * @param string $content
	 * @return string
	 */
	function ubc_delicious_checkbox($atts, $content = null) {
		//enqueue script/css
		wp_enqueue_script('ubc-delicious-search');
		wp_enqueue_style('ubc-delicious-search');
	
		$this->ubc_delicious_attributes['checkbox'] = shortcode_atts(array(
				'optionslist' => '',			//list of options
				'defaultoption' => '',			//comma separated VALUE of checkboxes to make checked
				'optiontitle' => '',			//label for the dropdown
				'extraclasses' => ''			//extra classes!
		), $atts);
	
		//escaping values
		$optiontitle = esc_html(trim($this->ubc_delicious_attributes['checkbox']['optiontitle']));
		$extraclasses = esc_attr(trim($this->ubc_delicious_attributes['checkbox']['extraclasses']));
	
		//output for the function
		$return_val = '';
	
		//figure out and create options for the select
		if (!empty($this->ubc_delicious_attributes['checkbox']['optionslist'])) {
			$raw_parsed = explode(',', $this->ubc_delicious_attributes['checkbox']['optionslist']);
			$raw_checked = explode(',', $this->ubc_delicious_attributes['checkbox']['defaultoption']);
			$checkbox_options = null;
		
			//clean up arrays to trim everything
			$raw_parsed = array_map("trim", $raw_parsed);
			$raw_checked = array_map("trim", $raw_checked);
				
			//add rest of options
			foreach ($raw_parsed as $single_option) {
				$checkbox_raw = explode('::', $single_option);
				$checkbox_extra = '';	//variable to hold extra stuff, like disabled.

				if ($checkbox_raw === false) {
					continue;
				} else if (count($checkbox_raw) == 1) {
					$checkbox_raw[] = $checkbox_raw[0];
				} else if (count($checkbox_raw) == 3) {
					if (strtolower($checkbox_raw[2]) === 'disabled') {
						$checkbox_extra = 'disabled="disabled"';
					} else {
						$checkbox_extra = $checkbox_raw[2];
					}
				}
					
				//if option value matches optiontitle, then make it default
				$is_selected = in_array(trim($checkbox_raw[0]), $raw_checked);
				$checkbox_options[] = '<input name="ubc-delicious-checkbox" class="ubc-delicious-checkbox" type="checkbox" '.($is_selected? 'checked':'').' value="'.esc_attr($checkbox_raw[0]).'" '.esc_attr($checkbox_extra).'>'.esc_html($checkbox_raw[1]).'<br>';
			}
			ob_start();
			?>
				<div class="ubc-delicious-checkbox-area <?php echo $extraclasses;?>">
					<span class="ubc-delicious-label-title"><?php echo $optiontitle;?></span>
			<?php 
				foreach ($checkbox_options as $checkbox_option) {
			?>
					<label class="ubc-delicious-checkbox-label">
						<?php echo $checkbox_option;?>
					</label>
		<?php	} ?>
				</div>
				<?php 
				$return_val = ob_get_clean();
			}		
	
			return $return_val;
		}
	
	/**
	 * creates the div where the results of the filter/searching should show
	 *
	 * @access public
	 * @param array $atts 
	 * @param string $content
	 * @return string
	 *
	 *@TODO
	 * - need to give errors when leaving default user blank.  Maybe make it into settings???
	 *
	 */ 
	function ubc_delicious_results($atts, $content = null) {
		//enqueue script/css
		wp_enqueue_script('ubc-delicious-search');
		wp_enqueue_style('ubc-delicious-search');
		
		$this->ubc_delicious_attributes['results'] = shortcode_atts(array(
			'limit' => 20,
			'defaulttag' => '',
			'defaultuser' => '',
			'view' => 'list',
			'useor' => 'false',
			'sort' => 'rank',
			'showcomments' => 'true',
			'pagination' => '0'
		), $atts);

		$results = 	'<div class="ubc_delicious_results resource_listings" '.
					'data-defaulttag="'.esc_attr($this->ubc_delicious_attributes['results']['defaulttag']).'" '.
					'data-user="'.esc_attr($this->ubc_delicious_attributes['results']['defaultuser']).'" '.
					'data-limit="'.esc_attr($this->ubc_delicious_attributes['results']['limit']).'" '.
					'data-useor="'.esc_attr($this->ubc_delicious_attributes['results']['useor']).'" '.
					'data-view="'.esc_attr($this->ubc_delicious_attributes['results']['view']).'" '.
					'data-sort="'.esc_attr($this->ubc_delicious_attributes['results']['sort']).'" '.
					'data-showcomments="'.esc_attr($this->ubc_delicious_attributes['results']['showcomments']).'" '.
					'data-pagination="'.esc_attr($this->ubc_delicious_attributes['results']['pagination']).'"'.
					'></div>';

		return $results;
	}
	
	/**
	 * creates the div where the results show only upon page load.  no filter/search
	 *
	 * @access public
	 * @param array $atts
	 * @param string $content
	 * @return string
	 *
	 *@TODO
	 * - need to give errors when leaving default user blank.  Maybe make it into settings???
	 *
	 */
	function ubc_delicious_results_once($atts, $content = null) {
		//enqueue script/css
		wp_enqueue_script('ubc-delicious-search');
		wp_enqueue_style('ubc-delicious-search');
	
		$this->ubc_delicious_attributes['results_once'] = shortcode_atts(array(
				'limit' => 20,
				'defaulttag' => '',
				'defaultuser' => '',
				'view' => 'list',
				'useor' => 'false',
				'sort' => 'rank',
				'showcomments' => 'true',
		), $atts);
	
		$results = 	'<div class="ubc_delicious_results_once resource_listings" '.
				'data-defaulttag="'.esc_attr($this->ubc_delicious_attributes['results_once']['defaulttag']).'" '.
				'data-user="'.esc_attr($this->ubc_delicious_attributes['results_once']['defaultuser']).'" '.
				'data-limit="'.esc_attr($this->ubc_delicious_attributes['results_once']['limit']).'" '.
				'data-useor="'.esc_attr($this->ubc_delicious_attributes['results_once']['useor']).'" '.
				'data-view="'.esc_attr($this->ubc_delicious_attributes['results_once']['view']).'" '.
				'data-sort="'.esc_attr($this->ubc_delicious_attributes['results_once']['sort']).'" '.
				'data-showcomments="'.esc_attr($this->ubc_delicious_attributes['results_once']['showcomments']).
				'"></div>';
	
		return $results;
	}
}

$UBCDelicious = new UBC_Delicious_Search();
