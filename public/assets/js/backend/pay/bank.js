define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'pay/bank/index',
                    add_url: 'pay/bank/add',
                    edit_url: 'pay/bank/edit',
                    del_url: 'pay/bank/del',
                    multi_url: 'pay/bank/multi',
                    table: 'pay_bank',
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
                        {checkbox: true},
                        {field: 'bank_name', title: '银行名称'},
                        {field: 'bank_name2', title: '支行名称'},
                        {field: 'real_name', title: '开户名'},
                        {field: 'bank_number', title: '银行卡号'},
                        {field: 'province', title: '所在省份'},
                        {field: 'city', title: '所在城市'},
                        {field: 'alias', title: '别名'},
                        {field: 'default', title: '默认支付'},
                        {field: 'marks', title: '备注'},
                        {field: 'create_date', title: '添加时间'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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