<?php
/**
* LifterLMS Course Model
*
* @since    1.0.0
* @version  3.0.0
*
* @property $capacity  (int)  Number of students who can be enrolled in the course before enrollment closes
* @property $content_restricted_message  (string)  Message displayed when non-enrolled visitors try to access lessons/quizzes directly
* @property $course_closed_message  (string)  Message displayed to visitors when the course is accessed after the Course End Date has passed. Only applicable when $time_period is 'yes'
* @property $course_opens_message  (string)  Message displayed to visitors when the course is accessed before the Course Start Date has passed. Only applicable when $time_period is 'yes'
* @property $enrollment_closed_message  (string)  Message displayed to non-enrolled visitors when the course is accessed after the Enrollment End Date has passed. Only applicable when $enrollment_period is 'yes'
* @property $enrollment_end_date   (string)  After this date, registration closes
* @property $enrollment_opens_message  (string)  Message displayed to non-enrolled visitorswhen the course is accessed before the Enrollment Start Date has passed. Only applicable when $enrollment_period is 'yes'
* @property $enrollment_period  (string)  Whether or not a course time period restriction is enabled [yes|no] (all checks should check for 'yes' as an empty string might be retruned)
* @property $enrollment_start_date  (string)  Before this date, registration is closed
* @property $end_date   (string)  Date when a course closes. Students may no longer view content or complete lessons / quizzes after this date.
* @property $start_date  (string)  Date when a course is opens. Students may register before this date but can only view content and complete lessons or quizzes after this date.
* @property $time_period  (string)  Whether or not a course time period restriction is enabled [yes|no] (all checks should check for 'yes' as an empty string might be retruned)
*/

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LLMS_Course extends LLMS_Post_Model {

	protected $db_post_type = 'course';
	protected $model_post_type = 'course';

	/**
	 * Get a property's data type for scrubbing
	 * used by $this->scrub() to determine how to scrub the property
	 * @param   string $key  property key
	 * @return  string
	 * @since   3.0.0
	 * @version 3.0.0
	 */
	protected function get_property_type( $key ) {

		switch ( $key ) {

			case 'capacity':
				$type = 'absint';
			break;

			case 'end_date';
			case 'start_date';
			default:
				$type = 'text';

		}

		return $type;

	}

	/**
	 * Get course's prerequisite id based on the type of prerequsite
	 * @param  string     $type  Type of prereq to retrieve id for [course|track]
	 * @return int|false         Post ID of a course, taxonomy ID of a track, or false if none found
	 */
	public function get_prerequisite_id( $type = 'course' ) {

		if ( $this->has_prerequisite() ) {

			switch ( $type ) {

				case 'course':
					$key = 'prerequisite';
				break;

				case 'track':
					$key = 'prerequisite_track';
				break;

			}

			if ( isset( $key ) ) {
				return $this->get( $key );
			}

		}

		return false;

	}

	/**
	 * Attempt to get oEmbed for an audio provider
	 * Falls back to the [audio] shortcode if the oEmbed fails
	 *
	 * @return string
	 * @since   1.0.0
	 * @version 3.0.0 -- updated to utilize oEmbed and fallback to audio shortcode
	 */
	public function get_audio() {

		if ( ! isset( $this->audio_embed ) ) {

			return '';

		} else {

			$r = wp_oembed_get( $this->get( 'audio_embed' ) );

			if ( ! $r ) {

				$r = do_shortcode( '[audio src="' . $this->get( 'audio_embed' ) . '"]' );

			}

			return $r;

		}

	}

	/**
	 * Get Difficulty
	 *
	 * @return string
	 */
	public function get_difficulty() {

		$terms = get_the_terms( $this->get( 'id' ), 'course_difficulty' );

		if ( $terms === false ) {

			return '';

		} else {

			foreach ( $terms as $term ) {

				return $term->name;

			}

		}

	}

	/**
	 * Retrieve an array of students currently enrolled in the course
	 * @return   array
	 * @since    1.0.0
	 * @version  3.0.0 - updated the function to be less complicated
	 */
	public function get_enrolled_students() {

		global $wpdb;

		return $wpdb->get_results(
			"SELECT ID, display_name, user_email
			 FROM {$wpdb->prefix}users AS users
			 JOIN {$wpdb->prefix}lifterlms_user_postmeta AS meta ON users.ID = meta.user_id
			 WHERE meta.post_id = $this->id
			   AND meta.meta_key = '_status'
			   AND ( meta.meta_value = 'Enrolled' OR meta.meta_value = 'enrolled' )
			"
		);

	}

	/**
	 * Get a user's percentage completion through the course
	 * @return  float
	 * @since   1.0.0
	 * @version 3.0.0 - updated to use LLMS_Student->get_progress()
	 */
	public function get_percent_complete( $user_id = '' ) {

		$student = new LLMS_Student( $user_id );
		return $student->get_progress( $this->get( 'id' ), 'course' );

	}

	/**
	 * Attempt to get oEmbed for a video provider
	 * Falls back to the [video] shortcode if the oEmbed fails
	 *
	 * @return string
	 * @since   1.0.0
	 * @version 3.0.0 -- added fallback to video shortcode when oEmbed fails
	 */
	public function get_video() {

		if ( ! isset( $this->video_embed ) ) {

			return '';

		} else {

			$r = wp_oembed_get( $this->get( 'video_embed' ) );

			if ( ! $r ) {

				$r = do_shortcode( '[video src="' . $this->get( 'audio_embed' ) . '"]' );

			}

			return $r;

		}

	}

	/**
	 * Compare a course meta info date to the current date and get a bool
	 * @param    string     $date_key  property key, eg "start_date" or "enrollment_end_date"
	 * @return   boolean               true when the date is in the past
	 *                                 false when the date is in the future
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function has_date_passed( $date_key ) {

		$now = current_time( 'timestamp' );
		$date = $this->get_date( $date_key, 'U' );

		// if there's no date, we can't make a comparison
		// so assume it's unset and unnecessary
		// so return 'false'
		if ( ! $date ) {

			return false;

		} else {

			return $now > $date;

		}

	}

	/**
	 * Determine if the course is at capacity based on course capacity serttings
	 * @return   boolean    true if not at capacity, false if at or over capacity
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function has_capacity() {

		if ( 'yes' !== $this->get( 'enable_capacity' ) ) {
			return true;
		}

		$capacity = $this->get( 'capacity' );
		if ( ! $capacity ) {
			return true;
		} else {
			return ( count( $this->get_enrolled_students() ) < $capacity );
		}
	}

	/**
	 * Determine if prerequisites are enabled and there are prereqs configured
	 *
	 * @return   boolean   Returns true if prereq is enabled and there is a prerequisite course or track
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function has_prerequisite() {

		if ( 'yes' === $this->get( 'has_prerequisite' ) ) {

			return ( $this->get( 'prerequisite' ) || $this->get( 'prerequisite_track' ) );

		}

		return false;

	}

	/**
	 * Determine if students can access course content based on the current date
	 * @return   boolean
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function is_enrollment_open() {

		// if no period is set, enrollment is automatically open
		if ( 'yes' !== $this->get( 'enrollment_period' ) ) {
			return true;
		} // time period exists, check against the current date
		else {

			return ( $this->has_date_passed( 'enrollment_start_date' ) && ! $this->has_date_passed( 'enrollment_end_date' ) );

		}

	}

	/**
	 * Determine if students can access course content based on the current date
	 *
	 * Note that enrollment does not affect the outcome of this check as regardless
	 * of enrollment, once a course closes content is locked
	 *
	 * @return   boolean
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function is_open() {

		// if a course time period is not enabled, just return true (content is accessible)
		if ( 'yes' !== $this->get( 'time_period' ) ) {

			return true;

		} // time period exists, check against the current date
		else {

			return ( $this->has_date_passed( 'start_date' ) && ! $this->has_date_passed( 'end_date' ) );

		}

	}

	/**
	 * Determine if a prerequesite is completed for a student
	 * @param    string     $type  type of prereq [course|track]
	 * @return   boolean
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function is_prerequisite_complete( $type = 'course' ) {

		// no user or no prereqs so no reason to proceed
		if ( ! get_current_user_id() || ! $this->has_prerequisite() ) {
			return false;
		}

		$prereq_id = $this->get_prerequisite_id( $type );

		// no prereq id of this type, no need to proceed
		if ( ! $prereq_id ) {
			return false;
		}

		// setup student
		$student = new LLMS_Student();

		return $student->is_complete( $prereq_id, $type );

	}



























	/**
	 * Get WP user object for the course author
	 * @return obj   instance of WP_User
	 *
	 * @since  3.0.0
	 */
	public function get_author() {
		return new WP_User( $this->get_author_id() );
	}


	/**
	 * Get the course author's WP User ID
	 * @return int
	 *
	 * @since  3.0.0
	 */
	public function get_author_id() {
		return $this->post->post_author;
	}


	/**
	 * Get a the Display Name of the course author
	 * @return string
	 *
	 * @since  3.0.0
	 */
	public function get_author_name() {
		$author = $this->get_author();
		return $author->display_name;
	}

	/**
	 * Get SKU
	 *
	 * @return string
	 */
	public function get_sku() {

		return $this->sku;

	}

	/**
	 * Get the ID
	 * @return int
	 *
	 * @since  3.0.0
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get the Title
	 * @return int
	 *
	 * @since  3.0.0
	 */
	public function get_title() {
		return get_the_title( $this->get_id() );
	}

	/**
	 * Get the course permalink
	 * @return string
	 *
	 * @since  3.0.0
	 */
	public function get_permalink() {
		return get_permalink( $this->get_id() );
	}


	public function get_purchase_button_text() {

		return get_option( 'lifterlms_button_purchase_course_custom_text', 'Take This Course' );
	}

	public function get_purchase_membership_button_text() {

		return get_option( 'lifterlms_button_purchase_membership_custom_text', 'Become a Member' );
	}

	public function get_children_sections() {

		$args = array(
			'post_type' 		=> 'section',
			'posts_per_page'	=> 500,
			'meta_key'			=> '_llms_order',
			'order'				=> 'ASC',
			'orderby'			=> 'meta_value_num',
			'meta_query' 		=> array(
				array(
					'key' 		=> '_parent_course',
	      			'value' 	=> $this->id,
	      			'compare' 	=> '=',
	  			),
		  	),
		);

		$sections = get_posts( $args );

		return $sections;

	}

	public function get_children_lessons() {

		$lessons = array();

		$args = array(
			'post_type' 		=> 'section',
			'posts_per_page'	=> 500,
			'meta_key'			=> '_llms_order',
			'order'				=> 'ASC',
			'orderby'			=> 'meta_value_num',
			'meta_query' 		=> array(
				array(
					'key' 		=> '_parent_course',
	      			'value' 	=> $this->id,
	      			'compare' 	=> '=',
	  			),
		  	),
		);

		$sections = get_posts( $args );

		foreach ($sections as $s) {

			$section = new LLMS_Section( $s->ID );

			$lessons = array_merge( $lessons, $section->get_children_lessons() );

		}

		return $lessons;

	}

	/**
	 * Return array of objects containing user meta data for a single post.
	 *
	 * @return  array
	 */
	public function get_user_postmeta_data( $post_id ) {
		global $wpdb;

		$user_id = get_current_user_id();

		$table_name = $wpdb->prefix . 'lifterlms_user_postmeta';

		$results = $wpdb->get_results( $wpdb->prepare(
		'SELECT * FROM '.$table_name.' WHERE post_id = %d', $user_id, $post_id) );

		for ($i = 0; $i < count( $results ); $i++) {
			$results[ $results[ $i ]->meta_key ] = $results[ $i ];
			unset( $results[ $i ] );
		}

		return $results;
	}

	/**
	 * Return array of objects containing user meta data for a single post.
	 *
	 * @return  array
	 */
	public function get_user_postmetas_by_key( $post_id, $meta_key ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'lifterlms_user_postmeta';

		$results = $wpdb->get_results( $wpdb->prepare(
		'SELECT * FROM '.$table_name.' WHERE post_id = %s and meta_key = "%s" ORDER BY updated_date DESC', $post_id, $meta_key ) );

		for ($i = 0; $i < count( $results ); $i++) {
			$results[ $results[ $i ]->post_id ] = $results[ $i ];
			unset( $results[ $i ] );
		}

		return $results;
	}

	/**
	 * Get checkout url
	 *
	 * @return string
	 */
	public function get_checkout_url() {

		if ( llms_is_alternative_checkout_enabled() || is_user_logged_in() ) {
			$checkout_page_id = llms_get_page_id( 'checkout' );
		} else {
			$checkout_page_id = llms_get_page_id( 'myaccount' );
		}

		$checkout_url = apply_filters( 'lifterlms_get_checkout_url', $checkout_page_id ? get_permalink( $checkout_page_id ) : '' );

		return apply_filters( 'lifterlms_product_purchase_checkout_redirect', add_query_arg( 'product-id', $this->id, $checkout_url ) );
	}



	public function get_start_date() {

		if ( $this->start_date ) {
			return $this->start_date;
		} else {
			return $this->post->post_date;
		}

	}

	public function get_end_date() {
		return $this->end_date;
	}

	/**
	 * Find the next uncompleted lesson in the course syllabus
	 * @return int [Next uncompleted lesson]
	 */
	public function get_next_uncompleted_lesson() {
		$lessons_not_completed = array();

		$lessons = $this->get_children_lessons();

		$user = new LLMS_Person;

		foreach ( $lessons as $lesson ) {

			$user_postmetas = $user->get_user_postmeta_data( get_current_user_id(), $lesson->ID );

			if ( ! isset( $user_postmetas['_is_complete'] ) ) {

				array_push( $lessons_not_completed, $lesson->ID );
			}
		}
		if ($lessons_not_completed) {
			return $lessons_not_completed[0];
		}
	}

	/**
	 * Get all lesson ids associated with a course
	 * @return array $array [array of all lesson ids in a course]
	 */
	public function get_lesson_ids() {
		$lessons = array();

		$args = array(
			'post_type' 		=> 'section',
			'posts_per_page'	=> 500,
			'meta_key'			=> '_llms_order',
			'order'				=> 'ASC',
			'orderby'			=> 'meta_value_num',
			'meta_query' 		=> array(
				array(
					'key' 		=> '_parent_course',
	      			'value' 	=> $this->id,
	      			'compare' 	=> '=',
	  			),
		  	),
		);

		$sections = get_posts( $args );

		foreach ($sections as $s) {
			$section = new LLMS_Section( $s->ID );
			$lessonset = $section->get_children_lessons();
			foreach ($lessonset as $lessonojb) {
				$lessons[] = $lessonojb->ID;
			}
		}

		return $lessons;
	}



	/**
	 * Get all sections
	 * @return array $sections [Returns array of all sections associated with a course.]
	 */
	public function get_sections() {
		$syllabus = $this->get_syllabus();
		$sections = array();
		if ($syllabus) {
			foreach ($syllabus as $key => $value) {

				array_push( $sections,$value['section_id'] );
			}
		}
		return $sections;
	}

	/**
	 * Get the course short description
	 *
	 * @return string (html)
	 */
	public function get_short_description() {

		$short_description = wpautop( $this->post->post_excerpt );

		return $short_description;

	}


	/**
	 * Get the Course Section and Lesson information
	 *
	 * @return array
	 */
	public function get_syllabus() {

		$syllabus = $this->sections;

		return $syllabus;

	}



	public function get_user_enroll_date( $user_id = '' ) {

		$enrolled_date = '';

		//if no user get current user
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if ( $this->is_user_enrolled( $user_id = '' ) ) {

			$user_post_data = self::get_user_post_data( $this->id, $user_id );

			foreach ( $user_post_data as $upd ) {
				if ( $upd->meta_value === 'Enrolled' ) {
					$enrolled_date = $upd->updated_date;
				}
			}

		}

		return $enrolled_date;

	}

	public static function get_user_post_data( $post_id, $user_id = '' ) {
		global $wpdb;

		$results = false;

		if ( ! empty( $post_id ) ) {

			// if user id is empty get current user id
			if ( empty( $user_id ) ) {

				$user_id = get_current_user_id();
			}

			// query user postmeta table
			$table_name = $wpdb->prefix . 'lifterlms_user_postmeta';
			$results = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM '.$table_name.
						' WHERE post_id = %s
							AND user_id = %s',
					$post_id, $user_id
				)
			);
		}

		return $results;

	}

	public static function check_enrollment( $course_id, $user_id = '' ) {
		global $wpdb;

		//set enrollment to false
		$enrolled = false;

		// if no course id then nothing we can do
		if ( ! empty( $course_id ) ) {

			// if user id is empty get current user id
			if ( empty( $user_id ) ) {

				$user_id = get_current_user_id();
			}

			//query user_postmeta table
			$table_name = $wpdb->prefix . 'lifterlms_user_postmeta';
			$results = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM '.$table_name.
						' WHERE post_id = %s
							AND user_id = %s',
					$course_id, $user_id
				)
			);

			if ( $results ) {
				foreach ( $results as $result ) {
					if ( $result->meta_key === '_status' && ( $result->meta_value === 'Enrolled' || $result->meta_value === 'Expired' ) ) {
						$enrolled = $results;
					}
				}

			}
		}

		return $enrolled;
	}

	public function is_user_enrolled( $user_id = '' ) {

		$enrolled = false;

		$user_post_data = self::get_user_post_data( $this->id, $user_id );

		if ( $user_post_data ) {

			foreach ( $user_post_data as $upd ) {
				if ( $upd->meta_key === '_status' && $upd->meta_value === 'Enrolled' ) {
					$enrolled = true;
				}
			}

		}

		return $enrolled;

	}

	public function get_student_progress( $user_id = '' ) {

		// if user_id is empty get current user id
		if ( empty( $user_id ) ) {

			$user_id = get_current_user_id();
		}

		//check if user is enrolled
		$enrollment = self::check_enrollment( $this->id, $user_id );

		// set up course details and enrollment information
		$obj = new stdClass();
		$obj->id = $this->id;

		if ( $enrollment ) {

			$obj->is_enrolled = true;
			$obj->is_complete = false;

			//loop through returned rows and save data to object
			foreach ( $enrollment as $row ) {

				if ( $row->meta_key === '_start_date' ) {

					$obj->start_date = $row->updated_date;

				} elseif ( $row->meta_key === '_is_complete' ) {

					$obj->is_complete = true;
					$obj->completed_date = $row->updated_date;

				} elseif ( $row->meta_key === 'status' ) {

					$obj->status = $row->meta_value;

				}
			}

		} else {

			$obj->is_enrolled = false;

		}

		//add sections array to object
		$obj->sections = array();
		//add lessons array to object
		$obj->lessons = array();

		$sections = $this->get_children_sections();

		foreach ( $sections as $child_section ) {
			$section_obj = new LLMS_Section( $child_section->ID );

			$section = array();
			$section['id'] = $section_obj->id;
			$section['title'] = $section_obj->post->post_title;

			// get any user post meta data
			$section['is_complete'] = false;
			$section_user_data = self::get_user_post_data( $section_obj->id, $user_id );

			$obj->is_complete = false;
			if ( $section_user_data ) {

				//loop through returned rows and save data to object
				foreach ( $section_user_data as $row ) {

					if ( $row->meta_key === '_is_complete' ) {

						$section['is_complete'] = true;
						$section['completed_date'] = $row->updated_date;

					}

				}

			}

			$obj->sections[] = $section;

			//get lesson data
			$lessons = $section_obj->get_children_lessons();

			if ( $lessons ) {

				foreach ( $lessons as $child_lesson ) {
					$lesson_obj = new LLMS_Lesson( $child_lesson->ID );

					$lesson = array();
					$lesson['id'] = $lesson_obj->id;
					$lesson['title'] = $lesson_obj->post->post_title;
					$lesson['parent_id'] = $lesson_obj->get_parent_section();

					$lesson['is_complete'] = false;
					$lesson_user_data = self::get_user_post_data( $lesson_obj->id, $user_id );

					//loop through returned rows and save data to object
					if ( $lesson_user_data ) {

						foreach ( $lesson_user_data as $row ) {

							if ( $row->meta_key === '_is_complete' ) {
								$lesson['is_complete'] = true;
								$lesson['completed_date'] = $row->updated_date;
							}

						}

					}

					$obj->lessons[] = $lesson;

				}

			}

		}

		return $obj;

	}

	/**
	 * Get url to membership checkout page, depends on it is user logged in and is alternative checkout on.
	 *
	 * @return string
	 */
	public function get_membership_link() {
		$memberships_required = get_post_meta( $this->id, '_llms_restricted_levels', true );

		if (count( $memberships_required ) > 1) {
			$membership_url = get_permalink( llms_get_page_id( 'memberships' ) );
		} //if only 1 membership level is assigned take visitor to the membership page
		else {
			$membership_url = get_permalink( $memberships_required[0] );
		}

		return apply_filters( 'lifterlms_product_purchase_redirect_membership_required', $membership_url );
	}
}