<div class="box box-info">
  <div class="box-header with-border">
    <h3 class="box-title">订单流水号：{{ $order->out_trade_no }}</h3>
    <div class="box-tools">
      <div class="btn-group pull-right" style="margin-right: 10px">
        <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-default"><i class="fa fa-list"></i> 列表</a>
      </div>
    </div>
  </div>
  <div class="box-body">
    <table class="table table-bordered">
      <tbody>
      <tr>
        <td>买家：</td>
        <td>{{ $order->users->username ? $order->users->username : $order->users->nickname }}</td>
        <td>创建时间：</td>
        <td>{{ $order->created_at->format('Y-m-d H:i:s') }}</td>
      </tr>
      <tr>
        <td>支付方式：</td>
        <td>{{ $order->type == 0 ? '微信支付' : 'M币兑换' }}</td>
        <td>支付渠道单号：</td>
        <td>{{ $order->pay_id }}</td>
      </tr>
      <tr>
        <td>收货地址</td>
        <td colspan="3">{{ $order->address }} {{ $order->consignee }}  {{ $order->phone }}</td>
      </tr>
      <tr>
        <td rowspan="{{ $order->items->count() + 1 }}">商品列表</td>
        <td>商品名称</td>
        <td>单价</td>
        <td>数量</td>
      </tr>
      @foreach($order->items as $item)
      <tr>
        <td>{{ $item->title }} {{ $item->norm }}</td>
        <td>￥{{ $item->pre_price }}</td>
        <td>{{ $item->total_price }}</td>
      </tr>
      @endforeach
      <tr>
        <td>订单金额：</td>
        <td>￥{{ $order->tradetotal }}</td>
        <td>支付金额：</td>
        <td>￥{{ $order->paiedtotal }}</td>
      </tr>
      @if($order->refund_status !== \App\Models\Order::REFUND_STATUS_PENDING)
      <tr>
        <td>退款状态：</td>
        <td colspan="2">{{ \App\Models\Order::$refundStatusMap[$order->refund_status] }}</td>
        <td>
          <!-- 如果订单退款状态是已申请，则展示处理按钮 -->
          <!-- @if($order->refund_status === \App\Models\Order::REFUND_STATUS_APPLIED) -->
          <button class="btn btn-sm btn-success" id="btn-refund-agree">同意</button>
          <button class="btn btn-sm btn-danger" id="btn-refund-disagree">不同意</button>
          <!-- @endif -->
      </tr>
	  @else
	  <tr>
        <td>订单状态：</td>
        <td>

		{{ \App\Models\Order::$orderStatusMap[$order->status] }}

			@if($order->status === \App\Models\Order::ORDER_STATUS_PAYED)
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<button class="btn btn-sm btn-success" id="btn-order-send">发货</button>
			@endif
		</td>
        <td>物流单号：</td>
        <td>{{ $order->freightbillno }}</td>
      </tr>
      @endif
	 @if($order->status === \App\Models\Order::ORDER_STATUS_PAYED)
      <tr>
        <td colspan="4">
          <form id="form" action="{{ route('admin.orders.send', [$order->id]) }}" method="post" class="form-inline">
            <!-- 别忘了 csrf token 字段 -->
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <div class="form-group {{ $errors->has('express_company') ? 'has-error' : '' }}">
              <label for="express_company" class="control-label">物流公司</label>
              <input type="text" id="express_company" name="express_company" value="" class="form-control" placeholder="输入物流公司">
              @if($errors->has('express_company'))
                @foreach($errors->get('express_company') as $msg)
                  <span class="help-block">{{ $msg }}</span>
                @endforeach
              @endif
            </div>
            <div class="form-group {{ $errors->has('express_company') ? 'has-error' : '' }}">
              <label for="express_no" class="control-label">物流单号</label>
              <input type="text" id="freightbillno" name="freightbillno" value="" class="form-control" placeholder="输入物流单号">
              @if($errors->has('freightbillno'))
                @foreach($errors->get('freightbillno') as $msg)
                  <span class="help-block">{{ $msg }}</span>
                @endforeach
              @endif
            </div>
            <button type="button" class="btn btn-success" id="btn-order-send">发货</button>
          </form>
        </td>
      </tr>
      @else
      <!-- 否则展示物流公司和物流单号 -->
      <tr>
        <td>物流公司：</td>
        <td>{{ $order->express_company }}</td>
        <td>物流单号：</td>
        <td>{{ $order->freightbillno }}</td>
      </tr>
      @endif
      </tbody>
    </table>

