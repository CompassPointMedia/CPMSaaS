<?php
/**
 * responsive menu starting from a CSS-only base (but adding some JS)
 * https://medium.com/@heyoka/responsive-pure-css-off-canvas-hamburger-menu-aebc8d11d793
 * https://codepen.io/markcaron/pen/pPZVWO

 * todo:

• should .main-menu ul be changed -> .main-menu > ul ??
• dilemma - I want mobile flyout menu to be more material design/flat with semi-transparency but I may not be able to preserve themes if I mess with background colors
• minor: the background goes to viewport bottom, not bottom of page. When you screll this is noticeable
• the X button on mouseover shows that it's not clearing the element below it

scrolling doesn't work for large menus like we have, if I allow it it's ugly.  Need to handle with JS
some themes have submenus having rounded bottom corners but mine doesn't show it.
cyborg had dividers with shade between menu items, mine doesn't
get rid of pesky underline on links after I've clicked them (though the dotted outline in Firefox is nice)
escape key needs to close menu

 */


$invertImageThemes = 'slate|yeti|cyborg|superhero|darkly|';
$Theme = 'darkly';

$login = $_SESSION['login'] ?? [];

if(!function_exists('get_uri_from_page_title')) {
    function get_uri_from_page_title($x) {
        return $x;
    }
}

