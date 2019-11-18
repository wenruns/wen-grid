<?php
/**
 * Created by PhpStorm.
 * User: wen
 * Date: 2019/10/26
 * Time: 15:48
 */

namespace vendor\WenGrid;


use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid\Model;
use Encore\Admin\Grid\Tools\ExportButton;
use GuzzleHttp\Client;
use PhpParser\Node\Expr\AssignOp\Mod;

class WenExportButton extends ExportButton
{

    protected $key = '';

    protected function checkHtmlString($str)
    {
        if (empty($str)) {
            return '';
        }
        $arr = $str;
        if (!is_array($str)) {
            $arr = explode("\n", str_replace("\r\n", "\n", $str));
        }
        $str = '';
        $tr = '';
        $len = count($this->grid->getWenExporter()->getHead());
        foreach ($arr as $key => $string) {
            $str .= $string . '<br/>';
            if ($key) {
                $tr .= '<tr></tr>';
            }
        }
        $html = '<tr><td rowspan="' . count($arr) . '" class="xl68" height="' . (count($arr) * 28) . '" colspan="' . $len . '" style="height: ' . (count($arr) * 28) . 'px;border-right:none;border-bottom:none;" x:str>' . rtrim($str, '<br/>') . '</td></tr>' . $tr;
        return $html;
    }

