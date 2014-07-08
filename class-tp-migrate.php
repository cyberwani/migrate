<?php
/**
 * Plugin Name: Data migration
 * Description: Basic data migration plugin
 */

class TP_Migrate {
	var $_db_name = '_migrate_projectname';
	var $_db;

	function __construct() {
		/**
		 * Admin page
		 */
		add_action( 'admin_menu', array( $this, 'add_admin_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		/**
		 * Etc
		 */
		$this->include_files();
	}

	/**
	 * Convenience functions
	 */
	
	/**
	 * Set terms
	 * 
	 * @param int $post_id 
	 * @param array|string $terms
	 * @param string $taxonomy
	 */
	function set_terms( $post_id, $terms, $taxonomy ) {
		if( ! is_array( $terms ) )
			$terms = array( $terms );

		foreach( $terms as $term ) {
			if( 0 == strlen( $term ) ) continue;

			if( ! $term_id = term_exists( $term, $taxonomy ) ) {
				$term_id = wp_insert_term( $term, $taxonomy );
			}

			wp_set_object_terms( $post_id, (int) $term_id['term_id'], $taxonomy );
		}
	}

	/**
	 * Get a post by old DB ID
	 *
	 * @param int $reference_id Reference ID from old DB
	 * @param string $reference_table The table the ID belongs to
	 * @return object|bool(false)
	 */
	function get_post( $reference_id, $reference_table ) {
		if( ! $reference_id )
			return false;

		$posts = get_posts( array(
			'post_type'     => 'any',
			'post_status'   => 'any',
			'numberposts'   => 1,
			'meta_query'    => array(
				array(
					'key'   => '_reference_id',
					'value' => $reference_id
				),
				array(
					'key'   => '_reference_table',
					'value' => $reference_table
				),
			),
		) );

		if( isset( $posts[0] ) )
			return $posts[0];

		return false;
	}

	/**
	 * Admin panel
	 */

	/**
	 * Setup migration database
	 */
	function _connect() {
		$this->_db = new wpdb( 'root', 'root', $this->_db_name, 'localhost' );
	}

	/**
	 * Add admin page
	 */
	function add_admin_page() {
		add_menu_page( __( 'Migrate', 'tp' ), __( 'Migrate', 'tp' ), 'publish_posts', 'tp-migrate', array( $this, 'admin_page') );
	}

	/**
	 * Show admin page
	 */
	function admin_page() {
		?>
			<div class="wrap tp-migrate">
				<div id="icon-tools" class="icon32"><br /></div>
				<h2><?php _e( 'Migrate data', 'tp' ); ?></h2>
				
				<p class="description">
					<?php printf( __( 'Data is being migrated from %1$s.', 'tp' ), '<code>' . $this->_db_name . '</code>' ); ?>
				</p>

				<div class="tp-migrate-container">
					<form>
						<p>
							<input type="button" class="button-primary tp-migrate-start" value="<?php _e( 'Start migration', 'tp' ); ?>" />
						</p>
					</form>

					<div class="progress">
						<p>
							<progress></progress>
							<span class="description tp-migrate-status"></span>
						</p>
					</div>
				</div>
			</div>
		<?php
	}

	/**
	 * Add scripts and styles
	 */
	function enqueue_scripts() {
		wp_enqueue_script( 'tp-migrate', get_stylesheet_directory_uri() . '/assets/plugins/migrate/js/tp-migrate.js', array( 'jquery' ) );
		wp_enqueue_style( 'tp-migrate', get_stylesheet_directory_uri() . '/assets/plugins/migrate/sass/admin.css' );

		wp_localize_script( 'tp-migrate', 'TP_Migrate_Labels', array(
			'finished'        => __( 'Data migration complete.', 'tp' ),
			'posts_init'      => __( 'Initializing posts', 'tp' ),
			'posts_migrating' => __( 'Migrating posts (%i of %t)', 'tp' ),
		) );

		//Include migration scripts
		$dir = dirname( __FILE__ ) . '/js/types/';

		foreach( scandir( $dir ) as $file ) {
			if( '.' == substr( $file, 0, 1 ) ) continue;

			wp_enqueue_script( 'tp-migrate-'.$file, get_stylesheet_directory_uri() . '/assets/plugins/migrate/js/types/' . $file, array( 'tp-migrate' ) );
		}
	}

	/**
	 * Include other migrators
	 */
	function include_files() {
		$dir = dirname( __FILE__) . '/inc/';

		foreach( scandir( $dir ) as $file ) {
			if( '.' == substr( $file, 0, 1 ) ) continue;

			include_once( $dir. $file );
		}
	}
} new TP_Migrate;