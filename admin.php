<?php
session_start();
$table_rows=$invoice_list=$date=$paid=$customer=$description=$msg='';
$sub_total=$tax=$grand_total=0;
$page_title='Invoice Admin';

if (!file_exists('data.json')) { /* {{{ */
  $data='{}';
  $handle=fopen("data.json", "w");
  fwrite($handle, $data);
  fclose($handle);
  chmod("data.json",0600);
}
/* }}} */

/* Functions {{{ */
function rand_string($length){
	$result = "";
	$chars = "ABCDEFHJKLMNPQRTUVWXYZ23456789";
	$charArray = str_split($chars);

	for($i = 0; $i < $length; $i++){
		$randItem = array_rand($charArray);
		$result .= $charArray[$randItem];
	}
	return $result;
}
/* }}} */

/* troublshooter: {{{ */

// if (count($_POST)>3) {
//   $tbl='<table border="0" cellspacing="2" cellpadding="8">';
//   foreach ($_POST as $k => $v) {
//     $k = urldecode(stripslashes($k));
//     $v = urldecode(stripslashes($v));
//     $tbl.='
//     <tr>
//       <td>'. $k .'</td>
//       <td>'. $v .'</td>
//       <td>'. gettype($v) .'</td>
//     </tr>';
//   }
//   $tbl.='</table>';
//   echo $tbl;
//   die; 
// }

/* }}} */

if (isset($_REQUEST['logout'])) { /* {{{ */
	$msg.='<p class="msg success">You Have Successfully Logged Out</p>';
	session_destroy();
	header('Refresh: 3;url=admin.php');
}
/* }}} */

if (isset($_POST['create_account'])) { /* {{{ */
	$page_title='Account Created';
	$username=isset($_POST["username"]) ?  $_POST["username"] : '';
	$password=isset($_POST["password"]) ?  password_hash($_POST["password"], PASSWORD_DEFAULT) : '';

	if ($username=='' || $password=='') $msg.='<p class="msg error">Missing Required Info</p>';
	else {
		$data='<?php
$username=\''.$username.'\';
$password=\''.$password.'\';';

		$handle=fopen("login.php", "w");
		fwrite($handle, $data);
		fclose($handle);
		$_SESSION['admin_session']=$username;
	}
}
/* }}} */

