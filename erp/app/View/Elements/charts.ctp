<?php 

$date = date('Y-m-d', strtotime("-30 day", strtotime(date('Y-m-d'))));
 // End date
 $end_date = date('Y-m-d');
 $result = array();
//  $allcustomer = array();
 while (strtotime($date) <= strtotime($end_date)) {
                
                
                 foreach ($transactions as $transaction):
                    if($date  == date('Y-m-d', strtotime($transaction['Transaction']['created'])))
                    {
                        
                        $result[$date][]= array(
                          'amount'=>  $transaction['Transaction']['amount'],
                           'customer'=>$transaction['Transaction']['name'],
                            'price'=>$transaction['Transaction']['price'],
                            'created'=>$transaction['Transaction']['created']
                        );
                     //    $allcustomer[] = $transaction['Customer']['name'];
                    }
                    endforeach;
                        $result[$date][]= array(
                          'amount'=>  0,
                           'customer'=>"",
                            'price'=>0,
                            'created'=>date('Y-m-d H:i:s', strtotime($date))
                        );
                    
                    
                    
                 
                $date = date ("Y-m-d", strtotime("+1 day", strtotime($date)));
                
 }
 //echo "<pre>"; print_r($result); echo "</pre>";
 
// $resultCustomer = array_unique($allcustomer);
//   foreach ($resultCustomer as $key=>$value)
// {
//     $finalcustomers[] = $value; 
// }
 $datef = date('Y-m-d', strtotime("-30 day", strtotime(date('Y-m-d'))));
 // End date
 $end_datef = date('Y-m-d');
 $finalresult = array();

  while (strtotime($datef) <= strtotime($end_datef)) {
      $famount = 0;
      $fcustomer = 0;
      $fprice = 0;
      $fcreated= '';
      $transactioncount = 0;
      
                $maked = $result[$datef];
                if(!empty($maked))
                {
                     $transactioncount = count($maked);
                    foreach ($maked as $makeds): 
                
                   $famount = $famount + $makeds['amount'];
                    $fcustomer = !empty($makeds['customer']) ? $fcustomer + 1 : $fcustomer;
                    $fprice = $fprice + $makeds['price'];
                    $fcreated = $makeds['created'];
                    $allcustomer[] = $makeds['customer'];
                endforeach; 
                
 $resultCustomer = array_unique($allcustomer);
   foreach ($resultCustomer as $key=>$value)
 {
     $finalcustomers[] = $value; 
 }
 
                $finalresult[] = array(
                    'amount' => $famount,
                    'customer'=>count($finalcustomers) - 1,
                    'price'=>$fprice,
                    'created'=>$fcreated ? $fcreated : $datef,
                    'tcount'=>($transactioncount -1)
                );
                unset($allcustomer); unset($finalcustomers);
                }
                
                $datef = date ("Y-m-d", strtotime("+1 day", strtotime($datef)));
                unset($makeds);                unset($maked);
                
 }

 // echo "<pre>"; print_r($finalcustomers); echo "</pre>";

?>


<script type="text/javascript">
/**
 * @Package: Ultra Admin HTML Theme
 * @Since: Ultra 1.0
 * This file is part of Ultra Admin Theme HTML package.
 */


