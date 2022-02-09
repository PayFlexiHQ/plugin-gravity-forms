<?php

defined('ABSPATH') || die();

// Include the payment add-on framework.
GFForms::include_payment_addon_framework();

/**
 * Class GFPayflexi
 *
 * Primary class to manage the Payflexi add-on.
 *
 * @since 1.0
 *
 * @uses GFPaymentAddOn
 */
class GFPayflexi extends GFPaymentAddOn
{
	/**
	 * Contains an instance of this class, if available.
	 *
	 * @since  1.0
	 * @access private
	 *
	 * @used-by GFPayflexi::get_instance()
	 *
	 * @var object $_instance If available, contains an instance of this class.
	 */
	private static $_instance = null;

	/**
	 * Defines the version of the Payflexi Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @used-by GFPayflexi::scripts()
	 *
	 * @var string $_version Contains the version, defined from payflexi.php
	 */
	protected $_version = GF_PAYFLEXI_VERSION;

	/**
	 * Defines the minimum Gravity Forms version required.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_min_gravityforms_version The minimum version required.
	 */
	protected $_min_gravityforms_version = '2.0';

	/**
	 * Defines the plugin slug.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_slug The slug used for this plugin.
	 */
	protected $_slug = 'gravityformspayflexi';

	/**
	 * Defines the main plugin file.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_path The path to the main plugin file, relative to the plugins folder.
	 */
	protected $_path = 'gravityformspayflexi/payflexi.php';

	/**
	 * Defines the full path to this class file.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_full_path The full path.
	 */
	protected $_full_path = __FILE__;

	/**
	 * Defines the URL where this Add-On can be found.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_url The URL of the Add-On.
	 */
	protected $_url = 'https://developers.payflexi.co/';

	/**
	 * Defines the title of this Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_title The title of the Add-On.
	 */
	protected $_title = 'PayFlexi Add-On for Gravity Forms';

	/**
	 * Defines the short title of the Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_short_title The short title.
	 */
	protected $_short_title = 'PayFlexi';

	/**
	 * Defines if user will not be able to create feeds for a form until a credit card field has been added.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var bool $_requires_credit_card false.
	 */
	protected $_requires_credit_card = false;

	/**
	 * Defines if callbacks/webhooks/IPN will be enabled and the appropriate database table will be created.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var bool $_supports_callbacks true
	 */
	protected $_supports_callbacks = true;

	/**
	 * PayFlexi requires monetary amounts to be formatted as the smallest unit for the currency being used e.g. kobo, pesewas, cent
	 *
	 * @since  1.10.1
	 * @access protected
	 *
	 * @var bool $_requires_smallest_unit true
	 */
	protected $_requires_smallest_unit = false;

	/**
	 * Defines the capability needed to access the Add-On settings page.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_settings_page The capability needed to access the Add-On settings page.
	 */
	protected $_capabilities_settings_page = 'gravityforms_payflexi';

	/**
	 * Defines the capability needed to access the Add-On form settings page.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_form_settings The capability needed to access the Add-On form settings page.
	 */
	protected $_capabilities_form_settings = 'gravityforms_payflexi';

	/**
	 * Defines the capability needed to uninstall the Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_uninstall The capability needed to uninstall the Add-On.
	 */
	protected $_capabilities_uninstall = 'gravityforms_payflexi_uninstall';

	/**
	 * Defines the capabilities needed for the PayFlexi Add-On
	 *
	 * @since  1.0
	 * @access protected
	 * @var    array $_capabilities The capabilities needed for the Add-On
	 */
	protected $_capabilities = array('gravityforms_payflexi', 'gravityforms_payflexi_uninstall');

	/**
	 * Holds the custom meta key currently being processed. Enables the key to be passed to the gform_payflexi_field_value filter.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @used-by GFPayflexi::maybe_override_field_value()
	 *
	 * @var string $_current_meta_key The meta key currently being processed.
	 */
	protected $_current_meta_key = '';

	/**
	 * Enable rate limits to log card errors etc.
	 *
	 * @since 1.0
	 *
	 * @var bool
	 */
	protected $_enable_rate_limits = true;

	/**
	 * PayFlexi API Wrapper
	 *
	 * @var GFPayflexiApi
	 */
	protected $payflexi_api;

	/**
	 * Get an instance of this class.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFPayflexi
	 * @uses GFPayflexi::$_instance
	 *
	 * @return object GFPayflexi
	 */
	public static function get_instance()
	{
		if (null === self::$_instance) {
			self::$_instance = new GFPayflexi();
		}

		return self::$_instance;
	}

	/**
	 * Load the Payflexi credit card field.
	 *
	 * @since 1.0
	 */
	public function pre_init()
	{
		// For form confirmation redirection, this must be called in `wp`,
		// or confirmation redirect to a page would throw PHP fatal error.
		// Run before calling parent method. We don't want to run anything else before displaying thank you page.
		add_action('wp', array($this, 'maybe_thankyou_page'), 5);

		parent::pre_init();
	}
	// # PLUGIN SETTINGS -----------------------------------------------------------------------------------------------

	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @access public
	 *
	 * @used-by GFAddOn::maybe_save_plugin_settings()
	 * @used-by GFAddOn::plugin_settings_page()
	 * @uses  GFPayflexi::api_settings_fields()
	 * @uses  GFPayflexi::get_webhooks_section_description()
	 *
	 * @return array Plugin settings fields to add.
	 */
	public function plugin_settings_fields()
	{
		$fields = array(
			array(
				'title'  => esc_html__('Configuration', 'gravityformspayflexi'),
				'fields' => $this->api_settings_fields(),
			),
		);

		return $fields;
	}

