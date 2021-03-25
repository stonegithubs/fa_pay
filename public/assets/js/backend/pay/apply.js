define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'pay/apply/index',
                    add_url: 'pay/apply/add',
                    edit_url: 'pay/apply/edit',
                    del_url: '',
                    multi_url: 'pay/apply/multi',
                    table: 'pay_cashed',
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

                },
                columns: [
                    [
                        {checkbox: true},

                        {
                            field: 'createtime',
                            title: '申请时间',
                            operate: false,
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime,
                            sortable: true,
                        },
                        {field: 'appid', title: '商户号',formatter: Table.api.formatter.search},
                        {
                            field: 'amount',
                            title: '提现金额',
                            sortable: true
                        },

                        {
                            field: 'real_amount',
                            title: '实际到账金额',
                            sortable: true
                        },
                        {
                            field: 'bank_number',
                            title: '卡号',
                        },
                        {
                            field: 'real_name',
                            title: '开户名',
                        },
                        {
                            field: 'bank_name',
                            title: '银行名称',
                        },
                        {
                            field: 'bank_name2',
                            title: '支行名称',
                        },

                        {
                            field: 'createtime',
                            title: '申请时间',
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime,
                            sortable: true,
                        },

                        {
                            field: 'updatetime',
                            title: '审核时间',
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime,
                            sortable: true,
                        },
                        {
                            field: 'status',
                            title: __('Status'),
                            searchList: {
                                "0": '未审核',
                                "1": '已结算',
                                "-1": '驳回',
                            },
                            formatter: Table.api.formatter.status,
                            sortable: true,
                            custom: {
                                "0": 'inprogress',
                                "-1": 'warning',
                                "1": 'success'
                            }
                        },
                        {
                            field: 'marks',
                            title: '备注'
                        },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
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
        }
    };
    return Controller;
});

