<?php

/**
 * This is a manager for the Fields API, based on the WP_Customize_Manager.
 *
 * @package    WordPress
 * @subpackage Fields_API
 */
final class WP_Fields_API {

	/**
	 * @var WP_Fields_API
	 */
	private static $instance;

	/**
	 * Registered Forms
	 *
	 * @access protected
	 * @var array
	 */
	protected static $forms = array();

	/**
	 * Registered Sections
	 *
	 * @access protected
	 * @var array
	 */
	protected static $sections = array();

	/**
	 * Registered Fields
	 *
	 * @access protected
	 * @var array
	 */
	protected static $fields = array();

	/**
	 * Registered Controls
	 *
	 * @access protected
	 * @var array
	 */
	protected static $controls = array();

	/**
	 * Form types that may be rendered.
	 *
	 * @access protected
	 * @var array
	 */
	protected static $registered_form_types = array();

	/**
	 * Section types that may be rendered.
	 *
	 * @access protected
	 * @var array
	 */
	protected static $registered_section_types = array();

	/**
	 * Field types that may be rendered.
	 *
	 * @access protected
	 * @var array
	 */
	protected static $registered_field_types = array();

	/**
	 * Control types that may be rendered.
	 *
	 * @access protected
	 * @var array
	 */
	protected static $registered_control_types = array();

	/**
	 * Datasources that may be used.
	 *
	 * @access protected
	 * @var array
	 */
	protected static $registered_datasources = array();

	/**
	 * Include the library and bootstrap.
	 *
	 * @constructor
	 * @access public
	 */
	private function __construct() {

		$fields_api_dir = WP_FIELDS_API_DIR . 'implementation/wp-includes/fields-api/';

		// Include API classes
		require_once( $fields_api_dir . 'class-wp-fields-api-container.php' );
		require_once( $fields_api_dir . 'class-wp-fields-api-form.php' );
		require_once( $fields_api_dir . 'class-wp-fields-api-section.php' );
		require_once( $fields_api_dir . 'class-wp-fields-api-field.php' );
		require_once( $fields_api_dir . 'class-wp-fields-api-control.php' );
		require_once( $fields_api_dir . 'class-wp-fields-api-datasource.php' );

		// Include section types
		require_once( $fields_api_dir . 'section-types/class-wp-fields-api-table-section.php' );
		require_once( $fields_api_dir . 'section-types/class-wp-fields-api-meta-box-section.php' );
		require_once( $fields_api_dir . 'section-types/class-wp-fields-api-meta-box-table-section.php' );

		// Include control types
		require_once( $fields_api_dir . 'control-types/class-wp-fields-api-readonly-control.php' );
		require_once( $fields_api_dir . 'control-types/class-wp-fields-api-textarea-control.php' );
		require_once( $fields_api_dir . 'control-types/class-wp-fields-api-wysiwyg-control.php' );
		require_once( $fields_api_dir . 'control-types/class-wp-fields-api-checkbox-control.php' );
		require_once( $fields_api_dir . 'control-types/class-wp-fields-api-multi-checkbox-control.php' );
		require_once( $fields_api_dir . 'control-types/class-wp-fields-api-radio-control.php' );
		//require_once( $fields_api_dir . 'control-types/class-wp-fields-api-radio-multi-label-control.php' ); // @todo Revisit
		require_once( $fields_api_dir . 'control-types/class-wp-fields-api-select-control.php' );
		require_once( $fields_api_dir . 'control-types/class-wp-fields-api-color-control.php' );
		require_once( $fields_api_dir . 'control-types/class-wp-fields-api-media-control.php' );
		require_once( $fields_api_dir . 'control-types/class-wp-fields-api-media-file-control.php' );
		require_once( $fields_api_dir . 'control-types/class-wp-fields-api-number-inline-description.php' );

		// Include datasources
		require_once( $fields_api_dir . 'datasources/class-wp-fields-api-admin-color-scheme-datasource.php' );
		require_once( $fields_api_dir . 'datasources/class-wp-fields-api-comment-datasource.php' );
		require_once( $fields_api_dir . 'datasources/class-wp-fields-api-post-datasource.php' );
		require_once( $fields_api_dir . 'datasources/class-wp-fields-api-page-datasource.php' );
		require_once( $fields_api_dir . 'datasources/class-wp-fields-api-term-datasource.php' );
		require_once( $fields_api_dir . 'datasources/class-wp-fields-api-user-datasource.php' );

		// Register our wp_loaded() first before WP_Customize_Manage::wp_loaded()
		add_action( 'wp_loaded', array( $this, 'wp_loaded' ), 9 );

	}