	/**
	 * Define the settings which appear in the PayFlexi API section.
	 *
	 * @access public
	 *
	 * @used-by GFPayFlexi::plugin_settings_fields()
	 *
	 * @return array The API settings fields.
	 */
	public function api_settings_fields()
	{
		$api_mode = '';

		if ($this->is_detail_page() && empty($api_mode)) {
			$api_mode = $this->get_plugin_setting('api_mode');
		}

		$fields = array(
			array(
				'name'          => 'api_mode',
				'label'         => esc_html__('Mode', 'gravityformspayflexi'),
				'type'          => 'radio',
				'default_value' => $api_mode,
				'choices'       => array(
					array(
						'label' => esc_html__('Live', 'gravityformspayflexi'),
						'value' => 'live',
					),
					array(
						'label' => esc_html__('Test', 'gravityformspayflexi'),
						'value' => 'test',
					),
				),
				'horizontal'    => true,
			)
		);

		$credentials_fields = array(
			array(
				'name'  => 'enabled_payment_gateway',
				'label' => esc_html__('Enabled Payment Gateway', 'gravityformspayflexi'),
				'type'  => 'text',
				'class'    => 'medium',
				'description' => 'Add the corresponding gateway you connected on PayFlexi. Enter "stripe" if you connected your Stripe account on PayFlexi',
			),
			array(
				'name'  => 'test_public_key',
				'label' => esc_html__('Test Public Key', 'gravityformspayflexi'),
				'type'  => 'text',
				'class'    => 'medium',
			),
			array(
				'name'  => 'test_secret_key',
				'label' => esc_html__('Test Secret Key', 'gravityformspayflexi'),
				'type'  => 'text',
				'class'    => 'medium',
			),
			array(
				'name'  => 'live_public_key',
				'label' => esc_html__('Live Public Key', 'gravityformspayflexi'),
				'type'  => 'text',
				'class'    => 'medium',
			),
			array(
				'name'  => 'live_secret_key',
				'label' => esc_html__('Live Secret Key', 'gravityformspayflexi'),
				'type'  => 'text',
				'class'    => 'medium',
			),
		);

		$fields = array_merge($fields, $credentials_fields);

		$webhook_fields = array(
			array(
				'name'        => 'webhooks_enabled',
				'label'       => esc_html__('Webhooks Enabled?', 'gravityformspayflexi'),
				'type'        => 'checkbox',
				'horizontal'  => true,
				'required'    => ($this->get_current_feed_id() || !isset($_GET['fid'])) ? true : false,
				'description' => $this->get_webhooks_section_description(),
				'dependency'  => $this->is_detail_page() ? array($this, 'is_feed_payflexi_connect_enabled') : false,
				'choices'     => array(
					array(
						'label' => esc_html__('I have enabled the Gravity Forms webhook URL in my PayFlexi account.', 'gravityformspayflexi'),
						'value' => 1,
						'name'  => 'webhooks_enabled',
					),
				),
			)
		);

		$fields = array_merge($fields, $webhook_fields);

		return $fields;
	}

	/**
	 * Define the markup to be displayed for the webhooks section description.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFPayflexi::plugin_settings_fields()
	 * @uses    GFPayflexi::get_webhook_url()
	 *
	 * @return string HTML formatted webhooks description.
	 */
	public function get_webhooks_section_description()
	{
		ob_start();
?>
		<a href="javascript:void(0);" onclick="tb_show('Webhook Instructions', '#TB_inline?width=500&inlineId=payflexi-webhooks-instructions', '');" onkeypress="tb_show('Webhook Instructions', '#TB_inline?width=500&inlineId=payflexi-webhooks-instructions', '');">
			<?php esc_html_e('View Instructions', 'gravityformspayflexi'); ?>
		</a>
		</p>

		<div id="payflexi-webhooks-instructions" style="display:none;">
			<ol class="payflexi-webhooks-instructions">
				<li>
					<p><?php esc_html_e('Click the following link and log in to access your PayFlexi Webhooks management page:', 'gravityformspayflexi'); ?> </p>
					<a href="https://merchant.payflexi.co/developers?tab=api-keys-integrations" target="_blank">https://merchant.payflexi.co/developers?tab=api-keys-integrations</a>
				</li>
				<li>
					<p>
						<?php esc_html_e('Enter the following URL in the "Webhook URL" field:', 'gravityformspayflexi'); ?>
						<br />
						<code><?php echo $this->get_webhook_url($this->get_current_feed_id()); ?></code>
					</p>
				</li>
				<li><?php esc_html_e('Save Changes.', 'gravityformspayflexi'); ?></li>
			</ol>
		</div>

<?php
		return ob_get_clean();
	}

	// # FEED SETTINGS -------------------------------------------------------------------------------------------------

