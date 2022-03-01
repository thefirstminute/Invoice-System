<?php
$i=isset($_REQUEST['i']) ? preg_replace('#[^0-9a-z]#i', '', $_REQUEST['i']) : 'asdf';

$table_rows=$date=$customer='';
$sub_total=$tax=$grand_total=0;
$rows=array();

$data = file_get_contents('data.json');
$arr = json_decode($data, true);

if (array_key_exists($i, $arr)) {
  $invoice= strtoupper($i);

  $date=$arr[$i]['date'];
  $customer=$arr[$i]['customer'];


  foreach ($arr[$i]['rows'] as $row) {
    $amt=$row['unit']*$row['qty'];
    $sub_total=$sub_total+$amt;

    $table_rows.='
          <tr>
            <td class="description" data-label="Description">'. $row['description'] .'</td>
            <td class="prices" data-label="Unit Price">$'. $row['unit'] .'</td>
            <td class="prices" data-label="Quantity">'. $row['qty'] .'</td>
            <td class="prices" data-label="Amount">$'. $amt .'</td>
          </tr>';
  }
  $tax=$sub_total*0.13;
  $grand_total=$sub_total+$tax;

} else {
  echo "<h1>nope</h1>";
  die('<style> body { background-color: #333; color:#EEE; }</style>');
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="style.css" media="all">
<title>Invoice ID: <?php echo $invoice; ?></title>
</head>
<body>
  <div class="wrap">
    <header>
      <div class="owner">
        <div class="logo">
          <img src="logo.png" alt="Logo">
        </div>
        <p>Your Name</p>
        <p>Your Address</p>
        <p>Other Info??</p>
      </div>

      <div class="customer">

        <div class="row two_col">
          <span>Invoice ID:</span>
          <span><?php echo $invoice; ?></span>
        </div>

        <div class="row two_col">
          <span>Date:</span>
          <span><?php echo $date; ?></span>
        </div>

        <div class="row two_col">
          <span>Customer:</span>
          <span><?php echo $customer; ?></span>
        </div>

        <?php if ($paid > 0 ): ?>
        <div class="row two_col">
          <span>Amount Paid:</span>
          <span><?php echo $paid; ?></span>
        </div>
        <?php endif; ?>

      </div>

    </header>

    <main>

    <table>

        <thead>
          <tr>
            <th class="description" scope="col">Description</th>
            <th class="prices" scope="col">Unit Price</th>
            <th class="prices" scope="col">Quantity</th>
            <th class="prices" scope="col">Amount</th>
          </tr>
        </thead>
        <tbody>
        <?php echo $table_rows; ?>
        </tbody>
      </table> 

      <div class="totals">
        <div class="row two_col">
          <span>Sub Total:</span>
          <span>$<?php echo $sub_total; ?></span>
        </div>

        <div class="row two_col">
          <span>Tax:</span>
          <span>$<?php echo $tax; ?></span>
        </div>

        <div class="row two_col">
          <span>Grand Total:</span>
          <span>$<?php echo $grand_total; ?></span>
        </div>

      </div>
  </main>

  <footer>
            <p style="margin-top:1em">
              cheques made payable to Your Name,<br>
              BTC: 17GbiZKUTFaRBvTnTXrh1FaXqtFW2buvG5<br>
              email transfer payments@mydomain.com<br>
              <b>Thank You!</b></p>
              
            <p style="opacity:.5">A finance charge of 1.5% will be made on unpaid balances after 30 days.</p>
  </footer>

  </div> <!-- class="wrap" -->
</body>
</html>
