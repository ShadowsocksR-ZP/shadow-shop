<?php

/**
 * The admin area of the plugin to load the User List Table
 */

if (isset($_REQUEST['action']) && -1 != $_REQUEST['action']) {
	$current_action = $_REQUEST['action'];
} elseif (isset($_REQUEST['action2']) && -1 != $_REQUEST['action2']) {
	$current_action = $_REQUEST['action2'];
} else {
	$current_action = null;
}

switch ($current_action) {
	case 'dodelete':

		check_admin_referer('delete-accounts');

		if (empty($_REQUEST['accounts'])) {
			wp_redirect($redirect);
			exit();
		}

		$accountids = (array)$_REQUEST['accounts'];

		$update = 'del';
		$delete_count = 0;

		$error_messages = array();
		foreach ($accountids as $id) {

			$data_array = array (
				"ids" => array($id),
			);
		
			$return = Shadowsocks_Hub_Helper::call_api("GET", "http://sshub/api/account/accounts", $data_array);
		
			$error = $return['error'];
			$http_code = $return['http_code'];
			$response = $return['body'];
		
			if ($http_code === 200) {
				$userId = $response[0]['purchase']['userId'];
				$host = $response[0]['node']['server']['ipAddressOrDomainName'];
				$port = $response[0]['port'];
				$user = get_user_by('id', (int) $userId);
				$userEmail = $user->data->user_email;

			} elseif ($http_code === 400) {
				$error_messages[] = urlencode("Invalid account id");
				continue;
			} elseif ($http_code === 500) {
				$error_messages[] = urlencode("Backend system error (getAccountsByIds)");
				continue; // no need to proceed with calling delete
			} elseif ($error) {
				$error_messages[] = urldecode("Backend system error: ".$error);
				continue;
			} else {
				$error_messages[] = urldecode("Backend system error undetected error.");
				continue;
			};

			$data_array = array (
				"id" => $id,
			);
		
			$return = Shadowsocks_Hub_Helper::call_api("DELETE", "http://sshub/api/account", $data_array);
		
			$error = $return['error'];
			$http_code = $return['http_code'];
			$response = $return['body'];
		
			if ($http_code === 204) {
				++$delete_count;
			} elseif ($http_code === 400) {
				$error_messages[] = urlencode("Validation error");
			} elseif ($http_code === 409) {
				$error_messages[] = urlencode("Account ($host; $port; $userEmail) is in use. Delete its accounts first.");
			} elseif ($http_code === 500) {
				$error_messages[] = urlencode("Backend system error (deleteAccount)");
			} elseif ($error) {
				$error_messages[] = urldecode("Backend system error: ".$error);	
			} else {
				$error_messages[] = urldecode("Backend system error undetected error.");
			};
		}

		$redirect = add_query_arg( array(
			'delete_count' => $delete_count, 
			'update' => $update,
			'errors' => $error_messages,
		), admin_url('admin.php?page=shadowsocks_hub_accounts'));
		
		wp_redirect($redirect);
		exit();

	case 'delete':

        //check_admin_referer('delete-accounts');
        
		if (empty($_REQUEST['accounts']))
			$accountids = array($_REQUEST['account']);
		else
			$accountids = (array)$_REQUEST['accounts'];

		?>

<form method="post" name="updateaccounts" id="updateaccounts">
<?php wp_nonce_field('delete-accounts') ?>

<div class="wrap">
<h1><?php _e('Delete Accounts'); ?></h1>
<?php if (isset($_REQUEST['error'])) : ?>
	<div class="error">
		<p><strong><?php _e('ERROR:'); ?></strong> <?php _e('Please select an option.'); ?></p>
	</div>
<?php endif; ?>

<?php if (1 == count($accountids)) : ?>
	<p><?php _e('You have specified this account for deletion:'); ?></p>
<?php else : ?>
	<p><?php _e('You have specified these accounts for deletion:'); ?></p>
<?php endif; ?>

<ul>
<?php
$go_delete = 0;
foreach ($accountids as $id) {

	$data_array = array (
		"ids" => array($id),
	);

	$return = Shadowsocks_Hub_Helper::call_api("GET", "http://sshub/api/account/accounts", $data_array);

    $error = $return['error'];
    $http_code = $return['http_code'];
	$response = $return['body'];

	if ($http_code === 200) {
		$userId = $response[0]['purchase']['userId'];
		$host = $response[0]['node']['server']['ipAddressOrDomainName'];
		$port = $response[0]['port'];
		$user = get_user_by('id', (int) $userId);
		$userEmail = $user->data->user_email;
	} elseif ($http_code === 400) {
		$error_message = "Invalid accound id";
	} elseif ($http_code === 500) {
		$error_message = "Backend system error (getAccountsByIds)";
	} elseif ($error) {
		$error_message = "Backend system error: ".$error;
	} else {
		$error_message = "Backend system error undetected error.";
	};
	
	if ($http_code === 200) {
		echo "<li><input type=\"hidden\" name=\"accounts[]\" value=\"" . esc_attr($id) . "\" />" . sprintf(__('Host: <strong> %1$s </strong>; Port: <strong> %2$s </strong>; User: <strong> %3$s </strong>'), $host, $port, $userEmail) . "</li>\n";
		$go_delete++;	
	} else {
		echo "<li><input type=\"hidden\" name=\"accounts[]\" value=\"" . esc_attr($id) . "\" />" . sprintf(__('<strong> %1$s </strong>'), $error_message) . "</li>\n";
	}
}
?>
	</ul>
<?php if ($go_delete) :
?>
	<input type="hidden" name="action" value="dodelete" />
	<?php submit_button(__('Confirm Deletion'), 'primary'); ?>
<?php else : ?>
	<p><?php _e('There are no valid accounts selected for deletion.'); ?></p>
<?php endif; ?>
</div>
</form>
<?php
break;
default:

$messages = array();
	if ( isset($_GET['update']) ) :
		switch($_GET['update']) {
		case 'del':
		case 'del_many':
			$delete_count = isset($_GET['delete_count']) ? (int) $_GET['delete_count'] : 0;
			if ( 1 == $delete_count ) {
				$message = __( 'Account deleted.' );
			} else {
				$message = _n( '%s accounts deleted.', '%s accounts deleted.', $delete_count );
			}
			$messages[] = '<div id="message" class="updated notice is-dismissible"><p>' . sprintf( $message, number_format_i18n( $delete_count ) ) . '</p></div>';
			break;
		case 'add':
			if ( isset( $_GET['id'] ) && ( $user_id = $_GET['id'] ) && current_user_can( 'edit_user', $user_id ) ) {
				/* translators: %s: edit page url */
				$messages[] = '<div id="message" class="updated notice is-dismissible"><p>' . sprintf( __( 'New account added.' ),
					esc_url( add_query_arg( 'wp_http_referer', urlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) ),
						self_admin_url( 'user-edit.php?user_id=' . $user_id ) ) ) ) . '</p></div>';
			} else {
				$messages[] = '<div id="message" class="updated notice is-dismissible"><p>' . __( 'New account added.' ) . '</p></div>';
			}
			break;
		}
	endif; ?>

