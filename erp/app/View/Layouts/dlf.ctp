<!DOCTYPE html>
<html class=" ">
    <head>
        <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
        <meta charset="utf-8" />
        <title>Madras Coffee House</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
        <meta content="" name="description" />
        <meta content="" name="author" />

        <link rel="shortcut icon" href="assets/images/favicon.png" type="image/x-icon" />    <!-- Favicon -->
        <link rel="apple-touch-icon-precomposed" href="assets/images/apple-touch-icon-57-precomposed.png">	<!-- For iPhone -->
        <link rel="apple-touch-icon-precomposed" sizes="114x114" href="assets/images/apple-touch-icon-114-precomposed.png">    <!-- For iPhone 4 Retina display -->
        <link rel="apple-touch-icon-precomposed" sizes="72x72" href="assets/images/apple-touch-icon-72-precomposed.png">    <!-- For iPad -->
        <link rel="apple-touch-icon-precomposed" sizes="144x144" href="assets/images/apple-touch-icon-144-precomposed.png">    <!-- For iPad Retina display -->




        <!-- CORE CSS FRAMEWORK - START -->
        <?php echo $this->Html->css(array('../assets/plugins/pace/pace-theme-flash', '../assets/plugins/bootstrap/css/bootstrap.min', '../assets/plugins/bootstrap/css/bootstrap-theme.min','../assets/fonts/font-awesome/css/font-awesome', '../assets/css/animate.min', '../assets/plugins/perfect-scrollbar/perfect-scrollbar')); ?>
        <!-- CORE CSS FRAMEWORK - END -->

        <!-- OTHER SCRIPTS INCLUDED ON THIS PAGE - START --> 
        
        <?php 
      echo $this->Html->css(array('../assets/plugins/datepicker/css/datepicker'));
        ?>

        <!-- OTHER SCRIPTS INCLUDED ON THIS PAGE - END --> 


        <!-- CORE CSS TEMPLATE - START -->
        <?php echo $this->Html->css(array('../assets/css/style', '../assets/css/responsive')); ?>
        <!-- CORE CSS TEMPLATE - END -->

        <!-- CORE JS FRAMEWORK - START --> 
        <?php echo $this->Html->script(array('../assets/js/jquery-1.11.2.min','../assets/js/jquery.easing.min','../assets/plugins/bootstrap/js/bootstrap.min','../assets/plugins/pace/pace.min','../assets/plugins/perfect-scrollbar/perfect-scrollbar.min','../assets/plugins/viewport/viewportchecker')); ?>

        <!-- CORE JS FRAMEWORK - END --> 
    </head>
    <!-- END HEAD -->

    <!-- BEGIN BODY -->
    <body class=" ">

        <style>
    .wraplist {
        padding: 0px;
    margin: 0px;
    padding-left: 0px; }
    .wraplist .btn-primary { width: 100%; padding: 20px 0; margin-bottom: 10px;} 
    .wraplist .btn-warning { width: 100%; padding: 20px 0; margin-bottom: 10px;} 
    .wraplist .btn-danger { width: 100%; padding: 20px 0; margin-bottom: 10px;} 
    .wraplist .btn-info { width: 100%; padding: 20px 0; margin-bottom: 10px;} 
     .wraplist .btn-success { width: 100%; padding: 20px 0; margin-bottom: 10px;} 
      .wraplist .btn-default { width: 100%; padding: 20px 0; margin-bottom: 10px;} 
    .nopadding { padding: 0;} .nomargin { margin: 0 ;}
    .wraplist li  a .fa { font-size: 18px;}
    .wraplist li a{ font-size: 12px;}
    .error-message { color: red;}
 </style>
        <!-- START CONTAINER -->
        <div class="page-container row-fluid">

           
            <!-- START CONTENT -->
            <section id="main-content" class=" " style="margin: 0 auto;">
                
                <section class="wrapper main-wrapper nopadding" style=''>
                    <div class="container-fluid nopadding">
                        <div class="row-fluid nomargin">
                            <div class="col-md-1 col-sm-1 nopadding">
  

                            </div>
                            <div class="col-md-11  col-sm-11 nopadding">
                                <?php echo $this->Flash->render() ?>
                        <?php echo $this->fetch('content'); ?>
                            </div>
                        </div>
                    </div>
                     
                    
                    
                    
                </section>
            </section>
            <!-- END CONTENT -->
            <?php //echo $this->element('chat'); ?>
         </div>
        <!-- END CONTAINER -->
        <!-- LOAD FILES AT PAGE END FOR FASTER LOADING -->


        


        <!-- OTHER SCRIPTS INCLUDED ON THIS PAGE - START --> 
        
        
         <?php
         
          
           echo $this->Html->script(array('../assets/plugins/datepicker/js/datepicker','../assets/plugins/autosize/autosize.min','../assets/plugins/inputmask/min/jquery.inputmask.bundle.min')); 
      
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
        
        <?php //echo $this->element('sql_dump'); ?>
    </body>
</html>
