<?php
/**
 * Plugin Name: Find and remove orphaned LearnDash content
 * Description: Allows administrators to see and delete LearnDash topics, lessons and quizzes that are not assigned to a course
 * Version: 1.0
 * Author: Training Spark
 * Author URI: https://www.training-spark.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package TrainingSparkLearnDashManagement
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

add_action( 'admin_menu', 'training_spark_get_orphaned_ld_content_menu' );
add_action( 'admin_post_training_spark_trash_orphaned_ld_content', 'training_spark_trash_orphaned_ld_content' );

/**
 * Displays orphaned LearnDash content in the WordPress admin.
 * Fetches the orphaned content and renders a user interface to view and delete them.
 */
function training_spark_show_orphaned_ld_content() {
	$orphaned = training_spark_get_orphaned_ld_content();

	echo "<div class='wrap'><h1>Find and remove orphaned LearnDash content</h1>
    
    <p>This is a free plugin from <a href='https://www.training-spark.com' target='_blank'>Training Spark</a> that scans your LearnDash platform for lessons, topics and quizzes that are not assigned to any courses.</p>
    <p>Any <em>'orphaned'</em> content will be listed below and you can delete it using the bottom at the bottom of the page.</p>
    <p>This plugin is distributed under the GNU General Public License and should be used at your own risk.</p>
    <hr />";

	if ( ! empty( $orphaned ) ) {

		foreach ( $orphaned as $type => $items ) {

			echo '<h2>' . esc_attr( training_spark_ld_content_type( $type ) ) . '</h2>';

			echo '<ul>';

			foreach ( $items as $item ) {

				echo '<li>' . esc_html( get_the_title( $item->ID ) ) . ' - <a href="' . esc_url( get_the_permalink( $item->ID ) ) . '">View</a> - <a href="' . esc_url( get_edit_post_link( $item->ID ) ) . '">Edit</a></li>';

			}

			echo '</ul>';

		}

		echo "<hr />
<form action='" . esc_url( admin_url( 'admin-post.php' ) ) . "' method='post'>
        <input type='hidden' name='action' value='training_spark_trash_orphaned_ld_content'>";
		wp_nonce_field( 'training_spark_trash_orphaned_ld_content', 'training_spark_trash_orphaned_ld_content_nonce' );
		echo "<input type='submit' class='button button-primary' value='" . esc_attr__( 'Trash' ) . " orphaned content (Do so at your own risk!)'>
        </form>";

	} else {

		echo "<p>Looks like there's no orphaned LearnDash content! You can probably delete this plugin.</p>";

	}

	echo '</div>';

	die();

}

/**
 * Retrieves orphaned LearnDash content.
 * Queries for LearnDash post types (lessons, topics, quizzes) and checks if they are not assigned to any courses.
 * Returns an array of orphaned content grouped by post type.
 */
function training_spark_get_orphaned_ld_content() {
	$orphaned = array();

	$args = array(
		'post_type'      => array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ),
		'posts_per_page' => -1,
		'post_status'    => 'publish',
	);

	$all_content = get_posts( $args );

	foreach ( $all_content as $content_item ) {

		$assignments = learndash_get_courses_for_step( $content_item->ID );

		if ( empty( $assignments ) || ( empty( $assignments['primary'] ) && empty( $assignments['secondary'] ) ) ) {

			$orphaned[ $content_item->post_type ][] = $content_item;

		}
	}

	return $orphaned;

}

/**
 * Converts LearnDash post type keys to human-readable names.
 * Accepts a post type key and returns the corresponding human-readable name.
 *
 * @param string $type The post type key (e.g., 'sfwd-quiz').
 * @return string|false The human-readable name of the post type or false if not found.
 */
function training_spark_ld_content_type( $type ) {
	$types = array(
		'sfwd-quiz'    => 'Quizzes',
		'sfwd-topic'   => 'Topics',
		'sfwd-lessons' => 'Lessons',
	);

	if ( array_key_exists( $type, $types ) ) {
		return $types[ $type ];
	}

	return false;

}

/**
 * Adds a menu page to the WordPress admin for the plugin.
 * Registers a new menu item under which the plugin's UI is accessible.
 */
function training_spark_get_orphaned_ld_content_menu() {
	add_menu_page(
		'Orphaned LearnDash content',
		'Orphaned LearnDash content',
		'manage_options',
		'orphaned-learndash-content',
		'training_spark_show_orphaned_ld_content'
	);
}

/**
 * Handles the deletion of orphaned LearnDash content.
 * Performs security checks and deletes the content if the user has the required permissions.
 */
function training_spark_trash_orphaned_ld_content() {
	if ( isset( $_POST['training_spark_trash_orphaned_ld_content_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['training_spark_trash_orphaned_ld_content_nonce'] ) ), 'training_spark_trash_orphaned_ld_content' ) && current_user_can( 'manage_options' ) ) {

		$orphaned = training_spark_get_orphaned_ld_content();

		if ( ! empty( $orphaned ) ) {

			foreach ( $orphaned as $type => $items ) {

				foreach ( $items as $item ) {
					wp_trash_post( $item->ID );
				}
			}
		}

		wp_die( '<p>Orphaned LearnDash content deleted.</p><p><a href="' . esc_url( admin_url( '/admin.php?page=orphaned-learndash-content' ) ) . '">&laquo; Back</p>' );

	}

	wp_die( 'Security check failed' );

}
