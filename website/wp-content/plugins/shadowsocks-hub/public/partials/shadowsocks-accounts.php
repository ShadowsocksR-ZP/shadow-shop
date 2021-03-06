<?php 
$userId = get_current_user_id();

$data_array = array (
    "uiType" => "wordpress",
    "userId" => $userId,
);
            
$return = Shadowsocks_Hub_Helper::call_api("GET", "http://sshub/api/account/accounts_by_user_id", $data_array);

$error = $return['error'];
$http_code = $return['http_code'];
$response = $return['body'];

if ($http_code === 200) {
    $accounts = $response;
} elseif ($http_code === 500) {
	$error_message = "Backend system error (getAccountsByUserId)";
} elseif ($error) {
	$error_message = "Backend system error: ".$error;
} else {
	$error_message = "Backend system error undetected error.";
}; 

if ($http_code !== 200) { ?>
    <div class="error">
        <ul>
            <?php echo "<li>$error_message</li>\n"; ?>
        </ul>
    </div>
<?php
}

if ( empty($accounts) ) { ?>
    <div class="woocommerce-message woocommerce-message--info woocommerce-Message woocommerce-Message--info woocommerce-info">
		<a class="woocommerce-Button button" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
			<?php _e( 'Go shop', 'woocommerce' ) ?>
		</a>
	</div>
    <?php
    die();
}
?>

<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
	<thead>
		<tr>
            <th class="woocommerce-orders-table__header"><span class="nobr">Host</span></th>
            <th class="woocommerce-orders-table__header"><span class="nobr">Port</span></th>
            <th class="woocommerce-orders-table__header"><span class="nobr">Password</span></th>
            <th class="woocommerce-orders-table__header"><span class="nobr">Encryption</span></th>
            <th class="woocommerce-orders-table__header"><span class="nobr">Actions</span></th>
		</tr>
	</thead>

	<tbody>
            <?php 
            foreach ( $accounts as $account ) :
                $host = $account['node']['server']['ipAddressOrDomainName'];
                $port = $account['port'];
                $password = $account['password'];
                $method = $account['method'];
                $url = "ss://" . base64_encode("$method:$password@$host:$port")?>
			<tr class="woocommerce-orders-table__row">
                <td class="woocommerce-orders-table__cell" data-title="Host"><?php echo $host?></td>
                <td class="woocommerce-orders-table__cell" data-title="Port"><?php echo $port?></td>
                <td class="woocommerce-orders-table__cell" data-title="Password"><?php echo $password?></td>
                <td class="woocommerce-orders-table__cell" data-title="Encryption"><?php echo $method?></td>
                <td class="woocommerce-orders-table__cell" data-title="Actions">
                    <button class="woocommerce-button button qrcode" url="<?php echo $url?>">QR code</button>
                </td>
			<?php endforeach; ?>
	</tbody>
</table>

<script type="text/javascript">
	var orderElement = document.querySelector(".woocommerce-MyAccount-navigation ul li.woocommerce-MyAccount-navigation-link--orders a");
	var allQrcodeButtonElements = document.querySelectorAll(".woocommerce-button.qrcode");
    var orderIcon = getComputedStyle(orderElement, "::before").content;
    allQrcodeButtonElements.forEach( function(element) {
	    if (orderIcon === "\"\uf291\"") {
		    element.setAttribute("menu-icon", "\uf029");
	    } else {
            element.setAttribute("menu-icon", "");
        }
	});
</script>

<div id="myModal" class="modal">
        <div class="modal-content">
            <span id="close">&times;</span>
            <div class="vertical-center-container">
            <div class="qrcode-container">
                <div class="qrcode-div">
                    <span id="sshub-qr-code"></span>
                </div>
            </div>
            </div>
        </div>
    </div>

<script type="text/javascript">
    var modal = document.getElementById("myModal");
    var btnClass = document.getElementsByClassName("qrcode");
    var span = document.getElementById("close");
    var qrcode = new QRCode(document.getElementById("sshub-qr-code"), {
                        width : '200',
                        height : '200',
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel : QRCode.CorrectLevel.H
                        });

    for (i = 0; i < btnClass.length; i++) {
        btnClass[i].onclick = function() {
            modal.style.display = "block";
            var url = jQuery( this ).attr('url');
            jQuery( document ).ready(function() {
                        qrcode.makeCode(url);
                    });
        }
    }

    span.onclick = function() {
        modal.style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == modal) {
                modal.style.display = "none";
        }
    }
</script>