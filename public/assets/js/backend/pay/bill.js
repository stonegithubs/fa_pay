define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'pay/bill/index',
                    add_url: 'pay/bill/add',
                    edit_url: 'pay/bill/edit',
                    del_url: 'pay/bill/del',
                    multi_url: 'pay/bill/multi',
                    table: 'pay_bill',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [

                        {field: 'appid', title: '商户ID'},
                        {field: 'nickname', title: '商户名称'},
                        {field: 'type', title: '类型',formatter: Table.api.formatter.status,
                            searchList: {
                                "0": '系统修正',
                                "1": '订单到账',
                                "2": '订单手续费',
                                "3": '提现到账',
                                "4": '提现手续费',
                            },custom: {
                                "0": 'danger',
                                "1": 'inprogress',
                                "2": 'warning',
                                "3": 'success',
                                "4": 'warning',
                            }},
                        {field: 'money', title: '变更金额'},
                        {field: 'after', title: '变更后金额'},

                        {field: 'create_time', title: '添加时间',operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime,},
                        {field: 'marks', title: '备注',align:'left'},
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
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
        }
    };
    return Controller;
});