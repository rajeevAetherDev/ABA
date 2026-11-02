<!DOCTYPE html>
<html class=" ">
    <head>
        <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
        <meta charset="utf-8" />
        <title>Phylo POS</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
        <meta content="" name="description" />
        <meta content="" name="author" />

        
<?php $paramaction = $this->request->params['action']; ?>


        <!-- CORE CSS FRAMEWORK - START -->
        <?php echo $this->Html->css(array('../assets/plugins/pace/pace-theme-flash', '../assets/plugins/bootstrap/css/bootstrap.min', '../assets/plugins/bootstrap/css/bootstrap-theme.min','../assets/fonts/font-awesome/css/font-awesome', '../assets/css/animate.min', '../assets/plugins/perfect-scrollbar/perfect-scrollbar')); ?>
        <!-- CORE CSS FRAMEWORK - END -->

        <!-- OTHER SCRIPTS INCLUDED ON THIS PAGE - START --> 
        
        <?php 
       if($paramaction == 'dashboard' || $paramaction == 'admin_dashboard' || $paramaction == 'admin_dailyreport')
       {
        echo $this->Html->css(array('../assets/plugins/morris-chart/css/morris', '../assets/plugins/jquery-ui/smoothness/jquery-ui.min', '../assets/plugins/rickshaw-chart/css/graph','../assets/plugins/rickshaw-chart/css/detail', '../assets/plugins/rickshaw-chart/css/legend', '../assets/plugins/rickshaw-chart/css/extensions', '../assets/plugins/rickshaw-chart/css/rickshaw.min', '../assets/plugins/rickshaw-chart/css/lines', '../assets/plugins/jvectormap/jquery-jvectormap-2.0.1', '../assets/plugins/icheck/skins/minimal/white')); 
       }
       if($paramaction == 'index' ||$paramaction == 'rechargetransaction' ||$paramaction == 'transactions')
       {
         //  echo $this->Html->css(array('../assets/plugins/datatables/css/jquery.dataTables', '../assets/plugins/datatables/extensions/TableTools/css/dataTables.tableTools.min', '../assets/plugins/datatables/extensions/Responsive/css/dataTables.responsive', '../assets/plugins/datatables/extensions/Responsive/bootstrap/3/dataTables.bootstrap'));  
       }
//       if($paramaction == 'add' || $paramaction == 'admin_dailyreport' || $paramaction == 'admin_cashierreport' || $paramaction == 'admin_dailysalesreport' || $paramaction == 'admin_productreport' || $paramaction == 'admin_auditreportallvendors' || $paramaction == 'admin_auditreport' || $paramaction == 'admin_allcashierreportfromto' || $paramaction == 'admin_cashierreportfromto' || $paramaction == 'admin_billwisereport' || $paramaction == 'admin_billcancelreport' || $paramaction == 'admin_productsreportmultidate'|| $paramaction == 'admin_timeslotwisereport'|| $paramaction == 'admin_menuengineeringreport' || $paramaction == 'admin_dsrcashier' || $paramaction == 'admin_add' || $paramaction == 'admin_edit' || $paramaction == 'admin_stockininventory' || $paramaction == 'admin_stockinreport' || $paramaction == 'admin_stockinventoryreport' || $paramaction == 'admin_searchstockinventoryreport' || $paramaction == 'admin_datewisedailysalereport')
//       {
           echo $this->Html->css(array('../assets/plugins/datepicker/css/datepicker','../assets/plugins/daterangepicker/css/daterangepicker-bs3'));  
     //  }

       
        ?>

        <!-- OTHER SCRIPTS INCLUDED ON THIS PAGE - END --> 


        <!-- CORE CSS TEMPLATE - START -->
        <?php echo $this->Html->css(array('../assets/css/style', '../assets/css/responsive')); ?>
        <?php  echo $this->Html->script(array('../assets/js/jquery-1.11.2.min')); ?>
		<?php  echo $this->Html->script(array('../assets/plugins/daterangepicker/js/moment','../assets/plugins/daterangepicker/js/daterangepicker')); ?>
        <!-- CORE CSS TEMPLATE - END -->
        <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.css">
  <!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    </head>
    <!-- END HEAD -->

    <!-- BEGIN BODY -->
    <body class=" ">
        <!-- START TOPBAR -->
         <?php
                     echo $this->element('topbar');
                ?>
        <!-- END TOPBAR -->
        <!-- START CONTAINER -->
        <div class="page-container row-fluid">

            <!-- SIDEBAR - START -->
            <div class="page-sidebar ">

                <!-- MAIN MENU - START -->
                <?php
                    echo $this->element('usermainmenu'); 
                ?>
                <!-- MAIN MENU - END -->



                <div style="color:white;" class="project-info text-center">
					
                </div>



            </div>
            <!--  SIDEBAR - END -->
            <!-- START CONTENT -->
            <section id="main-content" class=" ">
                <section class="wrapper main-wrapper" style=''>
                    <?php echo $this->Flash->render() ?>
                    <?php echo $this->fetch('content'); ?>
                </section>
            </section>
            <!-- END CONTENT -->
            <?php //echo $this->element('chat'); ?>
         </div>
        <!-- END CONTAINER -->
        <!-- LOAD FILES AT PAGE END FOR FASTER LOADING -->


        <!-- CORE JS FRAMEWORK - START --> 
        <?php echo $this->Html->script(array('../assets/js/jquery.easing.min','../assets/plugins/bootstrap/js/bootstrap.min','../assets/plugins/pace/pace.min','../assets/plugins/perfect-scrollbar/perfect-scrollbar.min','../assets/plugins/viewport/viewportchecker')); ?>

        <!-- CORE JS FRAMEWORK - END --> 


        <!-- OTHER SCRIPTS INCLUDED ON THIS PAGE - START --> 
        
        
         <?php
         
          if($paramaction == 'dashboard'  || $paramaction == 'admin_dashboard' || $paramaction == 'admin_dailyreport')
       {
         echo $this->Html->script(array('../assets/plugins/rickshaw-chart/vendor/d3.v3','../assets/plugins/jquery-ui/smoothness/jquery-ui.min','../assets/plugins/rickshaw-chart/js/Rickshaw.All','../assets/plugins/sparkline-chart/jquery.sparkline.min','../assets/plugins/easypiechart/jquery.easypiechart.min','../assets/plugins/morris-chart/js/raphael-min','../assets/plugins/morris-chart/js/morris.min','../assets/plugins/jvectormap/jquery-jvectormap-2.0.1.min','../assets/plugins/jvectormap/jquery-jvectormap-world-mill-en','../assets/plugins/gauge/gauge.min', '../assets/plugins/icheck/icheck.min'));//, '../assets/js/crm-dashboard'
       }
       if($paramaction == 'index' ||$paramaction == 'rechargetransaction' ||$paramaction == 'transactions')
       {
          
       }
