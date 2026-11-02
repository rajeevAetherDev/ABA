
                    <div class='col-lg-12 col-md-12 col-sm-12 col-xs-12'>
                        <div class="page-title">

                            <div class="pull-left">
                                <h1 class="title">POS</h1>                            </div>

                            <div class="pull-right hidden-xs">
                                <ol class="breadcrumb">
                                    <li>
                                        <a href="#"><i class="fa fa-home"></i>Home</a>
                                    </li>
                                    <li>
                                        <a href="#">POS</a>
                                    </li>
                                    <li class="active">
                                        <strong>Admin</strong>
                                    </li>
                                </ol>
                            </div>

                        </div>
                    </div>
                    <div class="clearfix"></div>


                    <div class="col-lg-12">
                        <section class="box nobox">
                            <div class="content-body">
                                
                                
                                
                                <div class="row">
                                    <div class="col-md-3 col-sm-6 col-xs-12">
                                        <div class="r4_counter db_box">
                                            <i class='pull-left fa fa-thumbs-up icon-md icon-rounded icon-primary'></i>
                                            <div class="stats">
                                                <h4><strong><?php echo $customer_count; ?></strong></h4>
                                                <span>Total Customers</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 col-xs-12">
                                        <div class="r4_counter db_box">
                                            <i class='pull-left fa fa-shopping-cart icon-md icon-rounded icon-orange'></i>
                                            <div class="stats">
                                                <h4><strong><?php echo $product_count; ?></strong></h4>
                                                <span>Total Products</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 col-xs-12">
                                        <div class="r4_counter db_box">
                                            <i class='pull-left fa fa-dollar icon-md icon-rounded icon-purple'></i>
                                            <div class="stats">
                                                <h4><strong><?php echo $transaction_today_count; ?></strong></h4>
                                                <span>Today Sales </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 col-xs-12">
                                        <div class="r4_counter db_box">
                                            <i class='pull-left fa fa-users icon-md icon-rounded icon-warning'></i>
                                            <div class="stats">
                                                <h4><strong><?php echo $customer_today_count; ?></strong></h4>
                                                <span>New Customers</span>
                                            </div>
                                        </div>
                                    </div>
                                </div> <!-- End .row -->
                                
                                
                                <div class="row">



                                    <div class="col-md-12 col-sm-7 col-xs-12">
                                        <div class="r1_maingraph db_box">
                                            <span class='pull-left'>
                                                <i class='icon-purple fa fa-square icon-xs'></i>&nbsp;<small>Amount</small>&nbsp; &nbsp;<i class='fa fa-square icon-xs icon-primary'></i>&nbsp;<small>Price</small>
                                            </span>
                                            <div id="db_morris_bar_graph" style="height:272px;width:100%;"></div>
                                        </div>
                                    </div>



                                    <div class="col-md-12 col-sm-7 col-xs-12">
                                        <div class="r1_maingraph db_box">
                                            <span class='pull-left'>
                                                <i class='icon-purple fa fa-square icon-xs'></i>&nbsp;<small>Amount</small>&nbsp; &nbsp;<i class='fa fa-square icon-xs icon-primary'></i>&nbsp;<small>Customers</small>
                                            </span>
                                            <div id="db_morris_area_graph" style="height:272px;width:100%;"></div>
                                        </div>
                                    </div>



                                    <div class="col-md-12 col-sm-7 col-xs-12">
                                        <div class="r1_maingraph db_box">
                                            <span class='pull-left'>
                                                <i class='icon-purple fa fa-square icon-xs'></i>&nbsp;<small>Amount</small>&nbsp; &nbsp;<i class='fa fa-square icon-xs icon-primary'></i>&nbsp;<small>No. of Transactions</small>
                                            </span>
                                            <div id="db_morris_line_graph" style="height:272px;width:100%;"></div>
                                        </div>
                                    </div>


                                </div> <!-- End .row -->


                                	


                            </div>
                        </section></div>



<!--                    <div class="col-lg-6">
                        <section class="box ">
                            <header class="panel_header">
                                <h2 class="title pull-left">Targets Achieved</h2>
                                <div class="actions panel_actions pull-right">
                                    <i class="box_toggle fa fa-chevron-down"></i>
                                    <i class="box_setting fa fa-cog" data-toggle="modal" href="#section-settings"></i>
                                    <i class="box_close fa fa-times"></i>
                                </div>
                            </header>
                            <div class="content-body">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th style="width:60%">Name</th>
                                            <th style="width:30%">Progress</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>

                                            <td>Harry P.</td>
                                            <td><span class="playlist_song2">...</span></td>
                                        </tr>
                                        <tr>

                                            <td>Will Mark</td>
                                            <td><span class="playlist_song3">...</span></td>
                                        </tr>
                                        <tr>

                                            <td>Jason D.</td>
                                            <td><span class="playlist_song4">...</span></td>
                                        </tr>

                                        <tr>

                                            <td>Nik P.</td>
                                            <td><span class="playlist_song6">...</span></td>
                                        </tr>
                                        <tr>

                                            <td>Kate Wilson</td>
                                            <td><span class="playlist_song7">...</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section></div>

                    <div class="col-lg-6">
                        <section class="box ">
                            <header class="panel_header">
                                <h2 class="title pull-left">New Products</h2>
                                <div class="actions panel_actions pull-right">
                                    <i class="box_toggle fa fa-chevron-down"></i>
                                    <i class="box_setting fa fa-cog" data-toggle="modal" href="#section-settings"></i>
                                    <i class="box_close fa fa-times"></i>
                                </div>
                            </header>
                            <div class="content-body">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th style="width:60%">Name</th>
                                            <th style="width:30%">Progress</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>

                                            <td>Harry P.</td>
                                            <td><span class="playlist_song2">...</span></td>
                                        </tr>
                                        <tr>

                                            <td>Will Mark</td>
                                            <td><span class="playlist_song3">...</span></td>
                                        </tr>
                                        <tr>

                                            <td>Jason D.</td>
                                            <td><span class="playlist_song4">...</span></td>
                                        </tr>

                                        <tr>

                                            <td>Nik P.</td>
                                            <td><span class="playlist_song6">...</span></td>
                                        </tr>
                                        <tr>

                                            <td>Kate Wilson</td>
                                            <td><span class="playlist_song7">...</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section></div>-->



                    
