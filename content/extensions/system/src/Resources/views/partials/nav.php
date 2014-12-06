<nav class="navbar navbar-default navbar-fixed-top" role="navigation" style="margin-bottom: 0">
    <div class="container nav-container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".sidebar-collapse">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="<?php echo admin_url('system/dashboard'); ?>"><i class="fa fa-home"></i></a>
            </div>
            <!-- /.navbar-header -->
            <div class="dt-off-canvas">
            <ul class="nav navbar-top-links navbar-left">
                <?php foreach ($menus as $menu): ?>
                    
                    <?php if($menu->hasChildren()): ?>
                        <li class="dropdown">
                            <a class="navbar-link dropdown-toggle" data-toggle="dropdown" href="#">
                                <?php echo $menu->label; ?> <!--<i class="fa fa-caret-down"></i>-->
                            </a>
                            <ul class="dropdown-menu">
                                <div class="dropdown-caret">
                                  <span class="caret-outer"></span>
                                  <span class="caret-inner"></span>
                                </div>

                                <?php foreach($menu->children as $childMenu): ?>
                                    <li><a id="<?php echo $childMenu->id ?>" class="<?php echo $childMenu->class ?>" href="<?php echo admin_url($childMenu->href);  ?>"><?php echo $childMenu->label; ?></a></li>
                                <?php endforeach;?>
                            </ul>
                            <!-- /.dropdown -->
                        </li>
                    
                    <?php else: ?>
                         <li>
                            <a id="<?php echo $menu->id ?>" class="navbar-link <?php echo $menu->class ?>"  href="<?php echo admin_url($menu->href); ?>">
                                <?php echo $menu->label; ?>
                            </a>
                        </li>
                    <?php endif; ?>

                <?php endforeach ?>
            </ul>
            </div>

            <ul class="nav navbar-top-links navbar-right user-preferences">
                <!-- /.dropdown -->
                <li class="dropdown">
                    <a class=" navbar-link dropdown-toggle" data-toggle="dropdown" href="#">
                        <img width="20px;" class="profile-avatar" src="<?php echo $userGravatar; ?>" alt="<?php echo $userName ?>"/>
                        <!--<i class="fa fa-caret-down"></i>-->
                    </a>
                    <ul class="dropdown-menu dropdown-menu-right dropdown-user">
                        <div class="dropdown-caret">
                            <span class="caret-outer"></span>
                            <span class="caret-inner"></span>
                        </div>
                        <li> <a href="<?php echo base_url(); ?>" target="_blank"><?= __('Visit Site') ?> <i class="fa fa-external-link"></i> </a> </li>
                        <!--<li><a href="#" class="soon">My Profile</a> </li>
                        <li><a href="#" class="soon">Preferences</a> </li>-->
                        <li><a href="<?php echo admin_url('logout'); ?>"><?= __('Logout') ?></a> </li>
                    </ul>
                    <!-- /.dropdown-user -->
                </li>
                <!-- /.dropdown -->
            </ul>
            <!-- /.navbar-top-links -->
    </div>
</nav>