<?php if ( isset($_REQUEST['errors']) ) : ?>
	<div class="error">
		<ul>
		<?php
			$error_messages = $_REQUEST['errors'];
			foreach ( $error_messages as $err )
			echo "<li>$err</li>\n";
		?>
		</ul>
	</div>
<?php endif;

if ( ! empty($messages) ) {
	foreach ( $messages as $msg )
		echo $msg;
}
	?>
<div class="wrap">    
	<h2>
		<?php _e('Accounts'); ?>
		<a href="<?php echo admin_url('admin.php?page=shadowsocks_hub_add_account'); ?>" class="page-title-action"><?php echo esc_html_x('Add New', 'account'); ?></a>
	</h2>
	<?php
	$return = Shadowsocks_Hub_Helper::call_api("GET", "http://sshub/api/account/all", false);

    $error = $return['error'];
    $http_code = $return['http_code'];
	$response = $return['body'];

	$data = array();
	if ($http_code === 200) {
        $arr_length = count($response);

        for ($i = 0; $i < $arr_length; $i++) {
			$userId = $response[$i]['purchase']['userId'];
			$user = get_user_by('id', (int) $userId);
			$userEmail = $user->data->user_email;

            $data[] = array(
				'id' => $response[$i]['id'],
				'protocol' => $response[$i]['node']['protocol'],
				'host' => $response[$i]['node']['server']['ipAddressOrDomainName'],
				'port' => $response[$i]['port'],
				'password' => $response[$i]['password'],
				'user' => $userEmail,
				'orderId' => $response[$i]['purchase']['orderId'],
				'lifeSpan' => $response[$i]['purchase']['lifeSpan'],
				'encryption' => $response[$i]['method'],
                'created_date' => date_i18n(get_option('date_format'), $response[$i]['createdTime'] / 1000).' '.date_i18n(get_option('time_format'), $response[$i]['createdTime'] / 1000),
                'epoch_time' => $response[$i]['createdTime'],
			);
		}
	} elseif ($http_code === 500) {
		$error_message = "Backend system error (getAllAccounts)";
	} elseif ($error) {
		$error_message = "Backend system error: ".$error;
	} else {
		$error_message = "Backend system error undetected error.";
	}; 

	if ($http_code === 200) {
		$this->accounts_obj->set_table_data($data);
	} else { ?>
		<div class="error">
		<ul>
		<?php
			echo "<li>$error_message</li>\n";
		?>
		</ul>
	</div>
	<?php
	}
	?>
	<form id="shadowsocks-hub-accounts-list-form" method="get">
		<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
		<?php
			$this->accounts_obj->prepare_items();
			$this->accounts_obj->search_box(__('Search Accounts'), 'shadowsocks-hub-node-find');
			$this->accounts_obj->display();
	?>					
	</form>
</div>

<?php

} // end of the $doaction switch
?>