	/**
	 * Setup instance for singleton
	 *
	 * @return WP_Fields_API
	 */
	public static function get_instance() {

		if ( empty( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;

	}

	/**
	 * Trigger the `fields_register` action hook on `wp_loaded`.
	 *
	 * Fields, Sections, Forms, and Controls should be registered on this hook.
	 *
	 * @access public
	 */
	public function wp_loaded() {

		// Register default controls
		$this->register_defaults();

		/**
		 * Fires when the Fields API is available, and components can be registered.
		 *
		 * @param WP_Fields_API $this The Fields manager object.
		 */
		do_action( 'fields_register', $this );

	}

	/**
	 * Get the registered forms.
	 *
	 * @access public
	 *
	 * @param string $object_type Object type.
	 * @param string $object_subtype Object subtype (for post types and taxonomies).
	 *
	 * @return WP_Fields_API_Form[]
	 */
	public function get_forms( $object_type = null, $object_subtype = null ) {

		$primary_object_subtype = '_' . $object_type;

		// Default to _object_type for internal handling
		if ( empty( $object_subtype ) && ! empty( $object_type ) ) {
			$object_subtype = $primary_object_subtype;
		}

		$forms = array();

		if ( null === $object_type ) {
			// Late init.
			foreach ( self::$forms as $object_type => $object_subtypes ) {
				foreach ( $object_subtypes as $object_subtype => $forms ) {
					$this->get_forms( $object_type, $object_subtype );
				}
			}

			$forms = self::$forms;
		} elseif ( isset( self::$forms[ $object_type ][ $object_subtype ] ) ) {
			// Late init.
			foreach ( self::$forms[ $object_type ][ $object_subtype ] as $id => $form ) {
				// Late init
				self::$forms[ $object_type ][ $object_subtype ][ $id ] = $this->setup_form( $object_type, $id, $object_subtype, $form );
			}

			$forms = self::$forms[ $object_type ][ $object_subtype ];

			// Object subtype inheritance for getting data that covers all Object subtypes
			if ( $primary_object_subtype !== $object_subtype ) {
				$forms = array_merge( $this->get_forms( $object_type, $primary_object_subtype ), $forms );
			}
		} elseif ( true === $object_subtype ) {
			// Get all forms.
			// Late init.
			foreach ( self::$forms[ $object_type ] as $object_subtype => $object_forms ) {
				$forms = array_merge( $forms, array_values( $this->get_forms( $object_type, $object_subtype ) ) );
			}
		} elseif ( $primary_object_subtype !== $object_subtype ) {
			// Object subtype inheritance for getting data that covers all Object subtypes
			$forms = $this->get_forms( $object_type, $primary_object_subtype );
		}

		return $forms;

	}

	/**
	 * Add a field form.
	 *
	 * @access public
	 *
	 * @param string                      $object_type Object type.
	 * @param WP_Fields_API_Form|string $id          Field Form object, or Form ID.
	 * @param string                      $object_subtype Object subtype (for post types and taxonomies).
	 * @param array                       $args        Optional. Form arguments. Default empty array.
	 *
	 * @return bool|WP_Error True on success, or error
	 */
	public function add_form( $object_type, $id, $object_subtype = null, $args = array() ) {

		if ( empty( $id ) && empty( $args ) ) {
			return new WP_Error( '', __( 'ID is required.', 'fields-api' ) );
		}

		if ( is_a( $id, 'WP_Fields_API_Form' ) ) {
			$form = $id;

			$id = $form->id;
		} else {
			// Save for late init.
			$form = $args;
		}

		// $object_subtype defaults to '_{$object_type}' for internal handling.
		if ( empty( $object_subtype ) && ! empty( $object_type ) ) {
			$object_subtype = '_' . $object_type;
		}

		if ( ! isset( self::$forms[ $object_type ] ) ) {
			self::$forms[ $object_type ] = array();
		}

		if ( ! isset( self::$forms[ $object_type ][ $object_subtype ] ) ) {
			self::$forms[ $object_type ][ $object_subtype ] = array();
		}

		// @todo Remove this when done testing
		if ( defined( 'WP_FIELDS_API_TESTING' ) && WP_FIELDS_API_TESTING && ! empty( $_GET['no-fields-api-late-init'] ) ) {
			$form = $this->setup_form( $object_type, $id, $object_subtype, $form );
		}

		if ( isset( self::$forms[ $object_type ][ $object_subtype ][ $id ] ) ) {
			return new WP_Error( '', __( 'Form already exists.', 'fields-api' ) );
		}

		self::$forms[ $object_type ][ $object_subtype ][ $id ] = $form;

		return true;

	}

	/**
	 * Retrieve a field form.
	 *
	 * @access public
	 *
	 * @param string $object_type Object type.
	 * @param string $id          Form ID to get.
	 * @param string $object_subtype Object subtype (for post types and taxonomies).
	 *
	 * @return WP_Fields_API_Form|null Requested form instance.
	 */
	public function get_form( $object_type, $id, $object_subtype = null ) {

		if ( is_a( $id, 'WP_Fields_API_Form' ) ) {
			return $id;
		}

		$primary_object_subtype = '_' . $object_type;

		// Default to _object_type for internal handling
		if ( empty( $object_subtype ) && ! empty( $object_type ) ) {
			$object_subtype = $primary_object_subtype;
		}

		$form = null;

		if ( isset( self::$forms[ $object_type ][ $object_subtype ][ $id ] ) ) {
			// Late init
			self::$forms[ $object_type ][ $object_subtype ][ $id ] = $this->setup_form( $object_type, $id, $object_subtype, self::$forms[ $object_type ][ $object_subtype ][ $id ] );

			$form = self::$forms[ $object_type ][ $object_subtype ][ $id ];
		} elseif ( $primary_object_subtype !== $object_subtype ) {
			// Object subtype inheritance for getting data that covers all Object subtypes
			$form = $this->get_form( $object_type, $id, $primary_object_subtype );
		}

		return $form;

	}

	/**
	 * Setup the form.
	 *
	 * @access public
	 *
	 * @param string $object_type Object type.
	 * @param string $id          ID of the form.
	 * @param string $object_subtype Object subtype (for post types and taxonomies).
	 * @param array  $args        Form arguments.
	 *
	 * @return WP_Fields_API_Form|null $form The form object.
	 */
	public function setup_form( $object_type, $id, $object_subtype = null, $args = null ) {

		$form = null;

		$form_class = 'WP_Fields_API_Form';

		if ( is_a( $args, $form_class ) ) {
			$form = $args;
		} elseif ( is_array( $args ) ) {
			$args['object_subtype'] = $object_subtype;

			if ( ! empty( $args['type'] ) ) {
				if ( ! empty( self::$registered_form_types[ $args['type'] ] ) ) {
					$form_class = self::$registered_form_types[ $args['type'] ];
				} elseif ( in_array( $args['type'], self::$registered_form_types ) ) {
					$form_class = $args['type'];
				}
			}

			/**
			 * @var $form WP_Fields_API_Form
			 */
			$form = new $form_class( $object_type, $id, $args );
		}

		return $form;

	}

	/**
	 * Remove a form.
	 *
	 * @access public
	 *
	 * @param string $object_type Object type, set true to remove all forms.
	 * @param string $id          Form ID to remove, set true to remove all forms from an object.
	 * @param string $object_subtype Object subtype (for post types and taxonomies), set true to remove to all objects from an object type.
	 */
	public function remove_form( $object_type, $id, $object_subtype = null ) {

		if ( true === $object_type ) {
			// Remove all forms
			self::$forms = array();
		} elseif ( true === $object_subtype ) {
			// Remove all forms for an object type
			if ( isset( self::$forms[ $object_type ] ) ) {
				unset( self::$forms[ $object_type ] );
			}
		} else {
			if ( empty( $object_subtype ) && ! empty( $object_type ) ) {
				$object_subtype = '_' . $object_type; // Default to _object_type for internal handling
			}

			if ( true === $id && null !== $object_subtype ) {
				// Remove all forms for an object type
				if ( isset( self::$forms[ $object_type ][ $object_subtype ] ) ) {
					unset( self::$forms[ $object_type ][ $object_subtype ] );
				}
			} elseif ( isset( self::$forms[ $object_type ][ $object_subtype ][ $id ] ) ) {
				// Remove form from object type and name
				unset( self::$forms[ $object_type ][ $object_subtype ][ $id ] );
			}
		}

	}

	/**
	 * Register a form type.
	 *
	 * @access public
	 *
	 * @see    WP_Fields_API_Form
	 *
	 * @param string $type         Form type ID.
	 * @param string $form_class Name of a custom form which is a subclass of WP_Fields_API_Form.
	 */
	public function register_form_type( $type, $form_class = null ) {

		if ( null === $form_class ) {
			$form_class = $type;
		}

		self::$registered_form_types[ $type ] = $form_class;

	}

	/**
	 * Render JS templates for all registered form types.
	 *
	 * @access public
	 */
	public function render_form_templates() {

		/**
		 * @var WP_Fields_API_Form $form
		 */
		foreach ( self::$registered_form_types as $form_type => $form_class ) {
			$form = $this->setup_form( null, 'temp', null, array( 'type' => $form_type ) );

			//$form->print_template();
		}

	}

	/**
	 * Get the registered sections.
	 *
	 * @access public
	 *
	 * @param string                    $object_type Object type.
	 * @param string                    $object_subtype Object subtype (for post types and taxonomies).
	 * @param string|WP_Fields_API_Form $form        Form ID or object.
	 *
	 * @return WP_Fields_API_Section[]
	 */
	public function get_sections( $object_type = null, $object_subtype = null, $form = null ) {

		$primary_object_subtype = '_' . $object_type;

		// Default to _object_type for internal handling
		if ( empty( $object_subtype ) && ! empty( $object_type ) ) {
			$object_subtype = $primary_object_subtype;
		}

		$sections = array();

		$form_id = null;

		if ( $form ) {
			$form_id = $form;

			if ( is_object( $form ) ) {
				$form_id = $form->id;
			}
		}

		if ( null === $object_type ) {
			// Late init
			foreach ( self::$sections as $object_type => $object_subtypes ) {
				foreach ( $object_subtypes as $object_subtype => $sections ) {
					$this->get_sections( $object_type, $object_subtype );
				}
			}

			$sections = self::$sections;

			// Get only sections for a specific form
			if ( $form_id ) {
				$form_sections = array();

				/**
				 * @var $section WP_Fields_API_Section
				 */
				foreach ( $sections as $object_type => $object_subtypes ) {
					foreach ( $object_subtypes as $object_subtype => $object_sections ) {
						foreach ( $object_sections as $id => $section ) {
							$section_form = $section->get_form();

							if ( $section_form && $form_id == $section_form->id ) {
								if ( ! isset( $form_sections[ $object_type ] ) ) {
									$form_sections[ $object_type ] = array();
								}

								if ( ! isset( $form_sections[ $object_type ][ $object_subtype ] ) ) {
									$form_sections[ $object_type ][ $object_subtype ] = array();
								}

								$form_sections[ $object_type ][ $object_subtype ][ $id ] = $section;
							}
						}
					}
				}

				$sections = $form_sections;
			}
		} elseif ( isset( self::$sections[ $object_type ][ $object_subtype ] ) ) {
			// Late init
			foreach ( self::$sections[ $object_type ][ $object_subtype ] as $id => $section ) {
				if ( is_array( $section ) && empty( $section['type'] ) && is_a( $form, 'WP_Fields_API_Form' ) && $form->default_section_type ) {
					$section['type'] = $form->default_section_type;
				}

				// Late init
				self::$sections[ $object_type ][ $object_subtype ][ $id ] = $this->setup_section( $object_type, $id, $object_subtype, $section );
			}

			$sections = self::$sections[ $object_type ][ $object_subtype ];

			// Object subtype inheritance for getting data that covers all Object subtypes
			if ( $primary_object_subtype !== $object_subtype ) {
				$object_sections = $this->get_sections( $object_type, $primary_object_subtype );

				if ( $object_sections ) {
					$sections = array_merge( $sections, $object_sections );
				}
			}

			// Get only sections for a specific form
			if ( $form_id ) {
				$form_sections = array();

				/**
				 * @var $section WP_Fields_API_Section
				 */
				foreach ( $sections as $id => $section ) {
					$section_form = $section->get_form();

					if ( $section_form && $form_id == $section_form->id ) {
						$form_sections[ $id ] = $section;
					}
				}

				$sections = $form_sections;
			}
		} elseif ( true === $object_subtype ) {
			// Get all sections

			// Late init
			foreach ( self::$sections[ $object_type ] as $object_subtype => $object_sections ) {
				$object_sections = $this->get_sections( $object_type, $object_subtype, $form );

				if ( $object_sections ) {
					$sections = array_merge( $sections, array_values( $object_sections ) );
				}
			}
		} elseif ( $primary_object_subtype !== $object_subtype ) {
			// Object subtype inheritance for getting data that covers all Object subtypes
			$sections = $this->get_sections( $object_type, $primary_object_subtype, $form );
		}

		return $sections;

	}

	/**
	 * Add a field section.
	 *
	 * @access public
	 *
	 * @param string                       $object_type Object type.
	 * @param WP_Fields_API_Section|string $id          Field Section object, or Section ID.
	 * @param string                       $object_subtype Object subtype (for post types and taxonomies).
	 * @param array                        $args        Section arguments.
	 *
	 * @return bool|WP_Error True on success, or error
	 */
	public function add_section( $object_type, $id, $object_subtype = null, $args = array() ) {

		if ( empty( $id ) && empty( $args ) ) {
			return new WP_Error( '', __( 'ID is required.', 'fields-api' ) );
		}

		$controls = array();

		if ( is_a( $id, 'WP_Fields_API_Section' ) ) {
			$section = $id;

			$id = $section->id;
		} else {
			// Save for late init
			$section = $args;

			if ( isset( $section['controls'] ) ) {
				$controls = $section['controls'];

				// Remove from section args
				unset( $section['controls'] );
			}
		}

		if ( empty( $object_subtype ) && ! empty( $object_type ) ) {
			$object_subtype = '_' . $object_type; // Default to _object_type for internal handling
		}

		if ( ! isset( self::$sections[ $object_type ] ) ) {
			self::$sections[ $object_type ] = array();
		}

		if ( ! isset( self::$sections[ $object_type ][ $object_subtype ] ) ) {
			self::$sections[ $object_type ][ $object_subtype ] = array();
		}

		// @todo Remove this when done testing
		if ( defined( 'WP_FIELDS_API_TESTING' ) && WP_FIELDS_API_TESTING && ! empty( $_GET['no-fields-api-late-init'] ) ) {
			if ( is_array( $section ) && empty( $section['type'] ) ) {
				$form = null;

				if ( ! empty( $section['form'] ) ) {
					$form = $section['form'];
				}

				if ( ! is_a( $form, 'WP_Fields_API_Form' ) ) {
					$form = $this->get_form( $object_type, $form, $object_subtype );
				}

				if ( $form && $form->default_section_type ) {
					$section['type'] = $form->default_section_type;
				}
			}

			$section = $this->setup_section( $object_type, $id, $object_subtype, $section );
		}

		if ( isset( self::$sections[ $object_type ][ $object_subtype ][ $id ] ) ) {
			return new WP_Error( '', __( 'Section already exists.', 'fields-api' ) );
		}

		self::$sections[ $object_type ][ $object_subtype ][ $id ] = $section;

		// Controls handling
		if ( ! empty( $controls ) ) {
			if ( isset( $controls['id'] ) ) {
				$controls = array( $controls );
			}

			foreach ( $controls as $control_id => $control ) {
				if ( is_a( $control, 'WP_Fields_API_Section' ) ) {
					$control->section = $id;

					$control_id = $control->id;
				} elseif ( is_array( $control ) ) {
					$control['section'] = $id;

					if ( ! empty( $control['id'] ) ) {
						$control_id = $control['id'];
					}
				} else {
					// Invalid control
					$control_id = null;
				}

				if ( $control_id ) {
					// Add control for section
					$this->add_control( $object_type, $control_id, $object_subtype, $control );
				}
			}
		}

		return true;

	}

	/**
	 * Retrieve a field section.
	 *
	 * @access public
	 *
	 * @param string $object_type Object type.
	 * @param string $id          Section ID to get.
	 * @param string $object_subtype Object subtype (for post types and taxonomies).
	 *
	 * @return WP_Fields_API_Section|null Requested section instance.
	 */
	public function get_section( $object_type, $id, $object_subtype = null ) {

		if ( is_a( $id, 'WP_Fields_API_Section' ) ) {
			return $id;
		}

		$primary_object_subtype = '_' . $object_type;

		// Default to _object_type for internal handling
		if ( empty( $object_subtype ) && ! empty( $object_type ) ) {
			$object_subtype = $primary_object_subtype;
		}

		$section = null;

		if ( isset( self::$sections[ $object_type ][ $object_subtype ][ $id ] ) ) {
			// Late init
			self::$sections[ $object_type ][ $object_subtype ][ $id ] = $this->setup_section( $object_type, $id, $object_subtype, self::$sections[ $object_type ][ $object_subtype ][ $id ] );

			$section = self::$sections[ $object_type ][ $object_subtype ][ $id ];
		} elseif ( $primary_object_subtype !== $object_subtype ) {
			// Object subtype inheritance for getting data that covers all Object subtypes
			$section = $this->get_section( $object_type, $id, $primary_object_subtype );
		}

		return $section;

	}

	/**
	 * Setup the section.
	 *
	 * @access public
	 *
	 * @param string $object_type Object type.
	 * @param string $id          ID of the section.
	 * @param string $object_subtype Object subtype (for post types and taxonomies).
	 * @param array  $args        Section arguments.
	 *
	 * @return WP_Fields_API_Section|null $section The section object.
	 */
	public function setup_section( $object_type, $id, $object_subtype = null, $args = null ) {

		$section = null;

		$section_class = 'WP_Fields_API_Section';

		if ( is_a( $args, $section_class ) ) {
			$section = $args;
		} elseif ( is_array( $args ) ) {
			$args['object_subtype'] = $object_subtype;

			if ( ! empty( $args['type'] ) ) {
				if ( ! empty( self::$registered_section_types[ $args['type'] ] ) ) {
					$section_class = self::$registered_section_types[ $args['type'] ];
				} elseif ( in_array( $args['type'], self::$registered_section_types ) ) {
					$section_class = $args['type'];
				}
			}

			/**
			 * @var $section WP_Fields_API_Section
			 */
			$section = new $section_class( $object_type, $id, $args );
		}

		return $section;

	}

	/**
	 * Remove a section.
	 *
	 * @access public
	 *
	 * @param string $object_type Object type, set true to remove all sections.
	 * @param string $id          Section ID to remove, set true to remove all sections from an object.
	 * @param string $object_subtype Object subtype (for post types and taxonomies), set true to remove to all objects from an object type.
	 */
	public function remove_section( $object_type, $id, $object_subtype = null ) {

		if ( true === $object_type ) {
			// Remove all sections
			self::$sections = array();
		} elseif ( true === $object_subtype ) {
			// Remove all sections for an object type
			if ( isset( self::$sections[ $object_type ] ) ) {
				unset( self::$sections[ $object_type ] );
			}
		} else {
			if ( empty( $object_subtype ) && ! empty( $object_type ) ) {
				$object_subtype = '_' . $object_type; // Default to _object_type for internal handling
			}

			if ( true === $id && null !== $object_subtype ) {
				// Remove all sections for an object type
				if ( isset( self::$sections[ $object_type ][ $object_subtype ] ) ) {
					unset( self::$sections[ $object_type ][ $object_subtype ] );
				}
			} elseif ( isset( self::$sections[ $object_type ][ $object_subtype ][ $id ] ) ) {
				// Remove section from object type and name
				unset( self::$sections[ $object_type ][ $object_subtype ][ $id ] );
			}
		}

	}

	/**
	 * Register a section type.
	 *
	 * @access public
	 *
	 * @see    WP_Fields_API_Section
	 *
	 * @param string $type          Section type ID.
	 * @param string $section_class Name of a custom section which is a subclass of WP_Fields_API_Section.
	 */
	public function register_section_type( $type, $section_class = null ) {

		if ( null === $section_class ) {
			$section_class = $type;
		}

		self::$registered_section_types[ $type ] = $section_class;

	}

	/**
	 * Render JS templates for all registered section types.
	 *
	 * @access public
	 */
	public function render_section_templates() {

		/**
		 * @var $section WP_Fields_API_Section
		 */
		foreach ( self::$registered_control_types as $section_type => $section_class ) {
			$section = $this->setup_section( null, 'temp', null, array( 'type' => $section_type ) );

			//$section->print_template();
		}

	}

	/**
	 * Get the registered fields.
	 *
	 * @access public
	 *
	 * @param string $object_type Object type.
	 * @param string $object_subtype Object subtype (for post types and taxonomies).
	 *
	 * @return WP_Fields_API_Field[]
	 */
	public function get_fields( $object_type = null, $object_subtype = null ) {

		$primary_object_subtype = '_' . $object_type;

		// Default to _object_type for internal handling
		if ( empty( $object_subtype ) && ! empty( $object_type ) ) {
			$object_subtype = $primary_object_subtype;
		}

		$fields = array();

		if ( null === $object_type ) {
			// Late init
			foreach ( self::$fields as $object_type => $object_subtypes ) {
				foreach ( $object_subtypes as $object_subtype => $fields ) {
					$this->get_fields( $object_type, $object_subtype );
				}
			}

			$fields = self::$fields;
		} elseif ( isset( self::$fields[ $object_type ][ $object_subtype ] ) ) {
			// Late init
			foreach ( self::$fields[ $object_type ][ $object_subtype ] as $id => $field ) {
				// Late init
				self::$fields[ $object_type ][ $object_subtype ][ $id ] = $this->setup_field( $object_type, $id, $object_subtype, $field );
			}

			$fields = self::$fields[ $object_type ][ $object_subtype ];

			// Object subtype inheritance for getting data that covers all Object subtypes
			if ( $primary_object_subtype !== $object_subtype ) {
				$object_fields = $this->get_fields( $object_type, $primary_object_subtype );

				if ( $object_fields ) {
					$fields = array_merge( $fields, $object_fields );
				}
			}
		} elseif ( true === $object_subtype ) {
			// Get all fields

			// Late init
			foreach ( self::$fields[ $object_type ] as $object_subtype => $object_fields ) {
				$object_fields = $this->get_fields( $object_type, $object_subtype );

				if ( $object_fields ) {
					$fields = array_merge( $fields, array_values( $object_fields ) );
				}
			}
		} elseif ( $primary_object_subtype !== $object_subtype ) {
			// Object subtype inheritance for getting data that covers all Object subtypes
			$fields = $this->get_fields( $object_type, $primary_object_subtype );
		}

		return $fields;

	}

	/**
	 * Add a field.
	 *
	 * @access public
	 *
	 * @param string                     $object_type Object type.
	 * @param WP_Fields_API_Field|string $id          Fields API Field object, or ID.
	 * @param string                     $object_subtype Object subtype (for post types and taxonomies).
	 * @param array                      $args        Field arguments; passed to WP_Fields_API_Field
	 *                                                constructor.
	 *
	 * @return bool|WP_Error True on success, or error
	 */
	public function add_field( $object_type, $id, $object_subtype = null, $args = array() ) {

		if ( empty( $id ) && empty( $args ) ) {
			return new WP_Error( '', __( 'ID is required.', 'fields-api' ) );
		}

		$control = array();

		if ( is_a( $id, 'WP_Fields_API_Field' ) ) {
			$field = $id;

			$id = $field->id;
		} else {
			// Save for late init
			$field = $args;

			if ( isset( $field['control'] ) ) {
				$control = $field['control'];

				// Remove from field args
				unset( $field['control'] );
			}
		}

		if ( empty( $object_subtype ) && ! empty( $object_type ) ) {
			$object_subtype = '_' . $object_type; // Default to _object_type for internal handling
		}

		if ( ! isset( self::$fields[ $object_type ] ) ) {
			self::$fields[ $object_type ] = array();
		}

		if ( ! isset( self::$fields[ $object_type ][ $object_subtype ] ) ) {
			self::$fields[ $object_type ][ $object_subtype ] = array();
		}

		// @todo Remove this when done testing
		if ( defined( 'WP_FIELDS_API_TESTING' ) && WP_FIELDS_API_TESTING && ! empty( $_GET['no-fields-api-late-init'] ) ) {
			$field = $this->setup_field( $object_type, $id, $object_subtype, $field );
		}

		if ( isset( self::$fields[ $object_type ][ $object_subtype ][ $id ] ) ) {
			return new WP_Error( '', __( 'Field already exists.', 'fields-api' ) );
		}

		self::$fields[ $object_type ][ $object_subtype ][ $id ] = $field;

		// Control handling
		if ( ! empty( $control ) ) {
			// Generate Control ID if not set
			if ( empty( $control['id'] ) ) {
				$control['id'] = $id;
			}

			// Get Control ID
			$control_id = $control['id'];

			// Remove ID from control args
			unset( $control['id'] );

			// Add field
			$control['field'] = $id;

			// Add control for field
			$this->add_control( $object_type, $control_id, $object_subtype, $control );
		}

		$this->register_meta_integration( $object_type, $id, $field, $object_subtype );

		return true;

	}

	/**
	 * Register meta integration for register_meta and REST API
	 *
	 * @param string                    $object_type Object type
	 * @param string                    $id          Field ID
	 * @param array|WP_Fields_API_Field $field       Field object or options array
	 * @param string|null               $object_subtype Object subtype
	 */
	public function register_meta_integration( $object_type, $id, $field, $object_subtype = null ) {

		// Meta types call register_meta() and register_rest_field() for their fields
		if ( in_array( $object_type, array( 'post', 'term', 'user', 'comment' ) ) && ! $this->get_field_arg( $field, 'internal' ) ) {
			// Set callbacks
			$sanitize_callback = array( $this, 'register_meta_sanitize_callback' );
			$auth_callback = $this->get_field_arg( $field, 'meta_auth_callback' );

			register_meta( $object_type, $id, $sanitize_callback, $auth_callback );

			if ( function_exists( 'register_rest_field' ) && $this->get_field_arg( $field, 'show_in_rest' ) ) {
				$rest_field_args = array(
					'get_callback'    => $this->get_field_arg( $field, 'rest_get_callback' ),
					'update_callback' => $this->get_field_arg( $field, 'rest_update_callback' ),
					'schema'          => $this->get_field_arg( $field, 'rest_schema_callback' ),
					'type'            => $this->get_field_arg( $field, 'rest_field_type' ),
					'description'     => $this->get_field_arg( $field, 'rest_field_description' ),
				);

				register_rest_field( $object_type, $id, $rest_field_args );
			}
		}

	}

	/**
	 * Retrieve a field.
	 *
	 * @access public
	 *
	 * @param string $object_type Object type.
	 * @param string $id          Field ID.
	 * @param string $object_subtype Object subtype (for post types and taxonomies).
	 *
	 * @return WP_Fields_API_Field|null
	 */
	public function get_field( $object_type, $id, $object_subtype = null ) {

		if ( is_a( $id, 'WP_Fields_API_Field' ) ) {
			return $id;
		}

		$primary_object_subtype = '_' . $object_type;

		// Default to _object_type for internal handling
		if ( empty( $object_subtype ) && ! empty( $object_type ) ) {
			$object_subtype = $primary_object_subtype;
		}

		$field = null;

		if ( isset( self::$fields[ $object_type ][ $object_subtype ][ $id ] ) ) {
			// Late init
			self::$fields[ $object_type ][ $object_subtype ][ $id ] = $this->setup_field( $object_type, $id, $object_subtype, self::$fields[ $object_type ][ $object_subtype ][ $id ] );

			$field = self::$fields[ $object_type ][ $object_subtype ][ $id ];
		} elseif ( $primary_object_subtype !== $object_subtype ) {
			// Object subtype inheritance for getting data that covers all Object subtypes
			$field = $this->get_field( $object_type, $id, $primary_object_subtype );
		}

		return $field;

	}

	/**
	 * Setup the field.
	 *
	 * @access public
	 *
	 * @param string $object_type Object type.
	 * @param string $id          ID of the field.
	 * @param string $object_subtype Object subtype (for post types and taxonomies).
	 * @param array  $args        Field arguments.
	 *
	 * @return WP_Fields_API_Field|null $field The field object.
	 */
	public function setup_field( $object_type, $id, $object_subtype = null, $args = null ) {

		$field = null;

		$field_class = 'WP_Fields_API_Field';

		if ( is_a( $args, $field_class ) ) {
			$field = $args;
		} elseif ( is_array( $args ) ) {
			$args['object_subtype'] = $object_subtype;

			if ( ! empty( $args['type'] ) ) {
				if ( ! empty( self::$registered_field_types[ $args['type'] ] ) ) {
					$field_class = self::$registered_field_types[ $args['type'] ];
				} elseif ( in_array( $args['type'], self::$registered_field_types ) ) {
					$field_class = $args['type'];
				}
			}

			/**
			 * @var $field WP_Fields_API_Field
			 */
			$field = new $field_class( $object_type, $id, $args );
		}

		return $field;

	}

	/**
	 * Remove a field.
	 *
	 * @access public
	 *
	 * @param string $object_type Object type, set true to remove all fields.
	 * @param string $id          Field ID to remove, set true to remove all fields from an object.
	 * @param string $object_subtype Object subtype (for post types and taxonomies), set true to remove to all objects from an object type.
	 */
	public function remove_field( $object_type, $id, $object_subtype = null ) {

		if ( true === $object_type ) {
			// Remove all fields
			self::$fields = array();
		} elseif ( true === $object_subtype ) {
			// Remove all fields for an object type
			if ( isset( self::$fields[ $object_type ] ) ) {
				unset( self::$fields[ $object_type ] );
			}
		} else {
			if ( empty( $object_subtype ) && ! empty( $object_type ) ) {
				$object_subtype = '_' . $object_type; // Default to _object_type for internal handling
			}

			if ( true === $id && null !== $object_subtype ) {
				// Remove all fields for an object type
				if ( isset( self::$fields[ $object_type ][ $object_subtype ] ) ) {
					unset( self::$fields[ $object_type ][ $object_subtype ] );
				}
			} elseif ( isset( self::$fields[ $object_type ][ $object_subtype ][ $id ] ) ) {
				// Remove field from object type and name
				unset( self::$fields[ $object_type ][ $object_subtype ][ $id ] );
			}
		}

	}

	/**
	 * Register a field type.
	 *
	 * @access public
	 *
	 * @see    WP_Fields_API_Field
	 *
	 * @param string $type         Field type ID.
	 * @param string $field_class  Name of a custom field type which is a subclass of WP_Fields_API_Field.
	 */
	public function register_field_type( $type, $field_class = null ) {

		if ( null === $field_class ) {
			$field_class = $type;
		}

		self::$registered_field_types[ $type ] = $field_class;

	}

	/**
	 * Get the registered controls.
	 *
	 * @access public
	 *
	 * @param string                       $object_type Object type.
	 * @param string                       $object_subtype Object subtype (for post types and taxonomies).
	 * @param string|WP_Fields_API_Section $section     Section ID.
	 *
	 * @return WP_Fields_API_Control[]
	 */
	public function get_controls( $object_type = null, $object_subtype = null, $section = null ) {

		$primary_object_subtype = '_' . $object_type;

		// Default to _object_type for internal handling
		if ( empty( $object_subtype ) && ! empty( $object_type ) ) {
			$object_subtype = $primary_object_subtype;
		}

		$controls = array();

		$section_id = null;

		if ( $section ) {
			$section_id = $section;

			if ( is_object( $section ) ) {
				$section_id = $section->id;
			}
		}

		if ( null === $object_type ) {
			// Late init
			foreach ( self::$controls as $object_type => $object_subtypes ) {
				foreach ( $object_subtypes as $object_subtype => $controls ) {
					$this->get_controls( $object_type, $object_subtype );
				}
			}

			$controls = self::$controls;

			// Get only controls for a specific section
			if ( $section_id ) {
				$section_controls = array();

				/**
				 * @var $control WP_Fields_API_Control
				 */
				foreach ( $controls as $object_type => $object_subtypes ) {
					foreach ( $object_subtypes as $object_subtype => $object_controls ) {
						foreach ( $object_controls as $id => $control ) {
							$control_section = $control->get_section();

							if ( $control_section && $section_id == $control_section->id ) {
								if ( ! isset( $section_controls[ $object_type ] ) ) {
									$section_controls[ $object_type ] = array();
								}

								if ( ! isset( $section_controls[ $object_type ][ $object_subtype ] ) ) {
									$section_controls[ $object_type ][ $object_subtype ] = array();
								}

								$section_controls[ $object_type ][ $object_subtype ][ $id ] = $control;
							}
						}
					}
				}

				$controls = $section_controls;
			}
		} elseif ( isset( self::$controls[ $object_type ][ $object_subtype ] ) ) {
			// Late init
			foreach ( self::$controls[ $object_type ][ $object_subtype ] as $id => $control ) {
				// Late init
				self::$controls[ $object_type ][ $object_subtype ][ $id ] = $this->setup_control( $object_type, $id, $object_subtype, $control );
			}

			$controls = self::$controls[ $object_type ][ $object_subtype ];

			// Object subtype inheritance for getting data that covers all Object subtypes
			if ( $primary_object_subtype !== $object_subtype ) {
				$object_controls = $this->get_controls( $object_type, $primary_object_subtype );

				if ( $object_controls ) {
					$controls = array_merge( $controls, $object_controls );
				}
			}

			// Get only controls for a specific section
			if ( $section_id ) {
				$section_controls = array();

				/**
				 * @var $control WP_Fields_API_Control
				 */
				foreach ( $controls as $id => $control ) {
					$control_section = $control->get_section();

					if ( $control_section && $section_id == $control_section->id ) {
						$section_controls[ $id ] = $control;
					}
				}

				$controls = $section_controls;
			}
		} elseif ( true === $object_subtype ) {
			// Get all fields

			// Late init
			foreach ( self::$controls[ $object_type ] as $object_subtype => $object_controls ) {
				$object_controls = $this->get_controls( $object_type, $object_subtype, $section );

				if ( $object_controls ) {
					$controls = array_merge( $controls, array_values( $object_controls ) );
				}
			}
		} elseif ( $primary_object_subtype !== $object_subtype ) {
			// Object subtype inheritance for getting data that covers all Object subtypes
			$controls = $this->get_controls( $object_type, $primary_object_subtype, $section );
		}

		return $controls;

	}

	/**
	 * Add a field control.
	 *
	 * @access public
	 *
	 * @param string                       $object_type Object type.
	 * @param WP_Fields_API_Control|string $id          Field Control object, or ID.
	 * @param string                       $object_subtype Object subtype (for post types and taxonomies).
	 * @param array                        $args        Control arguments; passed to WP_Fields_API_Control
	 *                                                  constructor.
	 *
	 * @return bool|WP_Error True on success, or error
	 */
	public function add_control( $object_type, $id, $object_subtype = null, $args = array() ) {

		if ( empty( $id ) && empty( $args ) ) {
			return new WP_Error( '', __( 'ID is required.', 'fields-api' ) );
		}

		$field = false;

		if ( is_a( $id, 'WP_Fields_API_Control' ) ) {
			$control = $id;

			$id = $control->id;
		} else {
			// Save for late init
			$control = $args;

			// Add a field automatically for every control unless it's referencing another field already
			if ( ! isset( $control['field'] ) || ( is_a( $control['field'], 'WP_Fields_API_Field' ) || is_array( $control['field'] ) ) ) {
				$field = null;

				if ( isset( $control['field'] ) ) {
					$field = $control['field'];
				}

				if ( is_a( $field, 'WP_Fields_API_Field' ) ) {
					/**
					 * @var $field WP_Fields_API_Field
					 */
					$control['field'] = $field->id;
				} elseif ( ! empty( $field['id'] ) ) {
					$control['field'] = $field['id'];
				} else {
					$control['field'] = $id;

					if ( ! is_array( $field ) ) {
						$field = array();
					}
				}
			}
		}

		if ( empty( $object_subtype ) && ! empty( $object_type ) ) {
			$object_subtype = '_' . $object_type; // Default to _object_type for internal handling
		}

		if ( ! isset( self::$controls[ $object_type ] ) ) {
			self::$controls[ $object_type ] = array();
		}

		if ( ! isset( self::$controls[ $object_type ][ $object_subtype ] ) ) {
			self::$controls[ $object_type ][ $object_subtype ] = array();
		}

		// @todo Remove this when done testing
		if ( defined( 'WP_FIELDS_API_TESTING' ) && WP_FIELDS_API_TESTING && ! empty( $_GET['no-fields-api-late-init'] ) ) {
			$control = $this->setup_control( $object_type, $id, $object_subtype, $control );
		}

		if ( isset( self::$controls[ $object_type ][ $object_subtype ][ $id ] ) ) {
			return new WP_Error( '', __( 'Control already exists.', 'fields-api' ) );
		}

		self::$controls[ $object_type ][ $object_subtype ][ $id ] = $control;

		// Field handling
		if ( is_array( $field ) ) {
			$field_id = $id;

			if ( ! empty( $field['id'] ) ) {
				// Get Field ID
				$field_id = $field['id'];

				// Field ID from field args
				unset( $field['id'] );
			}

			// Add field for control
			$this->add_field( $object_type, $field_id, $object_subtype, $field );
		}

		return true;

	}

	/**
	 * Retrieve a field control.
	 *
	 * @access public
	 *
	 * @param string $object_type Object type.
	 * @param string $id          ID of the control.
	 * @param string $object_subtype Object subtype (for post types and taxonomies).
	 *
	 * @return WP_Fields_API_Control|null $control The control object.
	 */
	public function get_control( $object_type, $id, $object_subtype = null ) {

		if ( is_a( $id, 'WP_Fields_API_Control' ) ) {
			return $id;
		}

		$primary_object_subtype = '_' . $object_type;

		// Default to _object_type for internal handling
		if ( empty( $object_subtype ) && ! empty( $object_type ) ) {
			$object_subtype = $primary_object_subtype;
		}

		$control = null;

		if ( isset( self::$controls[ $object_type ][ $object_subtype ][ $id ] ) ) {
			// Late init
			self::$controls[ $object_type ][ $object_subtype ][ $id ] = $this->setup_control( $object_type, $id, $object_subtype, self::$controls[ $object_type ][ $object_subtype ][ $id ] );

			$control = self::$controls[ $object_type ][ $object_subtype ][ $id ];
		} elseif ( $primary_object_subtype !== $object_subtype ) {
			// Object subtype inheritance for getting data that covers all Object subtypes
			$control = $this->get_control( $object_type, $id, $primary_object_subtype );
		}

		return $control;

	}

	/**
	 * Setup the field control.
	 *
	 * @access public
	 *
	 * @param string $object_type Object type.
	 * @param string $id          ID of the control.
	 * @param string $object_subtype Object subtype (for post types and taxonomies).
	 * @param array  $args        Control arguments.
	 *
	 * @return WP_Fields_API_Control|null $control The control object.
	 */
	public function setup_control( $object_type, $id, $object_subtype = null, $args = null ) {

		$control = null;

		$control_class = 'WP_Fields_API_Control';

		if ( is_a( $args, $control_class ) ) {
			$control = $args;
		} elseif ( is_array( $args ) ) {
			$args['object_subtype'] = $object_subtype;

			if ( ! empty( $args['type'] ) ) {
				if ( ! empty( self::$registered_control_types[ $args['type'] ] ) ) {
					$control_class = self::$registered_control_types[ $args['type'] ];
				} elseif ( in_array( $args['type'], self::$registered_control_types ) ) {
					$control_class = $args['type'];
				}
			}

			/**
			 * @var $control WP_Fields_API_Control
			 */
			$control = new $control_class( $object_type, $id, $args );
		}

		if ( $control ) {
			// Setup field
			$control->get_field();
		}

		return $control;

	}

	/**
	 * Remove a field control.
	 *
	 * @access public
	 *
	 * @param string $object_type Object type, set true to remove all controls.
	 * @param string $id          Control ID to remove, set true to remove all controls from an object.
	 * @param string $object_subtype Object subtype (for post types and taxonomies), set true to remove to all objects from an object type.
	 */
	public function remove_control( $object_type, $id, $object_subtype = null ) {

		if ( true === $object_type ) {
			// Remove all controls
			self::$controls = array();
		} elseif ( true === $object_subtype ) {
			// Remove all controls for an object type
			if ( isset( self::$controls[ $object_type ] ) ) {
				unset( self::$controls[ $object_type ] );
			}
		} else {
			if ( empty( $object_subtype ) && ! empty( $object_type ) ) {
				$object_subtype = '_' . $object_type; // Default to _object_type for internal handling
			}

			if ( true === $id && null !== $object_subtype ) {
				// Remove all controls for an object type
				if ( isset( self::$controls[ $object_type ][ $object_subtype ] ) ) {
					unset( self::$controls[ $object_type ][ $object_subtype ] );
				}
			} elseif ( isset( self::$controls[ $object_type ][ $object_subtype ][ $id ] ) ) {
				// Remove control from object type and name
				unset( self::$controls[ $object_type ][ $object_subtype ][ $id ] );
			}
		}

	}

	/**
	 * Register a field control type.
	 *
	 * @access public
	 *
	 * @see    WP_Fields_API_Control
	 *
	 * @param string $type          Control type ID.
	 * @param string $control_class Name of a custom control which is a subclass of WP_Fields_API_Control.
	 */
	public function register_control_type( $type, $control_class = null ) {

		if ( null === $control_class ) {
			$control_class = $type;
		}

		self::$registered_control_types[ $type ] = $control_class;

	}

	/**
	 * Render JS templates for all registered control types.
	 *
	 * @access public
	 */
	public function render_control_templates() {

		static $rendered;

		// Only render once
		if ( ! empty( $rendered ) ) {
			return;
		}

		/**
		 * @var $control WP_Fields_API_Control
		 */
		foreach ( self::$registered_control_types as $control_type => $control_class ) {
			$control = $this->setup_control( null, 'temp', null, array( 'type' => $control_type ) );

			$control->print_template();
		}

		$rendered = true;

	}

	/**
	 * Setup the datasource.
	 *
	 * @access public
	 *
	 * @param string $type Datasource type.
	 * @param array  $args Datasource arguments.
	 *
	 * @return WP_Fields_API_Control|null $control The control object.
	 */
	public function setup_datasource( $type, $args = null ) {

		$datasource = null;

		$datasource_class = 'WP_Fields_API_Datasource';

		if ( is_a( $args, $datasource_class ) ) {
			$datasource = $args;
		} else {
			if ( is_array( $args ) && ! empty( $args['type'] ) ) {
				$type = $args['type'];

				unset( $args['type'] );
			}

			if ( ! empty( self::$registered_datasources[ $type ] ) ) {
				$datasource_class = self::$registered_datasources[ $type ];
			} elseif ( in_array( $type, self::$registered_datasources ) ) {
				$datasource_class = $type;
			}

			/**
			 * @var $datasource WP_Fields_API_Datasource
			 */
			$datasource = new $datasource_class( $type, $args );
		}

		return $datasource;

	}

	/**
	 * Register a datasource type.
	 *
	 * @access public
	 *
	 * @see    WP_Fields_API_Datasource
	 *
	 * @param string $type             Datasource type ID.
	 * @param string $datasource_class Name of a custom datasource which is a subclass of WP_Fields_API_Datasource.
	 */
	public function register_datasource( $type, $datasource_class = null ) {

		if ( null === $datasource_class ) {
			$datasource_class = $type;
		}

		self::$registered_datasources[ $type ] = $datasource_class;

	}

	/**
	 * Helper function to compare two objects by priority, ensuring sort stability via instance_number.
	 *
	 * @access protected
	 *
	 * @param WP_Fields_API_Container $a Object A.
	 * @param WP_Fields_API_Container $b Object B.
	 *
	 * @return int
	 */
	public static function _cmp_priority( $a, $b ) {

		$compare = 0;

		if ( isset( $a->priority ) || isset( $b->priority ) ) {
			$priorities = array(
				'high'    => 0,
				'core'    => 100,
				'default' => 200,
				'low'     => 300,
			);

			// Set defaults
			$a_priority = $priorities['default'];
			$b_priority = $priorities['default'];

			if ( isset( $a->priority ) ) {
				$a_priority = $a->priority;
			}

			if ( isset( $b->priority ) ) {
				$b_priority = $b->priority;
			}

			// Convert string priority
			if ( ! is_int( $a_priority ) ) {
				if ( isset( $priorities[ $a_priority ] ) ) {
					$a_priority = $priorities[ $a_priority ];
				} else {
					$a_priority = $priorities['default'];
				}
			}

			// Convert string priority
			if ( ! is_int( $b_priority ) ) {
				if ( isset( $priorities[ $b_priority ] ) ) {
					$b_priority = $priorities[ $b_priority ];
				} else {
					$b_priority = $priorities['default'];
				}
			}

			// Priority integers
			$compare = $a_priority - $b_priority;

			// Tie breakers can use instance number
			if ( $a_priority === $b_priority && isset( $a->instance_number ) && isset( $b->instance_number ) ) {
				$compare = $a->instance_number - $b->instance_number;
			}
		}

		return $compare;

	}

	/**
	 * Register some default form and control types.
	 *
	 * @access public
	 */
	public function register_defaults() {

		/* Section Types */
		$this->register_section_type( 'meta-box', 'WP_Fields_API_Meta_Box_Section' );
		$this->register_section_type( 'meta-box-table', 'WP_Fields_API_Meta_Box_Table_Section' );
		$this->register_section_type( 'table', 'WP_Fields_API_Table_Section' );

		/* Control Types */
		$this->register_control_type( 'text', 'WP_Fields_API_Control' );
		$this->register_control_type( 'number', 'WP_Fields_API_Control' );
		$this->register_control_type( 'email', 'WP_Fields_API_Control' );
		$this->register_control_type( 'password', 'WP_Fields_API_Control' );
		$this->register_control_type( 'hidden', 'WP_Fields_API_Control' );
		$this->register_control_type( 'readonly', 'WP_Fields_API_Readonly_Control' );
		$this->register_control_type( 'textarea', 'WP_Fields_API_Textarea_Control' );
		$this->register_control_type( 'wysiwyg', 'WP_Fields_API_WYSIWYG_Control' );
		$this->register_control_type( 'checkbox', 'WP_Fields_API_Checkbox_Control' );
		$this->register_control_type( 'multi-checkbox', 'WP_Fields_API_Multi_Checkbox_Control' );
		$this->register_control_type( 'radio', 'WP_Fields_API_Radio_Control' );
		//$this->register_control_type( 'radio-multi-label', 'WP_Fields_API_Radio_Multi_Label_Control' ); // @todo Revisit
		$this->register_control_type( 'select', 'WP_Fields_API_Select_Control' );
		$this->register_control_type( 'color', 'WP_Fields_API_Color_Control' );
		$this->register_control_type( 'media', 'WP_Fields_API_Media_Control' );
		$this->register_control_type( 'media-file', 'WP_Fields_API_Media_File_Control' );
		$this->register_control_type( 'number-inline-desc', 'WP_Fields_API_Number_Inline_Description_Control' ); // @todo Revisit

		/* Datasources */
		$this->register_datasource( 'post-format', 'WP_Fields_API_Datasource' );
		$this->register_datasource( 'post-type', 'WP_Fields_API_Datasource' );
		$this->register_datasource( 'post-status', 'WP_Fields_API_Datasource' );
		$this->register_datasource( 'page-status', 'WP_Fields_API_Datasource' );
		$this->register_datasource( 'user-role', 'WP_Fields_API_Datasource' );
		$this->register_datasource( 'admin-color-scheme', 'WP_Fields_API_Admin_Color_Scheme_Datasource' );
		$this->register_datasource( 'comment', 'WP_Fields_API_Comment_Datasource' );
		$this->register_datasource( 'post', 'WP_Fields_API_Post_Datasource' );
		$this->register_datasource( 'page', 'WP_Fields_API_Page_Datasource' );
		$this->register_datasource( 'term', 'WP_Fields_API_Term_Datasource' );
		$this->register_datasource( 'user', 'WP_Fields_API_User_Datasource' );

		/**
		 * Fires once WordPress has loaded, allowing control types to be registered.
		 *
		 * @param WP_Fields_API $this WP_Fields_API instance.
		 */
		do_action( 'fields_register_controls', $this );

	}

	/**
	 * Hook into register_meta() sanitize callback and call field
	 *
	 * @param mixed  $meta_value Meta value to sanitize.
	 * @param string $meta_key   Meta key.
	 * @param string $meta_type  Meta type.
	 *
	 * @return mixed
	 */
	public function register_meta_sanitize_callback( $meta_value, $meta_key, $meta_type ) {

		$field = $this->get_field( $meta_type, $meta_key );

		if ( $field ) {
			$meta_value = $field->sanitize( $meta_value );
		}

		return $meta_value;

	}

	/**
	 * Get Fields API stats
	 *
	 * @param null|string $object_type Object type
	 * @param null|string $object_subtype Object subtype
	 *
	 * @return array
	 */
	public function get_stats( $object_type = null, $object_subtype = null ) {

		$stats = array(
			'forms'      => 0,
			'form-types' => 0,

			'sections'      => 0,
			'section-types' => 0,

			'controls'      => 0,
			'control-types' => 0,

			'fields'      => 0,
			'field-types' => 0,

			'object-types' => 0,
			'object-names' => 0,

			'all-objects' => 0,
		);

		$stats['form-types'] = count( self::$registered_form_types );
		$stats['section-types'] = count( self::$registered_section_types );
		$stats['control-types'] = count( self::$registered_control_types );
		$stats['field-types'] = count( self::$registered_field_types );

		$object_types = array();
		$object_subtypes = array();

		if ( empty( $object_type ) ) {
			foreach ( self::$forms as $object_type => $object_subtype_forms ) {
				foreach ( $object_subtype_forms as $form_object_subtype => $objects ) {
					if ( $object_subtype && $object_subtype !== $form_object_subtype ) {
						continue;
					}

					$object_subtypes[] = $object_subtype;

					$stats['forms'] += count( $objects );

					if ( $object_subtype ) {
						$object_types[] = $object_type;
					}
				}
			}

			foreach ( self::$sections as $object_type => $object_subtype_forms ) {
				foreach ( $object_subtype_forms as $form_object_subtype => $objects ) {
					if ( $object_subtype && $object_subtype !== $form_object_subtype ) {
						continue;
					}

					$object_subtypes[] = $object_subtype;

					$stats['sections'] += count( $objects );

					if ( $object_subtype ) {
						$object_types[] = $object_type;
					}
				}
			}

			foreach ( self::$controls as $object_type => $object_subtype_forms ) {
				foreach ( $object_subtype_forms as $form_object_subtype => $objects ) {
					if ( $object_subtype && $object_subtype !== $form_object_subtype ) {
						continue;
					}

					$object_subtypes[] = $form_object_subtype;

					$stats['controls'] += count( $objects );

					if ( $object_subtype ) {
						$object_types[] = $object_type;
					}
				}
			}

			foreach ( self::$fields as $object_type => $object_subtype_forms ) {
				foreach ( $object_subtype_forms as $form_object_subtype => $objects ) {
					if ( $object_subtype && $object_subtype !== $form_object_subtype ) {
						continue;
					}

					$object_subtypes[] = $form_object_subtype;

					$stats['fields'] += count( $objects );

					if ( $object_subtype ) {
						$object_types[] = $object_type;
					}
				}
			}
		} else {
			if ( ! empty( self::$forms[ $object_type ] ) ) {
				$object_subtype_forms = self::$forms[ $object_type ];

				foreach ( $object_subtype_forms as $form_object_subtype => $objects ) {
					if ( $object_subtype && $object_subtype !== $form_object_subtype ) {
						continue;
					}

					$object_subtypes[] = $form_object_subtype;

					$stats['forms'] += count( $objects );

					if ( $object_subtype ) {
						$object_types[] = $object_type;
					}
				}
			}

			if ( ! empty( self::$sections[ $object_type ] ) ) {
				$object_subtype_forms = self::$sections[ $object_type ];

				foreach ( $object_subtype_forms as $form_object_subtype => $objects ) {
					if ( $object_subtype && $object_subtype !== $form_object_subtype ) {
						continue;
					}

					$object_subtypes[] = $form_object_subtype;

					$stats['sections'] += count( $objects );

					if ( $object_subtype ) {
						$object_types[] = $object_type;
					}
				}
			}

			if ( ! empty( self::$controls[ $object_type ] ) ) {
				$object_subtype_forms = self::$controls[ $object_type ];

				foreach ( $object_subtype_forms as $form_object_subtype => $objects ) {
					if ( $object_subtype && $object_subtype !== $form_object_subtype ) {
						continue;
					}

					$object_subtypes[] = $form_object_subtype;

					$stats['controls'] += count( $objects );

					if ( $object_subtype ) {
						$object_types[] = $object_type;
					}
				}
			}

			if ( ! empty( self::$fields[ $object_type ] ) ) {
				$object_subtype_forms = self::$fields[ $object_type ];

				foreach ( $object_subtype_forms as $form_object_subtype => $objects ) {
					if ( $object_subtype && $object_subtype !== $form_object_subtype ) {
						continue;
					}

					$object_subtypes[] = $form_object_subtype;

					$stats['fields'] += count( $objects );

					if ( $object_subtype ) {
						$object_types[] = $object_type;
					}
				}
			}
		}

		if ( ! $object_subtype ) {
			$object_types = array_merge( $object_types, array_keys( self::$forms ), array_keys( self::$sections ), array_keys( self::$controls ), array_keys( self::$fields ) );
		}

		$object_types = array_unique( $object_types );
		$object_types = array_filter( $object_types );
		$stats['object-types'] = count( $object_types );

		$object_subtypes = array_unique( $object_subtypes );
		$object_subtypes = array_filter( $object_subtypes );
		$stats['object-names'] = count( $object_subtypes );

		$stats['all-objects'] += $stats['forms'];
		$stats['all-objects'] += $stats['sections'];
		$stats['all-objects'] += $stats['controls'];
		$stats['all-objects'] += $stats['fields'];

		return $stats;

	}

	/**
	 * Get argument from field array or object
	 *
	 * @param array|object $field
	 * @param string $arg
	 *
	 * @return null|mixed
	 */
	public function get_field_arg( $field, $arg ) {

		$value = null;

		if ( is_array( $field ) && isset( $field[ $arg ] ) ) {
			$value = $field[ $arg ];
		} elseif ( is_object( $field ) && isset( $field->{$arg} ) ) {
			$value = $field->{$arg};
		}

		return $value;

	}

}