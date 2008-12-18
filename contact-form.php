<?php
/*
Plugin Name: Contact Form
Plugin URI: http://www.semiologic.com/software/publishing/contact-form/
Description: Contact form widgets for WordPress, with built-in spam protection and akismet integration
Author: Denis de Bernardy
Version: 1.0.2
Author URI: http://www.getsemiologic.com
Update Service: http://version.semiologic.com/plugins
Update Tag: contact_form
Update Package: http://www.semiologic.com/media/publishing/contact-form/contact-form/contact-form.zip
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


class contact_form
{
	#
	# init()
	#
	
	function init()
	{
		if ( !is_admin() )
		{
			add_action('init', array('contact_form', 'send_message'));
			
			add_action('wp_head', array('contact_form', 'css'));
			
			add_filter('contact_form_validate', array('contact_form', 'akismet'));
		}
		
		add_action('widgets_init', array('contact_form', 'widgetize'));
	} # init()
	
	
	#
	# widgetize()
	#
	
	function widgetize()
	{
		$options = contact_form::get_options();
		
		$widget_options = array('classname' => 'contact_form', 'description' => __( "A contact form with spam protection, including Akismet integration.") );
		$control_options = array('width' => 500, 'id_base' => 'contact_form');
		
		$id = false;

		# registered widgets
		foreach ( array_keys($options) as $o )
		{
			if ( !is_numeric($o) ) continue;
			$id = "contact_form-$o";
			wp_register_sidebar_widget($id, __('Contact Form'), array('contact_form', 'display_widget'), $widget_options, array( 'number' => $o ));
			wp_register_widget_control($id, __('Contact Form'), array('contact_form_admin', 'widget_control'), $control_options, array( 'number' => $o ) );
		}
		
		# default widget if none were registered
		if ( !$id )
		{
			$id = "contact_form-1";
			wp_register_sidebar_widget($id, __('Contact Form'), array('contact_form', 'display_widget'), $widget_options, array( 'number' => -1 ));
			wp_register_widget_control($id, __('Contact Form'), array('contact_form_admin', 'widget_control'), $control_options, array( 'number' => -1 ) );
		}
	} # widgetize()
	
	
	#
	# display_widget()
	#
	
	function display_widget($args, $widget_args = 1)
	{
		$options = contact_form::get_options();
		
		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP );
		
		$options = $options[$number];
		
		if ( is_admin() )
		{
			echo $args['before_widget'] . "\n"
				. $args['before_title'] . $options['email'] . $args['after_title']
				. $args['after_widget'] . "\n";
			
			return;
		}
		
		if ( !$options['email'] )
		{
			$form = '<div style="border: solid 1px red; background: #ffeeee; color: #cc0000; font-weight: bold; padding: 10px;">'
			. 'Please configure the contact form under Design / Widgets'
			. '</div>' . "\n";
		}
		elseif ( $_POST['cf_number'] == $number
			&& $GLOBALS['cf_status'][$_POST['cf_number']] == 'success'
			)
		{
			$form = '<div class="cf_success">'
				. wpautop($options['captions']['success_message'])
				. '</div>' . "\n";
		}
		else
		{
			$form = '<form method="post" action="">' . "\n"
				. '<input type="hidden" name="cf_number" value="' . intval($number) . '">' . "\n";

			$errorCode = $GLOBALS['cf_status'][$_POST['cf_number']];
			
			if ( $_POST['cf_number'] == $number
				&& $errorCode )
			{
				$form .= '<div class="cf_error">'
					. $options['captions'][$errorCode]
					. '</div>' . "\n";
			}
			
			foreach ( array(
					'name',
					'email',
					'phone',
					'subject',
					'message',
					'cc',
					'send'
					) as $var )
			{
				switch ( $var )
				{
				case 'name':
				case 'email':
				case 'phone':
				case 'subject':
					$form .= '<div class="cf_' . $var . '">' . "\n"
						. '<label>'
						. $options['captions'][$var] . '<br />' . "\n"
						. '<input type="text" class="cf_field"'
							. ' name="cf_' . $var . '"'
							. ' value="' . htmlspecialchars(stripslashes($_POST['cf_' . $var])) . '"'
							. ' />'
						. '</label>'
						. '</div>' . "\n";
					break;
				case 'message':
					$form .= '<div class="cf_' . $var . '">' . "\n"
						. '<label>'
						. $options['captions'][$var] . '<br />' . "\n"
						. '<textarea class="cf_field"'
							. ' name="cf_' . $var . '"'
							. ' >'
						. htmlspecialchars(stripslashes($_POST['cf_' . $var]))
						. '</textarea>'
						. '</label>'
						. '</div>' . "\n";
					break;
				case 'cc':
					$form .= '<div class="cf_' . $var . '">' . "\n"
						. '<label>'
						. '<input type="checkbox" class="cf_checkbox"'
							. ' name="cf_' . $var . '"'
							. ( isset($_POST['cf_' . $var])
								? ' checked="checked"'
								: ''
								)
							. ' />'
						. $options['captions'][$var] . '<br />' . "\n"
						. '</label>'
						.'</div>' . "\n";
					break;
				case 'send':
					$form .= '<div class="cf_' . $var . '">' . "\n"
						. '<label>'
						. '<input type="submit" class="submit"'
							. ' value="' . htmlspecialchars(stripslashes($options['captions'][$var])) . '"'
							. ' />'
						. '</label>'
						.'</div>' . "\n";
					break;
				}
			}

			$form .= '</form>' . "\n";
		}
				
		echo $args['before_widget'] . "\n"
			. ( $options['title']
				? ( $args['before_title'] . $options['title'] . $args['after_title'] . "\n" )
				: ''
				)
			. '<div style="clear: both;"></div>' . "\n"
			. $form
			. $args['after_widget'] . "\n";
	} # display_widget()


	#
	# send_message()
	#

	function send_message()
	{
		if ( !$_POST['cf_number'] )
		{
			# toggle cf
			setcookie(
				'cf_' . COOKIEHASH,
				1,
				time() + 3600,
				COOKIEPATH,
				COOKIE_DOMAIN
				);
			
			$_POST['cf_number'] = 0;

			return;
		}
		
		if ( contact_form::validate() )
		{
			$options = contact_form::get_options();
			
			$_POST['cf_number'] = intval($_POST['cf_number']);

			$number = $_POST['cf_number'];
			
			$options = $options[$number];
			
			if ( !( $to = $options['email'] ) )
			{
				return;
			}
			
			foreach ( array('name', 'email', 'phone', 'subject', 'message') as $var )
			{
				$$var = strip_tags(stripslashes($_POST['cf_' . $var]));
			}
			
			$headers = 'From: "' . $name . '" <' . $email . '>';
			
			$message = 'Site: ' . get_option('blogname') . "\n"
				. 'From: ' . $name . "\n"
				. 'Email: ' . $email . "\n"
				. 'Phone: '. $phone . "\n"
				. "\n"
				. $message;
			
			wp_mail($to, $subject, $message, $headers);
			
			if ( $_POST['cf_cc'] )
			{
				wp_mail($email, $subject, $message, $headers);
			}
			
			$GLOBALS['cf_status'][$number] = 'success';
		}
	} # send_message()
	
	
	#
	# validate()
	#
	
	function validate()
	{
		$status = 'spam_caught';
		
		$ok = strpos($_SERVER['HTTP_REFERER'], $_SERVER['REQUEST_URI']) !== false
			&& $_COOKIE['cf_' . COOKIEHASH];
		
		# sanitize $_POST variables
		foreach ( array('name', 'email', 'phone', 'subject', 'message') as $var )
		{
			$_POST['cf_' . $var] = trim(strip_tags($_POST['cf_' . $var]));
		}

		if ( $ok )
		{
			foreach ( array('name', 'email', 'subject', 'message') as $var )
			{
				$$var = $_POST['cf_' . $var];

				switch ( $var )
				{
				case 'email':
					if ( !contact_form::is_email($$var) )
					{
						$ok = false;
						$status = 'invalid_email';
					}
				case 'name':
					if ( urldecode($$var) != $$var )
					{
						$ok = false;
					}
					foreach ( array("\r", "\n", ":", "%") as $kvetch )
					{
						if ( strpos($$var, $kvetch) !== false )
						{
							$ok = false;
						}
					}
				default:
					if ( $$var === '' )
					{
						$ok = false;
						$status = 'required_field';
					}
				}
				
				if ( !$ok )
				{
					break;
				}
			} # foreach
		}
		
		if ( $ok )
		{
			# create a fake comment
			$comment['comment_post_ID'] = 0;
			$comment['comment_author'] = stripslashes($_POST['cf_name']);
			$comment['comment_author_email'] = stripslashes($_POST['cf_email']);
			$comment['comment_author_url'] = '';
			$comment['comment_content'] = stripslashes($_POST['cf_message']);
			$comment['comment_type'] = '';
			$comment['user_ID'] = '';

			$args = array();
			$args['ok'] =& $ok;
			$args['comment'] =& $comment;
			
			# comment spam filters can now filter this the usual way with an appropriate method
			$ok = apply_filters('contact_form_validate', $args);
		}
				
		if ( !$ok )
		{
			$GLOBALS['cf_status'][$_POST['cf_number']] = $status;
		}
		
		return $ok;
	} # validate()
	
	
	#
	# akismet()
	#
	
	function akismet($args)
	{
		if ( !$args['ok'])
		{
			return false;
		}
		else
		{
			$comment =& $args['comment'];
		}
		# pass posted message through akismet
		if ( function_exists('akismet_auto_check_comment') && get_option('wordpress_api_key') )
		{
			global $akismet_api_host, $akismet_api_port;

			$comment['user_ip'] = preg_replace( '/[^0-9., ]/', '', $_SERVER['REMOTE_ADDR'] );
			$comment['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
			$comment['referrer'] = $_SERVER['HTTP_REFERER'];
			$comment['blog'] = get_option('home');

			$ignore = array( 'HTTP_COOKIE' );

			foreach ( $_SERVER as $key => $value )
				if ( !in_array( $key, $ignore ) )
					$comment["$key"] = $value;

			$query_string = '';
			foreach ( $comment as $key => $data )
				$query_string .= $key . '=' . urlencode( stripslashes($data) ) . '&';

			$response = akismet_http_post($query_string, $akismet_api_host, '/1.1/comment-check', $akismet_api_port);

			if ( 'true' == $response[1] )
			{
				return false;
			}
		}
		
		return true;
	} # akismet()
	
	
	#
	# get_options()
	#
	
	function get_options()
	{
		if ( ( $o = get_option('contact_form_widgets') ) === false )
		{
			$o = array();
			
			update_option('contact_form_widgets', $o);
		}
		
		return $o;
	} # get_options()
	
	
	#
	# new_widget()
	#
	
	function new_widget()
	{
		$o = contact_form::get_options();
		$k = time();
		do $k++; while ( isset($o[$k]) );
		$o[$k] = contact_form::default_options();
		
		update_option('contact_form_widgets', $o);
		
		return 'contact_form-' . $k;
	} # new_widget()
	
	
	#
	# default_options()
	#
	
	function default_options()
	{
		return array(
			'title' => 'Contact Us',
			'email' => get_option('admin_email'),
			'captions' => array(
				'name' => 'Your Name',
				'email' => 'Your Email',
				'phone' => 'Your Phone Number (optional)',
				'subject' => 'Subject',
				'message' => 'Message',
				'cc' => 'Receive a carbon copy of this email',
				'send' => 'Send Email',
				'success_message' => 'Thank you for your email.',
				'invalid_email' => 'Please enter a valid email',
				'required_field' => 'Please fill in all of the required fields',
				'spam_caught' => 'Your message has been filtered out as spam',
				)
			);
	} # default_options()
	
	
	#
	# css()
	#
	
	function css()
	{
		$site_url = trailingslashit(get_option('siteurl'));

		$path = 'wp-content/'
			. 'plugins/'
			. 'contact-form/';
			
		$file = 'contact-form.css';

		echo '<link'
			. ' rel="stylesheet" type="text/css"'
				. ' href="' . $site_url . $path . $file . '?ver=1.0"'
				. ' />' . "\n";
	} # css()
	
	
	#
	# is_email()
	#
	
	function is_email($str)
	{
		return preg_match("/
			^
			[a-z0-9_-]+(?:\.[a-z0-9_-]+)*		# user
			@									# @
			(?:[a-z0-9_-]+\.)+[a-z]{2,}			# domain
			$
			/ix",
			$str
			);
	} # is_email()
} # contact_form

contact_form::init();


if ( is_admin() )
{
	include dirname(__FILE__) . '/contact-form-admin.php';
}
?>