    protected function setUpScripts()
    {
        $head = json_encode($this->grid->getWenExporter()->getHead());
        $body = json_encode($this->grid->getWenExporter()->getBody());
        $header = $this->checkHtmlString($this->grid->getWenExporter()->setHeader());
        $footer = $this->checkHtmlString($this->grid->getWenExporter()->setFooter());
        // TODO: Change the autogenerated stub
        $script = <<<SCRIPT
            var pageN_{$this->key} = 0; // 请求数据次数（第几次）
            var pageRange_{$this->key} = {}; // 导出页数（导出指定页数范围）
            var excelHtml_{$this->key} = ''; // 导出的excel表格
            var index_{$this->key} = 0;  // 导出的历史索引记录
            var data_{$this->key} = new Array(); // 导出的数据集合
            var cancel_{$this->key} = false;
            
            function initData(){
                pageN_{$this->key} = 0;
                pageRange_{$this->key} = {};
                excelHtml_{$this->key} = '';
                index_{$this->key} = 0;
                data_{$this->key} = [];
            }
            
            // 生成头部
            function makeHead(){
                excelHtml_{$this->key} = '<'+'html xmlns:o="urn:schemas-microsoft-com:office:office" ' +
                    'xmlns:x="urn:schemas-microsoft-com:office:excel" ' +
                    'xmlns="http://www.w3.org/TR/REC-html40"><head>' +
                    '<!--[if gte mso 9]>' +
                    '<xml>' +
                    '<x:ExcelWorkbook>' +
                    '   <x:ExcelWorksheets>' +
                    '       <x:ExcelWorksheet>' +
                    '           <x:Name><'+'/'+'x:Name>'+
                    '           <x:WorksheetOptions>' +
                    '               <x:DisplayGridlines'+'/'+'>' +
                    '           <'+'/'+'x:WorksheetOptions>' +
                    '       <'+'/'+'x:ExcelWorksheet>' +
                    '   <'+'/'+'x:ExcelWorksheets>' +
                    '<'+'/'+'x:ExcelWorkbook>' +
                    '<'+'/'+'xml>' +
                    '<![endif]-->' +
                    '<style>' +
                    "   br{mso-data-placement:same-cell;}" +
                    "   .wen-excel-header{font-family:{$this->grid->getWenExporter()->setFontFamily()};}" +
                    "   .wen-excel-head{text-align: center;height: 34px; font-family:{$this->grid->getWenExporter()->setFontFamily()};}" +
                    "   .wen-excel-body{text-align: center;height: 28px; font-family:{$this->grid->getWenExporter()->setFontFamily()};}" +
                    "   .wen-excel-statistic{text-align: left !important;height: 28px; font-family:{$this->grid->getWenExporter()->setFontFamily()};}" +
                    '<'+'/style>' +
                    '</head><body><table><thead>';
                var head = {$head};
                var header = '{$header}';
                if (header) {
                    excelHtml_{$this->key} += header;
                }
                excelHtml_{$this->key} += '<tr>';
                for (var i in head) {
                    excelHtml_{$this->key} += '<th class="wen-excel-head">'+head[i]+'</th>'
                }
                excelHtml_{$this->key} += '</tr></thead><tbody>';
            }
            
            
            // 生成主体
            function makeBody(){
                var body = {$body};
                var formatFunc = {$this->grid->getWenExporter()->setFormat()};
                /*console.log('func ==> ', formatFunc, {$head}, body, data_{$this->key});*/
                for (var i = index_{$this->key}; i < data_{$this->key}.length; i++) {
                    var item = data_{$this->key}[i];
                    <!--console.log(item);-->
                    for (var j = 0; j < item.length; j++) {
                        excelHtml_{$this->key} += '<tr>'; 
                        for (var field in body) {
                            <!--console.log(body[field]);-->
                            excelHtml_{$this->key} += '<td class="wen-excel-body">' + formatFunc(item[j], body[field]) +'</td>';
                        }
                        excelHtml_{$this->key} += '</tr>'; 
                    }
                    index_{$this->key}++;
                }
            }
            
            // 生成脚部
            function makeFooter(){
                var footer = '{$footer}';
                if (footer) {
                    excelHtml_{$this->key} += footer;
                }
                excelHtml_{$this->key} += '<'+'/tbody><'+'/table><'+'/body><'+'/html>';
            }
            
            
            function download(){
//                console.log(excelHtml_{$this->key});
                // 生成excel表格
                let blob = new Blob([excelHtml_{$this->key}], {
                    type: 'application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' //,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
                })
                if (window.navigator.msSaveOrOpenBlob) {
                    navigator.msSaveBlob(blob);
                } else {
                    let elink = document.createElement('a');
                    elink.download = "{$this->grid->getWenExporter()->getFileName()}.{$this->grid->getWenExporter()->getType()}";
                    elink.style.display = 'none';
                    elink._target = 'blank';
                    elink.href = URL.createObjectURL(blob);
                    document.body.appendChild(elink);
                    elink.click();
                    document.body.removeChild(elink);
                }
                hideTipsBox();
            }
            
            function hideTipsBox(){
                $(".wen-tips-box-{$this->key}").addClass('wen-hide');
            }
            
            function showTipsBox(){
                $(".wen-tips-box-{$this->key}").removeClass('wen-hide');
            }
        
            
            
        
            function getData(event){
                if (cancel_{$this->key}) {
                    return;
                }
                var target = event.target.getAttribute('data-target');
                var url = event.target.getAttribute('data-href');
                var data = {
                    pageN: pageN_{$this->key},
                    per_page: {$this->grid->perPage}
                };
                switch (target) {
                    case 'all':
                        break;
                    case 'page':
                        break;
                    case 'selected':
                        url += {$this->grid->getSelectedRowsName()}().join(',')
                        break;
                    case 'pages':
                        if (!Object.keys(pageRange_{$this->key}).length) {
                            hideTipsBox();
                            Swal.fire({
                                title: '请选择导出页数范围', //标题
                                html: '<div>第&nbsp;<input type="text" id="rangePage-start" style="width:80px;line-height: 25px;text-align:center;font-size:18px;"> — <input type="text" id="rangePage-end" style="width:80px;line-height: 25px;text-align:center;font-size:18px;">&nbsp;&nbsp;页<\/div>', // HTML
                                confirmButtonColor: '#3085d6',// 确定按钮的 颜色
                                confirmButtonText: '确定',// 确定按钮的 文字
                                allowOutsideClick:false,
                                showCancelButton:true,
                                cancelButtonText: '取消',
                            }).then((isConfirm) => {
                                try {
                                    //判断 是否 点击的 确定按钮
                                    if (isConfirm.value) {
                                        let startPage = $("#rangePage-start").val();
                                        let endPage = $("#rangePage-end").val();
                                        // console.log(startPage, endPage)
                                        pageRange_{$this->key} = {
                                            start: startPage,
                                            end: endPage,
                                        }
                                        getData(event);
                                    }
                                } catch (e) {
                                    alert(e);
                                }
                            });
                            return false;
                        }
                        url += 'all';
                        showTipsBox();
                        data.pageRange = pageRange_{$this->key};
                        break;
                    default:
                        
                }
                <!--console.log(url, data);-->
                
                // 发送ajax请求
                ajaxRequest = $.ajax({
                    url: url,
                    method: 'GET',
                    // contentType:"application/json",
                    responseType: 'json',
                    data: data,
                    success: function(res) {
                        <!--console.log('responseText => ', res);-->
                        res = JSON.parse(res)
                        <!--console.log('response ==> ', res);-->
                        if (res.data.length > 0) {
                            data_{$this->key}.push(res.data);
                        }
                        makeBody();
                        // 判断数据获取完成
                        if(res.finished == true) {
                            makeFooter();
                            // 导出excel
                            download();
                        } else {
                            // 轮询查询数据
                            pageN_{$this->key}++;
                            getData(event);
                        }
                    },
                    fail: function(err) {
                        /*initData();*/
                        console.warn(err)
                    }
                });
            }
            
            
            // 导出点击事件
            $('.wen-dropdown-menu-{$this->key}').click(function(e){
                showTipsBox();
                cancel_{$this->key} = false;
                initData();
                makeHead();
                getData(e);
            });

            // 取消导出
            $('.wen-cancel-export-btn-{$this->key}').click(function(e){
                hideTipsBox();
                cancel_{$this->key} = true;
                initData();
            });

SCRIPT;

        Admin::script($script);
    }