</div>
<script>
$(document).ready(function() {
  // 不同意 按钮的点击事件
  $('#btn-refund-disagree').click(function() {
  // 注意：Laravel-Admin 的 swal 是 v1 版本，参数和 v2 版本的不太一样
    swal({
      title: '输入拒绝退款理由',
      type: 'input',
      showCancelButton: true,
      closeOnConfirm: false,
      confirmButtonText: "确认",
      cancelButtonText: "取消",
    }, function(inputValue){
      // 用户点击了取消，inputValue 为 false
      // === 是为了区分用户点击取消还是没有输入
      if (inputValue === false) {
        return;
      }
      if (!inputValue) {
        swal('理由不能为空', '', 'error')
        return;
      }
      // Laravel-Admin 没有 axios，使用 jQuery 的 ajax 方法来请求
      $.ajax({
        url: '{{ route('admin.orders.handle_refund', [$order->id]) }}',
        type: 'POST',
        data: JSON.stringify({   // 将请求变成 JSON 字符串
          agree: false,  // 拒绝申请
          reason: inputValue,
          // 带上 CSRF Token
          // Laravel-Admin 页面里可以通过 LA.token 获得 CSRF Token
          _token: LA.token,
        }),
        contentType: 'application/json',  // 请求的数据格式为 JSON
        success: function (data) {  // 返回成功时会调用这个函数
          swal({
            title: '操作成功',
            type: 'success'
          }, function() {
            // 用户点击 swal 上的 按钮时刷新页面
            location.reload();
          });
        },
	  error: function (data) {
		  console.log(data.responseJSON);
		swal({
            title:data.responseJSON.msg,
            type:"warning",
            showCancelButton:false,
            closeOnConfirm:true,
            closeOnCancel:true
        });
	  }
      });
    });
  });
  // 同意 按钮的点击事件
  $('#btn-refund-agree').click(function() {
	  swal({
      title: '确认要将款项退还给用户？',
      type: 'warning',
      showCancelButton: true,
      closeOnConfirm: false,
      confirmButtonText: "确认",
      cancelButtonText: "取消",
    }, function(ret){
      // 用户点击取消，不做任何操作
      if (!ret) {
        return;
      }
	  $.ajax({
      url: '{{ route('admin.orders.handle_refund', [$order->id]) }}',
      type: 'POST',
      data: JSON.stringify({   // 将请求变成 JSON 字符串
        agree: true,  // 同意申请
        _token: LA.token,
      }),
      contentType: 'application/json',  // 请求的数据格式为 JSON
      success: function (data) {  // 返回成功时会调用这个函数

        swal({
          title: '操作成功',
          type: 'success'
        }, function() {
          // 用户点击 swal 上的 按钮时刷新页面
          location.reload();
        });
      },
	  error: function (data) {
		  console.log(data.responseJSON);
		swal({
            title:data.responseJSON.msg,
            type:"warning",
            showCancelButton:false,
            closeOnConfirm:true,
            closeOnCancel:true
        });
	  }
    });
     });
  });

  // 发货 按钮的点击事件
$('#btn-order-send').click(function() {
  var is_submit = '{{ $order->status !== \App\Models\Order::ORDER_STATUS_PAYED || $order->refund_status !== \App\Models\Order::REFUND_STATUS_PENDING}}';
  if(is_submit) {
      swal({
          title:'订单状态不正确或退款正在处理中',
          type:"warning",
          showCancelButton:false,
          closeOnConfirm:true,
          closeOnCancel:true
      });
      return;
  } else {
      var freightbillno = $('#freightbillno').val();
      var express_company = $('#express_company').val();
      if(!express_company || !freightbillno) {
        swal({
            title:'物流公司或物流单号未填写',
            type:"warning",
            showCancelButton:false,
            closeOnConfirm:true,
            closeOnCancel:true
        });
        return;
      } else {
        $('#form').submit();
      }

  }


  swal({
    title: '输入物流单号',
    type: 'input',
    showCancelButton: true,
    closeOnConfirm: false,
    confirmButtonText: "确认",
    cancelButtonText: "取消",
  }, function(inputValue){
    // 用户点击了取消，inputValue 为 false
    // === 是为了区分用户点击取消还是没有输入
    if (inputValue === false) {
      return;
    }
    if (!inputValue) {
      swal('理由不能为空', '', 'error')
      return;
    }
    // Laravel-Admin 没有 axios，使用 jQuery 的 ajax 方法来请求
    $.ajax({
      url: '{{ route('admin.orders.send', [$order->id]) }}',
      type: 'POST',
      data: JSON.stringify({   // 将请求变成 JSON 字符串
        ship_no: inputValue,
        // 带上 CSRF Token
        // Laravel-Admin 页面里可以通过 LA.token 获得 CSRF Token
        _token: LA.token,
      }),
      contentType: 'application/json',  // 请求的数据格式为 JSON
      success: function (data) {  // 返回成功时会调用这个函数
        swal({
          title: '操作成功',
          type: 'success'
        }, function() {
          // 用户点击 swal 上的 按钮时刷新页面
          location.reload();
        });
      },
	  error: function (data) {
		  console.log(data.responseJSON);
		swal({
            title:data.responseJSON.msg,
            type:"warning",
            showCancelButton:false,
            closeOnConfirm:true,
            closeOnCancel:true
        });
	  }
    });
  });
});
});
</script>
