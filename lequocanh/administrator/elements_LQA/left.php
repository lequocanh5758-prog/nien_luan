<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<?php
require_once './elements_LQA/mod/phanquyenCls.php';
require_once './elements_LQA/mod/phanHeQuanLyCls.php';
$phanQuyen = new PhanQuyen();
$phanHeObj = new PhanHeQuanLy();
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');
$isAdmin = isset($_SESSION['ADMIN']) || $phanQuyen->isAdmin($username);
$isNhanVien = $phanQuyen->isNhanVien($username);
?>
<div class="left-menu">
    <div>
        <a href="index.php" class="<?php echo !isset($_GET['req']) ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> Menu
        </a>
    </div>
    <div class="">
        <a href="#"><i class="fas fa-cogs"></i> Quản lý</a>
    </div>
    <div class="">
        <ul>
            <?php
            $current_page = isset($_GET['req']) ? $_GET['req'] : '';
            require_once './elements_LQA/mod/menuConfig.php';

            if (isset($_SESSION['ADMIN'])) {
                $phanHeObj = new PhanHeQuanLy();
                $syncResult = $phanHeObj->syncModulesFromMenu($menu_items);
                if ($syncResult['added'] > 0 || $syncResult['updated'] > 0) {
                    error_log("Menu sync: Added {$syncResult['added']}, Updated {$syncResult['updated']} modules");
                }
            }

            foreach ($menu_items as $req => $item) {
                $shouldShow = false;

                if ($isAdmin) {
                    $shouldShow = true;
                }

                else if ($username === 'manager1') {

                    $manager1AllowedModules = [
                        'baocaoview',
                        'sanPhamBanChayView',
                        'loiNhuanView',
                        'userprofile',
                        'userUpdateProfile',
                        'thongbao'
                    ];
                    $shouldShow = in_array($req, $manager1AllowedModules);
                } else if ($username === 'staff2') {

                    $staff2AllowedModules = [
                        'hanghoaview',
                        'dongiaview',
                        'userprofile',
                        'userUpdateProfile',
                        'thongbao'
                    ];
                    $shouldShow = in_array($req, $staff2AllowedModules);
                } else if ($username === 'lequocanh05') {

                    $lequocanhAllowedModules = [
                        'khachhangview',
                        'adminGiohangView',
                        'lichsumuahang',
                        'userprofile',
                        'userUpdateProfile',
                        'thongbao'
                    ];
                    $shouldShow = in_array($req, $lequocanhAllowedModules);
                }

                else if ($isNhanVien || strpos($username, 'manager') !== false) {

                    try {
                        $shouldShow = $phanQuyen->checkAccess($req, $username);
                    } catch (Exception $e) {
                        error_log("Menu access check error for $req: " . $e->getMessage());
                        $shouldShow = false;
                    }
                }

                else {

                    $basicUserModules = ['userprofile', 'userUpdateProfile', 'lichsumuahang'];
                    $shouldShow = in_array($req, $basicUserModules);
                }

                if ($shouldShow) {
                    $active_class = ($current_page === $req) ? 'active' : '';
                    $devBadge = isset($item['dev']) && $item['dev'] ? ' <span style="font-size:9px;background:#ff9800;color:#fff;padding:1px 4px;border-radius:3px;margin-left:3px;">DEV</span>' : '';
                    echo "<li><a href='index.php?req=$req' class='$active_class'><i class='{$item['icon']}'></i> {$item['text']}{$devBadge}</a></li>";
                }
            }
            ?>
            <?php

            $shouldShowShoppingPage = false;

            if ($isAdmin && $username === 'admin') {

                $shouldShowShoppingPage = true;
            } else if (!$isNhanVien) {

                $shouldShowShoppingPage = true;
            }

            if ($shouldShowShoppingPage) {
                echo '<li><a href="../index.php"><i class="fas fa-store"></i> Trang mua hàng</a></li>';
            }
            ?>
        </ul>
    </div>
</div>