//       if($paramaction == 'add' || $paramaction == 'admin_dailyreport' || $paramaction == 'admin_cashierreport' || $paramaction == 'admin_dailysalesreport' || $paramaction == 'admin_productreport' || $paramaction == 'admin_auditreportallvendors' || $paramaction == 'admin_auditreport' || $paramaction == 'admin_allcashierreportfromto' || $paramaction == 'admin_cashierreportfromto' || $paramaction == 'admin_billwisereport' || $paramaction == 'admin_billcancelreport' || $paramaction == 'admin_productsreportmultidate'|| $paramaction == 'admin_timeslotwisereport'|| $paramaction == 'admin_menuengineeringreport' || $paramaction == 'admin_dsrcashier' || $paramaction == 'admin_add' || $paramaction == 'admin_edit'  || $paramaction == 'admin_stockininventory' || $paramaction == 'admin_stockinreport' || $paramaction == 'admin_stockinventoryreport' || $paramaction == 'admin_searchstockinventoryreport' || $paramaction == 'admin_datewisedailysalereport')
//        {
// echo $this->Html->script(array('../assets/plugins/datatables/js/jquery.dataTables.min','../assets/plugins/datatables/extensions/TableTools/js/dataTables.tableTools.min','../assets/plugins/datatables/extensions/Responsive/js/dataTables.responsive.min','../assets/plugins/datatables/extensions/Responsive/bootstrap/3/dataTables.bootstrap')); 
           echo $this->Html->script(array('../assets/plugins/datepicker/js/datepicker','../assets/plugins/autosize/autosize.min','../assets/plugins/inputmask/min/jquery.inputmask.bundle.min')); 
     //  }
        ?>
        <!-- OTHER SCRIPTS INCLUDED ON THIS PAGE - END --> 


        <!-- CORE TEMPLATE JS - START --> 
         <?php echo $this->Html->script(array('../assets/js/scripts')); ?>
        <!-- END CORE TEMPLATE JS - END --> 

        <!-- Sidebar Graph - START --> 
         <?php echo $this->Html->script(array('../assets/plugins/sparkline-chart/jquery.sparkline.min','../assets/js/chart-sparkline')); ?>
        <!-- Sidebar Graph - END --> 


        <!-- General section box modal start -->
        <div class="modal" id="section-settings" tabindex="-1" role="dialog" aria-labelledby="ultraModal-Label" aria-hidden="true">
            <div class="modal-dialog animated bounceInDown">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title">Any Queries</h4>
                    </div>
                    <div class="modal-body">

                        Please send report to Administrator.

                    </div>
                    <div class="modal-footer">
                        <button data-dismiss="modal" class="btn btn-default" type="button">Close</button>
                        <button data-dismiss="modal" class="btn btn-success" type="button">Thanks</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- modal end -->
        
        
        <?php  if($paramaction == 'dashboard'  || $paramaction == 'admin_dashboard' )
       { 
            echo $this->element('charts');
            
       } ?>
        <?php  if($paramaction == 'admin_dailyreport')
       { 
            //echo $this->element('dailyreport');
            
       } ?>
        <?php echo $this->element('sql_dump'); ?>
        
        
        
        <script>
             $(".datepickerhw").datepicker();
             
            </script>
            <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.js"></script>



    </body>
</html>
