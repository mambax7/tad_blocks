<?php
use XoopsModules\Tadtools\FancyBox;
use XoopsModules\Tadtools\TadDataCenter;
use XoopsModules\Tadtools\TadUpFiles;

//取得 gallery 區塊 DataCenter 內容
function get_content($bid = 0)
{
    global $xoopsTpl;

    require __DIR__ . "/config.php";
    foreach ($default as $k => $v) {
        $xoopsTpl->assign($k, $v);
    }
    $xoopsTpl->assign('default', $default);
    // 傳回陣列的項目
    if ($bid) {
        $arr           = ['groups'];
        $TadDataCenter = new TadDataCenter('tad_blocks');
        $TadDataCenter->set_col('bid', $bid);
        $block = $TadDataCenter->getData();

        foreach ($block as $k => $v) {
            if (in_array($k, $arr)) {
                $xoopsTpl->assign($k, $v);
            } else {
                $xoopsTpl->assign($k, $v[0]);
            }
        }
    }

    $pic_col_sn = $block['pic_col_sn'][0] ? $block['pic_col_sn'][0] : $default['pic_col_sn'];

    $TadUpFiles = new TadUpFiles("tad_blocks");
    $TadUpFiles->set_col('gallery', $pic_col_sn);
    $xoopsTpl->assign('pic_col_sn', $pic_col_sn);

                                             // $TadUpFiles->set_var('require', true); //必填
    $TadUpFiles->set_var("show_tip", false); //不顯示提示
    $upform = $TadUpFiles->upform(true, 'gallery_files');
    $xoopsTpl->assign('upform', $upform);

    return $block;
}

//製作 gallery 區塊內容
function mk_content($bid, $TDC)
{

    require __DIR__ . "/config.php";
    $myts = \MyTextSanitizer::getInstance();

    $mode        = empty($TDC['mode']) ? $default['mode'] : $myts->htmlSpecialChars($TDC['mode']);
    $show_desc   = empty($TDC['show_desc']) ? $default['show_desc'] : (int) $TDC['show_desc'];
    $bg_size     = empty($TDC['bg_size']) ? $default['bg_size'] : $myts->htmlSpecialChars($TDC['bg_size']);
    $pic_col_sn  = empty($TDC['pic_col_sn']) ? $default['pic_col_sn'] : (int) $TDC['pic_col_sn'];
    $show_width  = empty($TDC['show_width']) ? $default['show_width'] : (int) $TDC['show_width'];
    $show_height = empty($TDC['show_height']) ? $default['show_height'] : (int) $TDC['show_height'];
    $desc_height = empty($TDC['desc_height']) ? $default['desc_height'] : (int) $TDC['desc_height'];
    $thumb_css   = empty($TDC['thumb_css']) ? $default['thumb_css'] : $myts->htmlSpecialChars($TDC['thumb_css']);
    $thumb_css .= "background-size: $bg_size";
    $TadUpFiles = new TadUpFiles("tad_blocks");
    $TadUpFiles->set_col('gallery', $pic_col_sn);
    $TadUpFiles->upload_file('gallery_files', null, null, null, null, true);

    $fancybox = new FancyBox(".fancybox_gallery" . $pic_col_sn, '1920', '1080');
    $content  = $fancybox->render(false, null, null, null, true);

    if ($mode === 'thumbs') {

        $TadUpFiles->set_var('thumb_css', $thumb_css);
        $TadUpFiles->set_var('show_width', $show_width);
        $TadUpFiles->set_var('show_height', $show_height);
        $TadUpFiles->set_var('desc_height', $desc_height);
        $content .= "
        <link rel='stylesheet' type='text/css' media='all' title='Style sheet' href='" . XOOPS_URL . "/modules/tad_blocks/type/gallery/thumbs.css'>";
        $rand = mt_rand(0, 999999);
        $TadUpFiles->set_var('dl_function_name', 'downloadFile' . $rand);
        $content .= $TadUpFiles->show_files('gallery_files', true, $mode, $show_desc, false, null, null, false);

        //show_files($upname="",$thumb=true,$show_mode="",$show_description=false,$show_dl=false,$limit=NULL,$path=NULL,$hash=false,$playSpeed=5000)

    } elseif ($mode === 'marquee') {

        $file_arr = $TadUpFiles->get_file(null, null, null, false, true);

        $content .= "
        <script src='" . XOOPS_URL . "/modules/tadtools/tad_loop/tad_loop.js'></script>
        <link rel='stylesheet' type='text/css' media='all' title='Style sheet' href='" . XOOPS_URL . "/modules/tadtools/tad_loop/tad_loop.css'>
        <div class='tad-loop' id='tad-loop-{$bid}'>
            <div class='item-wrap'>
        ";
        foreach ($file_arr as $files_sn => $pic) {
            $content .= "
            <div class='item'>
                <a href='{$pic['path']}' rel='fgallery{$pic_col_sn}' class='fancybox_gallery{$pic_col_sn}'><img src='{$pic['tb_path']}' alt='{$pic['description']}'></a>
            </div>";
        }

        $content .= "
            </div>
        </div>
        <script>
            var marquee = new TadLoop('#tad-loop-{$bid}', {
            velocity: 2,
            forward: true,
            pauseOnHover: true
        });
        </script>
      ";

    } elseif ($mode === 'slide') {

        $file_arr = $TadUpFiles->get_file(null, null, null, false, true);

        $content .= "
        <link rel='stylesheet' type='text/css' href='" . XOOPS_URL . "/modules/tadtools/tad_slide/tad-slide.css?t=2026032602' >
        <script type='text/javascript' src='" . XOOPS_URL . "/modules/tadtools/tad_slide/tad-slide.js?t=2026032602'></script>

        <div id='slide_{$pic_col_sn}' class='tad-slide' aria-label='圖片輪播'>
            <ul class='tad-slide__list'>";

        foreach ($file_arr as $files_sn => $pic) {
            $content .= "
                <li class='tad-slide__item'>
                    <img src='{$pic['path']}' alt='{$pic['description']}'>
                </li>
            ";

            $i++;
        }

        $content .= "
            </ul>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            tadSlide('#slide_{$pic_col_sn}', {
            navMode : 'sides',
            effect  : 'slide',
            timeout : 8000,
            speed   : 600,
            });
        });
        </script>
        ";

    }

    return $content;
}
