<?php
/**
 * AMD Require Handler
 *
 * ### Actions
 *
 * - ud:requires
 * - ud:requires:{name}
 *
 * ### Filters
 *
 * - ud:requires:headers
 * - ud:requires:config
 *
 * @author potanin@UD
 * @version 0.1.0
 * @module UsabilityDynamics
 */
namespace UsabilityDynamics {

  if( !class_exists( 'UsabilityDynamics\Requires' ) ) {

    /**
     * Class Requires
     *
     * @class Requires
     */
    class Requires extends \UsabilityDynamics\Utility {

      /**
       * Instance Name.
       *
       * @public
       * @property $name
       * @type {Object}
       */
      public $name = null;

      /**
       * Instance Path.
       *
       * @public
       * @property $path
       * @type {Object}
       */
      public $path = null;
      
      /**
       * Public View.
       *
       * @public
       * @property $public
       * @type {Object}
       */      
      public $public = false;

      /**
       * Library Server.
       *
       * @public
       * @property $server
       * @type {Object}
       */
      public $server = '//cdn.udx.io/require.js';

      /**
       * Instance Settings.
       *
       * @public
       * @property $_settings
       * @type {Object}
       */
      public $_settings = false;

      /**
       * Rendered Flag.
       *
       * @public
       * @property $_rendered
       * @type {Object}
       */
      public static $_rendered = false;

      /**
       * Singleton Instance Reference.
       *
       * @public
       * @static
       * @property $instance
       * @type {Object}
       */
      public static $instance = false;

      /**
       * Constructor.
       *
       * args.path - relative path to home to serve data-main
       *
       * @todo Add output cleaning to remove any errors or warnigns.
       * @todo Add logic to not serve JS until template_redirect action to let JS deps register.
       * @todo Instane / settings should probably be based on scope since only a single requires.js instance can be handled per view.
       *
       * @param array $_atts
       * @internal param array|mixed $args .path
       */
      function __construct( $_atts = array() ) {

        // Return Singleton Instance.
        if( self::$instance ) {
          //return self::$instance;
        }

        // Save Instance.
        //self::$instance = &$this;

        $args = (object) shortcode_atts( array(
          'name'  => 'app.state',
          'path' => '/',
          'scope'  => array( 'public' ),
          'debug' => defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? true : false
        ), $_atts );

        // Force Array.
        $this->scope = (array) $args->scope;

        // Set Instance Properties.
        $this->name = self::create_slug( $args->name  ? $args->name : str_replace( '.js', '', basename( $args->path || '/app.state.js' ) ), array( 'separator' => '-' ) );
        $this->path = ( $args->path ? $args->path : '/scripts/' ) . $this->name . '.js' ;
        $this->debug = $args->debug ?  $args->debug : false;

        // Create Runtime Settings Instance.
        $this->_settings = new \UsabilityDynamics\Settings(array(
          'id' => $this->name,
          'namespace' => 'UsabilityDynamics\Requires',
          'runtime' => true
        ));

        if( in_array( 'public', $this->scope ) ) {
          $this->public = true;
        }

        if( in_array( 'preview', $this->scope ) ) {
          $this->preview = true;
        }

        if( in_array( 'backend', $this->scope ) ) {
          $this->backend = true;
        }

        if( in_array( 'login', $this->scope ) ) {
          $this->login = true;
        }

        if( in_array( 'customizer', $this->scope ) ) {
          $this->customizer = true;
        }

        $this->context = self::create_slug( $args->name, array( 'separator' => '_' ) );

        // Bind Actions.
        add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) );

        add_action( 'wp_head', array( &$this, '_render_tag' ), 100 );
        add_action( 'wp_footer', array( &$this, '_render_tag' ), 100 );
        add_action( 'admin_print_scripts', array( &$this, '_render_tag' ), 100 );
        add_action( 'customize_controls_print_scripts', array( &$this, '_render_tag' ), 100 );
        add_action( 'customize_controls_print_footer_scripts', array( &$this, '_render_tag' ), 100 );
        add_action( 'customize_preview_init', array( &$this, '_render_tag' ), 100 );
        add_action( 'login_enqueue_scripts', array( &$this, '_render_tag' ), 100 );

        // Serve Scripts.
        add_action( 'admin_init', array( &$this, '_render_script' ) );
        add_action( 'template_redirect', array( &$this, '_render_script' ) );

        // @chainable.
        return $this;

      }

      /**
       * Add Library / Dependencies
       *
       * @param string $lib
       * @param array  $deps
       */
      public function add( $lib = '', $deps = array() ) {

      }

