<?php
/**
*
*/
class PTT_Rest_Users extends PTT_Rest {

	protected static $inst = NULL;

	protected $user = NULL;

	protected $org = NULL;

	/**
	 * leave empty and public on purpose
	 */
	function __construct() {}

	/**
	 * instantiate our class
	 */
	public static function get_inst() {
		if ( self::$inst === NULL ) {
			self::$inst = new self;
		}
		return self::$inst;
	}


	public function login() {
		// 1. Try Logging in the user
		$request  = new WP_REST_Request( 'GET', '/wp/v2/users/me' );
		$response = rest_do_request( $request );
		if ( $response->is_error() ) {
			// not sucessful.
			// mean not authorized
			return wp_send_json_error( array(
				'message' => 'Username and password do not match.'
			) );
		}
		// Successful
		// get user from the response
		$user = $response->get_data();
		// 2. check this user has access to this blog
		$has_access = false;
		$blog_id    = get_current_blog_id();
		$user_blogs = get_blogs_of_user( $user['id'] );
		foreach ($user_blogs as $blog) {
			if ( (int) $blog_id === (int) $blog->userblog_id ) {
				$has_access = true;
			}
		}
		// if no access return error
		if ( ! $has_access ) {
			return wp_send_json_error( array(
				'message' => 'You do no have access to this organization.'
			) );
		}
		$this->user = $user;
		$this->org  = get_blog_details( $blog_id );
		// 3. return the user and the site info
		// return wp_send_json(array(
		// 	'user' => $user,
		// 	'site' => get_blog_details( $blog_id ),
		// ));
	}

	/**
	 * returns current user and site if logged in. Otherwise, logs user in.
	 *
	 * @return array array converted to JSON fromat.
	 */
	public function current_user() {
		if ( !$this->user && !$this->org ) {
			$this->login();
		}
		return wp_send_json(array(
			'user' => $this->user,
			'site' => $this->org,
		));
	}

	/**
	 * checks if a user exisits by matching a string to the username or email of users in the database. Matches partial strings too so can return multiple results.
	 *
	 * Note: this checks the main users table. so it will find users regardless of the organization wthey belong to.
	 *
	 * @param  string $user    username or email to look for
	 * @param  int    $exclude user id to exclude
	 * @return array           an array of json objects for each user found.
	 */
	public function exists($user, $exclude, $columns = array( 'user_login', 'user_email' ), $exact = false) {
		$users = new WP_User_Query( array(
			'search' => $exact ? sanitize_text_field($user) : '*'.sanitize_text_field($user).'*',
			'search_columns' => $columns,
			'exclude' => sanitize_text_field($exclude),
			'blog_id' => 0,
		) );
		return wp_send_json( $users->get_results() );
	}

	public function add_to_org( $user_id ) {

		$_id     = (int) $user_id;
		$blog_id = get_current_blog_id();

		if ( $_id ) {
			// 1. Let's add user to this site
			$add_to_blog = add_user_to_blog($blog_id, $_id, 'pwptt_freelancer');

			// 2. handle response
			if ( ! is_wp_error( $add_to_blog ) ) {
				$user = get_userdata( $_id );
				ttt_send_new_user($user->user_email, $blog_id, $user->user_login );
				return wp_send_json( $add_to_blog );
			}
			else {
				return wp_send_json_error( array(
					'message' => 'The user could not be added to your organization.',
				) );
			}
		}
		else {
			return wp_send_json_error( array(
				'message' => 'No user ID param.',
			) );
		}
	}

	function create_user() {
		$email    = isset( $_REQUEST['email'] )    ? sanitize_email( $_REQUEST['email'] )    : null;
		$username = isset( $_REQUEST['username'] ) ? sanitize_text_field( $_REQUEST['username'] ) : null;
		$password = wp_generate_password();

		$blog_id  = get_current_blog_id();

		// 1. create the user
		// do not send password change email
		add_filter( 'send_password_change_email', false );
		$request = new WP_REST_Request( 'POST', '/wp/v2/users' );
		$request->set_param( 'username', $username );
		$request->set_param( 'email', $email );
		$request->set_param( 'password', $password );
		$response = rest_do_request( $request );
		// revert back this filter.
		add_filter( 'send_password_change_email', true );
		if ( $response->is_error() ) {
			// not sucessful.
			// return response.
			return wp_send_json_error( array(
				'message' => 'The user could not be created',
			) );
		}
		// we have a user
		$user = $response->get_data();

		// 2. Let's add user to this site
		$add_to_blog = add_user_to_blog($blog_id, $user['id'], 'pwptt_freelancer');

		// 3. handle response
		if ( ! is_wp_error( $add_to_blog ) ) {
			// 4. send email to user about with password and login info
			ttt_send_new_user($email, $blog_id, $username, $password);

			return wp_send_json( $user );
		}
		else {
			return wp_send_json_error( array(
				'message' => 'The user was created but they could not be added to your organization.',
			) );
		}
	}

