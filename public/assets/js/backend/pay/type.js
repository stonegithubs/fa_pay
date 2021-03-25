define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'pay/type/index',
                    add_url: 'pay/type/add',
                    edit_url: 'pay/type/edit',
                    table: 'pay_type',
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
                        {field: 'name', title: __('Name')},
                        {field: 'type', title: __('Type')},
                        {field: 'rate', title: '默认费率', operate:'BETWEEN'},
                        {field: 'access', title: '默认权限',
                            searchList: {
                                "0": '不可用',
                                "1": '可用',
                            },
                            formatter: Table.api.formatter.status,
                            custom: {
                                "0": 'warning',
                                "1": 'success',
                            }
                        },
                        {field: 'config', title:'配置信息',visible :false},
                        {
                            field: 'status',
                            title: __('Status'),
                            searchList: {
                                "0": '不可用',
                                "1": '可用',
                            },
                            formatter: Table.api.formatter.status,
                            custom: {
                                "0": 'warning',
                                "1": 'success',
                            }
                        },
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
        pay_rate:function ()
            {
                Table.api.init({
                    edit_url: 'pay/type/edit',
                });

                var table = $("#table");


                // 为表格绑定事件
                Table.api.bindevent(table);

                $("form.edit-form").data("validator-options", {
                    display: function (elem) {
                        return $(elem).closest('tr').find("td:first").text();
                    }
                });
                Form.api.bindevent($("form.edit-form"));

                //不可见的元素不验证
                $("form#add-form").data("validator-options", {ignore: ':hidden'});
                Form.api.bindevent($("form#add-form"), null, function (ret) {
                    location.reload();
                });


                //删除配置
                $(document).on("click", ".btn-delcfg", function () {
                var that = this;
                Layer.confirm(__('Are you sure you want to delete this item?'), {icon: 3, title:'提示'}, function (index) {
                    Backend.api.ajax({
                        url: "pay/type/pay_rate_del/ids/"+$(that).data("name"),
                        data: {ids: $(that).data("id")}
                    }, function () {
                        $(that).closest("tr").remove();
                        Layer.closeAll();
                    });
                });

            });
            },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});