if (!file_exists('login.php')) { /* {{{ */
  $page_title = 'Create Your Admin Login';
	$_SESSION['admin_session']='';
  $page_body='
<div class="login_box">
  <h2 class="text-center">Create Admin</h2>
  <form action="admin.php" enctype="multipart/form-data" method="post">
    <input class="login_field" type="text" name="username" placeholder="Username" />
    <input class="login_field" type="password" name="password" placeholder="Password" />
    <input type="hidden" name="create_account" />
    <input class="btn" type="submit" value="Submit" />
  </form>
</div>
';
  /* }}} */
} else {
  /* {{{ */

  /* Login Post: {{{ */
	require_once "login.php";
	if (isset($_POST['login'])) {
    if ($_POST['username']==$username && password_verify($_POST["password"], $password)) {
      $_SESSION['admin_session']=$username;
    } else $msg='<p class="msg error">Username Or Password Incorrect</p>';
	}
/* }}} */

  /* Create Login Form {{{ */
  if (!isset($_SESSION['admin_session']) || $_SESSION['admin_session']=='') {
    $page_title='Log In';
    $page_body='
      <div class="login_box">
      <h2 class="text-center">Login</h2>
      <form action="admin.php" enctype="multipart/form-data" method="post">
      <input class="login_field" type="text" name="username" placeholder="Username" />
      <input class="login_field" type="password" name="password" placeholder="Password" />
      <input type="hidden" name="login" />
      <input class="btn" type="submit" value="Submit" />
      </form>
      </div>
      <p style="opacity:.4; font-size:.8em; text-align: center;">If you forget your password log into your hosting account and delete the "login.php" file</p>
      ';

  } else {
    /* }}} */
    // NOTE: Now You're Logged In
    /* Get Raw Invoice Json Data {{{ */
    $i=isset($_REQUEST['i']) ? preg_replace('#[^0-9a-z]#i', '', $_REQUEST['i']) : '';
    $invoice= strtoupper($i);
    $data = file_get_contents('data.json');
    $arr = json_decode($data, true);
    /* }}} */

		if (isset($_GET['create_new'])) { /* {{{ */
      $rand=rand_string(8);
      while (array_key_exists($rand, $arr)) $rand=rand_string(8);
      header('Location: admin.php?i='.$rand);
      die();

    /* }}} */
		} elseif (isset($_POST['add_customer'])) { /* {{{ */
      $customer=$_POST['customer'];
      $date=$_POST['date'];
      $paid=$_POST['paid'];
      $invoice=$_POST['invoice'];

      $arr["$invoice"] = array (
        'customer' => "$customer",
        'date' => "$date",
        'paid' => "$paid",
        'rows' => array(),
      );

      $reload_json=true;

    /* }}} */
		} elseif (isset($_POST['add_row'])) { /* {{{ */
      $description=$_POST['description'];
      $unit=$_POST['unit'];
      $qty=$_POST['qty'];
      $invoice=$_POST['invoice'];

      $new_row=array( 'description' => "$description", 'unit' => "$unit", 'qty' => "$qty" );
      array_push($arr["$invoice"]['rows'],$new_row);

      $reload_json=true;

    /* }}} */
		} elseif (isset($_POST['del_row'])) { /* {{{ */
      $row=$_POST['row'];
      $invoice=$_POST['invoice'];
      unset($arr[$invoice]['rows'][$row]);

      $reload_json=true;

    /* }}} */
		} elseif (isset($_POST['update_paid'])) { /* {{{ */
      $paid=$_POST['paid'];
      $invoice=$_POST['invoice'];
      $arr[$invoice]['paid']=$paid;

      $reload_json=true;

    /* }}} */
		} // END POSTS/REQUESTS LOGICS

    // Update The Json File, If Modfied Above: {{{ */
    if (isset ($reload_json) && $reload_json==true) {
      if (file_put_contents('data.json', json_encode($arr))) {
        $msg.='<p class="msg success">Invoice Updated</p>';
        $data = file_get_contents('data.json');
        $arr = json_decode($data, true);
      } else $msg.='<p class="msg error">Invoice Updated</p>';
    }
    /* }}} */

    // Create list of all invoices: {{{ */
    foreach ($arr as $k => $v) {
      $invoice_list.='
        <tr>
        <td class="description" data-label="Customer">'. $v['customer'] .'</td>
        <td class="unit" data-label="Date">'. $v['date'] .'</td>
        <td class="qty" data-label="Paid">'. $v['paid'] .'</td>
        <td class="description" data-label="ID"> <a href="admin.php?i='. $k .'">'. $k .'</a></td>
        </tr>';
    }
    /* }}} */
    /* Creat Invoice Data {{{ */
    if (array_key_exists($i, $arr)) {
      $isInvoice=true;
      $date=$arr[$i]['date'];
      $customer=$arr[$i]['customer'];
      $paid=$arr[$i]['paid'];
      $n=0;
      foreach ($arr[$i]['rows'] as $row) {
        $u=$row['unit'];
        $q=$row['qty'];
        if (is_numeric($u) && $u>0  && is_numeric($q) && $q>0) $amt=$u*$q;
        else $amt=0;

        $sub_total=$sub_total+$amt;
        $table_rows.='
          <tr>
          <td class="description" data-label="Description">'. $row['description'] .'</td>
          <td class="unit" data-label="Unit Price">'. $row['unit'] .'</td>
          <td class="qty" data-label="Quantity">'. $row['qty'] .'</td>
          <td data-label="Remove">
          <form action="admin.php?i='. $invoice .'" method="post">
            <input type="hidden" name="del_row" />
            <input type="hidden" name="invoice" value="'. $invoice .'" />
            <input type="hidden" name="row" value="'. $n .'" />
            <input type="submit" value="Remove" />
          </form>
          </td>
          </tr>';
        $n++;
      }
      $tax=$sub_total*0.13;
      $grand_total=$sub_total+$tax;
    }
    /* END Create Invoice Data }}} */

	}  // END LOGGED IN LOGIC
}  // END LOGIN FILE CHECK }}} */

if ($date=='')  $date = date('Y-m-d');
if ($paid=='')  $paid = 0;

