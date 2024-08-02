<?php
class Boilerplate_Post {

	private static $instance = null;

	static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	// Constructor.
	function __construct() {
		add_action( 'mainwp_save_bulkpost', array( &$this, 'save_meta_bulkpost' ), 9, 1 ); // default bulkpost acction order is 10
		add_action( 'mainwp_save_bulkpage', array( &$this, 'save_meta_bulkpage' ), 9, 1 ); // default bulkpost acction order is 10
	}

	public function admin_init() {
		add_action( 'wp_ajax_mainwp_boilerplate_delete_token', array( &$this, 'ajax_delete_token' ) );
		add_action( 'wp_ajax_mainwp_boilerplate_save_token', array( &$this, 'ajax_save_token' ) );
		add_action( 'wp_ajax_mainwp_boilerplate_delete_site_post', array( &$this, 'ajax_boilerplate_delete_site_post' ) );
		add_action( 'wp_ajax_mainwp_boilerplate_delete_post', array( &$this, 'ajax_boilerplate_delete_post' ) );
		add_filter( 'mainwp-after-posting-bulkpost-result', array( &$this, 'after_posting_bulkpost' ), 10, 4 );
		add_filter( 'mainwp-after-posting-bulkpage-result', array( &$this, 'after_posting_bulkpage' ), 10, 4 );
	}

	function boilerplate_metabox_handle( $post_id, $post_type ) {
		$post = get_post( $post_id );
		if ( $post->post_type == $post_type ) {
			update_post_meta( $post_id, '_mainwp_boilerplate', 'yes' );
			return;
		}
		return;
	}

	public function redirect_edit_bulkpost( $location, $post_id ) {
		$location .= '&boilerplate=1';
		return $location;
	}