	public function new_user() {
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			switch ( $_REQUEST['action'] ) {
				case 'check_user' :
					return $this->exists($_REQUEST['s'], $_REQUEST['current_user']);
				break;

				case 'add_user' :
					return $this->add_to_org($_REQUEST['user_id']);
				break;

				case 'new_user' :
					return $this->create_user();
				break;

				case 'remove_user' :
					$user_id = isset( $_REQUEST['user_id'] ) ? (int) $_REQUEST['user_id'] : null;
					$blog_id  = get_current_blog_id();

					remove_user_from_blog($user_id, $blog_id);

					return wp_send_json( array( 'message' => 'done.' ) );
				break;
			}
		}
	}

	public function check_user() {
		if ( $_SERVER['REQUEST_METHOD'] === 'GET' ) {
			if ( ! isset( $_GET['username'] )
				&& ! isset( $_GET['email'] ) ) {
				return wp_send_json_error( array(
					'message' => 'Not enough info',
				) );
			}

			if ( isset( $_GET['username'] ) ) {
				return $this->exists( $_REQUEST['username'], '', array('user_login'), true);
			}
			elseif ( isset( $_GET['email'] ) ) {
				return $this->exists( $_REQUEST['email'], '', array('user_email'), true);
			}
		}
	}

	public function forgot_password() {
		$username = isset( $_GET['username'] ) ? sanitize_text_field( $_GET['username'] ) : false;
		$email = isset( $_GET['email'] ) ? sanitize_email( $_GET['email'] ) : false;

		if ( ! $username && ! $email ) {
			return wp_send_json_error( array( 'message' => 'No params supplied.') );
		}

		$user = ($username) ? get_user_by('login', $username) : get_user_by('email', $email);

		if ( ! $user ) {
			return wp_send_json_error( array( 'message' => 'We cannot find your user.') );
		}

		if ( $username ) {
			$password = wp_generate_password();
			wp_set_password( $password, $user->ID );

			ttt_send_new_pw($user->user_email, $user->user_login, $password);
		}
		else if ( $email ) {
			ttt_send_username( $email, $user->user_login );
		}

		return true;
	}

	public function register_organization() {
		// if this is a post request
		// we should create the org
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			// 1. Creaate a new user
			// To create a new user we need:
			// username, password, email
			if ( isset( $_REQUEST['user_id'] ) ) {
				$_user = get_user_by( 'ID', (int) $_REQUEST['user_id'] );
				$user_id = $_user->ID;
			}
			else {
				$username = isset( $_REQUEST['username'] ) ? sanitize_text_field( $_REQUEST['username'] ) : false;
				$password = isset( $_REQUEST['password'] ) ? sanitize_text_field( $_REQUEST['password'] ) : false;
				$email = isset( $_REQUEST['email'] ) ? sanitize_text_field( $_REQUEST['email'] ) : false;
				// if we do not have the right params to create a user.
				if ( ! $username || ! $password || ! $email ) {
					return wp_send_json_error( array(
						'message' => 'Username, password and email are required to create the user that will be the admin for this organization.'
					) );
				}
				// create the user
				$user_id = wpmu_create_user($username, $password, $email);
			}
			if ( $user_id ) {
				// 2. create the organization
				$organization = isset( $_REQUEST['organization'] ) ? sanitize_text_field( $_REQUEST['organization'] ) : false;

				if ( ! $organization ) {
					return wp_send_json_error( array(
						'message' => 'No organization was supplied. An organization name is required to create a new account.',
					) );
				}

				$org = wpmu_create_blog($organization.'.'.$_SERVER['HTTP_HOST'], '/', ucwords($organization), $user_id);

				if ( is_wp_error( $org ) ) {
					return wp_send_json_error( $org );
				}

				return wp_send_json( $org );
			}
			else {
				return wp_send_json_error( array(
					'message' => 'The user cannot be created.',
				) );
			}
		}
		elseif ( 'GET' === $_SERVER['REQUEST_METHOD'] ) {
			$organization = isset( $_REQUEST['organization'] ) ? sanitize_text_field( $_REQUEST['organization'] ) : false;

			if ( $organization ) {
				return wp_send_json( domain_exists( $organization.'.'.$_SERVER['HTTP_HOST'], '/' ) );
			}
		}

		die();
	}

	public function try_demo() {
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			$username = isset( $_REQUEST['username'] ) ? sanitize_text_field( $_REQUEST['username'] ) : false;
			$email = isset( $_REQUEST['email'] ) ? sanitize_email( $_REQUEST['email'] ) : false;
			$password = isset( $_REQUEST['password'] ) ? sanitize_text_field( $_REQUEST['password'] ) : false;

			if (! $username
			|| ! $email
			|| ! $email ) {
				return wp_send_json_error( array(
					'message' => 'Username, email and password cannot be empty.',
				) );
			}

			$user_id = wpmu_create_user($username, $password, $email);

			if ( ! $user_id ) {
				return wp_send_json_error( array(
					'message' => 'This email address already exisits. You can\'t join the demo if you already have an account.',
				) );
			}

			// 6 is the ID for the demo organzation.
			// we hard code it here because it should never change
			$add_to_blog = add_user_to_blog(6, $user_id, 'pwptt_freelancer');

			if ( ! is_wp_error( $add_to_blog ) ) {
				// 4. send email to user about with password and login info
				ttt_send_new_user($email, $blog_id, $username, $password);

				return wp_send_json( $user_id );
			}
			else {
				return wp_send_json_error( array(
					'message' => 'The user was created but they could not be added to your organization.',
				) );
			}
		}
		die();
	}
}
?>