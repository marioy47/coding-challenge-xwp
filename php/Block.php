<?php
/**
 * Block class.
 *
 * @package SiteCounts
 */

namespace XWP\SiteCounts;

use WP_Block;
use WP_Query;

/**
 * The Site Counts dynamic block.
 *
 * Registers and renders the dynamic block.
 */
class Block {

	/**
	 * The Plugin instance.
	 *
	 * @var Plugin
	 */
	protected $plugin;

	/**
	 * Instantiates the class.
	 *
	 * @param Plugin $plugin The plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Adds the action to register the block.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Registers the block.
	 */
	public function register_block() {
		register_block_type_from_metadata(
			$this->plugin->dir(),
			[
				'render_callback' => [ $this, 'render_callback' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param array    $attributes The attributes for the block.
	 * @param string   $content    The block content, if any.
	 * @param WP_Block $block      The instance of this block.
	 * @return string The markup of the block.
	 */
	public function render_callback( $attributes, $content, $block ) {
		$post_types      = get_post_types( [ 'public' => true ] );
		$class_name      = isset( $attributes['className'] ) ? $attributes['className'] : '';
		$current_post_id = get_the_ID();
		ob_start();

		?>
		<div class="<?php echo esc_attr( $class_name ); ?>">
			<h2><?php esc_html_e( 'Post Counts', 'site-counts' ); ?></h2>
			<ul>
			<?php
			foreach ( $post_types as $post_type_slug ) :
				$post_type_object = get_post_type_object( $post_type_slug );
				$count_posts      = wp_count_posts( $post_type_slug );
				$post_count       = $count_posts->publish + $count_posts->inherit;

				?>
				<li>
					<?php // translators: 1: The number of posts. 2: The label name for the post type. ?>
					<?php printf( _n( 'There is %1$d %2$s', 'There are %1$d %2$s', $post_count, 'site-counts' ), number_format_i18n( $post_count ), esc_html( $post_type_object->labels->name ) ); ?>
				</li>
				<?php
				endforeach;
			?>
			</ul>

			<?php // translators: The ID of the current post. ?>
			<p><?php printf( esc_html__( 'The current post ID is %1$d.', 'site-counts' ), $current_post_id ); ?></p>

			<?php
			$query = new WP_Query(
				[
					'post_type'     => [ 'post', 'page' ],
					'post_status'   => 'any',
					'date_query'    => [
						[
							'hour'    => 9,
							'compare' => '>=',
						],
						[
							'hour'    => 17,
							'compare' => '<=',
						],
					],
					'post_per_page' => 5,
					'no_found_rows' => true,
					'tag'           => 'foo',
					'category_name' => 'baz',
					'post__not_in'  => [ get_the_ID() ], // Verify this on the loop.
					'meta_value'    => 'Accepted', // there is no meta_key.
				]
			);
			?>
			<?php if ( count( $query->posts ) ) : ?>
				<?php // translators: The number of posts found. ?>
				<h2><?php printf( _n( 'Any %d post with the tag of foo and the category of baz', 'Any %d posts with the tag of foo and the category of baz', count( $query->posts ), 'site-counts' ), count( $query->posts ) ); ?></h2>
				<ul>
					<?php
					foreach ( $query->posts as $post ) :
						if ( $post->ID === $current_post_id ) {
							continue;
						}
						?>
						<li><?php echo $post->post_title; ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php

		return ob_get_clean();
	}
}
