<?php
/**
 * contact_form_admin
 *
 * @package Contact Form
 **/

class contact_form_admin {
	/**
	 * widget_control()
	 *
	 * @param array $widget_args Widget args
	 * @return void
	 **/

	function widget_control($widget_args) {
		global $wp_registered_widgets;
		static $updated = false;
		
		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP ); // extract number
		
		$options = contact_form::get_options();
		
		if ( !$updated && !empty($_POST['sidebar']) ) {
			$sidebar = (string) $_POST['sidebar'];

			$sidebars_widgets = wp_get_sidebars_widgets();
			
			if ( isset($sidebars_widgets[$sidebar]) )
				$this_sidebar =& $sidebars_widgets[$sidebar];
			else
				$this_sidebar = array();

			foreach ( $this_sidebar as $_widget_id ) {
				if ( array('contact_form', 'display_widget') == $wp_registered_widgets[$_widget_id]['callback']
					&& isset($wp_registered_widgets[$_widget_id]['params'][0]['number'])
				) {
					$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
					if ( !in_array( "contact_form-$widget_number", $_POST['widget-id'] ) ) // the widget has been removed.
						unset($options[$widget_number]);
				}
			}

			foreach ( (array) $_POST['widget-contact_form'] as $num => $opt ) {
				$title = trim(stripslashes($opt['title']));
				$email = trim(stripslashes($opt['email']));

				if ( !contact_form::is_email($email) ) {
					$email = get_option('admin_email');
				}
				
				foreach ( array_keys( contact_form_admin::get_captions() ) as $var ) {
					if ( !current_user_can('unfiltered_html') ) {
						$captions[$var] = stripslashes(wp_filter_post_kses($opt['captions'][$var]));
					} else {
						$captions[$var] = stripslashes($opt['captions'][$var]);
					}
				}

				$options[$num] = compact( 'title', 'email', 'captions' );
			}

			update_option('contact_form_widgets', $options);
			$updated = true;
		}

		if ( -1 == $number ) {
			$ops = contact_form::default_options();
			$number = '%i%';
		} else {
			$ops = $options[$number];
		}
		
		extract($ops);
		
		echo '<h3>' . __('Configuration', 'contact-form') . '</h3>' . "\n";
		
		echo '<table style="width: 460px;">' . "\n";
		
		echo '<tr valign="top">' . "\n"
			. '<th scope="row" style="width: 100px;">'
			. __('Title', 'contact-form')
			. '</th>' . "\n"
			. '<td>'
			.'<input type="text" size="20" class="widefat"'
				. ' name="widget-contact_form[' . $number . '][title]"'
				. ' value="' . attribute_escape($title) . '"'
				. ' />'
			. '</td>' . "\n"
			. '</tr>' . "\n";
		
		echo '<tr valign="top">' . "\n"
			. '<th scope="row" style="width: 100px;">'
			. __('Your Email', 'contact-form')
			. '</th>' . "\n"
			. '<td>'
			.'<input type="text" size="20" class="widefat"'
				. ' name="widget-contact_form[' . $number . '][email]"'
				. ' value="' . attribute_escape($email) . '"'
				. ' />'
			. '</td>' . "\n"
			. '</tr>' . "\n";
		
		echo '</table>' . "\n";
		
		echo '<h3>' . __('Captions', 'contact-form') . '</h3>' . "\n";
		
		echo '<table style="width: 460px;">' . "\n";
		
		foreach ( contact_form_admin::get_captions() as $var => $caption ) {
			switch ( $var ) {
			case 'success_message':
				echo '<tr valign="top">' . "\n"
					. '<th scope="row" style="width: 100px;">'
					. $caption
					. '</th>' . "\n"
					. '<td>'
					.'<textarea cols="20" rows="6" class="widefat"'
						. ' name="widget-contact_form[' . $number . '][captions]['. $var . ']"'
						. ' >'
					. format_to_edit($captions[$var])
					. '</textarea>'
					. '</td>' . "\n"
					. '</tr>' . "\n";
				break;
			default:
				echo '<tr valign="top">' . "\n"
					. '<th scope="row">'
					. $caption
					. '</th>' . "\n"
					. '<td>'
					.'<input type="text" size="20" class="widefat"'
						. ' name="widget-contact_form[' . $number . '][captions]['. $var . ']"'
						. ' value="' . attribute_escape($captions[$var]) . '"'
						. ' />'
					. '</td>' . "\n"
					. '</tr>' . "\n";
				break;
			}
		}
		
		echo '</table>' . "\n";
	} # widget_control()
	
	
	/**
	 * get_captions()
	 *
	 * @return void
	 **/

	function get_captions() {
		return array(
			'name' => __('Name', 'contact-form'),
			'email' => __('Email', 'contact-form'),
			'phone' => __('Phone Number', 'contact-form'),
			'subject' => __('Subject', 'contact-form'),
			'message' => __('Message', 'contact-form'),
			'cc' => __('Receive a copy', 'contact-form'),
			'send' => __('Send Email', 'contact-form'),
			'success_message' => __('Thank you', 'contact-form'),
			'invalid_email' => __('Invalid Email', 'contact-form'),
			'required_field' => __('Missing Field', 'contact-form'),
			'spam_caught' => __('Spam Caught', 'contact-form'),
			);
	} # get_captions()
} # contact_form_admin
?>