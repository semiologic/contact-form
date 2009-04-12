<?php
/*
Plugin Name: Contact Form
Plugin URI: http://www.semiologic.com/software/contact-form/
Description: Contact form widgets for WordPress, with built-in spam protection in addition to WP Hashcash and akismet integration
Version: 1.1 RC
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


/**
 * contact_form
 *
 * @package Contact Form
 **/

if ( !is_admin() ) {
	add_action('init', array('contact_form', 'send_message'));
	
	add_action('wp_print_styles', array('contact_form', 'add_css'));
	add_action('wp_head', array('contact_form', 'hashcash'), 20);
	
	add_filter('contact_form_validate', array('contact_form', 'akismet'));
}

add_action('widgets_init', array('contact_form', 'widgetize'));

class contact_form {
	/**
	 * widgetize()
	 *
	 * @return void
	 **/
	
	function widgetize() {
		$options = contact_form::get_options();
		
		$widget_options = array('classname' => 'contact_form', 'description' => __( "A contact form with spam protection, including Akismet integration.") );
		$control_options = array('width' => 500, 'id_base' => 'contact_form');
		
		$id = false;

		# registered widgets
		foreach ( array_keys($options) as $o ) {
			if ( !is_numeric($o) ) continue;
			$id = "contact_form-$o";
			wp_register_sidebar_widget($id, __('Contact Form'), array('contact_form', 'display_widget'), $widget_options, array( 'number' => $o ));
			wp_register_widget_control($id, __('Contact Form'), array('contact_form_admin', 'widget_control'), $control_options, array( 'number' => $o ) );
		}
		
		# default widget if none were registered
		if ( !$id ) {
			$id = "contact_form-1";
			wp_register_sidebar_widget($id, __('Contact Form'), array('contact_form', 'display_widget'), $widget_options, array( 'number' => -1 ));
			wp_register_widget_control($id, __('Contact Form'), array('contact_form_admin', 'widget_control'), $control_options, array( 'number' => -1 ) );
		}
	} # widgetize()
	
	
	/**
	 * display_widget()
	 *
	 * @param array $args Widget args
	 * @param int $widget_args Widget number
	 * @return void
	 **/

	function display_widget($args, $widget_args = 1) {
		$options = contact_form::get_options();
		
		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP );
		
		$options = $options[$number];
		
		if ( is_admin() ) {
			echo $args['before_widget'] . "\n"
				. $args['before_title'] . $options['email'] . $args['after_title']
				. $args['after_widget'] . "\n";
			
			return;
		}
		