?>
<link rel="stylesheet" href="/asset/css/vendor.css" type="text/css" />
<link rel="stylesheet" href="/asset/css/menu.css" type="text/css" />
<div class="container-fluid">
    <header class="supple-nav">
        <a href="#main-menu"
           class="menu-toggle"
           role="button"
           id="main-menu-toggle"
           aria-expanded="false"
           aria-controls="main-menu"
           aria-label="Open main menu">
            <span class="sr-only">Open main menu</span>
            <span class="fa fa-bars" aria-hidden="true"></span>
        </a>
        <h1 class="logo">CompassPoint SAAS</h1>

        <!-- bs theme: navbar, navbar-inverse -->
        <nav id="main-menu"
             class="main-menu navbar navbar-inverse"
             role="navigation"
             aria-expanded="false"
             aria-label="Main menu">
            <a href="#main-menu-toggle"
               class="menu-close"
               role="button"
               id="main-menu-close"
               aria-expanded="false"
               aria-controls="main-menu"
               aria-label="Close main menu">

                <span class="sr-only">Close main menu</span>
                <span class="nav-larger" aria-hidden="true">&times;</span>
                <!-- orig <span class="fa fa-close"></span> -->
            </a>
            <!-- bs theme: nav, navbar-nav -->
            <ul class="nav navbar-nav">
                <?php
                $Admin = 'Y';


                $query   		= $dbMaster->query("SELECT * FROM v1_menu_navbar WHERE active = 'Y' ORDER BY display_order ASC");
                $results   	= $query->getResultArray();

                foreach ($results as $Row) {
                    $ThisNavID    		= $Row['nav_id'];
                    $MenuTitle    		= (!empty($Row['menu_title']) ? $Row['menu_title'] : '');
                    $DropDown    		= $Row['dropdown'];
                    $LinkName    		= $Row['link_name'];
                    $DisplayOrder  	 	= $Row['display_order'];
                    $HyperLink    		= $Row['hyperlink'];
                    $AdminMenu    		= $Row['admin_menu'];
                    $Target    			= $Row['target'];
                    $Target      	 	= !empty($Target) ? 'target="_'.$Target.'"' : '';

                    if($HyperLink === 'index.php') $HyperLink = '/';

                    if($AdminMenu == 'Y' && $Admin != 'Y'){
                        continue;
                    }
                    if($DropDown == 'Y'){
                        if ($LinkName === 'Data Objects') {
                            $Results3 = [];
                            // Tables only shown if logged in, and by permission
                            if (!empty($login['active']) && !empty($login['accounts'][$subdomain])) {

                                $roles = $login['accounts'][$subdomain]['roles'];

                                $query = $dbAccounts[$subdomain]->query("SELECT
                                t.title link_name,
                                '1' display_order,
                                CONCAT('/data/view/', t.table_key) hyperlink,
                                '' target,
                                t.title page_title,
                                '' img_source
                                FROM sys_table t 
                                                                
                                ORDER BY t.title");
                                $Results3 = $query->getResultArray();

                                $Results3 = array_merge($Results3, [
                                    [
                                        'link_name' => 'Manage Data Objects',
                                        'hyperlink' => '/data/manage',
                                        'display_order' => 1000,
                                        'target' => '',
                                        'page_title' => '',
                                        'img_source' => '',
                                    ], [
                                        'link_name' => 'Add Data Object',
                                        'hyperlink' => '/data/create',
                                        'display_order' => 1000,
                                        'target' => '',
                                        'page_title' => '',
                                        'img_source' => '',
                                    ]
                                ]);
                            }
                            $Results3   = array_merge(
                                $Results3,
                                [
                                    [
                                        'link_name' => 'Data Object Help',
                                        'hyperlink' => '/data/help',
                                        'display_order' => 1000,
                                        'target' => '',
                                        'page_title' => '',
                                        'img_source' => '',
                                    ]
                                ]
                            );
                        } else {
                            $query 		= $dbMaster->query("SELECT
                            m.*, s.page_title, b.img_source
                            FROM v1_menu_items m 
                            LEFT JOIN v1_menu_switch s ON s.id = m.switch_id
                            LEFT JOIN v1_menu_buttonbar b ON m.switch_id = b.page_id
                            WHERE nav_id = '$ThisNavID'
                            ORDER BY display_order, link_name");
                            $Results3	= $query->getResultArray();
                        }
                    }else{
                        $Results3 = [];
                    }


                    ?><li <?php if (! empty($Results3)) echo 'class="has-children"'; ?>><a href="<?php echo $HyperLink;?>" <?php echo $Target;?>><?php echo $LinkName;?></a>
                    <?php
                    if (count($Results3) > 0){
                        ?><ul class="dropdown-menu submenu sub-nav"><?php
                        foreach ($Results3 as $Row) {
                            $LinkName    		= $Row['link_name'];
                            $DisplayOrder  	 	= $Row['display_order'];
                            $HyperLink    		= $Row['hyperlink'];
                            $Target    			= $Row['target'];
                            $MenuPageTitle      = $Row['page_title'];
                            $ImgSource          = $Row['img_source'];
                            if(trim($HyperLink)){
                                $href = $HyperLink;
                            }else if($MenuPageTitle){
                                $href = get_uri_from_page_title($MenuPageTitle);
                            }else{
                                continue;
                            }
                            $class = '';
                            if(substr(strtolower($href), 0, 5) === 'http:' || substr(strtolower($href), 0, 6) === 'https:'){
                                $class = ' class="trackable-external-link-' . get_uri_from_page_title($LinkName) . '"';
                            }
                            $target      	    = !empty($Target) || $class ? ' target="_'.($Target ? $Target : 'blank').'"' : '';

                            ?><li><a href="<?php echo $href;?>" <?php echo $class . $target;?>><?php
                                echo $LinkName;
                                if ($ImgSource){
                                    ?><span class="link-icon"><img src="<?php echo $ImgSource;?>" style="<?php echo stristr($invertImageThemes, $Theme) ? 'filter: invert(100%);"' : '';?>" /></span><?php
                                }
                                ?></a></li><?php
                        }
                        ?></ul><?php
                    }
                    ?>
                    </li><?php
                }
                ?>
                <!-- final user tools -->
                <li class="user-tools"><?php
                    if (!empty($login['active'])) {
                        ?>
                        Hi, <?php echo $login['user']['first_name'];?>&nbsp; <a href="/Auth/processLogout"><span class="glyphicon glyphicon-log-out"></span> Logout</a>
                        <?php
                    } else {
                        ?>Hello guest user &nbsp; <a href="<?php echo ENVIRONMENT === 'testing' ? '/tmp/login.php' : '/system/casLogin.php';?>"><span class="glyphicon glyphicon-log-in"></span> Login</a><?php
                    }
                    ?></li>
            </ul>
        </nav>
        <a href="#main-menu-toggle"
           class="backdrop"
           tabindex="-1"
           aria-hidden="true"
           hidden></a>
    </header>
    <script>
        $(document).ready(function(){
            var selectedElement = '';
            //this could be one of the toughest areas
            $(document).click(function(event){
                if(selectedElement){
                    $(selectedElement).parent().toggleClass('nav-selected');
                    $(selectedElement).parent().find('ul').toggle(400);
                    selectedElement = '';
                }
            });
            $('#main-menu-toggle').click(function(event){
                event.preventDefault();
                $('.main-menu, .backdrop').addClass('expanded');
            });
            $('#main-menu-close, .backdrop').click(function(event){
                event.preventDefault();
                $('.main-menu, .backdrop').removeClass('expanded');
            });
            $('.main-menu > ul > li.has-children > a').click(function(event){
                //close other open menus besides this one
                if(this == selectedElement){
                    //just close it
                    $(this).parent().toggleClass('nav-selected');
                    $(this).parent().find('ul').toggle(400);
                    selectedElement = '';
                }else{
                    if(selectedElement){
                        //close it first
                        $(selectedElement).parent().toggleClass('nav-selected');
                        $(selectedElement).parent().find('ul').toggle(400);
                    }
                    //open this one
                    $(this).parent().toggleClass('nav-selected');
                    $(this).parent().find('ul').toggle(400);
                    selectedElement = this;
                }
                event.preventDefault();
                event.stopPropagation();
            });
        });
    </script>
</div>
