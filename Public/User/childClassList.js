 var tableIns;

  layui.use('table', function(){

    var table = layui.table;

    var form = layui.form;

 tableIns =  table.render({

      elem: '#childClassList'

      ,url: $("#childClassList").attr("dataurl")

      ,cols: [[

         {type:'numbers'}

         ,{field:'classname', title:'分类名称'}

         ,{field:'min_feilv', title:'最低可设费率'}

         ,{field:'max_feilv', title:'最高可设费率'}

         ,{field:'order_feilv', title:'交易费率',templet:'#feilv'}

      ]]

      ,page: false

      ,text: {

        none: '无数据'

      }

      ,method: 'post'

    });

  });