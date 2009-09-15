<?php

/**
 * @package SL Google Map
 * @version 1.1.2
 * @author Stephen Lewis (http://experienceinternet.co.uk/)
 * @copyright Copyright (c) 2009, Stephen Lewis
 * @license http://creativecommons.org/licenses/by-sa/3.0 Creative Commons Attribution-Share Alike 3.0 Unported
 * @link http://experienceinternet.co.uk/resources/details/sl-google-map/
*/

class Sl_google_map extends Fieldframe_Fieldtype {
  
  // Map types.
  const HYBRID     = 'hybrid';
  const NORMAL     = 'normal';
  const PHYSICAL   = 'physical';
  const SATELLITE  = 'satellite';  
	
	var $requires = array(
		'ff'		    => '1.2.0',
		'cp_jquery' => '1.1'
		);
	
	var $info = array(
		'name'							=> 'SL Google Map',
		'version'						=> '1.1.2',
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
	 * Performs house-keeping when upgrading from an older version of the extension.
	 *
	 * @param   string|bool   $from   The previous version, or FALSE if this is the initial installation.
	 */
	function update($from = FALSE)
	{
	  global $DB, $LANG;
	  
	  if ($from !== FALSE && $from < '1.1.0')
	  {
	    /**
	     * Changed the way the data is stored in version 1.1.0. Instead of a pipe-delimeted
	     * string, we now use a much more robust serialised array.
	     *
	     * Need to convert the old data to use the new format.
	     */
	     
	    // Retrieve all the SL Google Map weblog fields.
	    $db_field_ids = $DB->query('SELECT `field_id`
	      FROM `exp_weblog_fields`
	      WHERE `field_type` = "ftype_id_' . $this->_fieldtype_id . '"'
	      );
	      
	    // Loop through the SL Google Map fields, updating the data.
	    $field_ids = array();
	    foreach ($db_field_ids->result AS $db_field_id)
	    {
	      $field_ids[] = 'field_id_' . $db_field_id['field_id'];
	    }
	    
	    if (count($field_ids) > 0)
	    {
	      $sql = 'SELECT `entry_id`, ' . implode(', ', $field_ids) . ' FROM `exp_weblog_data` ';
	      $sql .= 'WHERE ';
	      foreach ($field_ids AS $field_id)
	      {
	        $sql .= $field_id . ' <> "" AND ';
	      }
	      $sql = rtrim($sql, ' AND ');
	      
	      $db_weblog_data = $DB->query($sql);
	      
	      // Loop through the weblog data records.
	      foreach ($db_weblog_data->result AS $db_data)
	      {
	        // Loop through each of the SL Google Map fields for
	        // the current record, and convert the data if required.
	        foreach ($field_ids AS $field_id)
	        {
	          $old_map_data = explode(',', $db_data[$field_id]);
	          if (count($old_map_data) === 5)
	          {
	            $map_data = addslashes(serialize(
	              array(
	                'map_lat' => $old_map_data[0],
	                'map_lng' => $old_map_data[1],
	                'map_zoom' => $old_map_data[2],
	                'pin_lat' => $old_map_data[3],
	                'pin_lng' => $old_map_data[4]
	                )
	              ));
	          }
	          else
	          {
	            $map_data = '';
	          }
	            
            $update_array[$field_id] = $map_data;
	          
	          // Update the record.
	          $DB->query($DB->update_string(
	            'exp_weblog_data',
	            $update_array,
	            'entry_id = "' . $db_data['entry_id'] . '"'
	            ));
	        }
	      }
	    }
	  }
	}
	
	
	/**
	 * Displays the site-wide settings in the CP.
	 * @return 		string 		HTML to be inserted into the field type settings block.
	 */
	function display_site_settings()
	{
	  global $LANG;
	  
		// Initialise our return variable.
		$r = '';
		
		// Initialise a new instance of the SettingsDisplay class.
		$sd = new FieldFrame_SettingsDisplay();
		
		// Site-wide settings.
		$r .= $sd->block('sitewide_settings');
		$r .= $sd->info_row('settings_tip', FALSE);
		
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
		$options = array();
		for ($count = 0; $count <= 17; $count++) {$options[$count . ''] = $count . '';}

		$r .= $sd->row(
			array(
				$sd->label('default_zoom'),
				$sd->select('map_zoom', isset($this->site_settings['map_zoom']) ? $this->site_settings['map_zoom'] : $this->default_site_settings['map_zoom'], $options)
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
		$options = array();
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
	 * Modifies the field data before it is saved to the database.
	 *
	 * @param   string    $field_data       The field POST data.
	 * @param   array     $field_settings   The field settings.
	 * @param   int|bool  $entry_id         The entry's ID, or FALSE if the user clicked "Preview".
	 */
	function save_field($field_data = '', $field_settings = array(), $entry_id = FALSE)
	{
	  $ret = '';
	  
	  $map_data = explode(',', $field_data);
	  if (count($map_data) === 5)
	  {
	    $ret = array(
	      'map_lat' => $map_data[0],
	      'map_lng' => $map_data[1],
	      'map_zoom' => $map_data[2],
	      'pin_lat' => $map_data[3],
	      'pin_lng' => $map_data[4]
	      );
    }
    
    return $ret;
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
	 * Displays the field editor in the CP, or the map output in a template.
	 * @param 		string	 	$field_name 			The field name.
	 * @param 		array 		$field_data 			The previously-saved field data.
	 * @param 		array 		$field_settings		The field settings.
	 * @param			array 		$init 						Initialisation object specifying UI and usage options.
	 * @return 		string 		The HTML to output.
	 */	
	function _display_field($field_name, $field_data, $field_settings, $init)
	{
		global $REGX, $LANG;

		// Initialise the return variable.
		$r = '';

		// Explicitly set the language file.
		$LANG->fetch_language_file('sl_google_map');

		// Retrieve the API key from the site settings array.
		$api_key = isset($this->site_settings['api_key']) ? $this->site_settings['api_key'] : '';

		// Retrieve the map coordinates from the field data array.		
		if ( ! is_array($field_data) OR count($field_data) !== 5)
		{
		  $field_data = array(
		    'map_lat' => $field_settings['map_lat'],
		    'map_lng' => $field_settings['map_lng'],
		    'map_zoom' => $field_settings['map_zoom'],
		    'pin_lat' => $field_settings['map_lat'],
		    'pin_lng' => $field_settings['map_lng']
		    );
		}

		// Include our "global" scripts for the CP.
		if ( ! isset($this->global_script_included) && isset($init['control_panel']) && $init['control_panel'] === TRUE)
		{
			$this->global_script_included = TRUE;

			$this->insert('body', '<script type="text/javascript" src="http://maps.google.com/maps?file=api&amp;v=2.x&amp;key=' . $api_key . '&amp;sensor=false"></script>');
			$this->insert('body', '<script type="text/javascript" src="http://www.google.com/uds/api?file=uds.js&amp;v=1.0&amp;key=' . $api_key . '"></script>');

			// Include the custom SL Google Map script.
			$this->include_js('js/global_script.js');
		}

		// The map container.
		$container_id = (isset($init['id']) && $init['id'] != '') ? $init['id'] : $field_name . '_container';
		$container_class = (isset($init['class']) && $init['class'] != '') ? $init['class'] : 'sl-google-map';
		
		$r .= '<div id="' . $container_id . '" class="' . $container_class . '"';
		$r .= ( ! isset($init['control_panel']) OR $init['control_panel'] !== FALSE) ? ' style="height: ' . $field_settings['map_size'] . 'px; margin-bottom : 1em;">' : '>';
		$r .= (isset($init['fallback']) && $init['fallback'] != '') ? $init['fallback'] : '<p>' . $LANG->line('publish_no_javascript') . '</p>';
		$r .= '</div>';
		
		// Only add "editor" doo-hickeys if we're in the CP.
		if ( ! isset($init['control_panel']) OR $init['control_panel'] !== TRUE)
		{
		  $map_field = $address_input = $address_submit = null;
		}
		else
		{
		  // The field to store the map data.
			$r .= '<div class="hidden">';
			$r .= '<input type="hidden" name="' . $field_name . '" id="' . $field_name . '" value="' . implode(',', $field_data) .'" />';			
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
		
		// Extract the options into separate variables for use in the our JS initialisation code.
		// I'm sure there must be a more elegant way of doing this, but the following will do for now.
		$ui_zoom 					= isset($init['options']['ui_zoom']) 					? $init['options']['ui_zoom'] 				: FALSE;
		$ui_scale 				= isset($init['options']['ui_scale']) 				? $init['options']['ui_scale'] 				: FALSE;
		$ui_overview 			= isset($init['options']['ui_overview']) 			? $init['options']['ui_overview'] 		: FALSE;
		$ui_map_type      = isset($init['options']['ui_map_type'])      ? $init['options']['ui_map_type']     : FALSE;
		$map_drag 				= isset($init['options']['map_drag']) 				? $init['options']['map_drag'] 				: FALSE;
		$map_click_zoom 	= isset($init['options']['map_click_zoom']) 	? $init['options']['map_click_zoom'] 	: FALSE;
		$map_scroll_zoom	= isset($init['options']['map_scroll_zoom']) 	? $init['options']['map_scroll_zoom'] : FALSE;
		$pin_drag 				= isset($init['options']['pin_drag']) 				? $init['options']['pin_drag'] 				: FALSE;
		$background				= isset($init['options']['background'])				? $init['options']['background']			: NULL;
		$map_types        = isset($init['options']['map_types'])        ? $init['options']['map_types']       : NULL;

		// The JavaScript to initialise this field.
		$r .= <<<JAVASCRIPT
<script type="text/javascript">

	if (typeof(SJL) == 'undefined' || ( ! SJL instanceof Object)) SJL = new Object();
	if (typeof(SJL.google_maps) == 'undefined' || ( ! SJL.google_maps instanceof Array)) SJL.google_maps = new Array();

	SJL.google_maps.push({
		init : {
			api_key: '{$api_key}',
			map_field: '{$map_field}',
			map_container: '{$container_id}',
			map_lat: {$field_data['map_lat']},
			map_lng: {$field_data['map_lng']},
			map_zoom: {$field_data['map_zoom']},
			pin_lat: {$field_data['pin_lat']},
			pin_lng: {$field_data['pin_lng']},
			address_input: '{$address_input}',
			address_submit: '{$address_submit}'
		},
		options : {
			ui_zoom: '{$ui_zoom}',
			ui_scale: '{$ui_scale}',
			ui_overview: '{$ui_overview}',
			ui_map_type: '{$ui_map_type}',
			map_drag: '{$map_drag}',
			map_click_zoom: '{$map_click_zoom}',
			map_scroll_zoom: '{$map_scroll_zoom}',
			pin_drag: '{$pin_drag}',
			background: '{$background}',
			map_types: '{$map_types}'
		}
	});

</script>		
JAVASCRIPT;

		return $r;
	}
		
	
	/**
	 * Displays the field in the CP.
	 * @param 		string	 	$field_name 			The field name.
	 * @param 		array 		$field_data 			The previously-saved field data.
	 * @param 		arrray 		$field_settings		The field settings.
	 * @return 		string 		The HTML to output.
	 */
	function display_field($field_name, $field_data, $field_settings)
	{
		// Include some custom CP CSS.
		$this->include_css('css/cp.css');
		
		// Call the function that does all the donkey work.			
		$init = array(
			'control_panel'	=> TRUE,
			'options'				=> array(
				'ui_zoom'					=> TRUE,
				'ui_scale'				=> TRUE,
				'ui_overview'			=> TRUE,
				'ui_map_type'     => TRUE,
				'map_drag'				=> TRUE,
				'map_click_zoom'	=> TRUE,
				'map_scroll_zoom'	=> FALSE,
				'pin_drag'				=> TRUE,
				'map_types'       => Sl_google_map::NORMAL . '|' .
				  Sl_google_map::SATELLITE . '|' .
				  Sl_google_map::HYBRID . '|' .
				  Sl_google_map::PHYSICAL
				)
			);
			
		return $this->_display_field($field_name, $field_data, $field_settings, $init);
	}
	
	
	/**
	 * Displays the field in the exp:weblog:entries template.
	 * @param 	array 		$params 					Key / value pairs of the template tag parameters.
	 * @param 	string 		$tagdata 					Contents of the template between the opening and closing tags, if it's a tag pair.
	 * @param 	array 		$field_data				The previously-saved field data.
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
			
		/** 
		 * It doesn't appear to be possible to use weblog variables in the field
		 * tag ({entry_id} for example). As a compromise, we allow people to use
	   * square brackets around weblog variables in the ID and class parameters
	   * ([entry_id], for example).
		 */
		
		if ( ! isset($params['id'])) $params['id'] = '';
		if ( ! isset($params['class'])) $params['class'] = '';
		
		foreach ($FF->row AS $prop_id => $prop_data)
		{
		  if (is_string($prop_data))
		  {
		    $params['id'] = preg_replace(
  		    '/\[' . $prop_id . '\]/i',
  		    $prop_data,
  		    $params['id']
    			);
    			
    		$params['class'] = preg_replace(
  		    '/\[' . $prop_id . '\]/i',
  		    $prop_data,
  		    $params['class']
    			);
		  }
		}
		
		// Create the initialisation array.
		$init = array(
			'id'						=> $params['id'],
			'class'					=> $params['class'],
			'control_panel'	=> FALSE,
			'fallback'			=> $fallback,
			'options'				=> array(
				'background'	=> (isset($params['background']) ? $params['background'] : '')
				)
			);

		// Retrieve settings from the tag parameters.
		if (isset($params['controls']))
		{
			$options = explode('|', $params['controls']);
			$init['options']['ui_zoom'] 		  = (array_search('zoom', $options) !== FALSE);
			$init['options']['ui_scale'] 		  = (array_search('scale', $options) !== FALSE);
			$init['options']['ui_overview']   = (array_search('overview', $options) !== FALSE);
			$init['options']['ui_map_type']   = (array_search('map_type', $options) !== FALSE);
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
		
		if (isset($params['map_types']))
		{
		  $init['options']['map_types'] = $params['map_types'];   // Leave it as a pipe-delimited string.
		}
		
		// Do we need to center the map on the pin location?
		if ($pin_center)
		{
			$field_data['map_lat'] = $field_data['pin_lat'];
			$field_data['map_lng'] = $field_data['pin_lng'];
		}
		
		// This ID will be used if none has been specified in the init settings.
		$field_id = 'entry_id_' . $FF->row['entry_id'] . '_field_id_' . $FF->field_id;
		
		// Initialisation done, let's get swapping.
		$r = preg_replace(
			'/' . LD . 'map' . RD . '(.*?)' . LD . SLASH . 'map' . RD .'/s',
			$this->_display_field($field_id, $field_data, $field_settings, $init),
			$tagdata
			);
		
		// Replace all the SL Google Map single variables.
		$r = $TMPL->swap_var_single('map_lat', $field_data['map_lat'], $r);
		$r = $TMPL->swap_var_single('map_lng', $field_data['map_lng'], $r);
		$r = $TMPL->swap_var_single('map_zoom', $field_data['map_zoom'], $r);
		$r = $TMPL->swap_var_single('pin_lat', $field_data['pin_lat'], $r);
		$r = $TMPL->swap_var_single('pin_lng', $field_data['pin_lng'], $r);
		
		return $r;
	}
}

?>