    public function render()
    {
        // TODO: Change the autogenerated stub
        $this->key = mt_rand(0000, 9999) . date('ymdHis');
        return <<<EOT
{$this->exportBtn()}
{$this->importBtn()}
EOT;
    }

    protected function exportBtn()
    {
        if (!$this->grid->showExportBtn()) {
            return '';
        }
        $this->setUpScripts();
        $export = trans('admin.export');
        $all = trans('admin.all');
        $currentPage = trans('admin.current_page');
        $selectedRows = trans('admin.selected_rows');

        $page = request('page', 1);
        return <<<EOT
{$this->showTips('正在导出', true)}
<div class="btn-group pull-right" style="margin-right: 10px">
    <a class="btn btn-sm btn-twitter" title="{$export}"><i class="fa fa-download"></i><span class="hidden-xs"> {$export}</span></a>
    <button type="button" class="btn btn-sm btn-twitter dropdown-toggle" data-toggle="dropdown">
        <span class="caret"></span>
        <span class="sr-only">Toggle Dropdown</span>
    </button>
    <ul class="dropdown-menu wen-dropdown-menu-{$this->key}" role="menu">
        <li><a data-target="all" data-href="{$this->grid->getExportUrl('all')}" target="_blank">{$all}</a></li>
        <li><a data-target="page" data-page="{$page}" data-href="{$this->grid->getExportUrl('page', $page)}" target="_blank">{$currentPage}</a></li>
        <li><a data-target="selected" data-href="{$this->grid->getExportUrl('selected')}" target="_blank" class='{$this->grid->getExportSelectedName()}'>{$selectedRows}</a></li>
        <li><a data-target="pages" data-href="{$this->grid->getExportUrl('page')}">导出指定页</a></li>
    </ul>
</div>
EOT;
    }


    protected function setImportScript($prefix = '')
    {
        $DS = DIRECTORY_SEPARATOR == '\\' ? '\\\\' : '/';
        $types = json_encode($this->grid->getWenExporter()->setImportTypes());
        $script = <<<SCRIPT
        
        function admin_toastr(msg, type = 'error'){
            $('.{$prefix}wen-tips-message-box{$this->key}').html(msg);
            $('.{$prefix}wen-tips-box{$this->key}').addClass('{$prefix}wen-tips-box-show{$this->key}');
            $('.{$prefix}wen-tips-box{$this->key}').addClass('{$prefix}wen-tips-'+type+'-box{$this->key}');
            var hand = setTimeout(function(){
                $('.{$prefix}wen-tips-box{$this->key}').removeClass('{$prefix}wen-tips-box-show{$this->key}');       
                $('.{$prefix}wen-tips-box{$this->key}').removeClass('{$prefix}wen-tips-'+type+'-box{$this->key}');    
                clearTimeout(hand);     
            },3000);
        }
        
        $('.{$prefix}wen-input-cancel-button{$this->key}').click(function(e){
            $('#{$prefix}wen-import-input{$this->key}').val('');
            $('#{$prefix}wen-import-input{$this->key}').change();
        });
        
        $('#{$prefix}wen-import-input{$this->key}').change(function(e){
            var name = '';
            if (e.currentTarget.value) {
                var file = e.currentTarget.files[0];
                name = file.name;
                var type = name.substring(name.lastIndexOf('.')+1);
                if({$types}.indexOf(type) < 0){
                    admin_toastr('文件格式不正确！');
                    return ;
                }
                var reader = new FileReader();
                reader.readAsText(file, 'UTF-8');
                reader.onload = function(evt){
                    var fileString = evt.target.result; // 读取文件内容
                    $('#{$prefix}wen-import-preview{$this->key}').html(fileString);
                }
            } else {
                $('#{$prefix}wen-import-preview{$this->key}').html('');
            }
            $('#{$prefix}wen-import-input-show{$this->key}').val(name);
        });
        
        $('.{$prefix}wen-import-button{$this->key}').click(function(e){
            $('.{$prefix}wen-import-box{$this->key}').show();
        });
SCRIPT;
        Admin::script($script);
    }

