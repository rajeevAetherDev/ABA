<!DOCTYPE html>
<html class=" ">
    <head>
        <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
        <meta charset="utf-8" />
        <title>ABA SUFI HOUSE</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
        <meta content="" name="description" />
        <meta content="" name="author" />

        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <?php echo $this->Html->css(array('../assets/css/script.css','../assets/css/bootstrap.css','../assets/css/jquery-ui.min.css','../assets/css/icons.min.css','../assets/css/metisMenu.min.css','../assets/css/app.css','../assets/css/daterangepicker.css','../assets/css/jquery.dataTables.min.css','https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.13.18/jquery.timepicker.min.css')); ?>
    	<?php echo $this->Html->script(array('https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js','https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js','https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js','https://cdn.datatables.net/plug-ins/1.10.12/sorting/datetime-moment.js','https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.13.18/jquery.timepicker.min.js'));?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    </head>
    <!-- END HEAD -->
<style>
 .daterangepicker {
    margin-top: -70px; /* Example adjustment */
}
.range_inputs {
            display: none !important;
        }
        
        input[name="daterangepicker_start"] {
    display: none;
}
.hidden {
    display: none;
}
.btn-success{
    background-color:green!important;
}
.booking-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 10px 10px; /* Adds space between rows */
    margin: 0 auto;
}

.booking-table td {
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-align: center;
    font-size: 18px;
}

.vacant {
    background-color: green;
    color: white;
}

.booked {
    background-color: red;
    color: white;
}

.booking-table td span {
    display: block;
    font-size: 14px;
    margin-top: 8px;
}

.booking-table a {
    text-decoration: none;
    color: inherit;
}

.status-badge {
    display: inline-block;
    padding: 5px 12px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 20px;
}

.status-warning {
    background-color: #fff3cd;
    color: #856404;
}

.status-success {
    background-color: #d4edda;
    color: #155724;
}

.status-danger {
    background-color: #f8d7da;
    color: #721c24;
}
</style>
    <!-- BEGIN BODY -->
    <body class=" ">
        <!-- START TOPBAR -->
         <?php
             echo $this->element('usertopbar');
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
            <section id="main-content">
                <section class="wrapper main-wrapper" style=''>
                    <?php echo $this->Flash->render() ?>
                    <?php echo $this->fetch('content'); ?>
                </section>
            </section>
            <!-- END CONTENT -->
            <?php echo $this->Html->script(array('../assets/js/jquery.min.js','../assets/js/daterangepicker.js','../assets/js/moment.min.js', '../assets/js/jquery-ui.min.js', '../assets/js/bootstrap.bundle.min.js', '../assets/js/metismenu.min.js', '../assets/js/waves.js', '../assets/js/feather.min.js', '../assets/js/jquery.slimscroll.min.js', '../assets/js/jquery.dataTables.min.js', '../assets/pages/jquery.datatable.init.js', '../assets/pages/jquery.sweet-alert.init.js')); ?>
            <?php //echo $this->element('chat'); ?>
            
 <script>
                $(document).ready(function() {
                    
                    setTimeout(function(){
        $(".flash-message").fadeOut("slow", function(){
            $(this).remove();
        });
    }, 1000); 
    
    if (!$.fn.DataTable.isDataTable('#example')) {
                        $('#example').DataTable();
                    }
            
    $('.datepicker').datepicker({
        // Options for the datepicker
        dateFormat: 'mm/dd/yy', // Example date format
        position: {
            my: 'left top',
            at: 'left bottom'
        }
    });
});
$("#example1").DataTable();
            

$(function() {
    $('input[name="daterange"]').daterangepicker();
});

                    
            </script>
         </div>
        
 
<?php if (!empty($semifinishedMessage) || !empty($skuMessage)) : ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-right',
        showConfirmButton: false,
        showCloseButton: true,
        timer: 5000,
        timerProgressBar: true,
        customClass: {
            popup: 'low-stock-toast',
            title: 'low-stock-title'
        }
    });

    let viewedAlerts = {
        semifinished: false,
        sku: false
    };

    function markAsViewed(type, skuIds, storeId) {
        if (!skuIds || skuIds.length === 0) return;
        $.ajax({
            url: "<?= $this->Html->url(['controller' => $controller, 'action' => 'markAlertViewed']); ?>",
            method: "POST",
            data: {
                type: type,
                sku_ids: skuIds,
                store_id: storeId
            },
            success: function(response) {
               // console.log(type + ' alerts marked as viewed');
                viewedAlerts[type] = true;
            },
            error: function(xhr, status, error) {
                console.error('Failed to mark ' + type + ' alerts:', error);
            }
        });
    }

    function showSemifinishedAlert() {
        <?php if (!empty($semifinishedMessage)) : ?>
        
        if (viewedAlerts.semifinished) return;

        Toast.fire({
            title: 'Semifinished Low Stock Alert',
            html: `<?= $semifinishedMessage ?>`,
            didOpen: (popup) => {
                popup.classList.add('semifinished-toast');

                markAsViewed('semifinished', <?= json_encode($semifinishedSkuIds) ?>, <?= $storeId ?>, '<?=$controller?>');

                popup.addEventListener('click', function(e) {
                    if (e.target.classList.contains('swal2-close')) return;
                    window.location.href = "<?= $this->Html->url(['controller' => 'report', 'action' => 'stock_level']); ?>";
                });
            }
        });
        <?php endif; ?>
    }

    function showSkuAlert() {
        <?php if (!empty($skuMessage)) : ?>
        
        if (viewedAlerts.sku) return;

        Toast.fire({
            title: 'SKU Low Stock Alert',
            html: `<?= $skuMessage ?>`,
            didOpen: (popup) => {
                popup.classList.add('sku-toast');

                markAsViewed('sku', <?= json_encode($skuIds) ?>, <?= $storeId ?>, '<?=$controller?>');

                popup.addEventListener('click', function(e) {
                    if (e.target.classList.contains('swal2-close')) return;
                    <?php if($controller == 'dashboard' || $controller == 'report'){ 
                        $targetUrl = $this->Html->url(['controller' => 'report', 'action' => 'stock_level']); 
                    } else { 
                        $targetUrl = $this->Html->url(['controller' => 'outlet', 'action' => 'sku_stock_level']); 
                    } ?>
                    window.location.href = "<?= $targetUrl; ?>";
                });
            }
        });
        <?php endif; ?>
    }

    function showAllAlerts() {
        showSkuAlert();
        setTimeout(showSemifinishedAlert, 6000); 
    }

    showAllAlerts();
    setInterval(showAllAlerts, 30000); 
});
</script>
<?php endif; ?>




<style>
.low-stock-toast {
    padding-right: 40px !important;
    position: relative; 
}

.low-stock-toast .swal2-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}

.low-stock-toast .swal2-close {
    position: absolute !important;
    top: 10px;
    right: -10px;
}

.semifinished-toast {
    border-left: 4px solid #ff9800; 
}
.sku-toast {
    border-left: 4px solid #2196f3; 
}

</style>


    </body>
</html>
