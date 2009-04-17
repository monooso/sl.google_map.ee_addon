<?php

/**
 * @package SL Google Map
 * @version 1.0.4
 * @author Stephen Lewis (http://experienceinternet.co.uk/)
 * @copyright Copyright (c) 2009, Stephen Lewis
 * @license http://creativecommons.org/licenses/by-sa/3.0 Creative Commons Attribution-Share Alike 3.0 Unported
 * @link http://experienceinternet.co.uk/resources/details/sl-google-map/
*/

class Sl_google_map extends Fieldframe_Fieldtype {
	
	var $info = array(
		'name'							=> 'SL Google Map',
		'version'						=> '1.0.4',
		'desc'							=> 'Google Map Field Type with full SAEF and weblogs tag support.',
		'docs_url'					=> 'http://experienceinternet.co.uk/resources/details/sl-google-map/',
		'versions_xml_url'	=> 'http://experienceinternet.co.uk/addon-versions.xml'
		);
		
	var $default_site_settings = array(
		'map_lat'		=> 39.368,
		'map_lng'		=> -1.406,
		'map_zoom'	=> 1,
		'map_size'	=> 400
		);
		
		
	/**
	 * Displays the site-wide settings in the CP.
	 * @return 		string 		HTML to be inserted into the field type settings block.
	 */
	function display_site_settings()
	{
		// Initialise our return variable.
		$r = '';
		
		// Initialise a new instance of the SettingsDisplay class.
		$sd = new FieldFrame_SettingsDisplay();
		
		// Site-wide settings.
		$r .= $sd->block('sitewide_settings');
		$r .= $sd->info_row('settings_tip');
		
		// API key.
		$r .= $sd->row(array(
				$sd->label('api_key'),
				$sd->text('api_key', isset($this->site_settings['api_key']) ? $this->site_settings['api_key'] : '')
				));
				
		// Default latitude.
		$r .= $sd->row(
			array(
				$sd->label('default_latitude'),
				$sd->text('map_lat', isset($this->site_settings['map_lat']) ? $this->site_settings['map_lat'] : $this->default_site_settings['map_lat'])
				)
			);					

		// Default longitude.
		$r .= $sd->row(
			array(
				$sd->label('default_longitude'),
				$sd->text('map_lng', isset($this->site_settings['map_lng']) ? $this->site_settings['map_lng'] : $this->default_site_settings['map_lng'])
				)
			);

		// Default zoom.
		$o = array();
		for ($count = 0; $count <= 17; $count++) {$o[$count . ''] = $count . '';}

		$r .= $sd->row(
			array(
				$sd->label('default_zoom'),
				$sd->select('map_zoom', isset($this->site_settings['map_zoom']) ? $this->site_settings['map_zoom'] : $this->default_site_settings['map_zoom'], $o)
				)
			);
				
		// Close the settings block.
		$r .= $sd->block_c();
		
		return $r;
	}
	
	
	/**
	 * Performs validation when the site-wide settings are saved.
	 * @param 		array 		$site_settings 		The site settings.
	 * @return 		array 		An array of modified site settings.
	 */
	function save_site_settings($site_settings)
	{
		// Latitude.
		if ( ! isset($site_settings['map_lat']) OR (isset($site_settings['map_lat']) && ! is_numeric(trim($site_settings['map_lat']))))
		{
			$site_settings['map_lat'] = $this->default_site_settings['map_lat'];
		}
		
		// Longitude.
		if ( ! isset($site_settings['map_lng']) OR (isset($site_settings['map_lng']) && ! is_numeric(trim($site_settings['map_lng']))))
		{
			$site_settings['map_lng'] = $this->default_site_settings['map_lng'];
		}
		
		return $site_settings;
	}
	
	
	/**
	 * Displays the field settings form.
	 * @param 		array 		$field_settings 			Previously-saved field settings.
	 * @return 		array 		An associative array.
	 */
	function display_field_settings($field_settings)
	{
		global $LANG;
		
		// Initialise a new instance of SettingsDisplay.
		$sd = new Fieldframe_SettingsDisplay();
		
		// Retrieve the saved settings, and fill in the gaps where necessary.
		$map_size = isset($field_settings['map_size']) ? $field_settings['map_size'] : $this->default_site_settings['map_size'];

		$c = '';
		
		// Open the table.
		$c .= $sd->block();
		$c .= $sd->info_row('settings_tip');

		// Default map latitude.
		$c .= $sd->row(array(
			$sd->label('default_latitude'),
			$sd->text('map_lat', isset($field_settings['map_lat']) ? $field_settings['map_lat'] : $this->site_settings['map_lat'])
			));

		// Default map longitude.
		$c .= $sd->row(array(
			$sd->label('default_longitude'),
			$sd->text('map_lng', isset($field_settings['map_lng']) ? $field_settings['map_lng'] : $this->site_settings['map_lng'])
			));

		// Default map zoom.
		$o = array();
		for ($count = 0; $count <= 17; $count++) {$options[$count . ''] = $count . '';}

		$c .= $sd->row(array(
			$sd->label('default_zoom'),
			$sd->select('map_zoom', isset($field_settings['map_zoom']) ? $field_settings['map_zoom'] : $this->site_settings['map_zoom'], $options)
			));

		// Close the table.
		$c .= $sd->block_c();
		
		// Set our return data.
		$r = array(
			'cell1'									=> $sd->text('map_size', $map_size, array('maxlength' => 4, 'width' => '75px')) . NBS . $LANG->line('map_size'),
			'cell2'									=> $c,
			'formatting_available'	=> FALSE,
			'direction_available'		=> FALSE
			);
		
		return $r;
	}
	
	
	/**
	 * Performs validation when the field settings are saved.
	 * @param 		array 		$field_settings 			The field settings.
	 * @return 		array 		The modified field settings.
	 */
	function save_field_settings($field_settings)
	{		
		// Size.
		if ( ! isset($field_settings['map_size']) OR (isset($field_settings['map_size']) && ! is_int(intval(trim($field_settings['map_size'])))))
		{
			$field_settings['map_size'] = $this->default_site_settings['map_size'];
		}
		
		// Latitude.
		if ( ! isset($field_settings['map_lat']) OR (isset($field_settings['map_lat']) && ! is_numeric(trim($field_settings['map_lat']))))
		{
			$field_settings['map_lat'] = $this->site_settings['map_lat'];
		}
		
		// Longitude.
		if ( ! isset($field_settings['map_lng']) OR (isset($field_settings['map_lng']) && ! is_numeric(trim($field_settings['map_lng']))))
		{
			$field_settings['map_lng'] = $this->site_settings['map_lng'];
		}
		
		return $field_settings;
	}
	
	
	/**
	 * Displays the field editor in the CP or a SAEF.
	 * @param 		string	 	$field_name 			The field name.
	 * @param 		string 		$field_data 			The previously-saved field data.
	 * @param 		array 		$field_settings		The field settings.
	 * @param			array 		$init 						Initialisation object specifying UI and usage options.
	 * @return 		string 		The HTML to output.
	 */	
	function _display_field($field_name, $field_data, $field_settings, $init)
	{
		global $DSP, $LANG;

		// Initialise the return variable.
		$r = '';

		// Explicitly set the language file.
		$LANG->fetch_language_file('sl_google_map');

		// Retrieve the API key from the site settings array.
		$api_key = isset($this->site_settings['api_key']) ? $this->site_settings['api_key'] : '';

		// Retrieve the map coordinates from the field data array.
		if ($field_data == '')
		{
			$field_data = $field_settings['map_lat'] . ',' . $field_settings['map_lng'] . ',' . $field_settings['map_zoom'] . ',';
			$field_data .= $field_settings['map_lat'] . ',' . $field_settings['map_lng'];
		}

		// Include our "global" scripts for the CP.
		if ( ! isset($this->global_script_included) && ( ! isset($init['control_panel']) OR $init['control_panel'] !== FALSE))
		{
			$this->global_script_included = TRUE;

			// Include the Google Maps scripts. We can't do this using the "include_js" convenience function
			// because we're calling an external script, so we do it the old fashioned way.
			$h = '<script type="text/javascript" src="http://maps.google.com/maps?file=api&amp;v=2.x&amp;key=' . $api_key . '&amp;sensor=false"></script>' . "\n";
			$h .= '<script type="text/javascript" src="http://www.google.com/uds/api?file=uds.js&amp;v=1.0&amp;key=' . $api_key . '"></script>' . "\n";
			$DSP->extra_header .= $h;

			// Include the custom SL Google Map script.
			$this->include_js('js/global_script.js');
		}

		// The map container.
		$container_id = (isset($init['id']) && $init['id'] != '') ? $init['id'] : $field_name . '_container';
		$r .= '<div id="' . $container_id . '" class="sl-google-map';		
		$r .= (isset($init['class']) && $init['class'] != '') ? ' ' . $init['class'] . '"' : '"';
		$r .= ( ! isset($init['control_panel']) OR $init['control_panel'] !== FALSE) ? ' style="height: ' . $field_settings['map_size'] . 'px; margin-bottom : 1em;">' : '>';
		$r .= (isset($init['fallback']) && $init['fallback'] != '') ? $init['fallback'] : '<p>' . $LANG->line('publish_no_javascript') . '</p>';
		$r .= '</div>';
		
		// Additional doo-hickeys if the map is in "editor" mode.
		if ( ! isset($init['editor']) OR $init['editor'] !== FALSE)
		{
			$r .= '<div class="hidden">';
			
			// The field to store the map data.
			$r .= '<input type="hidden" name="' . $field_name . '" id="' . $field_name . '" value="' . $field_data .'" />';
		
			// If we're not in the control panel (i.e. this is a SAEF), we also need to store the field formatting value.
			if (isset($init['control_panel']) && $init['control_panel'] === FALSE)
			{
				$r .= '<input type="hidden"' .
								' name="' . str_replace('field_id_', 'field_ft_', $field_name) . '"' .
								' id="' . str_replace('field_id_', 'field_ft_' , $field_name) . '"' .
								' value="' . $field_data .'" />';
			}
			
			$r .='</div>';

			// The address finder.
			$r .= '<div class="sl-google-map-address-lookup">';
			$r .= '<label for="' . $field_name . '_address_input">' . $LANG->line('publish_finder_label') . '</label>';
			$r .= '<input id="' . $field_name . '_address_input" class="sl-google-map-address-input" />';
			$r .= '<input id="' . $field_name . '_address_submit" class="sl-google-map-address-submit" type="button" value="' . $LANG->line('publish_finder_button') . '" />';
			$r .= '</div>';
			
			// Make a note of the map data and address finder fields.
			$map_field 			= $field_name;
			$address_input 	= $field_name . '_address_input';
			$address_submit = $field_name . '_address_submit';
		}
		else
		{
			$map_field = $address_input = $address_submit = null;
		}		

		// Extract the field data into separate variables for use in our JS initialisation code.
		list($map_lat, $map_lng, $map_zoom, $pin_lat, $pin_lng) = explode(',', $field_data);
		
		// Extract the options into separate variables for use in the our JS initialisation code.
		// I'm sure there must be a more elegant way of doing this, but the following will do for now.
		$ui_zoom 					= isset($init['options']['ui_zoom']) 					? $init['options']['ui_zoom'] 				: FALSE;
		$ui_scale 				= isset($init['options']['ui_scale']) 				? $init['options']['ui_scale'] 				: FALSE;
		$ui_overview 			= isset($init['options']['ui_overview']) 			? $init['options']['ui_overview'] 		: FALSE;
		$map_drag 				= isset($init['options']['map_drag']) 				? $init['options']['map_drag'] 				: FALSE;
		$map_click_zoom 	= isset($init['options']['map_click_zoom']) 	? $init['options']['map_click_zoom'] 	: FALSE;
		$map_scroll_zoom	= isset($init['options']['map_scroll_zoom']) 	? $init['options']['map_scroll_zoom'] : FALSE;
		$pin_drag 				= isset($init['options']['pin_drag']) 				? $init['options']['pin_drag'] 				: FALSE;
		$background				= isset($init['options']['background'])				? $init['options']['background']			: NULL;

		// The JavaScript to initialise this field.
		$r .= <<<JAVASCRIPT
<script type="text/javascript">

	if (typeof(SJL) == 'undefined' || ( ! SJL instanceof Object)) SJL = new Object();
	if (typeof(SJL.google_maps) == 'undefined' || ( ! SJL.google_maps instanceof Array)) SJL.google_maps = new Array();

	SJL.google_maps.push({
		init : {
			api_key					: '{$api_key}',
			map_field				: '{$map_field}',
			map_container		: '{$container_id}',
			map_lat					: {$map_lat},
			map_lng					: {$map_lng},
			map_zoom				: {$map_zoom},
			pin_lat					: {$pin_lat},
			pin_lng					: {$pin_lng},
			address_input 	: '{$address_input}',
			address_submit	: '{$address_submit}'
		},
		options : {
			ui_zoom					: '{$ui_zoom}',
			ui_scale				: '{$ui_scale}',
			ui_overview			: '{$ui_overview}',
			map_drag				: '{$map_drag}',
			map_click_zoom	: '{$map_click_zoom}',
			map_scroll_zoom : '{$map_scroll_zoom}',
			pin_drag				: '{$pin_drag}',
			background			: '{$background}'
		}
	});

</script>		
JAVASCRIPT;

		return $r;
	}
		
	
	/**
	 * Displays the field in the CP.
	 * @param 		string	 	$field_name 			The field name.
	 * @param 		string 		$field_data 			The previously-saved field data.
	 * @param 		arrray 		$field_settings		The field settings.
	 * @return 		string 		The HTML to output.
	 */
	function display_field($field_name, $field_data, $field_settings)
	{
		// Include some custom CP CSS.
		$this->include_css('css/cp.css');
		
		// Call the function that does all the donkey work.			
		$init = array(
			'editor'				=> TRUE,
			'control_panel'	=> TRUE,
			'options'				=> array(
				'ui_zoom'					=> TRUE,
				'ui_scale'				=> TRUE,
				'ui_overview'			=> TRUE,
				'map_drag'				=> TRUE,
				'map_click_zoom'	=> TRUE,
				'map_scroll_zoom'	=> FALSE,
				'pin_drag'				=> TRUE
				)
			);
			
		return $this->_display_field($field_name, $field_data, $field_settings, $init);
	}
	
	
	/**
	 * Displays the field in the exp:weblog:entries template.
	 * @param 	array 		$params 					Key / value pairs of the template tag parameters.
	 * @param 	string 		$tagdata 					Contents of the template between the opening and closing tags, if it's a tag pair.
	 * @param 	string 		$field_data				The previously-saved field data.
	 * @param 	array 		$field_settings		The field settings.
	 * @return 	string 		String of template markup.
	 */
	function display_tag($params, $tagdata, $field_data, $field_settings)
	{
		global $FF, $TMPL;
		
		$pin_center = FALSE;
		
		// Extract the fallback text from inside the {map} tag pair.
		$fallback = preg_replace(
			'/' . LD . 'map' . RD . '(.*?)' . LD . SLASH . 'map' . RD .'/s',
			'$1',
			$tagdata
			);
		
		if (isset($params['edit']) &&
			(strtolower($params['edit']) == 'y' ||
			strtolower($params['edit']) == 'yes' ||
			strtolower($params['edit']) == 'true')
		)
		{
			// As it's an editor, we set some sensible defaults...
			$init = array(
				'id'						=> (isset($params['id']) ? $params['id'] : ''),
				'class'					=> (isset($params['class']) ? $params['class'] : ''),
				'editor'				=> TRUE,
				'control_panel'	=> FALSE,
				'fallback'			=> $fallback,
				'options'				=> array(
					'ui_zoom'					=> TRUE,
					'ui_scale'				=> TRUE,
					'ui_overview'			=> TRUE,
					'map_drag'				=> TRUE,
					'map_click_zoom'	=> TRUE,
					'map_scroll_zoom'	=> FALSE,
					'pin_drag'				=> TRUE,
					'background'			=> (isset($params['background']) ? $params['background'] : ''),
					)
				);
		}
		else
		{
			$init = array(
				'id'						=> (isset($params['id']) ? $params['id'] : ''),
				'class'					=> (isset($params['class']) ? $params['class'] : ''),
				'editor'				=> FALSE,
				'control_panel'	=> FALSE,
				'fallback'			=> $fallback,
				'options'				=> array(
					'background'	=> (isset($params['background']) ? $params['background'] : '')
					)
				);
		}
		
		// Retrieve settings from the tag parameters.
		if (isset($params['controls']))
		{
			$options = explode('|', $params['controls']);
			$init['options']['ui_zoom'] 		= (array_search('zoom', $options) !== FALSE);
			$init['options']['ui_scale'] 		= (array_search('scale', $options) !== FALSE);
			$init['options']['ui_overview'] = (array_search('overview', $options) !== FALSE);
		}
		
		if (isset($params['map']))
		{
			$options = explode('|', $params['map']);
			$init['options']['map_drag'] 				= (array_search('drag', $options) !== FALSE);
			$init['options']['map_click_zoom']	= (array_search('click_zoom', $options) !== FALSE);
			$init['options']['map_scroll_zoom']	= (array_search('scroll_zoom', $options) !== FALSE);
		}
		
		if (isset($params['pin']))
		{
			$options = explode('|', $params['pin']);
			$init['options']['pin_drag'] = (array_search('drag', $options) !== FALSE);
			$pin_center = (array_search('center', $options) !== FALSE);
		}
		
		// Extract the current field data.
		list($map_lat, $map_lng, $map_zoom, $pin_lat, $pin_lng) = explode(',', $field_data);
		
		// Do we need to center the map on the pin location?
		if ($pin_center)
		{
			$field_data = $pin_lat . ',' . $pin_lng . ',' . $map_zoom . ',' . $pin_lat . ',' . $pin_lng;
		}
		
		// Initialisation done, let's get swapping.			
		$r = preg_replace(
			'/' . LD . 'map' . RD . '(.*?)' . LD . SLASH . 'map' . RD .'/s',
			$this->_display_field('field_id_' . $FF->field_id, $field_data, $field_settings, $init),
			$tagdata
			);
		
		$r = $TMPL->swap_var_single('map_lat', $map_lat, $r);
		$r = $TMPL->swap_var_single('map_lng', $map_lng, $r);
		$r = $TMPL->swap_var_single('map_zoom', $map_zoom, $r);
		$r = $TMPL->swap_var_single('pin_lat', $pin_lat, $r);
		$r = $TMPL->swap_var_single('pin_lng', $pin_lng, $r);
		
		return $r;
	}
}

?>