	/**
	 * Configures the settings which should be rendered on the feed edit page.
	 *
	 * @access public
	 *
	 * @uses GFPaymentAddOn::feed_settings_fields()
	 * @uses GFAddOn::replace_field()
	 * @uses GFAddOn::get_setting()
	 * @uses GFAddOn::add_field_after()
	 * @uses GFAddOn::remove_field()
	 * @uses GFAddOn::add_field_before()
	 *
	 * @return array The feed settings.
	 */
	public function feed_settings_fields()
	{
		// Get default payment feed settings fields.
		$default_settings = parent::feed_settings_fields();

		$fields = array(
			array(
				'name'          => 'mode',
				'label'         => esc_html__('Mode', 'gravityformspayflexi'),
				'type'          => 'radio',
				'choices'       => array(
					array('id' => 'gf_payflexi_live_mode', 'label' => esc_html__('Live', 'gravityformspayflexi'), 'value' => 'live'),
					array('id' => 'gf_payflexi_test_mode', 'label' => esc_html__('Test', 'gravityformspayflexi'), 'value' => 'test'),
				),
				'horizontal'    => true,
				'default_value' => 'live',
				'tooltip'       => '<h6>' . esc_html__('Mode', 'gravityformspayflexi') . '</h6>' . esc_html__('Select Live to receive payments in production. Select Test for testing purposes when using the PayFlexi development sandbox.', 'gravityformspayflexi')
			),
		);

		$default_settings = parent::add_field_after('feedName', $fields, $default_settings);

		// hide default display of setup fee, not used by PayFlexi
		$default_settings = parent::remove_field('setupFee', $default_settings);

		// Hide default display of trial, not used by PayFlexi
		$default_settings = parent::remove_field('trial', $default_settings);
		// Customer information fields.
		$customer_info_field = array(
			'name'       => 'customerInformation',
			'label'      => esc_html__('Customer Information', 'gravityformspayflexi'),
			'type'       => 'field_map',
			'field_map'  => array(
				array(
					'name'       => 'email',
					'label'      => esc_html__('Email Address', 'gravityformspayflexi'),
					'required'   => true,
					'field_type' => array('email', 'hidden'),
					'tooltip'    => '<h6>' . esc_html__('Email', 'gravityformspayflexi') . '</h6>' . esc_html__('You can specify an email field and it will be sent to the Paystack screen as the customer\'s email.', 'gravityformspaystack'),
				),
				array(
					'name'     => 'first_name',
					'label'    => esc_html__('First Name', 'gravityformspayflexi'),
					'required' => false,
				),
				array(
					'name'     => 'last_name',
					'label'    => esc_html__('Last Name', 'gravityformspayflexi'),
					'required' => false,
				),
				array(
					'name'     	=> 'phone',
					'label'    	=> esc_html__('Phone Number', 'gravityformspayflexi'),
					'required' 	=> false,
				),
			),
		);

		// Add customer information fields.
		$default_settings = $this->add_field_before('billingInformation', $customer_info_field, $default_settings);

		// Prepare meta data field.
		$custom_meta = array(
			array(
				'name'                => 'metaData',
				'label'               => esc_html__('Metadata', 'gravityformspayflexi'),
				'type'                => 'dynamic_field_map',
				'limit'				  => 20,
				'tooltip'             => '<h6>' . esc_html__('Metadata', 'gravityformspayflexi') . '</h6>' . esc_html__('You may send custom meta information to PayFlexi. A maximum of 20 custom keys may be sent.', 'gravityformspayflexi'),
				'validation_callback' => array($this, 'validate_custom_meta'),
			),
		);

		// Add meta data field.
		$default_settings = $this->add_field_after('billingInformation', $custom_meta, $default_settings);

		// hide default display of billing Information if transaction type is donation
		if ($this->get_setting('transactionType') === 'donation') {
			$default_settings = parent::remove_field('billingInformation', $default_settings);
		}

		return $default_settings;
	}
	/**
	 * Define the markup for the trial type field.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFAddOn::settings_checkbox()
	 *
	 * @param array     $field The field properties.
	 * @param bool|true $echo Should the setting markup be echoed. Defaults to true.
	 *
	 * @return string|void The HTML markup if $echo is set to false. Void otherwise.
	 */
	public function settings_trial($field, $echo = true)
	{
		// Prepare enabled field settings.
		$enabled_field = array(
			'name'       => $field['name'] . '_checkbox',
			'type'       => 'checkbox',
			'horizontal' => true,
			'choices'    => array(
				array(
					'label'    => esc_html__('Enabled', 'gravityformspayflexi'),
					'name'     => $field['name'] . '_enabled',
					'value'    => '1',
					'onchange' => "if(jQuery(this).prop('checked')){
						jQuery('#gaddon-setting-row-trialPeriod').show('slow');
					} else {
						jQuery('#gaddon-setting-row-trialPeriod').hide('slow');
						jQuery('#trialPeriod').val( '' );
					}",
				),
			),
		);

		// Get checkbox markup.
		$html = $this->settings_checkbox($enabled_field, false);

		// Echo setting markup, if enabled.
		if ($echo) {
			echo $html;
		}

