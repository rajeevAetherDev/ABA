<!DOCTYPE html>
<html class=" ">
    <head>
        <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
        <meta charset="utf-8" />
        <title>Madras Coffee House</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />

        <!-- CORE CSS FRAMEWORK - START -->
        <?php echo $this->Html->css(array('../assets/plugins/bootstrap/css/bootstrap.min', '../assets/plugins/bootstrap/css/bootstrap-theme.min','../assets/fonts/font-awesome/css/font-awesome', '../assets/css/animate.min', '../assets/plugins/perfect-scrollbar/perfect-scrollbar', 'index', 'jquery.numpad', 'keyboard', 'jquery-ui.min')); ?>
        <!-- CORE CSS FRAMEWORK - END -->
<!-- CORE JS FRAMEWORK - START --> 
<?php echo $this->Html->script(array('../assets/js/jquery-1.11.2.min','../assets/js/jquery.easing.min','../assets/plugins/bootstrap/js/bootstrap.min','../assets/plugins/pace/pace.min','../assets/plugins/perfect-scrollbar/perfect-scrollbar.min','../assets/plugins/viewport/viewportchecker', 'jquery.numpad', 'jquery.keyboard', 'jquery-ui.min')); ?>
<!-- CORE JS FRAMEWORK - END -->
    </head>
    <!-- END HEAD -->

     <body class=" " >
                        <?php echo $this->Flash->render(); ?>

			<?php echo $this->fetch('content'); ?>

    </body>
</html>
