<?php 
function searchArray($array, $key, $value)
{
    
     
    $results = array();

    if (is_array($array)) {
        if (isset($array[$key]) && $array[$key] == $value) {
            $results[] = $array;
        }

        foreach ($array as $subarray) {
            $results = array_merge($results, searchArray($subarray, $key, $value));
        }
    }

    return $results;
}
?>


<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html  moznomarginboxes mozdisallowselectionprint>
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
	<title></title>
	<style type="text/css">
            @font-face {
	font-family: 'Conv_ArchivoNarrow-Regular';
	src: url('<?php echo $this->base; ?>/fonts/ArchivoNarrow-Regular.eot');
	src: local('☺'), url('<?php echo $this->base; ?>/fonts/ArchivoNarrow-Regular.woff') format('woff'), url('<?php echo $this->base; ?>/fonts/ArchivoNarrow-Regular.ttf') format('truetype'), url('<?php echo $this->base; ?>/fonts/ArchivoNarrow-Regular.svg') format('svg');
	font-weight: normal;
	font-style: normal;
}
            
            
                @page { margin-left: 0.08in; margin-right: 0.08in; margin-top: 0in; margin-bottom: 0.79in;  size:auto;}
		p { margin-bottom: 0.04in; direction: ltr; line-height: 120%; text-align: left; widows: 2; orphans: 2 }
                a:link:after, a:visited:after {
                content: ""; 
        }
/*body,div,table,thead,tbody,tfoot,tr,th,td,p { font-family:"Calibri"; font-size:xx-small; }*/
body { width: 3in !important; font-family: 'Conv_ArchivoNarrow-Regular'; font-weight: bolder;}
@media print
{    
    .no-print, .no-print *
    {
        display: none !important;
    }
}
	</style>
        <script>
       window.print();
        
        </script>
</head>
<body lang="en-US" dir="ltr">
<!--    <p style="text-align:center;"><img src="<?php// echo $this->base; ?>/assets/images/.png" height="80px" width="auto"/></p>-->
    <p style="text-align:center; font-size:20px; text-transform: uppercase;"><span style="font-size: 16px;">Item Sale Statement report</span><br/>
    From Date : <?php echo $startdate; ?><br/>
    To Date : <?php echo $enddate; ?><br/>
    </p>

