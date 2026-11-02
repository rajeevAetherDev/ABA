<div class="topbar">           
            <!-- Navbar -->
            <nav class="navbar-custom">    
                <ul class="list-unstyled topbar-nav float-right mb-0"> 
                    <li class="dropdown">
                        <a class="nav-link dropdown-toggle waves-effect waves-light nav-user" data-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false" >
                            <b><?php echo $this->Session->read('Auth.User.empName');?></b>
                        </a>
                        <!--<div class="dropdown-menu dropdown-menu-right">-->
                        <!--   <?php echo $this->Html->link($this->Html->tag('i', '', array('class' => 'fa fa-home')) .'Logout', array('controller'=>'users', 'action'=>'logout'),array('escape'=>false)); ?>-->
                        <!--</div>-->
                    </li>
                    
                </ul><!--end topbar-nav-->
    
                <ul class="list-unstyled topbar-nav mb-0">  
                    <!--<li>-->
                    <!--    <a href="">-->
                    <!--        <span class="responsive-logo">-->
                    <!--            <?php echo $this->Html->link($this->Html->image('../assets/images/logo.png', array('class'=>'img-circle img-inline', array('height' => '45px'))).$this->Html->tag('span', $this->Session->read('Auth.User.username'). ' <i class="fa fa-angle-down"></i>',''), '#', array('escape'=>false, 'class'=>'toggle', 'data-toggle'=>'dropdown')); ?>-->
                    <!--        </span>-->
                    <!--    </a>-->
                    <!--</li>                      -->
                    <li>
                        <button class="button-menu-mobile nav-link waves-effect waves-light">
                            <i data-feather="menu" class="align-self-center"></i>
                        </button>
                    </li>
                </ul>
            </nav>
        </div>
    
        