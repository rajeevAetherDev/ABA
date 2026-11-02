        <div class="page-sidebar-wrapper" id="main-menu-wrapper"> 

                    <!-- USER INFO - START -->
                    <div class="profile-info row">

                        <div class="profile-image col-md-4 col-sm-4 col-xs-4">
                            <a href="ui-profile.html">
                                <img src="data/profile/profile-crm.jpg" class="img-responsive img-circle">
                            </a>
                        </div>

                        <div class="profile-details col-md-8 col-sm-8 col-xs-8">

                            <h3>
                                <a href="ui-profile.html">
                                    <?php echo $this->Session->read('Auth.username'); ?>
                                </a>
                                <?php //echo $this->Html->link(''); ?>
                                <!-- Available statuses: online, idle, busy, away and offline -->
                                <span class="profile-status online"></span>
                            </h3>

                            <p class="profile-title">Administrator</p>

                        </div>

                    </div>
                    <!-- USER INFO - END -->



                    <ul class='wraplist'>	


                        <li class=""> 
                            <a href="index-crm.html">
                                <i class="fa fa-dashboard"></i>
                                <span class="title">Dashboard</span>
                            </a>
                        </li>
                        <li class=""> 
                            <a href="javascript:;">
                                <i class="fa fa-envelope"></i>
                                <span class="title">Mailbox</span>
                                <span class="arrow "></span><span class="label label-orange">4</span>
                            </a>
                            <ul class="sub-menu" >
                                <li>
                                    <a class="" href="crm-mail-inbox.html" >Inbox</a>
                                </li>
                                <li>
                                    <a class="" href="crm-mail-compose.html" >Compose</a>
                                </li>
                                <li>
                                    <a class="" href="crm-mail-view.html" >View</a>
                                </li>
                            </ul>
                        </li>
                        <li class=""> 
                            <a href="javascript:;">
                                <i class="fa fa-users"></i>
                                <span class="title">Customers</span>
                                <span class="arrow "></span>
                            </a>
                            <ul class="sub-menu" >
                                <li>
                                    <a class="" href="crm-customers.html" >All Customers</a>
                                </li>
                                <li>
                                    <a class="" href="crm-customer-add.html" >Add Customer</a>
                                </li>
                                <li>
                                    <a class="" href="crm-customer-edit.html" >Edit Customer</a>
                                </li>
                            </ul>
                        </li>
                        <li class=""> 
                            <a href="javascript:;">
                                <i class="fa fa-users"></i>
                                <span class="title">Leads</span>
                                <span class="arrow "></span>
                            </a>
                            <ul class="sub-menu" >
                                <li>
                                    <a class="" href="crm-leads.html" >All Leads</a>
                                </li>
                                <li>
                                    <a class="" href="crm-lead-add.html" >Add Lead</a>
                                </li>
                                <li>
                                    <a class="" href="crm-lead-edit.html" >Edit Lead</a>
                                </li>
                            </ul>
                        </li>
                        <li class=""> 
                            <a href="javascript:;">
                                <i class="fa fa-users"></i>
                                <span class="title">Vendors</span>
                                <span class="arrow "></span>
                            </a>
                            <ul class="sub-menu" >
                                <li>
                                    <a class="" href="crm-vendors.html" >All Vendors</a>
                                </li>
                                <li>
                                    <a class="" href="crm-vendor-add.html" >Add Vendor</a>
                                </li>
                                <li>
                                    <a class="" href="crm-vendor-edit.html" >Edit Vendor</a>
                                </li>
                            </ul>
                        </li>
                        <li class=""> 
                            <a href="javascript:;">
                                <i class="fa fa-edit"></i>
                                <span class="title">Quotes</span>
                                <span class="arrow "></span>
                            </a>
                            <ul class="sub-menu" >
                                <li>
                                    <a class="" href="crm-quotes.html" >All Quotes</a>
                                </li>
                                <li>
                                    <a class="" href="crm-quote-add.html" >Add Quote</a>
                                </li>
                                <li>
                                    <a class="" href="crm-quote-edit.html" >Edit Quote</a>
                                </li>
                            </ul>
                        </li>
                        <li class=""> 
                            <a href="javascript:;">
                                <i class="fa fa-dollar "></i>
                                <span class="title">Invoices</span>
                                <span class="arrow "></span>
                            </a>
                            <ul class="sub-menu" >
                                <li>
                                    <a class="" href="crm-invoices.html" >All Invoices</a>
                                </li>
                                <li>
                                    <a class="" href="crm-invoice-add.html" >Add Invoice</a>
                                </li>
                                <li>
                                    <a class="" href="crm-invoice-edit.html" >Edit Invoice</a>
                                </li>
                                <li>
                                    <a class="" href="crm-invoice-view.html" >View Invoice</a>
                                </li>
                            </ul>
                        </li>
                        <li class=""> 
                            <a href="javascript:;">
                                <i class="fa fa-phone"></i>
                                <span class="title">Contacts</span>
                                <span class="arrow "></span>
                            </a>
                            <ul class="sub-menu" >
                                <li>
                                    <a class="" href="crm-contacts.html" >All Contacts</a>
                                </li>
                                <li>
                                    <a class="" href="crm-contact-add.html" >Add Contact</a>
                                </li>
                                <li>
                                    <a class="" href="crm-contact-edit.html" >Edit Contact</a>
                                </li>
                            </ul>
                        </li>
                        <li class=""> 
                            <a href="crm-calendar.html">
                                <i class="fa fa-calendar"></i>
                                <span class="title">Calendar</span>
                            </a>
                        </li>
                        <li class=""> 
                            <a href="crm-map.html">
                                <i class="fa fa-map-marker"></i>
                                <span class="title">Map</span>
                            </a>
                        </li>
                        <li class=""> 
                            <a href="javascript:;">
                                <i class="fa fa-bar-chart"></i>
                                <span class="title">Reports</span>
                                <span class="arrow "></span>
                            </a>
                            <ul class="sub-menu" >
                                <li>
                                    <a class="" href="crm-report-sales.html" >Sales</a>
                                </li>
                                <li>
                                    <a class="" href="crm-report-customers.html" >Customers</a>
                                </li>
                                <li>
                                    <a class="" href="crm-report-targets.html" >Targets</a>
                                </li>
                            </ul>
                        </li>
                        <li class=""> 
                            <a href="javascript:;">
                                <i class="fa fa-question-circle"></i>
                                <span class="title">Tickets</span>
                                <span class="arrow "></span>
                            </a>
                            <ul class="sub-menu" >
                                <li>
                                    <a class="" href="crm-tickets.html" >All Tickets</a>
                                </li>
                                <li>
                                    <a class="" href="crm-ticket-add.html" >Add Ticket</a>
                                </li>
                                <li>
                                    <a class="" href="crm-ticket-edit.html" >Edit Ticket</a>
                                </li>
                            </ul>
                        </li>
                        <li class=""> 
                            <a href="javascript:;">
                                <i class="fa fa-user"></i>
                                <span class="title">Users</span>
                                <span class="arrow "></span>
                            </a>
                            <ul class="sub-menu" >
                                <li>
                                    <a class="" href="crm-users.html" >All Users</a>
                                </li>
                                <li>
                                    <a class="" href="crm-user-add.html" >Add User</a>
                                </li>
                                <li>
                                    <a class="" href="crm-user-edit.html" >Edit User</a>
                                </li>
                            </ul>
                        </li>
                        <li class="open"> 
                            <a href="javascript:;">
                                <i class="fa fa-suitcase"></i>
                                <span class="title">Multi Purpose</span>
                                <span class="arrow open"></span><span class="label label-orange">NEW</span>
                            </a>
                            <ul class="sub-menu" style='display:block;'>
                                <li>
                                    <a class="" href="general.html"  target = '_blank' >General Admin</a>
                                </li>
                                <li>
                                    <a class="" href="hospital.html"  target = '_blank' >Hospital Admin</a>
                                </li>
                                <li>
                                    <a class="" href="music.html"  target = '_blank' >Music Admin</a>
                                </li>
                                <li>
                                    <a class="active" href="crm.html"  target = '_blank' >CRM Admin</a>
                                </li>
                                <li>
                                    <a class="" href="socialmedia.html"  target = '_blank' >Social Media Admin</a>
                                </li>
                                <li>
                                    <a class="" href="freelancing.html"  target = '_blank' >Freelancing Admin</a>
                                </li>
                                <li>
                                    <a class="" href="university.html"  target = '_blank' >University Admin</a>
                                </li>
                                <li>
                                    <a class="" href="ecommerce.html"  target = '_blank' >Ecommerce Admin</a>
                                </li>
                                <li>
                                    <a class="" href="blog.html"  target = '_blank' >Blog Admin</a>
                                </li>
                            </ul>
                        </li>

                    </ul>

                </div>