		if ( !$options['email'] ) {
			$form = '<div style="border: solid 1px red; background: #ffeeee; color: #cc0000; font-weight: bold; padding: 10px;">'
			. __('Please configure the contact form under Design / Widgets', 'contact-form')
			. '</div>' . "\n";
		} elseif ( intval($_POST['cf_number']) == $number
			&& $GLOBALS['cf_status'][intval($_POST['cf_number'])] == 'success'
			) {
			$form = '<div class="cf_success">'
				. wpautop($options['captions']['success_message'])
				. '</div>' . "\n";
		} else {
			$form = '<form method="post" action="">' . "\n"
				. '<input type="hidden" name="cf_number" value="' . intval($number) . '">' . "\n";

			if ( intval($_POST['cf_number']) == $number ) {
				$errorCode = $GLOBALS['cf_status'][intval($_POST['cf_number'])];

				if ( $errorCode ) {
					$form .= '<div class="cf_error">'
						. $options['captions'][$errorCode]
						. '</div>' . "\n";
				}
			}
			
			foreach ( array(
					'name',
					'email',
					'phone',
					'subject',
					'message',
					'cc',
					'send'
				) as $var ) {
				switch ( $var ) {
				case 'phone':
					if ( !$options['captions'][$var] )
						break;
				case 'name':
				case 'email':
				case 'subject':
					$form .= '<div class="cf_field cf_' . $var . '">' . "\n"
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
					$form .= '<div class="cf_field cf_' . $var . '">' . "\n"
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
					$form .= '<div class="cf_field cf_' . $var . '">' . "\n"
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
					$form .= '<div class="cf_field cf_' . $var . '">' . "\n"
						. '<label>'
						. '<input type="submit" class="submit"'
							. ' value="' . htmlspecialchars(stripslashes($options['captions'][$var])) . '"'
							. ' />'
						. '</label>'
						.'</div>' . "\n";
					break;
				}
			}
			
			if ( function_exists('wphc_option') ) {
				$form .= '<input type="hidden" name="wphc_value" value="" />' . "\n";
			}

			$form .= '</form>' . "\n";
		}
		#dump(htmlspecialchars($args['before_title'] . $options['title'] . $args['after_title']));
		echo $args['before_widget'] . "\n"
			. ( $options['title']
				? ( $args['before_title'] . $options['title'] . $args['after_title'] . "\n" )
				: ''
				)
			. '<div style="clear: both;"></div>' . "\n"
			. $form
			. $args['after_widget'] . "\n";
	} # display_widget()
	
	
	/**
	 * send_message()
	 *
	 * @return void
	 **/

	function send_message() {
		if ( !$_POST['cf_number'] ) {
			# toggle cf
			setcookie(
				'cf_' . COOKIEHASH,
				1,
				time() + 3600,
				COOKIEPATH,
				COOKIE_DOMAIN
				);

			return;
		}
		
		if ( contact_form::validate() ) {
			$options = contact_form::get_options();
			
			$_POST['cf_number'] = intval($_POST['cf_number']);

			$number = intval($_POST['cf_number']);
			
			$options = $options[$number];
			
			if ( !( $to = $options['email'] ) ) return;
			
			foreach ( array('name', 'email', 'phone', 'subject', 'message') as $var ) {
				$$var = strip_tags(stripslashes($_POST['cf_' . $var]));
			}
			
			$headers = __('From:', 'contact-form') . ' "' . $name . '" <' . $email . '>';
			
			$message = __('Site:', 'contact-form') . ' ' . get_option('blogname') . "\n"
				. __('From:', 'contact-form') . ' ' . $name . "\n"
				. __('Email:', 'contact-form') . ' ' . $email . "\n"
				. ( $phone
					? ( __('Phone:', 'contact-form') . ' '. $phone . "\n" )
					: ''
					)
				. "\n"
				. $message;
			
			wp_mail($to, $subject, $message, $headers);
			
			if ( $_POST['cf_cc'] )
				wp_mail($email, $subject, $message, $headers);
			
			$GLOBALS['cf_status'][$number] = 'success';
		}
	} # send_message()
	
	
	/**
	 * validate()
	 *
	 * @return void
	 **/

	function validate() {
		$status = 'spam_caught';
		
		$ok = strpos($_SERVER['HTTP_REFERER'], $_SERVER['REQUEST_URI']) !== false
			&& $_COOKIE['cf_' . COOKIEHASH];
		
		# sanitize $_POST variables
		foreach ( array('name', 'email', 'phone', 'subject', 'message') as $var ) {
			$_POST['cf_' . $var] = trim(strip_tags($_POST['cf_' . $var]));
		}

		if ( $ok ) {
			foreach ( array('name', 'email', 'subject', 'message') as $var ) {
				$$var = $_POST['cf_' . $var];

				switch ( $var ) {
				case 'email':
					if ( !contact_form::is_email($$var) ) {
						$ok = false;
						$status = 'invalid_email';
					}
				case 'name':
					if ( urldecode($$var) != $$var ) {
						$ok = false;
					}
					foreach ( array("\r", "\n", ":", "%") as $kvetch ) {
						if ( strpos($$var, $kvetch) !== false ) {
							$ok = false;
						}
					}
				default:
					if ( $$var === '' ) {
						$ok = false;
						$status = 'required_field';
					}
				}
				
				if ( !$ok ) {
					break;
				}
			} # foreach
		}
		
		# filter through hashcash
		if ( $ok ) {
			$wphc_options = wphc_option();
			$ok = in_array($_POST["wphc_value"], $wphc_options['key']);
		}
		
		# filter throgh akismet
		if ( $ok ) {
			# create a fake comment
			$comment['comment_post_ID'] = intval($_POST['cf_number']);
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
			$args = apply_filters('contact_form_validate', $args);
		}
		
		if ( !$ok ) {
			$GLOBALS['cf_status'][intval($_POST['cf_number'])] = $status;
		}
		
		return $ok;
	} # validate()
	
	
	/**
	 * akismet()
	 *
	 * @param array $args Status and fake WP comment
	 * @return void
	 **/

	function akismet($args) {
		if ( !$args['ok']) {
			return $args;
		} else {
			$comment =& $args['comment'];
		}
		
		# pass posted message through akismet
		if ( function_exists('akismet_auto_check_comment') && get_option('wordpress_api_key') ) {
			global $akismet_api_host, $akismet_api_port;

			$comment['user_ip'] = preg_replace( '/[^0-9., ]/', '', $_SERVER['REMOTE_ADDR'] );
			$comment['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
			$comment['referrer'] = $_SERVER['HTTP_REFERER'];
			$comment['blog'] = user_trailingslashit(get_option('home'));

			$ignore = array( 'HTTP_COOKIE' );

			foreach ( $_SERVER as $key => $value )
				if ( !in_array( $key, $ignore ) )
					$comment["$key"] = $value;

			$query_string = '';
			foreach ( $comment as $key => $data )
				$query_string .= $key . '=' . urlencode( stripslashes($data) ) . '&';

			$response = akismet_http_post($query_string, $akismet_api_host, '/1.1/comment-check', $akismet_api_port);

			if ( 'true' == $response[1] ) {
				$args['ok'] = false;
			}
		}
		
		return $args;
	} # akismet()
	
	
	/**
	 * hashcash()
	 *
	 * @return void
	 **/

	function hashcash() {
		if ( !function_exists('wphc_option') )
			return;
		
		echo "<script type=\"text/javascript\"><!--\n";
		
		if ( !is_singular() ) {
			echo <<<EOS
function addLoadEvent(func) {
  var oldonload = window.onload;
  if (typeof window.onload != 'function') {
    window.onload = func;
  } else {
    window.onload = function() {
      if (oldonload) {
        oldonload();
      }
      func();
    }
  }
}
EOS;
			echo  wphc_getjs() . "\n";
		}
		echo <<<EOS
addLoadEvent(function() {
	var value = wphc();
	for ( var i = 0; i < document.getElementsByName('wphc_value').length; i++ ) {
		document.getElementsByName('wphc_value')[i].value=value;
	}
});
EOS;
		echo "//--></script>\n";
	} # hashcash()
	
	
	/**
	 * is_email()
	 *
	 * @param string $str An email
	 * @return void
	 **/

	function is_email($str) {
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
	
	
	/**
	 * get_options()
	 *
	 * @return void
	 **/

	function get_options() {
		static $o;
		
		if ( isset($o) && !is_admin() )
			return $o;
		
		$o = get_option('contact_form_widgets');
		
		if ( $o === false ) {
			$o = array();
			update_option('contact_form_widgets', $o);
		}
		
		return $o;
	} # get_options()
	
	
	/**
	 * default_options()
	 *
	 * @return void
	 **/

	function default_options() {
		return array(
			'title' => __('Contact Us', 'contact-form'),
			'email' => get_option('admin_email'),
			'captions' => array(
				'name' => __('Your Name', 'contact-form'),
				'email' => __('Your Email', 'contact-form'),
				'phone' => __('Your Phone Number (optional)', 'contact-form'),
				'subject' => __('Subject', 'contact-form'),
				'message' => __('Message', 'contact-form'),
				'cc' => __('Receive a carbon copy of this email', 'contact-form'),
				'send' => __('Send Email'),
				'success_message' => __('Thank you for your email.', 'contact-form'),
				'invalid_email' => __('Please enter a valid email', 'contact-form'),
				'required_field' => __('Please fill in all of the required fields', 'contact-form'),
				'spam_caught' => __('Sorry... Your message has been caught as spam and was not sent', 'contact-form'),
				)
			);
	} # default_options()
	
	
	/**
	 * new_widget()
	 *
	 * @return void
	 **/
	
	function new_widget() {
		$o = contact_form::get_options();
		$k = time();
		while ( isset($o[$k]) ) $k++;
		$o[$k] = contact_form::default_options();
		
		update_option('contact_form_widgets', $o);
		
		return 'contact_form-' . $k;
	} # new_widget()
	
	
	/**
	 * add_css()
	 *
	 * @return void
	 **/
	
	function add_css() {
		$folder = plugin_dir_url(__FILE__);
		$css = $folder . 'css/contact-form.css';
		
		wp_enqueue_style('contact_form', $css, null, '1.1');
	} # add_css()
} # contact_form


/**
 * contact_form_admin()
 *
 * @return void
 **/

function contact_form_admin() {
	include dirname(__FILE__) . '/contact-form-admin.php';
} # contact_form_admin()

add_action('load-widgets.php', 'contact_form_admin');
?>