    protected function importBtn()
    {
        if (!$this->grid->showImporterBtn()) {
            return '';
        }
        $prefix = 'impot_';
        $this->setImportScript($prefix);
        $url = $this->grid->getImportUrl();
        $csrf_field = csrf_field();
        return <<<EOT
<div class="btn-group pull-right {$prefix}wen-import-button{$this->key}" style="margin-right: 10px">
    <a class="btn btn-sm btn-twitter" title="导入"><i class="fa fa-download"></i><span class="hidden-xs"> 导入</span></a>
</div>
<style>
    .{$prefix}wen-import-box{$this->key}{
        position: absolute;
        display: none;
    }
    .{$prefix}wen-import-file-bg{$this->key}{
        position: fixed;
        width: 100vw;
        min-height: 100vh;
        background: #000;
        opacity: 0.5;
        filter: opacity(5);
        top: 0px;
        left: 0px;
        z-index: 100;
    }
    .{$prefix}wen-import-file-box{$this->key}{
        position: fixed;
        top: 20vh;
        left: 30vw;
        z-index: 101;
        width: 500px;
        /*min-height: 300px;*/
        background: #ffffff;
        padding-bottom: 30px;
        -webkit-border-radius: 10px;
        -moz-border-radius: 10px;
        border-radius: 10px;
        overflow: hidden;
    }
    .{$prefix}wen-import-file-button{$this->key}{
        width: 80%;
        margin: auto;
    }
    .{$prefix}wen-import-title{$this->key}{
        text-align: center;
        background: #367fa9;
        margin-top: 0px;
        color: white;
        padding: 10px;
    }
    .{$prefix}wen-import-btn-box{$this->key}{
        display: flex;
        justify-content: space-around;
        margin-top: 15px;
    }
    #{$prefix}wen-import-preview{$this->key}{
        width: 80%;
        max-height: 200px;
        overflow: auto;
        margin: auto;
    }
    .{$prefix}wen-tips-box{$this->key}{
        position: fixed;
        z-index: 10000;
        min-width: 200px;
        right: 10px;
        top: -500px;
        padding: 10px;
        -webkit-border-radius: 5px;
        -moz-border-radius: 5px;
        border-radius: 5px;
        -webkit-transition: all 0.5s;
        -moz-transition: all 0.5s;
        -ms-transition: all 0.5s;
        -o-transition: all 0.5s;
        transition: all 0.5s;
    }
    
    .{$prefix}wen-tips-box-show{$this->key}{
        top: 10px;
    }
    