		return $html;
	}

	/**
	 * Define the markup for the trial_period type field.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFAddOn::settings_text()
	 * @uses GFAddOn::field_failed_validation()
	 * @uses GFAddOn::get_error_icon()
	 *
	 * @param array     $field The field properties.
	 * @param bool|true $echo  Should the setting markup be echoed. Defaults to true.
	 *
	 * @return string|void The HTML markup if $echo is set to false. Void otherwise.
	 */
	public function settings_trial_period($field, $echo = true)
	{
		// Get text input markup.
		$html = $this->settings_text($field, false);

		// Prepare validation placeholder name.
		$validation_placeholder = array('name' => 'trialValidationPlaceholder');

		// Add validation indicator.
		if ($this->field_failed_validation($validation_placeholder)) {
			$html .= '&nbsp;' . $this->get_error_icon($validation_placeholder);
		}

		// If trial is not enabled and setup fee is enabled, hide field.
		$html .= '
			<script type="text/javascript">
			if( ! jQuery( "#trial_enabled" ).is( ":checked" ) || jQuery( "#setupFee_enabled" ).is( ":checked" ) ) {
				jQuery( "#trial_enabled" ).prop( "checked", false );
				jQuery( "#gaddon-setting-row-trialPeriod" ).hide();
			}
			</script>';

		// Echo setting markup, if enabled.
		if ($echo) {
			echo $html;
		}

		return $html;
	}

	/**
	 * Prepare fields for field mapping in feed settings.
	 *
	 *
	 * @return array $fields
	 */
	public function billing_info_fields()
	{
		$fields = array(
			array(
				'name'       => 'address_line1',
				'label'      => __('Address', 'gravityformsconstantcontact'),
				'required'   => false,
				'field_type' => array('address'),
			),
			array(
				'name'       => 'address_line2',
				'label'      => __('Address 2', 'gravityformsconstantcontact'),
				'required'   => false,
				'field_type' => array('address'),
			),
			array(
				'name'       => 'address_city',
				'label'      => __('City', 'gravityformsconstantcontact'),
				'required'   => false,
				'field_type' => array('address'),
			),
			array(
				'name'       => 'address_state',
				'label'      => __('State', 'gravityformsconstantcontact'),
				'required'   => false,
				'field_type' => array('address'),
			),
			array(
				'name'       => 'address_zip',
				'label'      => __('Zip', 'gravityformsconstantcontact'),
				'required'   => false,
				'field_type' => array('address'),
			),
			array(
				'name'       => 'address_country',
				'label'      => __('Country', 'gravityformsconstantcontact'),
				'required'   => false,
				'field_type' => array('address'),
			),
		);

		return $fields;
	}

	/**
	 * Validate the custom_meta type field.
	 *
	 * @access public
	 *
	 * @uses GFAddOn::get_posted_settings()
	 * @uses GFAddOn::set_field_error()
	 *
	 * @param array $field The field properties. Not used.
	 *
	 * @return void
	 */
	public function validate_custom_meta($field)
	{
		$settings  = $this->get_posted_settings();
		$meta_data = $settings['metaData'];

		// If metadata is not defined, return.
		if (empty($meta_data)) {
			return;
		}

		// Get number of metadata items.
		$meta_count = count($meta_data);

		// If there are more than 20 metadata keys, set field error.
		if ($meta_count > 20) {
			$this->set_field_error(array(esc_html__('You may only have 20 custom keys.'), 'gravityformspayflexi'));
			return;
		}

		// Loop through metadata and check the key name length (custom_key).
		foreach ($meta_data as $meta) {
			if (empty($meta['custom_key']) && !empty($meta['value'])) {
				$this->set_field_error(array('name' => 'metaData'), esc_html__("A field has been mapped to a custom key without a name. Please enter a name for the custom key, remove the metadata item, or return the corresponding drop down to 'Select a Field'.", 'gravityformspayflexi'));
				break;
			} else if (strlen($meta['custom_key']) > 40) {
				$this->set_field_error(array('name' => 'metaData'), sprintf(esc_html__('The name of custom key %s is too long. Please shorten this to 40 characters or less.', 'gravityformspayflexi'), $meta['custom_key']));
				break;
			}
		}
	}

	/**
	 * Prevent the 'options' checkboxes setting being included on the feed.
	 *
	 * @access public
	 *
	 * @used-by GFPaymentAddOn::other_settings_fields()
	 *
	 * @return false
	 */
	public function option_choices()
	{
		return false;
	}

	/**
	 * Add supported notification events.
	 *
	 * @access public
	 *
	 * @used-by GFFeedAddOn::notification_events()
	 * @uses    GFFeedAddOn::has_feed()
	 *
	 * @param array $form The form currently being processed.
	 *
	 * @return array|false The supported notification events. False if feed cannot be found within $form.
	 */
	public function supported_notification_events($form)
	{
		// If this form does not have a PayFlexi feed, return false.
		if (!$this->has_feed($form['id'])) {
			return false;
		}

		// Return PayFlexi notification events.
		return array(
			'complete_payment'          => esc_html__('Payment Completed', 'gravityformspayflexi'),
			'refund_payment'            => esc_html__('Payment Refunded', 'gravityformspayflexi'),
			'fail_payment'              => esc_html__('Payment Failed', 'gravityformspayflexi')
		);
	}

	public function get_fields_meta_data($feed, $entry, $fields)
	{
		$data = [];

		foreach ($fields as $field) {
			$field_id = $feed['meta'][$field['meta_name']];
			$value    = rgar($entry, $field_id);

			if ($field['name'] == 'country') {
				$value = class_exists('GF_Field_Address') ? GF_Fields::get('address')->get_country_code($value) : GFCommon::get_country_code($value);
			} elseif ($field['name'] == 'state') {
				$value = class_exists('GF_Field_Address') ? GF_Fields::get('address')->get_us_state_code($value) : GFCommon::get_us_state_code($value);
			}

			if (!empty($value)) {
				$data["{$field['name']}"] = $value;
			}
		}

		return $data;
	}

	public function get_customer_fields()
	{
		return array(
			array('name' => 'email', 'label' => 'Email', 'meta_name' => 'customerInformation_email'),
			array('name' => 'first_name', 'label' => 'First Name', 'meta_name' => 'customerInformation_first_name'),
			array('name' => 'last_name', 'label' => 'Last Name', 'meta_name' => 'customerInformation_last_name'),
		);
	}

	public function get_billing_fields()
	{
		return array(
			array('name' => 'address1', 'label' => 'Address', 'meta_name' => 'billingInformation_address'),
			array('name' => 'address2', 'label' => 'Address 2', 'meta_name' => 'billingInformation_address2'),
			array('name' => 'city', 'label' => 'City', 'meta_name' => 'billingInformation_city'),
			array('name' => 'state', 'label' => 'State', 'meta_name' => 'billingInformation_state'),
			array('name' => 'zip', 'label' => 'Zip', 'meta_name' => 'billingInformation_zip'),
			array('name' => 'country', 'label' => 'Country', 'meta_name' => 'billingInformation_country'),
		);
	}

	/**
	 * Useful when developing a payment gateway that processes the payment outside of the website (i.e. Paystack Redirect).
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFPaymentAddOn::entry_post_save()
	 *
	 * @param array $feed            Active payment feed containing all the configuration data.
	 * @param array $submission_data Contains form field data submitted by the user as well as payment information (i.e. payment amount, setup fee, line items, etc...).
	 * @param array $form            Current form array containing all form settings.
	 * @param array $entry           Current entry array containing entry information (i.e data submitted by users).
	 *
	 * @return void|string Return a full URL (including http:// or https://) to the payment processor.
	 */
	public function redirect_url($feed, $submission_data, $form, $entry)
	{
		// Don't process redirect url if request is a Paystack return
		if (!rgempty('gf_payflexi_return', $_GET)) {
			return false;
		}

		// Getting mode (Live (Production) or Test (Sandbox))
		$mode = $feed['meta']['mode'];

		// Setup the Payflexi API
		$this->payflexi_api($mode);

		// Get the transaction type
		$transaction_type = $feed['meta']['transactionType'];

		// Getting the product status
		$is_product = $feed['meta']['transactionType'] === 'product';

		$return_url = $this->return_url($form['id'], $entry['id'], $feed['id']);

		// $setup_fee      = rgar($submission_data, 'setup_fee');
		$form_title   = rgar($submission_data, 'form_title');
		$payment_amount = rgar($submission_data, 'payment_amount');

		// Currency
		$currency = rgar($entry, 'currency');

		// Customer Info
		$customer_info = $this->get_fields_meta_data($feed, $entry, $this->get_customer_fields());

		// Get feed custom metadata
		$custom_data = $this->get_payflexi_meta_data($feed, $entry, $form);

		$custom_data[] = [
			'display_name' => 'Plugin Name',
			'variable_name' => 'plugin_name',
			'value' => 'pyfc-gravityforms'
		];

		// Generate transaction reference
		$reference = uniqid("gf-{$entry['id']}-");

		// Updating lead's payment_status to Processing
		GFAPI::update_entry_property($entry['id'], 'payment_status', 'Processing');

		// Prepare transaction data
		$args = array(
			'email'        => $customer_info['email'],
			'currency'     => $currency,
			'gateway' 	   => $this->get_plugin_setting( 'enabled_payment_gateway' ),
			'amount'       => (int)$this->get_amount_export($payment_amount, $currency),
			'reference'    => $reference,
			'callback_url' => $return_url,
			'domain'  => 'global',
			'meta'     => array(
				'title' 	  => $form_title,
				'entry_id'    => $entry['id'],
				'site_url'    => esc_url(get_site_url()),
				'ip_address'  => $_SERVER['REMOTE_ADDR']
			)
		);

		// Initialize the charge on PayFlexi's servers - this will be used to charge the user's card
		$response = (object) $this->payflexi_api->send_request("merchants/transactions/", $args);

		if ($response->errors) {
			return false;
		}

		gform_update_meta($entry['id'], 'payflexi_tx_reference', $response->reference);
		gform_update_meta($entry['id'], 'payflexi_tx_callback_url', $response->checkout_url);
		gform_update_meta($entry['id'], 'payflexi_total_order_amount', $args['amount']);

		return  $response->checkout_url;
	}


	public function return_url($form_id, $entry_id, $feed_id)
    {
        $pageURL = GFCommon::is_ssl() ? 'https://' : 'http://';

        $server_port = apply_filters('gform_payflexi_return_url_port', $_SERVER['SERVER_PORT']);

        if ($server_port != '80') {
            $pageURL .= $_SERVER['SERVER_NAME'] . ':' . $server_port . $_SERVER['REQUEST_URI'];
        } else {
            $pageURL .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
        }

		$ids_query = "ids={$entry_id}|{$feed_id}|{$form_id}";
		$ids_query .= '&hash=' . wp_hash($ids_query);

        $url = add_query_arg('gf_payflexi_return', base64_encode($ids_query), $pageURL);

		return $url;
    }
	/**
	 * Display the thank you page when there's a gf_payflexi_return URL param and the charge is successful.
	 *
	 * @since 1.0
	 */
	public function maybe_thankyou_page()
	{
		if (!$this->is_gravityforms_supported()) {
			return;
		}

		if ($str = sanitize_text_field(rgget('gf_payflexi_return'))) {
			$str = base64_decode($str);

			parse_str($str, $query);

			if (wp_hash('ids=' . $query['ids']) == $query['hash']) {
				list($entry_id, $feed_id, $form_id) = explode('|', $query['ids']);

				$entry = GFAPI::get_entry($entry_id);
				$form = GFAPI::get_form($form_id);

				if (is_wp_error($entry) || !$entry) {
					$this->log_error(__METHOD__ . '(): Entry could not be found. Aborting.');

					return false;
				}

				$this->log_debug(__METHOD__ . '(): Entry has been found => ' . print_r($entry, true));

				if ($entry['status'] == 'spam') {
					$this->log_error(__METHOD__ . '(): Entry is marked as spam. Aborting.');

					return false;
				}

				// Get feed
				$feed = $this->get_feed($feed_id);

				$this->log_debug(__METHOD__ . "(): Form {$entry['form_id']} is properly configured.");

				// Let's ignore forms that are no longer configured to use the Paystack add-on  
				if (!$feed || !rgar($feed, 'is_active')) {
					$this->log_error(__METHOD__ . "(): Form no longer uses the PayFlexi Addon. Form ID: {$entry['form_id']}. Aborting.");

					return false;
				}

				if ($feed && isset($_GET['pf_cancelled'])) {
					wp_redirect($entry['source_url']);
					exit;
				}
		
				if ($feed && isset($_GET['pf_declined'])) {
					wp_redirect($entry['source_url']);
					exit;
				}

				// Getting mode Live (Production) or Test (Sandbox)
				$mode = $feed['meta']['mode'];

				$this->payflexi_api($mode);

				$reference = sanitize_text_field(rgget('pf_approved'));

				$args = array();

				try {
					$response = $this->payflexi_api->send_request("merchants/transactions/{$reference}", $args, 'get');

					$this->log_debug(__METHOD__ . "(): Transaction verified. " . print_r($response, 1));
				} catch (\Exception $e) {
					$this->log_error(__METHOD__ . "(): Transaction could not be verified. Reason: " . $e->getMessage());

					return new WP_Error('transaction_verification', $e->getMessage());
				}

				$charge = $response['data'];

				if (!$response || $charge['status'] == 'failed' || $charge['status'] == 'abandoned') {
					// Updating lead's payment_status to Failed
					GFAPI::update_entry_property($entry['id'], 'payment_status', 'Failed');
					// Charge Failed
					$this->log_error(__METHOD__ . "(): Transaction verification failed Reason: " . $response['message']);

					return false;
				}

				gform_update_meta($entry['id'], 'payflexi_total_amount_paid', $charge['txn_amount']);

				// Updating lead's payment_status to Processing
				GFAPI::update_entry_property($entry['id'], 'payment_status', 'Processing');

				if (!class_exists('GFFormDisplay')) {
					require_once(GFCommon::get_base_path() . '/form_display.php');
				}

				$confirmation = GFFormDisplay::handle_confirmation($form, $entry, false);

				if (is_array($confirmation) && isset($confirmation['redirect'])) {
					header("Location: {$confirmation['redirect']}");
					exit;
				}

				GFFormDisplay::$submission[$form_id] = array(
					'is_confirmation'      => true,
					'confirmation_message' => $confirmation,
					'form'                 => $form,
					'lead'                 => $entry,
				);
			}
		}
	}

	// # WEBHOOKS ------------------------------------------------------------------------------------------------------

	/**
	 * If the PayFlexi callback or webhook belongs to a valid entry process the raw response into a standard Gravity Forms $action.
	 *
	 * @access public
	 *
	 * @uses GFAddOn::get_plugin_settings()
	 * @uses GFPayflexi::get_api_mode()
	 * @uses GFAddOn::log_error()
	 * @uses GFAddOn::log_debug()
	 * @uses GFPaymentAddOn::get_entry_by_transaction_id()
	 * @uses GFPaymentAddOn::get_amount_import()
	 * @uses GFPayflexi::get_subscription_line_item()
	 * @uses GFPayflexi::get_captured_payment_note()
	 * @uses GFAPI::get_entry()
	 * @uses GFCommon::to_money()
	 *
	 * @return array|bool|WP_Error Return a valid GF $action or if the webhook can't be processed a WP_Error object or false.
	 */
	public function callback()
	{
		if (!$this->is_gravityforms_supported()) {
			return;
		}

		$event = $this->get_webhook_event();

		if (!$event || is_wp_error($event)) {
			return $event;
		}

		$this->log_debug(__METHOD__ . '(): Webhook callback received. Starting to process => ' . print_r($_REQUEST, true));

		$type = $event['event'];

		if ('transaction.approved' === $type && $event['data']['status'] === 'approved') {
		
			if (!isset($event['data']['id']) || !isset($event['data']['meta']['entry_id'])) {
				return false;
			}

			$entry_id = rgars($event, 'data/meta/entry_id');

			if (!$entry_id && $reference = rgars($event, 'data/initial_reference')) {
				$entry_id = $this->get_entry_id_by_reference($reference);
			}

			if (!$entry_id) {
				return new WP_Error('entry_not_found', sprintf(__('Entry for transaction id: %s was not found. Webhook cannot be processed.', 'gravityformspayflexi'), $action['transaction_id']));
			}

			$entry = GFAPI::get_entry($entry_id);

			if (is_wp_error($entry)) {
				$this->log_error(__METHOD__ . '(): ' . $entry->get_error_message());
				return false;
			}

			$this->log_debug(__METHOD__ . '(): Entry has been found => ' . print_r($entry, true));

			$feed = $this->get_payment_feed($entry);

			// Let's ignore forms that are no longer configured to use the PayFlexi add-on  
			if (!$feed || !rgar($feed, 'is_active')) {
				$this->log_error(__METHOD__ . "(): Form no longer uses the PayFlexi Addon. Form ID: {$entry['form_id']}. Aborting.");

				return false;
			}

			$payflexi_tx_reference = gform_get_meta($entry['id'], 'payflexi_tx_reference');
			$payflexi_total_order_amount = gform_get_meta($entry['id'], 'payflexi_total_order_amount');

			if ($event['data']['txn_amount'] >= $payflexi_total_order_amount ) {
				gform_update_meta($entry['id'], 'payflexi_total_order_amount', $event['data']['amount']);
				gform_update_meta($entry['id'], 'payflexi_total_amount_paid', $event['data']['txn_amount']);
				$payflexi_total_amount_paid = gform_get_meta($entry['id'], 'payflexi_total_amount_paid');
			}
				
			if ($event['data']['txn_amount'] < $payflexi_total_order_amount ) {

				if($payflexi_tx_reference === $event['data']['initial_reference']){
					gform_update_meta($entry['id'], 'payflexi_total_order_amount', $event['data']['amount']);
					gform_update_meta($entry['id'], 'payflexi_total_amount_paid', $event['data']['txn_amount']);
					$payflexi_total_amount_paid = gform_get_meta($entry['id'], 'payflexi_total_amount_paid');
				}
				if($payflexi_tx_reference !== $event['data']['initial_reference']){
					$payflexi_total_amount_paid = gform_get_meta($entry['id'], 'payflexi_total_amount_paid');
					$total_installment_amount_paid = $payflexi_total_amount_paid + $event['data']['txn_amount'];
					gform_update_meta($entry['id'], 'payflexi_total_amount_paid', $total_installment_amount_paid);
					$payflexi_total_amount_paid = gform_get_meta($entry['id'], 'payflexi_total_amount_paid');
				}
			}
			
			$action['id']               = rgars($event, 'data/id') . '_' . $type;
			$action['entry_id']         = $entry_id;
			$action['transaction_id']   = rgars($event, 'data/id');
			$action['amount']           = $this->get_amount_import($payflexi_total_amount_paid, rgars($event, 'data/currency'));
			$action['type']             = 'complete_payment';
			$action['ready_to_fulfill'] = !$entry['is_fulfilled'] ? true : false;
			$action['payment_date']     = rgars($event, 'data/created_at');
			$action['payment_method']   = $this->_slug;
	
		}

		if (rgempty('entry_id', $action)) {
			$this->log_debug(__METHOD__ . '() entry_id not set for callback action; no further processing required.');

			return false;
		}

		return $action;
	}

	/**
	 * Helper for making the gform_post_payment_action hook available to the various payment interaction methods. Also handles sending notifications for payment events.
	 *
	 * @param array $entry
	 * @param array $action
	 */
	public function post_payment_action($entry, $action)
	{
		parent::post_payment_action($entry, $action);
	}

	/**
	 * Retrieve the PayFlexi Event for the received webhook.
	 *
	 *
	 * @return false|WP_Error
	 */
	public function get_webhook_event()
	{
		// Retrieve the request's payload
		$body = @file_get_contents('php://input');

		$response = json_decode($body, true);

		// Get api mode from data
		$mode = rgars($response, 'data/domain');

		$this->payflexi_api($mode);

		// Validate Webhook Request Payload
		try {
			$is_verified = $this->payflexi_api->validate_webhook($body);
		} catch (\Exception $e) {
			$this->log_error(__METHOD__ . "(): Error: " . $e->getMessage());

			return new WP_Error('Webhook Validation Failed', $e->getMessage());
		}

		if (!$is_verified) {
			$this->log_error(__METHOD__ . '(): Wehhook request is invalid. Aborting.');

			return false;
		}

		$this->log_debug(__METHOD__ . "(): Processing $mode mode event.");

		return $response;
	}

	/**
	 * Generate the url PayFlexi webhooks should be sent to.
	 *
	 * @access public
	 *
	 * @used-by GFPayflexi::get_webhooks_section_description()
	 *
	 * @param int $feed_id The feed id.
	 *
	 * @return string The webhook URL.
	 */
	public function get_webhook_url($feed_id = null)
	{
		$url = home_url('/', 'https') . '?callback=' . $this->_slug;

		if (!rgblank($feed_id)) {
			$url .= '&fid=' . $feed_id;
		}

		return $url;
	}
	/**
	 * Helper to check that webhooks are enabled.
	 *
	 * @access public
	 *
	 * @used-by GFPayflexi::can_create_feed()
	 * @uses    GFAddOn::get_plugin_setting()
	 *
	 * @return bool True if webhook is enabled. False otherwise.
	 */
	public function is_webhook_enabled()
	{
		return $this->get_plugin_setting('webhooks_enabled') == true;
	}

	// # PAYSTACK HELPER FUNCTIONS ---------------------------------------------------------------------------------------



	/**
	 * If custom meta data has been configured on the feed retrieve the mapped field values.
	 *
	 * @access public
	 *
	 * @uses  GFAddOn::get_field_value()
	 *
	 * @param array $feed  The feed object currently being processed.
	 * @param array $entry The entry object currently being processed.
	 * @param array $form  The form object currently being processed.
	 *
	 * @return array The PayFlexi meta data.
	 */
	public function get_payflexi_meta_data($feed, $entry, $form)
	{
		// Initialize metadata array.
		$metadata = array();

		// Find feed metadata.
		$custom_meta = rgars($feed, 'meta/metaData');

		if (is_array($custom_meta)) {
			// Loop through custom meta and add to metadata for payflexi.
			foreach ($custom_meta as $meta) {

				// If custom key or value are empty, skip meta.
				if (empty($meta['custom_key']) || empty($meta['value'])) {
					continue;
				}

				// Make the key available to the gform_payflexi_field_value filter.
				$this->_current_meta_key = $meta['custom_key'];

				// Get field value for meta key.
				$field_value = $this->get_field_value($form, $entry, $meta['value']);

				if (!empty($field_value)) {
					// Trim to 500 characters.
					$field_value = substr($field_value, 0, 500);

					// Add to metadata array.
					$metadata[] = [
						'display_name' => $meta['custom_key'],
						'variable_name' => sanitize_title($meta['custom_key']),
						'value' => $field_value,
					];
				}
			}

			// Clear the key in case get_field_value() and gform_payflexi_field_value are used elsewhere.
			$this->_current_meta_key = '';
		}

		return $metadata;
	}

	/**
	 * Initialize the PayFlexi API and returns the GF PayFlexi API Object.
	 *
	 * @access  public
	 *
	 * @used-by GFPayflexi::cancel()
	 * @used-by GFPayflexi::get_payflexi_event()
	 * @used-by GFPayflexi::subscribe()
	 * @uses    GFAddOn::get_base_path()
	 * @uses    GFPayflexi::get_secret_api_key()
	 * @uses    GFPayflexi::get_public_api_key()
	 * 
	 * @param null|string $mode The API mode; live or test.
	 * @param null|array  $settings The settings.
	 * 
	 * @return \GFPayflexiApi
	 */
	public function payflexi_api($mode = null, $settings = null)
	{
		if (empty($settings)) {
			$settings = $this->get_plugin_settings();
		}

		if (empty($mode)) {
			$mode  = $this->get_api_mode($settings);
		}

		$config = (object) array(
			'secret_key' => $this->get_secret_api_key($mode, $settings),
			'public_key' => $this->get_public_api_key($mode, $settings)
		);

		$this->log_debug(sprintf('%s(): Initializing PayFlexi API for %s mode.', __METHOD__, $mode));

		return $this->payflexi_api = new GFPayflexiApi($config);
	}

	// # OTHER HELPER FUNCTIONS ----------------------------------------------------------------------------------------------

	/**
	 * Retrieve the specified api key.
	 *
	 * @access  public
	 *
	 * @used-by GFPayflexi::get_public_api_key()
	 * @used-by GFPayflexi::get_secret_api_key()
	 * @uses    GFPayflexi::get_query_string_api_key()
	 * @uses    GFAddOn::get_plugin_settings()
	 * @uses    GFPayflexi::get_api_mode()
	 * @uses    GFAddOn::get_setting()
	 *
	 * @param string      $type    The type of key to retrieve.
	 * @param null|string $mode    The API mode; live or test.
	 * @param null|int    $settings The current settings.
	 *
	 * @return string
	 */
	public function get_api_key($type = 'secret', $mode = null, $settings = null)
	{
		// Check for api key in query first; user must be an administrator to use this feature.
		$api_key = $this->get_query_string_api_key($type);
		if ($api_key && current_user_can('update_core')) {
			return $api_key;
		}

		if (!isset($settings)) {
			$settings = $this->get_plugin_settings();

			if (!$mode) {
				// Get API mode.
				$mode = $this->get_api_mode($settings);
			}
		}

		// Get API key based on current mode and defined type.
		$setting_key = "{$mode}_{$type}_key";
		$api_key     = $this->get_setting($setting_key, '', $settings);

		return $api_key;
	}

	/**
	 * Helper to implement the gform_payflexi_api_mode filter so the api mode can be overridden.
	 *
	 * @access public
	 *
	 * @used-by GFPayflexi::get_api_key()
	 * @used-by GFPayflexi::callback()
	 * @used-by GFPayflexi::can_create_feed()
	 *
	 * @param array $settings The plugin settings.
	 * @param int   $feed_id  Feed ID.
	 *
	 * @return string $api_mode Either live or test.
	 */
	public function get_api_mode($settings, $feed_id = null)
	{
		// Get API mode from settings.
		$api_mode = rgar($settings, 'api_mode');

		/**
		 * Filters the API mode.
		 *
		 * @param string $api_mode The API mode.
		 * @param int    $feed_id  Feed ID.
		 */
		return apply_filters('gform_payflexi_api_mode', $api_mode, $feed_id);
	}

	/**
	 * Retrieve the specified api key from the query string.
	 *
	 * @access public
	 *
	 * @used-by GFPayflexi::get_api_key()
	 *
	 * @param string $type The type of key to retrieve. Defaults to 'secret'.
	 *
	 * @return string The result of the query string.
	 */
	public function get_query_string_api_key($type)
	{
		return rgget($type);
	}

	/**
	 * Retrieve the public api key.
	 *
	 * @access  public
	 *
	 * @uses  GFPayflexi::get_api_key()
	 *
	 * @param null|string $mode    The API mode; live or test.
	 * @param array|null $settings The current settings.
	 *
	 * @return string The public API key.
	 */
	public function get_public_api_key($mode = null, $settings = null)
	{
		if (empty($settings)) {
			$settings = $this->get_plugin_settings();
		}

		if (empty($mode)) {
			$mode = $this->get_api_mode($settings);
		}

		return $this->get_api_key('public', $mode, $settings);
	}

	/**
	 * Retrieve the secret api key.
	 *
	 * @access  public
	 *
	 * @uses  GFPayflexi::get_api_key()
	 *
	 * @param null|string $mode    The API mode; live or test.
	 * @param null|array  $settings The current settings.
	 *
	 * @return string The secret API key.
	 */
	public function get_secret_api_key($mode = null, $settings = null)
	{
		if (empty($settings)) {
			$settings = $this->get_plugin_settings();
		}

		if (empty($mode)) {
			$mode = $this->get_api_mode($settings);
		}

		return $this->get_api_key('secret', $mode, $settings);
	}
	/**
	 * Returns the specified plugin setting.
	 *
	 * @since 2.6.0.1
	 *
	 * @param string $setting_name The setting to be returned.
	 *
	 * @return mixed|string
	 */
	public function get_plugin_setting( $setting_name ) 
	{
		$setting = parent::get_plugin_setting( $setting_name );

		return $setting;
	}
	/**
	 * Check if rate limits is enabled.
	 *
	 *
	 * @param int $form_id The form ID.
	 *
	 * @return bool
	 */
	public function is_rate_limits_enabled($form_id)
	{
		/**
		 * Allow enabling or disable the rate limit check.
		 *
		 * @param bool $has_error The default is false.
		 * @param int  $form_id   The form ID.
		 */
		$this->_enable_rate_limits = apply_filters('gform_payflexi_enable_rate_limits', $this->_enable_rate_limits, $form_id);

		return $this->_enable_rate_limits;
	}

	public function get_entry_id_by_reference($reference)
	{
		global $wpdb;

		$entry_meta_table_name = self::get_entry_meta_table_name();

		$sql      = $wpdb->prepare("SELECT entry_id FROM {$entry_meta_table_name} WHERE meta_key = 'payflexi_tx_reference' AND meta_value = '%s'", $reference);
		$entry_id = $wpdb->get_var($sql);

		return $entry_id ? $entry_id : false;
	}
}