jQuery(function($) {

    'use strict';

    var ULTRA_SETTINGS = window.ULTRA_SETTINGS || {};

    /*--------------------------------
        Sparkline Chart
     --------------------------------*/
    ULTRA_SETTINGS.dbSparklineChart = function() {

        if ($.isFunction($.fn.sparkline)) {

            $('.db_dynamicbar').sparkline([8.4, 9, 8.8, 8, 9.5, 9.2, 9.9, 9, 9, 8, 7, 9, 9, 9.5, 8, 9.5, 9.8], {
                type: 'bar',
                barColor: '#f5f5f5',
                height: '40',
                barWidth: '10',
                barSpacing: 1,
            });

            $('.db_linesparkline').sparkline([2000, 3454, 5454, 2323, 3432, 4656, 2897, 3545, 4232, 5434, 4656, 3567, 4878, 3676, 3787], {
                type: 'line',
                width: '100%',
                height: '40',
                lineWidth: 2,
                lineColor: '#f5f5f5',
                fillColor: 'rgba(255,255,255,0.2)',
                highlightSpotColor: '#ffffff',
                highlightLineColor: '#ffffff',
                spotRadius: 3,
            });


            // Bar + line composite charts
            $('.db_compositebar').sparkline([4, 6, 7, 7, 4, 3, 2, 4, 6, 7, 7, 4, 3, 1, 4, 6, 5, 9], {
                type: 'bar',
                barColor: '#f5f5f5',
                height: '40',
                barWidth: '10',
                barSpacing: 1,
            });

            $('.db_compositebar').sparkline([4, 1, 5, 7, 9, 9, 8, 8, 4, 7, 9, 9, 8, 8, 4, 2, 5, 6, 7], {
                composite: true,
                fillColor: 'rgba(153,114,181,0)',
                type: 'line',
                width: '100%',
                height: '40',
                lineWidth: 2,
                lineColor: '#9972b5',
                highlightSpotColor: '#fa8564',
                highlightLineColor: '#9972b5',
                spotRadius: 3,
            });



        }

    };




    /*--------------------------------
        Easy PIE
     --------------------------------*/
    ULTRA_SETTINGS.dbEasyPieChart = function() {

        if ($.isFunction($.fn.easyPieChart)) {

            $('.db_easypiechart1').easyPieChart({
                barColor: '#9972b5',
                trackColor: '#f5f5f5',
                scaleColor: '#f5f5f5',
                lineCap: 'square',
                lineWidth: 6,
                size: 120,
                animate: 2000,
                onStep: function(from, to, percent) {
                    $(this.el).find('.percent').text(Math.round(percent));
                }
            });
        }

    };




    /*--------------------------------
        Morris 
     --------------------------------*/
    ULTRA_SETTINGS.dbMorrisChart = function() {


        /*Area Graph*/
        // Use Morris.Area instead of Morris.Line
        Morris.Area({
            element: 'db_morris_area_graph',
            data: [  <?php foreach ($finalresult as $finalresults): ?>
                             
                {
                x: "<?php echo date('Y-m-d H:i:s', strtotime($finalresults['created'])); ?>",
                y: <?php echo $finalresults['amount']; ?>,
                z: <?php echo $finalresults['customer']; ?>
            }, 
        <?php endforeach; ?>],
            resize: true,
            redraw: true,
            xkey: 'x',
            ykeys: ['y', 'z'],
            labels: ['Amount', 'Customers'],
            lineColors: ['#3FACE2', '#74BB6D'],
            pointFillColors: ['#fa8564']
        }).on('click', function(i, row) {
            console.log(i, row);
        });


        /*Line Graph*/
        /* data stolen from http://howmanyleft.co.uk/vehicle/jaguar_'e'_type */
        var day_data = [
            
              <?php foreach ($finalresult as $finalresults): ?>
                             
                {
                "x": "<?php echo date('Y-m-d H:i:s', strtotime($finalresults['created'])); ?>",
                "y": "<?php echo $finalresults['amount']; ?>",
                "z": "<?php echo $finalresults['tcount']; ?>"
            }, 
        <?php endforeach; ?>
           ];
        Morris.Line({
            element: 'db_morris_line_graph',
            data: day_data,
            resize: true,
            redraw: true,
            xkey: 'x',
            ykeys: ['y', 'z'],
            labels: ['Amount', 'No. of Transaction'],
            lineColors: ['#3FACE2', '#74BB6D'],
            pointFillColors: ['#fa8564']
        });

        /*Bar Graph*/
        // Use Morris.Bar
        Morris.Bar({
            element: 'db_morris_bar_graph',
            data: [  <?php foreach ($finalresult as $finalresults): ?>
                             
                {
                x: "<?php echo date('Y-m-d', strtotime($finalresults['created'])); ?>",
                y: <?php echo $finalresults['amount']; ?>,
                z: <?php echo $finalresults['price']; ?>
            }, 
        <?php endforeach; ?>],
            resize: true,
            redraw: true,
            xkey: 'x',
            ykeys: ['y', 'z'],
            labels: ['Amount', 'Price'],
            barColors: ['#3FACE2', '#74BB6D']
        }).on('click', function(i, row) {
            console.log(i, row);
        });

        $('.r1_maingraph .switch .fa').on('click', function() {

            $('.r1_maingraph .switch .fa').removeClass("icon-default").addClass("icon-secondary");

            if ($(this).hasClass("fa-bar-chart")) {
                $(this).toggleClass("icon-secondary icon-default");
                $("#db_morris_line_graph").hide();
                $("#db_morris_area_graph").hide();
                $("#db_morris_bar_graph").show();
            }

            if ($(this).hasClass("fa-line-chart")) {
                $(this).toggleClass("icon-secondary icon-default");
                $("#db_morris_line_graph").show();
                $("#db_morris_area_graph").hide();
                $("#db_morris_bar_graph").hide();
            }

            if ($(this).hasClass("fa-area-chart")) {
                $(this).toggleClass("icon-secondary icon-default");
                $("#db_morris_line_graph").hide();
                $("#db_morris_area_graph").show();
                $("#db_morris_bar_graph").hide();
            }

        });


    };



    /******************************
     initialize respective scripts 
     *****************************/
    $(document).ready(function() {
        ULTRA_SETTINGS.dbSparklineChart();
        ULTRA_SETTINGS.dbEasyPieChart();
        ULTRA_SETTINGS.dbMorrisChart();
    });

    $(window).resize(function() {
        ULTRA_SETTINGS.dbSparklineChart();
    });

    $(window).load(function() {});

});
    </script>