<table width="100%" cellpadding="4" cellspacing="0" style="border: none; font-size: small;">
	<col width="64*">
	<col width="64*">
	<col width="64*">
	<col width="64*">
        <?php /* foreach($cateresponse as $category): */		
                $totalquantity = 0;
                $totalamount = 0;
            ?>
        <tr valign="top">
		<td width="40%" style="border-top: 1px solid #888; border-bottom: 1px solid #888; padding-top: 0.04in; padding-bottom: 0.04in; padding-left: 0.04in; padding-right: 0in;">
            <p align="center" style="text-align: left; font-weight: bold;">Product Name</p>
		</td>
		<td width="15%" style="border-top: 1px solid #888; border-bottom: 1px solid #888; padding: 0.04in; ">
            <p align="center" style="text-align: center;">Category Name</p>
		</td>
        <td width="15%" style="border-top: 1px solid #888; border-bottom: 1px solid #888; padding: 0.04in; ">
            <p align="center" style="text-align: center;">Rate</p>
		</td>
        <td width="15%" style="border-top: 1px solid #888; border-bottom: 1px solid #888; padding-top: 0.04in; padding-bottom: 0.04in; padding-left: 0.04in; padding-right: 0in; ">
            <p align="center" style="text-align: right;">QTY</p>
		</td>
		<td width="15%" style="border-top: 1px solid #888; border-bottom: 1px solid #888; padding-top: 0.04in; padding-bottom: 0.04in; padding-left: 0.04in; padding-right: 0in; ">
            <p align="center" style="text-align: right;">Amount</p>
		</td>
		<!---
		<td width="15%" style="border-top: 1px solid #888; border-bottom: 1px solid #888; padding: 0.04in; ">
            <p align="center" style="text-align: right;">Item Wise</p>
		</td>
        <td width="15%" style="border-top: 1px solid #888; border-bottom: 1px solid #888; padding: 0.04in; ">
            <p align="center" style="text-align: right;">Sale Wise</p>
		</td>
		-->
	</tr>
        
        
        <?php foreach ($resp as $response): ?>
	<tr valign="top">
		<td width="40%" style="padding-top: 0.04in; padding-bottom: 0.01in; padding-left: 0.04in; padding-right: 0in; ">
            <p align="center" style="text-align: left;"><?php echo $response['item_name']; ?></p>
		</td>
		<td width="40%" style="padding-top: 0.04in; padding-bottom: 0.01in; padding-left: 0.04in; padding-right: 0in; ">
            <p align="center" style="text-align: left;"><?php echo $response['category_name']; ?></p>
		</td>
		<td width="15%" style="padding-top: 0.04in; padding-bottom: 0.01in; padding-left: 0.04in; padding-right: 0.04in;  ">
            <p align="center" style="text-align: center;"><?php echo $response['pprice']; ?></p>
		</td>
		<td width="15%" style="padding-top: 0.04in; padding-bottom: 0.01in; padding-left: 0.04in; padding-right: 0in; ">
            <p align="center" style="text-align: right;"><?php echo $response['quantity']; ?></p>
		</td>
		<td width="15%" style="padding-top: 0.04in; padding-bottom: 0.01in; padding-left: 0.04in; padding-right: 0in; ">
            <p align="center" style="text-align: right;"><?php echo $response['pprice']*$response['quantity']; ?></p>
		</td>
		<!---
        <td width="15%" style="padding-top: 0.04in; padding-bottom: 0.01in; padding-left: 0.04in; padding-right: 0.04in;  ">
            <p align="center" style="text-align: right;"><?php echo $response['price']; ?></p>
		</td>
        <td width="15%" style="padding-top: 0.04in; padding-bottom: 0.01in; padding-left: 0.04in; padding-right: 0.04in;  ">
            <p align="center" style="text-align: right;"><?php echo round((($response['price'] * 100) / $grandtotal ) , 2); ?></p>
		</td>
		-->
	</tr>
        <?php 
        
        $totalquantity = $totalquantity + $response['quantity']; $totalamount = $totalamount + $response['pprice']*$response['quantity'];
        endforeach;  unset($responses);  ?>
        
		<!---
        <tr valign="top">
		<td width="40%" style="padding-top: 0.04in; padding-bottom: 0.01in; padding-left: 0.04in; padding-right: 0in; ">
                    <p align="center" style="text-align: left;">Total</p>
		</td>
		<td width="15%" style="padding-top: 0.04in; padding-bottom: 0.01in; padding-left: 0.04in; padding-right: 0.04in;  ">
            <p align="center" style="text-align: right;">&nbsp;</p>
		</td>
        <td width="15%" style="padding-top: 0.04in; padding-bottom: 0.01in; padding-left: 0.04in; padding-right: 0in; ">
                    <p align="center" style="text-align: right;"><?php echo $totalquantity; ?>

			</p>
		</td>
		        <td width="15%" style="padding-top: 0.04in; padding-bottom: 0.01in; padding-left: 0.04in; padding-right: 0.0in;  ">
                    <p align="center" style="text-align: right;"><?php echo $totalamount; ?></p>
		</td>
                
	</tr>
	--->

	</table>
    <!---
	<p align="center" style="margin-top:0.4in; margin-bottom: 0in; line-height: 120%; text-align: center; font-size: small; width: 100%; border-top: 1px solid #888; border-bottom: 1px solid #888; padding-bottom: 0.2in;"><br/>Grand Total* : <?php echo $grandtotal; ?></p>
    <p style="font-size: small">*Note: Grand Total without tax and discount</p>
	---->
</body>
</html>
