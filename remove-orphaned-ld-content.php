<?php
/**
 * Plugin Name: Find and remove orphaned LearnDash content
 * Plugin URI: https://www.training-spark.com
 * Description: Allows administrators to see and delete LearnDash topics, lessons and quizzes that are not assigned to a course
 * Version: 1.0
 * Author: Training Spark
 * Author URI: https://www.training-spark.com
 */

function training_spark_show_orphaned_ld_content() {
	$orphaned = training_spark_get_orphaned_ld_content();

	echo "<div class='wrap'><h1>Orphaned LearnDash content</h1>
    
    <p>This is a free plugin from <a href='https://www.training-spark.com' target='_blank'>Training Spark</a> that scans your LearnDash platform for lessons, topics and quizzes that are not assigned to any courses.</p>
    <p>Any <em>'orphaned'</em> content will be listed below and you can delete it using the bottom at the bottom of the page.</p>
    <p>This plugin is distributed under the GNU General Public License and should be used at your own risk.</p>
    
    ";

	if ( ! empty( $orphaned ) ) {

		foreach ( $orphaned as $type => $items ) {

			echo '<h2>' . training_spark_ld_content_type( $type ) . '</h2>';

			echo '<ul>';

			foreach ( $items as $item ) {

				echo '<li>' . get_the_title( $item->ID ) . ' - ' . '<a href="' . get_the_permalink( $item->ID ) . '">View</a> - <a href="' . get_edit_post_link( $item->ID ) . '">Edit</a></li>';

			}

			echo '</ul>';

		}

		echo "<hr />
<form action='" . admin_url( 'admin-post.php' ) . "' method='post'>
        <input type='hidden' name='action' value='training_spark_trash_orphaned_ld_content'>
        <input type='hidden' name='training_spark_trash_orphaned_ld_content_nonce' value='" . wp_create_nonce( 'training_spark_trash_orphaned_ld_content' ) . "'>
        <input type='submit' class='button button-primary' value='" . esc_attr__( 'Trash' ) . " orphaned content (Do so at your own risk!)'>
        </form>";

	} else {

		echo "<p>Looks like there's no orphaned LearnDash content! You can probably delete this plugin.</p>";

	}

	echo '</div>';

	die();

}

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

function training_spark_get_orphaned_ld_content_menu() {
	add_menu_page(
		'Orphaned LearnDash content',
		'Orphaned LearnDash content',
		'manage_options',
		'orphaned-learndash-content',
		'training_spark_show_orphaned_ld_content'
	);
}

add_action( 'admin_menu', 'training_spark_get_orphaned_ld_content_menu' );

add_action( 'admin_post_training_spark_trash_orphaned_ld_content', 'training_spark_trash_orphaned_ld_content' );

function training_spark_trash_orphaned_ld_content() {
	if ( isset( $_POST['training_spark_trash_orphaned_ld_content_nonce'] ) && wp_verify_nonce( $_POST['training_spark_trash_orphaned_ld_content_nonce'], 'training_spark_trash_orphaned_ld_content' ) && current_user_can( 'manage_options' ) ) {

		$orphaned = training_spark_get_orphaned_ld_content();

		if ( ! empty( $orphaned ) ) {

			foreach ( $orphaned as $type => $items ) {

				foreach ( $items as $item ) {
					wp_trash_post( $item->ID );
				}
			}
		}

		wp_die( '<p>Orphaned LearnDash content deleted.</p><p><a href="' . admin_url( '/admin.php?page=orphaned-learndash-content' ) . '">&laquo; Back</p>' );

	}

	wp_die( 'Security check failed' );

}