/* Head {{{ */
?>
<!DOCTYPE html
  PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>
    <?php echo $page_title; ?>
  </title>
  <link rel="stylesheet" href="style.css" media="all">
  <script src="script.js"></script>
</head>

<body>
  <div class="wrap">
    <main>
      <?php echo $msg;
/* }}} */

if (!isset($_SESSION['admin_session']) || $_SESSION['admin_session']=='') {
  echo $page_body;
} else { ?>
      <nav>
        <span class="nav"><a href="admin.php?list=1">List All</a></span> |
        <span class="nav"><a href="admin.php?create_new=1">Create New</a></span> |
        <span class="nav"><a href="admin.php?logout=1">Logout</a></span>
      </nav>

      <?php if ($invoice=='') { ?>
      <h2 class="text-center">Current Invoices</h2>
      <table>
        <thead>
          <tr>
            <th scope="col">Customer</th>
            <th scope="col">Date</th>
            <th scope="col">Paid</th>
            <th scope="col">ID</th>
          </tr>
        </thead>

        <tbody>
          <?php echo $invoice_list; ?>
        </tbody>
      </table>


      <?php } else { ?>

      <h1 class="text-center">Invoice:
        <?php echo $invoice; ?>
      </h1>
      <div class="text-center">
        <?php if (!isset($isInvoice)) { ?>
        <form action="admin.php?i=<?php echo $invoice; ?>" 
        enctype="multipart/form-data" method="post" style="max-width:50ch; margin: 1em auto 3em;">
          <input class="login_field" type="text" name="customer" placeholder="Customer" />
          <input class="login_field" type="text" name="date" placeholder="YYYY-MM-DD" value="<?php echo $date; ?>" />
          <input class="login_field" type="text" name="paid" placeholder="Amount Paid (No $$ Signs)" />
          <input type="hidden" name="add_customer" />
          <input type="hidden" name="invoice" value="<?php echo $invoice; ?>" />
          <input class="btn" type="submit" value="Submit" />
        </form>
        <?php } else { ?>

        <h4 style="margin:0;">Linkd To Invoice</h4>
        <input type="text" id="invoice_url" 
        value="<?php echo 'https://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/?i='.$invoice; ?>" readonly />
        <button onclick="copyUrl()">Copy</button> 
        <h3>
          <span>Customer:</span>
          <span><?php echo $customer; ?></span>
          <br>
          <span>Date:</span>
          <span><?php echo $date; ?></span>
        <form action="admin.php?i=<?php echo $invoice; ?>" 
        enctype="multipart/form-data" method="post" style="max-width:50ch; margin: 1em auto 3em;">
        <span>Paid:</span>
          <input type="text" name="paid" value="<?php echo $paid; ?>" size="4"/>
          <input type="hidden" name="update_paid" />
          <input type="hidden" name="invoice" value="<?php echo $invoice; ?>" />
          <input type="submit" value="Update" />
        </form>
        </h3>

        <form action="admin.php?i=<?php echo $invoice; ?>" 
        enctype="multipart/form-data" method="post" style="margin: 1em auto;" >
          <input type="text" name="description" placeholder="Description" />
          <input type="text" name="unit" placeholder="Unit Price" />
          <input type="text" name="qty" placeholder="Quantity" />
          <input type="hidden" name="add_row" />
          <input type="hidden" name="invoice" value="<?php echo $invoice; ?>" />
          <input type="submit" value="Add Row" />
        </form>

        <table>
          <thead>
            <tr>
              <th scope="col">Description</th>
              <th scope="col">Unit Price</th>
              <th scope="col">Quantity</th>
              <th scope="col">Remove</th>
            </tr>
          </thead>
          <tbody>
            <?php echo $table_rows; ?>
          </tbody>
        </table>

        <div class="totals">
          <div class="row two_col">
            <span>Sub Total:</span>
            <span>$
              <?php echo $sub_total; ?>
            </span>
          </div>

          <div class="row two_col">
            <span>Tax:</span>
            <span>$
              <?php echo $tax; ?>
            </span>
          </div>

          <div class="row two_col">
            <span>Grand Total:</span>
            <span>$
              <?php echo $grand_total; ?>
            </span>
          </div>

        </div>

        <?php } // END isset($isInvoice)
          } // END $invoice=='' ?>

      <?php } //End Logged In Logics ?>
    </main>
  </div>
</body>
</html>