	function save_meta_bulkpost( $post_id ) {

		// verify if this is an auto save routine. If it is our form has not been submitted, so we dont want to do anything
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST['mainwp_boilerplate_nonce'] ) || ! wp_verify_nonce( $_POST['mainwp_boilerplate_nonce'], 'boilerplate_' . $post_id ) ) {
			return false;
		}

		add_filter( 'redirect_post_location', array( $this, 'redirect_edit_bulkpost' ), 11, 2 ); // priority 11 to adding boilerplate parameter

		$this->boilerplate_metabox_handle( $post_id, 'bulkpost' );

	}

	function save_meta_bulkpage( $post_id ) {

		// verify if this is an auto save routine. If it is our form has not been submitted, so we dont want to do anything
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST['mainwp_boilerplate_nonce'] ) || ! wp_verify_nonce( $_POST['mainwp_boilerplate_nonce'], 'boilerplate_' . $post_id ) ) {
			return false;
		}

		add_filter( 'redirect_post_location', array( $this, 'redirect_edit_bulkpost' ), 11, 2 ); // priority 11 to adding boilerplate parameter

		$this->boilerplate_metabox_handle( $post_id, 'bulkpage' );
	}

	// it seems does not used anymore
	public static function posting_bulk_handler( $data, $website, &$output ) {
		if ( preg_match( '/<mainwp>(.*)<\/mainwp>/', $data, $results ) > 0 ) {
			$result = $results[1];
			// $information = unserialize( base64_decode( $result ) );
			$information = json_decode( base64_decode( $result ), true );

			if ( isset( $information['added'] ) ) {
				$output->ok[ $website->id ] = '1';
				if ( isset( $information['link'] ) ) {
					$output->link[ $website->id ] = $information['link'];
				}
				if ( isset( $information['added_id'] ) ) {
					$output->added_id[ $website->id ] = $information['added_id'];
				}
			} elseif ( isset( $information['error'] ) ) {
				$output->errors[ $website->id ] = __( 'Error - ', 'boilerplate-extension' ) . $information['error'];
			} else {
				$output->errors[ $website->id ] = __( 'Undefined error occurred. Please try again.', 'boilerplate-extension' );
			}
		} else {
			$output->errors[ $website->id ] = apply_filters( 'mainwp_getErrorMessage', 'NOMAINWP', $website->url );
		}
	}

	public function ajax_delete_token() {
		global $wpdb;
		$ret      = array( 'success' => false );
		$token_id = intval( $_POST['token_id'] );

		if ( Boilerplate_DB::get_instance()->delete_token_by_id( $token_id ) ) { // ok
			$ret['success'] = true;
		}

		echo json_encode( $ret );
		exit;
	}

	public function ajax_boilerplate_delete_site_post() {
		global $mainWPBoilerplateExtensionActivator;
		$ret            = array( 'status' => false );
		$site_id        = intval( $_POST['site_id'] );
		$post_id        = intval( $_POST['post_id'] );
		$boilerplate_id = intval( $_POST['boilerplate_id'] );

		if ( $site_id && $post_id ) {
			$post_data = array(
				'id'     => $post_id,
				'action' => 'delete',
			);
			$ret       = apply_filters( 'mainwp_fetchurlauthed', $mainWPBoilerplateExtensionActivator->get_child_file(), $mainWPBoilerplateExtensionActivator->get_child_key(), $site_id, 'post_action', $post_data );
			if ( $boilerplate_id ) { // && is_array( $ret ) && isset( $ret['status'] ) && 'SUCCESS' == $ret['status'] ) {
				$sites_posts = get_post_meta( $boilerplate_id, '_mainwp_boilerplate_sites_posts', true );
				if ( isset( $sites_posts['previous_posts'] ) && isset( $sites_posts['previous_posts'][ $site_id ] ) && isset( $sites_posts['previous_posts'][ $site_id ]['post_id'] ) && $sites_posts['previous_posts'][ $site_id ]['post_id'] == $post_id ) {
					unset( $sites_posts['previous_posts'][ $site_id ] );
				}
				update_post_meta( $boilerplate_id, '_mainwp_boilerplate_sites_posts', $sites_posts );
			}
		}
		echo json_encode( $ret );
		exit;
	}

	public function ajax_save_token() {
		$return            = array(
			'success' => false,
			'error'   => '',
			'message' => '',
		);
		$token_name        = sanitize_text_field( $_POST['token_name'] );
		$token_description = sanitize_text_field( $_POST['token_description'] );

		// update token
		if ( isset( $_POST['token_id'] ) && $token_id = intval( $_POST['token_id'] ) ) {
			$current = Boilerplate_DB::get_instance()->get_tokens_by( 'id', $token_id ); // ok
			if ( $current && $current->token_name == $token_name && $current->token_description == $token_description ) {
				$return['success']  = true;
				$return['message']  = __( 'Token not changed.', 'boilerplate-extension' );
				$return['row_data'] = $this->create_token_item( $current, false );
			} elseif ( ( $current = Boilerplate_DB::get_instance()->get_tokens_by( 'token_name', $token_name ) ) && $current->id != $token_id ) { // ok
				$return['error'] = __( 'Token name alrady exists', 'boilderplate-extension' );
			} elseif ( $token = Boilerplate_DB::get_instance()->update_token(
				$token_id,
				array(
					'token_name'        => $token_name,
					'token_description' => $token_description,
				)
			) ) {
				$return['success']  = true;
				$return['row_data'] = $this->create_token_item( $token, false );
			}
		} else { // add new token
			if ( $current = Boilerplate_DB::get_instance()->get_tokens_by( 'token_name', $token_name ) ) { // ok
				$return['error'] = __( 'Token name alrady exists', 'boilderplate-extension' );
			} else {
				if ( $token = Boilerplate_DB::get_instance()->add_token(
					array(
						'token_name'        => $token_name,
						'token_description' => $token_description,
						'type'              => 0,
					)
				) ) {
					$return['success']  = true;
					$return['row_data'] = $this->create_token_item( $token );
				} else {
					$return['error'] = __( 'Token creation failed. Please try again.', 'boilderplate-extension' ); }
			}
		}
		echo json_encode( $return );
		exit;
	}

	public function ajax_boilerplate_delete_post() {
		global $wpdb;
		$post_id = intval( $_REQUEST['post_id'] );
		$ret     = array( 'success' => false );
		if ( $post_id && wp_delete_post( $post_id ) ) {
			$ret['success'] = true; 
		}
		echo json_encode( $ret );
		exit;
	}

	public function after_posting_bulkpost( $input, $post, $dbwebsites, $output ) {

		global $mainWPBoilerplateExtensionActivator;
		
		if ( $post ) {
			if ( 'yes' == get_post_meta( $post->ID, '_mainwp_boilerplate', true ) ) {
				// calculate what post is create new, updated or will delete
				self::bulkposting_posting_done_results( $post, $dbwebsites, $output );

				$sites_posts = get_post_meta( $post->ID, '_mainwp_boilerplate_sites_posts', true );

				$new_posts     = $sites_posts['new_posts'];
				$updated_posts = $sites_posts['updated_posts'];
				$aksto_delete  = $sites_posts['aks_to_delete'];

				$websites = apply_filters( 'mainwp-getsites', $mainWPBoilerplateExtensionActivator->get_child_file(), $mainWPBoilerplateExtensionActivator->get_child_key(), null );

				$all_sites = array();
				if ( is_array( $websites ) ) {
					foreach ( $websites as $website ) {
						$all_sites[ $website['id'] ] = $website;
					}
				}

				update_post_meta( $post->ID, '_bulkpost_do_not_del', 'yes' );

				?>
			<div id="message" class="updated">
				<?php
				if ( is_array( $new_posts ) ) {
					foreach ( $new_posts as $site_id => $_post ) {
						if ( isset( $all_sites[ $site_id ] ) ) {
							$website = $all_sites[ $site_id ];
							echo '<p>' . $website['name'] . ': New post created. <a href="' . $_post['link'] . '"  target=\"_blank\">View Post</a></p>';
						}
					}
				}

				if ( is_array( $updated_posts ) ) {
					foreach ( $updated_posts as $site_id => $_post ) {
						if ( isset( $all_sites[ $site_id ] ) ) {
							$website = $all_sites[ $site_id ];
							echo '<p>' . $website['name'] . ': Post updated. <a href="' . $_post['link'] . '"  target=\"_blank\">View Post</a></p>';
						}
					}
				}

				if ( is_array( $aksto_delete ) ) {
					foreach ( $aksto_delete as $site_id => $_post ) {
						if ( isset( $all_sites[ $site_id ] ) ) {
							$website = $all_sites[ $site_id ];
							echo '<p class="bpl_bulkpost_posting_item" type="post">' . $website['name'] . ': <span class="bpl_bulkpost_delete_status">Delete the post? <span class="bpl_bulkpost_delete_process hidden"></span><a href="#" class="bpl_bulkpost_delete_post" boilerplate_id="' . $post->ID . '" site_id="' . $site_id . '" post_id="' . $_post['post_id'] . '">Delete</a> | <a href="' . $_post['link'] . '"  target=\"_blank\">View Post</a></span></p>';
						}
					}
				}
				foreach ( $dbwebsites as $website ) {
					if ( ! isset( $output->ok[ $website->id ] ) ) {
						$err = $output->errors[ $website->id ];
						if ( $err == 'Error - Empty post id' ) {
							$err = ': Not found the post on child site to save.';
						} else {
							$err = ': ERROR - ' . $err;
						}
						echo $website->name . $err;
					}
				}
				?>
			</div>
			<br/>

			<a href="<?php echo get_admin_url(); ?>admin.php?page=PostBulkAdd&boilerplate=1" class="ui green button" target="_top"><?php _e( 'Add New Boilerplate', 'boilerplate-extension' ); ?></a>
			<a href="<?php echo get_admin_url(); ?>admin.php?page=Extensions-Boilerplate-Extension" class="ui green button" target="_top">
			
			<?php _e( 'Return to Boilerplate', 'boilerplate-extension'); ?>
																																						</a>
				<script type="text/javascript">
					jQuery(document).ready(function($) {
						jQuery(document).on( 'click', '.bpl_bulkpost_delete_post', function(e) {
							if (confirm(__('Are you sure?'))) {
								var parent = $(this).closest('.bpl_bulkpost_posting_item');
								var type = parent.attr('type');
								parent.find('.bpl_bulkpost_delete_process').html('<i class="fa fa-spinner fa-pulse"></i> Deleting... ').show();
								$(this).hide();
								jQuery.post(ajaxurl, {
									action: 'mainwp_boilerplate_delete_site_post',
									post_id: $(this).attr('post_id'),
									site_id: $(this).attr('site_id'),
									boilerplate_id: $(this).attr('boilerplate_id')
								}, function(data) {
									parent.find('.bpl_bulkpost_delete_process').hide();
									if (data && data['status'] == 'SUCCESS') {
										parent.find('.bpl_bulkpost_delete_status').html('<i class="fa fa-check-circle"></i> '+'The ' + type + ' was deleted successfully.');
									}
									else
									{
										parent.find('.bpl_bulkpost_delete_status').html('<i class="fa fa-exclamation-circle"></i> '+'Can not delete the ' + type + '.');
									}
								}, 'json');
							}
							return false;
						});
					});
				</script>
				<?php
				return true;
			}
		}
		return false;
	}

	public function after_posting_bulkpage( $input, $post, $dbwebsites, $output ) {
		global $mainWPBoilerplateExtensionActivator;

		if ( $post ) {
			if ( 'yes' == get_post_meta( $post->ID, '_mainwp_boilerplate', true ) ) {
				// calculate what post is create new, updated or will delete
				self::bulkposting_posting_done_results( $post, $dbwebsites, $output );

				$sites_posts   = get_post_meta( $post->ID, '_mainwp_boilerplate_sites_posts', true );
				$new_posts     = $sites_posts['new_posts'];
				$updated_posts = $sites_posts['updated_posts'];
				$aksto_delete  = $sites_posts['aks_to_delete'];

				$websites = apply_filters( 'mainwp-getsites', $mainWPBoilerplateExtensionActivator->get_child_file(), $mainWPBoilerplateExtensionActivator->get_child_key(), null );

				$all_sites = array();
				if ( is_array( $websites ) ) {
					foreach ( $websites as $website ) {
						$all_sites[ $website['id'] ] = $website;
					}
				}

				update_post_meta( $post->ID, '_bulkpost_do_not_del', 'yes' );

				?>
			<div class="ui relaxed list">
				<?php
				if ( is_array( $new_posts ) ) {
					foreach ( $new_posts as $site_id => $_post ) {
						if ( isset( $all_sites[ $site_id ] ) ) {
							$website = $all_sites[ $site_id ];
							echo '<div class="item">' . $website['name'] . ': New page created. <a href="' . $_post['link'] . '"  target=\"_blank\">View Page</a></div>';
						}
					}
				}

				if ( is_array( $updated_posts ) ) {
					foreach ( $updated_posts as $site_id => $_post ) {
						if ( isset( $all_sites[ $site_id ] ) ) {
							$website = $all_sites[ $site_id ];
							echo '<div class="item">' . $website['name'] . ': Page updated. <a href="' . $_post['link'] . '"  target=\"_blank\">View Page</a></div>';
						}
					}
				}
				if ( is_array( $aksto_delete ) ) {
					foreach ( $aksto_delete as $site_id => $_post ) {
						if ( isset( $all_sites[ $site_id ] ) ) {
							$website = $all_sites[ $site_id ];
							echo '<div class="item bpl_bulkpost_posting_item" type="page">' . $website['name'] . ': <span class="bpl_bulkpost_delete_status">Delete the page? <span class="bpl_bulkpost_delete_process hidden"></span><a href="#" class="bpl_bulkpost_delete_post" boilerplate_id="' . $post->ID . '"  site_id="' . $site_id . '" post_id="' . $_post['post_id'] . '">Delete</a> | <a href="' . $_post['link'] . '"  target=\"_blank\">View Page</a></span></div>';
						}
					}
				}
				foreach ( $dbwebsites as $website ) {
					if ( ! isset( $output->ok[ $website->id ] ) ) {
						$err = $output->errors[ $website->id ];

						if ( $err == 'Error - Empty post id' ) {
							$err = ': Not found the post on child site to save.';
						} else {
							$err = ': ERROR - ' . $err;
						}

						echo '<div class="item">' . $website->name . $err . '</div>';
					}
				}
				?>
			</div>
			<br/>
			<a href="<?php echo get_admin_url(); ?>admin.php?page=PageBulkAdd&boilerplate=1" class="ui green button" target="_top"><?php _e( 'Add New Boilerplate', 'boilerplate-extension' ); ?></a>
			<a href="<?php echo get_admin_url(); ?>admin.php?page=Extensions-Boilerplate-Extension" class="ui green button" target="_top"><?php _e( 'Return to Boilerplate', 'boilerplate-extension' ); ?></a>

				<script type="text/javascript">
					jQuery(document).ready(function($) {
						jQuery(document).on( 'click', '.bpl_bulkpost_delete_post', function(e) {
							if (confirm(__('Are you sure?'))) {
								var parent = $(this).closest('.bpl_bulkpost_posting_item');
								var type = parent.attr('type');
								parent.find('.bpl_bulkpost_delete_process').html('<i class="notched circle loading icon"></i> Deleting... ').show();
								$(this).hide();
								jQuery.post(ajaxurl, {
									action: 'mainwp_boilerplate_delete_site_post',
									post_id: $(this).attr('post_id'),
									site_id: $(this).attr('site_id'),
									boilerplate_id: $(this).attr('boilerplate_id')
								}, function(data) {
									parent.find('.bpl_bulkpost_delete_process').hide();
									if (data && data['status'] == 'SUCCESS') {
										parent.find('.bpl_bulkpost_delete_status').html('<i class="green check icon"></i> '+'The ' + type + ' was deleted successfully.');
									}
									else
									{
										parent.find('.bpl_bulkpost_delete_status').html('<i class="red times icon"></i> Can not delete the ' + type + '.');
									}
								}, 'json');
							}
							return false;
						});
					});
			</script>
				<?php
				return true; // return true to output
			}
		}

		return $input; // return false to no output
	}

	// Review that can be removed here.
	private function create_token_item( $token, $with_tr = true ) {

		$colspan = $html = '';
		if ( $token->type == 1 ) {
			$colspan = ' colspan="2" '; }
		if ( $with_tr ) {
			$html = '<tr class="mainwp-token" token_id="' . $token->id . '">'; }
		$html .= '<td class="token-name">
                <span class="text" ' . ( ( $token->type == 1 ) ? '' : 'value="' . $token->token_name ) . '">[' . stripslashes( $token->token_name ) . ']</span>' .
						( ( $token->type == 1 ) ? '' : '<span class="input hidden"><input type="text" value="' . htmlspecialchars( stripslashes( $token->token_name ) ) . '" name="token_name"></span>' ) .
			'</td>
                <td class="token-description" ' . $colspan . '>
                    <span class="text" ' . ( ( $token->type == 1 ) ? '' : 'value="' . stripslashes( $token->token_description ) ) . '">' . stripslashes( $token->token_description ) . '</span>';
		if ( $token->type != 1 ) {
			$html .= '<span class="input hidden"><input type="text" value="' . htmlspecialchars( stripslashes( $token->token_description ) ) . '" name="token_description"></span>
                        <span class="mainwp_more_loading"><i class="fa fa-spinner fa-pulse"></i></span>';
		}
		$html .= '</td>';

		if ( $token->type == 0 ) {
			$html .= '<td class="token-option">
                    <span class="mainwp_group-actions actions-text" ><a class="managetoken-edit" href="#"><i class="fa fa-pencil-square-o"></i> ' . __( 'Edit', 'mainwp' ) . '</a> | <a ><i class="fa fa-trash-o"></i> ' . __( 'Delete', 'mainwp' ) . '</a></span>
                    <span class="mainwp_group-actions actions-input hidden" ><a class="managetoken-save" href="#"><i class="fa fa-floppy-o"></i> ' . __( 'Save', 'mainwp' ) . '</a> | <a class="managetoken-cancel" href="#"><i class="fa fa-times-circle"></i> ' . __( 'Cancel', 'mainwp' ) . '</a></span>
              </td>';
		}

		if ( $with_tr ) {
			$html .= '</tr>';
		}
		return $html;
	}


	// This function calculate if post is to be updated, deleted or created
	static function bulkposting_posting_done_results( $post, $dbwebsites, $output ) {

		if ( ! $post ) {
			return;
		}

		if ( ! $post || ( $post->post_type != 'bulkpost' && $post->post_type != 'bulkpage' ) ) {
			return;
		}

		if ( get_post_meta( $post->ID, '_mainwp_boilerplate', true ) != 'yes' ) {
			return;
		}

		$sites_posts = get_post_meta( $post->ID, '_mainwp_boilerplate_sites_posts', true );

		$just_posts = $previous_posts = $new_posts = $updated_posts = $aksto_delete = array();

		if ( is_array( $sites_posts ) && isset( $sites_posts['previous_posts'] ) ) {
			$previous_posts = $sites_posts['previous_posts'];
		}

		if ( ! is_array( $previous_posts ) ) {
			$previous_posts = array();
		}

		if ( is_array( $dbwebsites ) ) {
			foreach ( $dbwebsites as $website ) {
				if ( isset( $output->errors[ $website->id ] ) ) {
					$err = $output->errors[ $website->id ];
					if ( $err == __( 'Post ID not found. Please, try again.', 'boilerplate-extension' ) ) {
						if ( isset( $previous_posts[ $website->id ] ) ) {
								unset( $previous_posts[ $website->id ] ); // not found the post on child site to update so remove in the previous list
						}
					}
				} elseif ( isset( $output->ok[ $website->id ] ) && ( $output->ok[ $website->id ] == 1 ) && ( isset( $output->added_id[ $website->id ] ) ) ) {
					$just_posts[ $website->id ] = array(
						'post_id' => $output->added_id[ $website->id ],
						'link'    => $output->link[ $website->id ],
					);
				}
			}
		}

		if ( is_array( $previous_posts ) ) {
			foreach ( $previous_posts as $site_id => $_post ) {
				if ( isset( $just_posts[ $site_id ] ) && $just_posts[ $site_id ]['post_id'] == $_post['post_id'] ) {
					$updated_posts[ $site_id ] = $just_posts[ $site_id ];
				} elseif ( ! isset( $just_posts[ $site_id ] ) ) {
					$aksto_delete[ $site_id ] = $previous_posts[ $site_id ];
				}
			}
		}

		if ( ! is_array( $previous_posts ) || count( $previous_posts ) <= 0 ) {
			$new_posts = $just_posts;
		} elseif ( is_array( $just_posts ) && is_array( $previous_posts ) ) {
			foreach ( $just_posts as $site_id => $_post ) {
				if ( ! isset( $previous_posts[ $site_id ] ) || ( isset( $previous_posts[ $site_id ] ) && $previous_posts[ $site_id ]['post_id'] != $_post['post_id'] ) ) {
					$new_posts[ $site_id ] = $_post;
				}
			}
		}

		// to fix create new posts/pages issue
		foreach ( $just_posts as $_siteid => $value ) {
			if ( ! isset( $previous_posts[ $_siteid ] ) ) {
				$previous_posts[ $_siteid ] = $value; // saved posts/pages was created on child sites
			}
		}

		$sites_posts                   = array();
		$sites_posts['previous_posts'] = $previous_posts;
		$sites_posts['new_posts']      = $new_posts;
		$sites_posts['updated_posts']  = $updated_posts;
		$sites_posts['aks_to_delete']  = $aksto_delete;

		update_post_meta( $post->ID, '_mainwp_boilerplate_sites_posts', $sites_posts );
	}


}
