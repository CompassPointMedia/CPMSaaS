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

if (! function_exists('get_uri_from_page_title')) {
    function get_uri_from_page_title($string) {
        $string = strtolower(trim($string));
        $string = preg_replace('/[^a-z0-9_ -]/', '', $string);
        $string = preg_replace('/[ ]/', '-', $string);
        $string = preg_replace('/[-]{2,}/', '-', $string);
        return $string;
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
                $sql        = "SELECT * FROM v1_menu_navbar ORDER BY display_order ASC";
                $query   	= $dbMaster->query($sql);
                $results   	= $query->getResultArray();

                if (!empty($login['active']) && !empty($login['accounts'][$subdomain])) {
                    $sql        = "SELECT 
                        CONCAT('local-', g.id) id,
                        g.name,
                        g.description title,
                        CONCAT('/data/group/', g.identifier) url,
                        g.id display_order,
                        '' img_source
                        FROM sys_data_object_group g JOIN sys_data_object o ON g.id = o.group_id
                        GROUP BY g.id ORDER BY g.id";
                    $query = $dbAccounts[$subdomain]->query($sql);
                    $resultsLocal = $query->getResultArray();

                    if (!empty($resultsLocal)) {
                        $results = array_merge($results, $resultsLocal);
                    }
                }

                foreach ($results as $row) {
                    if ($row['name'] === 'Data Objects') {
                        $results2 = [];
                        // Tables only shown if logged in, and by permission
                        if (!empty($login['active']) && !empty($login['accounts'][$subdomain])) {
                            $results2 = [
                                [
                                    'name'          => 'Manage Data Objects',
                                    'url'           => '/data/manage',
                                    'display_order' => 1000,
                                    'img_source'    => '',
                                ], [
                                    'name'          => 'Add Data Object',
                                    'url'           => '/data/create',
                                    'display_order' => 1000,
                                    'img_source'    => '',
                                ]
                            ];
                        }

                        $results2 = array_merge(
                            $results2,
                            [
                                [
                                    'name'          => 'Data Object Help',
                                    'url'           => '/data/help',
                                    'display_order' => 1000,
                                    'img_source'    => '',
                                ]
                            ]
                        );
                    } else if (substr($row['id'], 0, 6) === 'local-') {
                        $query    = $dbAccounts[$subdomain]->query("SELECT
                                t.title name,
                                t.description title,
                                CONCAT('/data/view/', t.table_key) url,
                                '' img_source
                                FROM sys_data_object t 
                                WHERE group_id = '" . str_replace('local-', '', $row['id']) . "'                              
                                ORDER BY t.title");
                        $results2 = $query->getResultArray();
                    } else {
                        $query 		= $dbMaster->query("SELECT m.* FROM v1_menu_items m WHERE nav_id = '" . $row['id'] . "' ORDER BY display_order");
                        $results2	= $query->getResultArray();
                    }

                    ?><li<?php if (! empty($results2)) echo ' class="has-children"'; ?>><a<?php echo !empty($row['title']) ? ' title="' . htmlentities($row['title']) . '"' : ''?> href="<?php echo $row['url']?>"><?php echo $row['name'];?></a>
                    <?php
                    // Subnav items
                    if (count($results2) > 0){
                        ?><ul class="dropdown-menu submenu sub-nav"><?php
                        foreach ($results2 as $row2) {
                            $class = '';
                            if(substr(strtolower($row2['name']), 0, 5) === 'http:' || substr(strtolower($row2['name']), 0, 6) === 'https:'){
                                $external = true;
                                $class = ' class="trackable-external-link-' . get_uri_from_page_title($row2['name']) . '"';
                            } else {
                                $external = false;
                            }
                            ?><li><a<?php echo !empty($row2['title']) ? ' title="' . htmlentities($row2['title']) . '"' : ''?> href="<?php echo strlen($row2['url']) ? $row2['url'] : '/sample/' . get_uri_from_page_title($row2['name']);?>" <?php echo $class;?><?php echo $external ? ' target="_blank"' : ''?>><?php
                                echo $row2['name'];
                                if ($row2['img_source']){
                                    ?><span class="link-icon"><img src="<?php echo $row2['img_source'];?>" style="<?php echo stristr($invertImageThemes, $Theme) ? 'filter: invert(100%);"' : '';?>" /></span><?php
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