    .{$prefix}wen-tips-success-box{$this->key}{
        background: deepskyblue;
        color: white;
    }
    .{$prefix}wen-tips-error-box{$this->key}{
        background: #E98582;
        color: white;
    }
    
</style>

<div class="{$prefix}wen-import-box{$this->key}">
    <div class="{$prefix}wen-import-file-bg{$this->key}"></div>
    <div class="{$prefix}wen-import-file-box{$this->key}">
        <h3 class="{$prefix}wen-import-title{$this->key}">选择文件</h3>
        <form id="{$prefix}wen-import-form{$this->key}" action="{$url}" method="POST" enctype="multipart/form-data" class="form-horizontal" pjax-container>
            {$csrf_field}
            <div class="{$prefix}wen-import-file-button{$this->key}">
                <div class="input-group file-caption-main">
                    <div class="file-caption form-control  kv-fileinput-caption" tabindex="500">
                        <span class="file-caption-icon"></span>
                        <input id="{$prefix}wen-import-input-show{$this->key}" class="file-caption-name" onkeydown="return false;" onpaste="return false;" placeholder="Select file...">
                    </div>
                    <div class="input-group-btn input-group-append">
                        <button type="button" tabindex="500" title="Abort ongoing upload" class="btn btn-default btn-secondary kv-hidden fileinput-cancel fileinput-cancel-button {$prefix}wen-input-cancel-button{$this->key}">
                            <i class="glyphicon glyphicon-ban-circle"></i>  
                            <span class="hidden-xs">Cancel</span>
                        </button>
                        <div tabindex="500" class="btn btn-primary btn-file">
                            <i class="glyphicon glyphicon-folder-open"></i>&nbsp;  
                            <span class="hidden-xs">浏览</span>
                            <input type="file" class="import" name="import" id="{$prefix}wen-import-input{$this->key}">
                        </div>
                    </div>
                </div>
            </div>
            <div id="{$prefix}wen-import-preview{$this->key}"></div>
            <div class="{$prefix}wen-import-btn-box{$this->key}">
                <button type="button" class="btn btn-warning">取消</button>
                <button type="submit" class="btn btn-primary {$prefix}wen-import-sure-button{$this->key}">确定</button>
            </div>
        </form>
    </div>
    <div class="{$prefix}wen-tips-box{$this->key}">
        <div class="{$prefix}wen-tips-title-box{$this->key}"></div>
        <div class="{$prefix}wen-tips-message-box{$this->key}"></div>
    </div>
</div>
EOT;
    }

    protected function showTips($msg, $cancelBtn = false, $prefix = '')
    {
        return <<<EOT
<style>
        .{$prefix}wen-dropdown-bgBox-{$this->key}{
            width: 100vw;
            height: 100vh;
            background: black;
            opacity: 0.5;
            position: fixed;
            top: 0px;
            left: 0px;
            z-index: 100;
        } 
        .{$prefix}wen-dropdown-tipsBox-{$this->key}{
            width: 100vw;
            height: 100vh;
            position: fixed;
            z-index: 101;
            left: 0vw;
            top: 0vh;
            border: 1px solid red;
        }
        .{$prefix}wen-dropdown-tipsBox-{$this->key} .wen-dropdown-tipsBox{
            width: 200px;
            height: 200px;
            background: white;
            border-radius: 5px;
            margin: 30vh auto;
        }
        .{$prefix}wen-change-{$this->key}{
            width: 40%;
            margin-top: 15%;
            -webkit-animation: {$prefix}mymove 2s infinite;
            animation: {$prefix}mymove 2s infinite;
        }
        .{$prefix}wen-hide{
            display: none;
        }

        @keyframes {$prefix}mymove
        {
            0% {-webkit-transform:rotate(0deg);}
            50% {-webkit-transform:rotate(180deg);}
            100% {-webkit-transform:rotate(360deg);}
        }
        @-webkit-keyframes {$prefix}mymove /*Safari and Chrome*/
        {
            0% {-webkit-transform:rotate(0deg);}
            50% {-webkit-transform:rotate(180deg);}
            100% {-webkit-transform:rotate(360deg);}
        }
</style>
<div class="{$prefix}wen-tips-box-{$this->key} wen-hide">
    <div class="{$prefix}wen-dropdown-bgBox-{$this->key}"></div>
    <div class="{$prefix}wen-dropdown-tipsBox-{$this->key}">
        <div class="{$prefix}wen-dropdown-tipsBox">
            <div style="height: 75%;text-align: center;">
                <img class="{$prefix}wen-change-{$this->key}" src="/images/static/loading.png" alt="">
                <div>{$msg}</div>
            </div>
            {$this->cancelBtn($cancelBtn, $prefix)}
        </div>   
    </div>
</div>
EOT;
    }

    protected function cancelBtn($cancelBtn = false, $prefix = '')
    {
        if (!$cancelBtn) {
            return '';
        }
        return <<<EOT
<div style="text-align: center;">
    <span class="btn-warning btn {$prefix}wen-cancel-export-btn-{$this->key}" style="height: 30px;line-height: 18px;">取消</span>
</div> 
EOT;
    }
}