      /**
       * General Admin Scripts.
       *
       * @todo The frontent preview is using incorrect action; "customize_preview_init" is triggered on body not in head.
       *
       * @action login_enqueue_scripts - Login page header scripts.
       * @action customize_controls_print_scripts - Customizer Interface scripts.
       * @action customize_controls_print_footer_scripts - Customizer Interface footer scripts.
       * @action admin_print_scripts - General administrative scripts.
       * @action wp_head - Frontend header scripts.
       * @action wp_footer - Frontend header scripts.
       */
      public function _render_tag() {

        // Already Rendered.
        if( self::$_rendered ) {
          return;
        }

        // Standard Admin.
        if( current_filter() == 'admin_print_scripts' && $this->backend ) {
          echo '<script data-scope="admin" data-name="' . $this->name . '" data-main="' . $this->path . '" src="' . $this->server . '"></script>' . "\n";
          self::$_rendered = true;

        }

        // Admin Customizer Controls.
        if( current_filter() == 'customize_controls_print_scripts' && $this->customizer ) {
          echo '<script data-scope="customizer" data-name="' . $this->name . '" data-main="' . $this->path . '" src="' . $this->server . '"></script>' . "\n";
          self::$_rendered = true;
        }

        // Login Scripts.
        if( current_filter() == 'login_enqueue_scripts' && $this->login ) {
          echo '<script data-scope="login" data-name="' . $this->name . '" data-main="' . $this->path . '" src="' . $this->server . '"></script>' . "\n";
          self::$_rendered = true;
        }

        // Public Frontend.
        if( current_filter() == 'wp_head' && $this->public ) {
          echo '<script data-scope="public" data-name="' . $this->name . '" data-main="' . $this->path . '" src="' . $this->server . '"></script>' . "\n";
          self::$_rendered = true;
        }

        // Frontned Customization Preview.
        if( current_filter() == 'customize_preview_init' && $this->preview ) {
          echo '<script data-scope="preview" data-name="' . $this->name . '" data-main="' . $this->path . '" src="' . $this->server . '"></script>' . "\n";
          self::$_rendered = true;
        }

      }

      /**
       * Serve Scripts.
       *
       * @action template_redirect
       * @action admin_init
       */
      function _render_script() {

        if( isset( $_SERVER[ 'REDIRECT_URL' ] ) && $_SERVER[ 'REDIRECT_URL' ] === $this->path ) {

          // Generate Action Handler.
          do_action( 'ud:requires', $this );

          // Instance Action Handler.
          do_action( 'ud:requires:' . $this->name );

          // Set Headers.
          add_filter( 'nocache_headers', function( $headers ) {

            if( !$headers ) {
              $headers = array();
            }

            // JavaScript Asset.
            $headers[ 'Content-Type' ] = 'application/javascript; charset=' . get_bloginfo( 'charset' );

            // limit rendering of pages to same origin iframes
            $headers[ 'X-Frame-Options' ] = 'SAMEORIGIN';

            // Varnish Support.
            $headers[ 'Vary' ] = 'Accept-Encoding';

            $headers = apply_filters( 'ud:requires:headers', $headers );

            return $headers;

          });

          // Standard Headers.
          nocache_headers();

          // WordPress will try to make it 404.
          http_response_code( 200 );

          $_config = apply_filters( 'ud:requires:config', array(
            // 'baseUrl' => home_url(),
            // 'packages' => [],
            'context' => $this->context,
            'paths' => array(
              'ajax' => esc_url( admin_url( 'admin-ajax.php' ) ),
              'home' => esc_url( home_url( '/' ) ),
              'login' => wp_login_url(),
              "jquery" => ""
            ),
            'browser'  => array(
              'mobile' => wp_is_mobile(),
              'ios' => wp_is_mobile() && preg_match( '/iPad|iPod|iPhone/', $_SERVER['HTTP_USER_AGENT'] ),
            )
          ));

          $_output = array(
            '/** ---- ' . $this->name . ' ----- */',
            'require(' . json_encode( (array) $_config, JSON_FORCE_OBJECT ) . ');',
          );

          if( $this->debug ) {
            $_output[] = 'console.log( "ud.requires", "' . $this->name .'");';
            $_output[] = 'console.log( "ud.requires.config", ' . json_encode( (array) $_config, JSON_FORCE_OBJECT ) . ');';
          }

          do_action( 'ud:requires:output', $_output );

          // Clean array and uutput JavaScript.
          die( implode( "\n", array_filter( (array) $_output ) ) );

        }

      }

      /**
       * Error Handler
       *
       * @param $errno
       * @param $errstr
       * @param $errfile
       * @param $errline
       *
       * @param $errfile
       *
       * @return bool
       */
      public static function error_handler( $errno = null, $errstr = '', $errfile = null, $errline = null ) {

        // This error code is not included in error_reporting
        if( !( error_reporting() & $errno ) ) {
          return;
        }

        switch( $errno ) {

          // Fatal
          case E_ERROR:
          case E_CORE_ERROR:
          case E_RECOVERABLE_ERROR:
          case E_COMPILE_ERROR:
          case E_USER_ERROR:
            wp_die( "<h1>Website Temporarily Unavailable</h1><p>We apologize for the inconvenience and will return shortly.</p>" );
            break;

          // Do Nothing
          case E_WARNING:
          case E_USER_NOTICE:
            return true;
            break;

          // No Idea.
          default:
            return;
            // wp_die( "<h1>Website Temporarily Unavailable</h1><p>We apologize for the inconvenience and will return shortly.</p>" );
            break;
        }

        return true;

      }

      /**
       * Get Setting.
       *
       * @method get
       *
       * @for Requires
       * @author potanin@UD
       * @since 0.1.1
       */
      public static function get( $key, $default = null ) {
        return self::$instance->_settings ? self::$instance->_settings->get( $key, $default ) : null;
      }

      /**
       * Set Setting.
       *
       * @usage
       *
       * @method get
       * @for Requires
       *
       * @author potanin@UD
       * @since 0.1.1
       */
      public static function set( $key, $value = null ) {
        return self::$instance->_settings ? self::$instance->_settings->set( $key, $value ) : null;
      }

      /**
       * Get the Cluster Singleton
       *
       * Concept based on the CodeIgniter get_instance() concept.
       *
       * @static
       * @return object
       *
       * @method get_instance
       * @for Requires
       */
      public static function &get_instance() {
        return self::$instance;
      }

    }

  }

}