<?php if (!defined('THINK_PATH')) exit(); /*a:4:{s:82:"D:\phpstudy_pro\WWW\fa_pay\public/../application/admin\view\pay\type\pay_rate.html";i:1616664694;s:69:"D:\phpstudy_pro\WWW\fa_pay\application\admin\view\layout\default.html";i:1616664694;s:66:"D:\phpstudy_pro\WWW\fa_pay\application\admin\view\common\meta.html";i:1616664694;s:68:"D:\phpstudy_pro\WWW\fa_pay\application\admin\view\common\script.html";i:1616664694;}*/ ?>
<!DOCTYPE html>
<html lang="<?php echo $config['language']; ?>">
    <head>
        <meta charset="utf-8">
<title><?php echo (isset($title) && ($title !== '')?$title:''); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<meta name="renderer" content="webkit">

<link rel="shortcut icon" href="/assets/img/favicon.ico" />
<!-- Loading Bootstrap -->
<link href="/assets/css/backend.css?v=<?php echo \think\Config::get('site.version'); ?>" rel="stylesheet">

<!-- HTML5 shim, for IE6-8 support of HTML5 elements. All other JS at the end of file. -->
<!--[if lt IE 9]>
  <script src="/assets/js/html5shiv.js"></script>
  <script src="/assets/js/respond.min.js"></script>
<![endif]-->
<script type="text/javascript">
    var require = {
        config:  <?php echo json_encode($config); ?>
    };
</script>
    </head>

    <body class="inside-header inside-aside <?php echo defined('IS_DIALOG') && IS_DIALOG ? 'is-dialog' : ''; ?>">
        <div id="main" role="main">
            <div class="tab-content tab-addtabs">
                <div id="content">
                    <div class="row">
                        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                            <section class="content-header hide">
                                <h1>
                                    <?php echo __('Dashboard'); ?>
                                    <small><?php echo __('Control panel'); ?></small>
                                </h1>
                            </section>
                            <?php if(!IS_DIALOG && !$config['fastadmin']['multiplenav']): ?>
                            <!-- RIBBON -->
                            <div id="ribbon">
                                <ol class="breadcrumb pull-left">
                                    <li><a href="dashboard" class="addtabsit"><i class="fa fa-dashboard"></i> <?php echo __('Dashboard'); ?></a></li>
                                </ol>
                                <ol class="breadcrumb pull-right">
                                    <?php foreach($breadcrumb as $vo): ?>
                                    <li><a href="javascript:;" data-url="<?php echo $vo['url']; ?>"><?php echo $vo['title']; ?></a></li>
                                    <?php endforeach; ?>
                                </ol>
                            </div>
                            <!-- END RIBBON -->
                            <?php endif; ?>
                            <div class="content">
                                <style type="text/css">
    @media (max-width: 375px) {
        .edit-form tr td input{width:100%;}
        .edit-form tr th:first-child,.edit-form tr td:first-child{
            width:20%;
        }
        .edit-form tr th:nth-last-of-type(-n+2),.edit-form tr td:nth-last-of-type(-n+2){
            display: none;
        }
    }
    .edit-form table > tbody > tr td a.btn-delcfg{
        visibility: hidden;
    }
    .edit-form table > tbody > tr:hover td a.btn-delcfg{
        visibility: visible;
    }
</style>
<div class="panel panel-default panel-intro">
    <div class="panel-heading">
        <?php echo build_heading(null, false); ?>
        <ul class="nav nav-tabs">
            <?php foreach($siteList as $index=>$vo): ?> 
            <li class="<?php echo !empty($vo['active'])?'active':''; ?>"><a href="#<?php echo $vo['name']; ?>" data-toggle="tab"><?php echo __($vo['title']); ?></a></li>
            <?php endforeach; ?>
            <li>
                <a href="#addcfg" data-toggle="tab"><i class="fa fa-plus"></i></a>
            </li>
        </ul>
    </div>

    <div class="panel-body">
        <div id="myTabContent" class="tab-content">
            <?php foreach($siteList as $index=>$vo): ?> 
            <div class="tab-pane fade <?php echo !empty($vo['active'])?'active in' : ''; ?>" id="<?php echo $vo['name']; ?>">
                <div class="widget-body no-padding">
                    <form id="<?php echo $vo['name']; ?>-form" class="edit-form form-horizontal" role="form" data-toggle="validator" method="POST" action="<?php echo url('pay.type/add_pay_rate'); ?>">
                        <input type="hidden" name="row[admin_id]" value="<?php echo $admin_id; ?>">
                        <input type="hidden" name="row[edit]" value="1">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th width="30%">通道名称</th>
                                    <th width="30%">通道标识</th>
                                    <th width="30%">费率</th>
                                    <th width="2%"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($vo['list'] as $item): ?>
                                <tr>
                                    <td><?php echo $item['pay_type_name']; ?></td>
                                    <td><?php echo $item['pay_type']; ?></td>
                                    <td>
                                        <div class="row">
                                            <div class="col-sm-8 col-xs-12">
                                                <?php switch($item['type']): case "number": ?>
                                                <input  type="number" name="row[<?php echo $item['id']; ?>]" value="<?php echo $item['rate']; ?>" class="form-control"  data-rule="required" />

                                                <?php break; endswitch; ?>
                                            </div>
                                            <div class="col-sm-4"></div>
                                        </div>

                                    </td>
                                    <td><a href="javascript:;" class="btn-delcfg text-muted" data-name="<?php echo $item['id']; ?>"><i class="fa fa-times"></i></a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td></td>
                                    <td>
                                        <button type="submit" class="btn btn-success btn-embossed"><?php echo __('OK'); ?></button>
                                        <button type="reset" class="btn btn-default btn-embossed"><?php echo __('Reset'); ?></button>
                                    </td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>


            <div class="tab-pane fade" id="addcfg">
                <form id="add-form" class="form-horizontal" role="form" data-toggle="validator" method="POST" action="<?php echo url('pay.type/add_pay_rate'); ?>">
                    <div class="form-group">
                        <label for="type" class="control-label col-xs-12 col-sm-2">通道:</label>
                        <div class="col-xs-12 col-sm-4">
                            <select name="row[content]" class="form-control selectpicker">
                                <?php if(is_array($typeList) || $typeList instanceof \think\Collection || $typeList instanceof \think\Paginator): if( count($typeList)==0 ) : echo "" ;else: foreach($typeList as $key=>$vo): ?>
                                <option value=<?php echo json_encode($vo); ?>><?php echo $vo['name']; ?></option>
                                <?php endforeach; endif; else: echo "" ;endif; ?>
                            </select>
                        </div>
                    </div>


                    <div class="form-group">
                        <label for="value" class="control-label col-xs-12 col-sm-2">费率:</label>
                        <div class="col-xs-12 col-sm-4">
                            <input type="number" class="form-control" id="rate" name="row[rate]" value="" data-rule="required;" />
                            <input type="hidden"  name="row[admin_id]" value="<?php echo $admin_id; ?>" />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-xs-12 col-sm-2"></label>
                        <div class="col-xs-12 col-sm-4">
                            <button type="submit" class="btn btn-success btn-embossed"><?php echo __('OK'); ?></button>
                            <button type="reset" class="btn btn-default btn-embossed"><?php echo __('Reset'); ?></button>
                        </div>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="/assets/js/require<?php echo \think\Config::get('app_debug')?'':'.min'; ?>.js" data-main="/assets/js/require-backend<?php echo \think\Config::get('app_debug')?'':'.min'; ?>.js?v=<?php echo $site['version']; ?>"></script>
    </body>
</html>