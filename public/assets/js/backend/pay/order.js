define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table', 'echarts', 'echarts-theme', 'template'], function ($, undefined, Backend, Datatable, Table, Echarts, undefined, Template) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'pay/order/index',
                    add_url: 'pay/order/add',
                    edit_url: 'pay/order/edit',
                    del_url: 'pay/order/del',
                    multi_url: 'pay/order/multi',
                    table: 'pay_order',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                onLoadSuccess : function(data) {
                    //console.log(data.all_money);
                    $("#all_money").text("实际收款金额："+data.all_money+" 元");
                    // $("#cash_money").text("可提现金额："+data.cash_money+" 元");
                },
                columns: [
                    [
                        {checkbox: true},
                        {
                            field: 'out_order_id',
                            title: '自定义订单号',
                        },

                        {field: 'appid', title: '商户ID',formatter: Table.api.formatter.search},

                        {
                            field: 'realprice',
                            title: '金额',
                            operate: 'BETWEEN',
                            sortable: true,
                            cellStyle: {
                                css: {"color": "#3c8dbc"}
                            }
                        },
                        {
                            field: 'freezed_amount',
                            title: '冻结金额',
                            operate: 'BETWEEN',
                            sortable: true,
                            cellStyle: {
                                css: {"color": "#e74c3c"}
                            }
                        },
                        {
                            field: 'rate',
                            title: '费率（%）',
                            operate: 'BETWEEN',
                        },
                        {
                            field: 'cost',
                            title: '手续费',
                            operate: 'BETWEEN',
                        },
                        {
                            operate: false,
                            field: 'paytype_text',
                            title: '支付通道',
                        },
                        {
                            field: 'paytype',
                            title: '支付通道标识',
                            formatter: Table.api.formatter.search
                        },

                        {
                            field: 'sys_order_id',
                            title: '系统订单号',
                            visible:false
                        },
                        {
                            field: 'createtime',
                            title: '创建时间',
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime,
                            sortable: true
                        },
                        {
                            field: 'paytime',
                            title: '支付时间',
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime,
                            sortable: true,
                        },

                        {
                            field: 'updatetime',
                            title: __('Updatetime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime,
                            sortable: true,
                            visible: false,
                        },
                        {
                            field: 'status',
                            title: __('Status'),
                            searchList: {
                                "0": __('inprogress'),
                                "1": __('expired'),
                                "2": __('paid'),
                                "4": __('settled'),
                                "3": __('unsettled'),
                            },
                            formatter: Table.api.formatter.status,
                            sortable: true,
                            custom: {
                                "0": 'inprogress',
                                "2": 'warning',
                                "1": 'danger',
                                "3": 'warning',
                                "4": 'success',
                            }
                        },

                        {
                            field: 'freezed',
                            title: '冻结',
                            searchList: {
                                "0": '正常',
                                "1": '冻结'
                            },
                            formatter: Table.api.formatter.status,
                            custom: {
                                "0": 'inprogress',
                                "1": 'danger'
                            }
                        },

                        {
                            field: 'action',
                            title: __('Operate'),
                            table: table,
                            operate: false,
                            formatter: Table.api.formatter.buttons,
                            buttons: [
                                {
                                    name: 'settled',
                                    text: '我已收款',
                                    url: 'pay/order/paid',
                                    classname: 'btn btn-primary btn-xs btn-ajax',
                                    extend: 'data-toggle="tooltip" data-placement="left"',
                                    title: '设为已完成,将同时发送通知',
                                    confirm: '确认已经收款已完成?',
                                    success: function (data) {
                                        table.bootstrapTable('refresh');
                                    },
                                    hidden: function (row) {
                                        return row.status >=2;
                                    }
                                },
                                {
                                    name: 'notify',
                                    text: '重发通知',
                                    extend: 'data-toggle="tooltip" data-placement="left"',
                                    title: '补发成功后订单状态将变为已完成',
                                    url: 'pay/order/notify',
                                    classname: 'btn btn-info btn-xs btn-ajax',
                                    success: function (data) {
                                        table.bootstrapTable('refresh');
                                    },
                                    hidden: function (row) {
                                        return row.status ===0 || row.status ===1;
                                    }
                                },
                                {
                                    name: 'notifyinfo',
                                    text: '回调信息',
                                    extend: 'data-toggle="tooltip" data-placement="left"',
                                    title: '查看请求参数和返回结果',
                                    url: 'pay/order/notifyinfo/',
                                    classname: 'btn btn-warning btn-xs btn-notifyinfo',
                                    hidden: function (row) {
                                        return row.status !==3;
                                    }
                                },
                                {
                                    name: 'freezed',
                                    text: '冻结订单',
                                    classname: 'btn btn-primary btn-xs btn-freezed',
                                    extend: 'data-toggle="tooltip" data-placement="top"',
                                    title: '冻结订单',
                                    hidden: function (row) {
                                        return row.freezed == 1;
                                    }
                                },
                                {
                                    name: 'freezed',
                                    text: '解除冻结',
                                    url: 'pay/order/freezed/type/0',
                                    classname: 'btn btn-info btn-xs btn-ajax',
                                    extend: 'data-toggle="tooltip" data-placement="top"',
                                    title: '解除冻结',
                                    confirm: '确认解除冻结?',
                                    success: function (data) {
                                        table.bootstrapTable('refresh');
                                    },
                                    hidden: function (row) {
                                        return row.freezed != 1;
                                    }
                                },

                            ]
                        },
                        // {
                        //     field: 'operate',
                        //     title: __('Operate'),
                        //     table: table,
                        //     events: Table.api.events.operate,
                        //     formatter: Table.api.formatter.operate
                        // }
                    ]
                ]
            });


            $(document).on("click", ".btn-freezed", function () {
                var that = this;
                var index = parseInt($(this).data("row-index"));
                var row = Table.api.getrowbyindex(table, index);
                Layer.prompt({title: '请输入冻结金额倍数', formType: 'number'}, function (value, index) {

                    Fast.api.ajax({
                        url: 'pay/order/freezed/type/1/ids/'+row.id+'/amount/'+value,
                    }, function (data, ret) {
                        Layer.closeAll();
                        table.bootstrapTable('refresh');
                        return false;
                    });
                    return false;
                });

            });


            $(document).on("click", ".btn-notifyinfo", function () {
                var index = parseInt($(this).data("row-index"));
                var row = Table.api.getrowbyindex(table, index);
                Fast.api.ajax({
                    url: $(this).attr("href"),
                }, function (data, ret) {
                    Layer.open({
                        title: "回调信息",
                        area: ["800px", "600px"],
                        content: Template("notifytpl", data)
                    });
                    return false;
                });
                return false;
            });
            $(document).on("click", ".btn-preview", function () {
                var winname = window.open('', "_blank", '');
                winname.document.open('text/html', 'replace');
                winname.opener = null;
                winname.document.write($("#response").val());
                winname.document.close();
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        select: function () {

        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        },

        statistics: function () {
            // 基于准备好的dom，初始化echarts实例
            var myChart = Echarts.init(document.getElementById('echart'), 'walden');

            // 指定图表的配置项和数据
            var option = {
                title: {
                    text: '',
                    subtext: ''
                },
                tooltip: {
                    trigger: 'axis'
                },
                legend: {
                    data: ['订单数', '金额数']
                },
                toolbox: {
                    show: false,
                    feature: {
                        magicType: {show: true, type: ['stack', 'tiled']},
                        saveAsImage: {show: true}
                    }
                },
                xAxis: {
                    type: 'category',
                    boundaryGap: false,
                    data: Orderdata.column
                },
                yAxis: {},
                grid: [{
                    left: 'left',
                    top: 'top',
                    right: '10',
                    bottom: 30
                }],
                series: [{
                    name: '订单数',
                    type: 'line',
                    smooth: true,
                    areaStyle: {
                        normal: {}
                    },
                    lineStyle: {
                        normal: {
                            width: 1.5
                        }
                    },
                    data: Orderdata.orderData
                },
                    {
                        name: '金额数',
                        type: 'line',
                        smooth: true,
                        areaStyle: {
                            normal: {}
                        },
                        lineStyle: {
                            normal: {
                                width: 1.5
                            }
                        },
                        data: Orderdata.amountData
                    }]
            };

            // 使用刚指定的配置项和数据显示图表。
            myChart.setOption(option);


            $(window).resize(function () {
                myChart.resize();
            });

            $(document).on("click", ".btn-checkversion", function () {
                top.window.$("[data-toggle=checkupdate]").trigger("click");
            });


        }
    };
    return Controller;
});

