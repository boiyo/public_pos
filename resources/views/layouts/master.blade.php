<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name"csrf-token" content"{{csrf_token()}}">
		<title>模組清單 - 
			@if(isset($title))
				{{$title}}
			@else
				首頁
			@endif
		</title>
	</head>
@if(isset($is_show))
	@if($is_show == '1')
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.js"></script>
	<script>window.jQuery || document.write('<script src="../../../../../js/jquery.js"><\/script>')</script>

	<link rel="stylesheet" href="../../../../../css/bootstrap.min.css">
	<link rel="stylesheet" href="../../../../../css/ie.css">
	<script src="../../../../../js/bootstrap.min.js"></script>
	<script src="../../../../../js/sha1.js"></script>
	<script src="../../../../../js/md5.js"></script>

	<script src="https://cdn.staticfile.org/jquery/2.1.1/jquery.min.js"></script>

<link href="https://code.jquery.com/ui/1.10.2/themes/ui-lightness/jquery-ui.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-1.10.2.js"></script>
<script src="https://code.jquery.com/ui/1.10.2/jquery-ui.js"></script>

	<link href="https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/css/select2.min.css" rel="stylesheet" />
	<script src="https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/js/select2.min.js"></script>
	@endif
@else
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.js"></script>
        <script>window.jQuery || document.write('<script src="../../../../../js/jquery.js"><\/script>')</script>

        <link rel="stylesheet" href="../../../../../css/bootstrap.min.css">
        <link rel="stylesheet" href="../../../../../css/ie.css">
        <script src="../../../../../js/bootstrap.min.js"></script>
        <script src="../../../../../js/sha1.js"></script>
	<script src="../../../../../js/md5.js"></script>

        <script src="https://cdn.staticfile.org/jquery/2.1.1/jquery.min.js"></script>

<link href="https://code.jquery.com/ui/1.10.2/themes/ui-lightness/jquery-ui.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-1.10.2.js"></script>
<script src="https://code.jquery.com/ui/1.10.2/jquery-ui.js"></script>

        <link href="https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/js/select2.min.js"></script>
@endif
	<script type="text/javascript">
		function do_open(url,action,item_name){
			var method = 'POST';
			var ok_url = ''
			var action_name = '';
			console.log(url);
			if(action=='add'){
				ok_url = url.replace('/create', '');
				action_name = '新增';
			}else if(action=='edit'){
				method = 'PUT';
				ok_url = url.replace('/edit', '');
				action_name = '修改';
			}else if(action=='details'){
				action_name = '明細';
			}else if(action=='search'){
				action_name = '查詢';
			}
			$.ajax({
				type: 'GET',
				url: '../'+url,
				data: {is_show:'0'},
				success: function(show_data){
					var str = ''+show_data+'';
					$("<div id=show_"+action+"></div>").appendTo('body').html(str).dialog({
						modal:true,
						zIndex: 10000,
						autoOpen: true,
						width: 1000,
						height: 600,
						resizable: false,
						maximized:true,
						title: item_name,
						buttons: {
							確定: function() {
								console.log($("[name='form1']").serialize());
								if(action != 'details'){
									//會員作業的地址用的解鎖
									$(".zip").prop('disabled',false);
									var str = $("[name='form1']").serialize();
									$.ajax({
										type: method,
										url: '../'+ok_url,
										data: str,
										success: function(show_data){
											//console.log(show_data);
											$('#show_'+action).html(show_data);
										},
										error: function(xhr, status, error){
											console.log(xhr);
										}
									});
								}
							},
							取消: function() {
								$(this).remove();
								location.reload();
							}
						},
						close: function(event,ui) {
							$(this).remove();
							location.reload();
						}
					});
				},
				error: function(xhr, status, error){
					console.log(xhr);
				}
			});
		}
		function worklist(method,model_name,model_id){
			$.ajax({
			    type: method,
			    url: model_name,
		//	    data: { id : model_id},
			    success: function(data){
					form1.submit();
			    },
			    error: function(xhr, status, error){
			        console.log(xhr);
			    }
			});
		}
		function actionchange(method,connect_url,types){
			var show_name = '';
			if(types=='del'){
				show_name = '刪除';
			}else if(types=='edit'){
				show_name = '修改';
			}else if(types=='audit'){
				show_name = '審核';
			}else if(types=='close'){
				show_name = '結案';
			}else if(types=='checkin'){
				show_name = '入庫';
			}else if(types=='cancel_checkin'){
				show_name = '取消入庫';
			}else if(types=='material'){
				show_name = '耗用';
			}else if(types=='stock_check'){
				show_name = '盤點';
			}else if(types=='stock_adjust'){
				show_name = '調整';
			}else if(types=='cancel_stock_adjust'){
				show_name = '取消調整';
			}else if(types=='transfer'){
				show_name = '調撥';
			}else if(types=='transfer_out'){
				show_name = '調撥出庫';
			}else if(types=='transfer_out_cancel'){
				show_name = '取消調撥出庫'
			}else if(types=='transfer_in'){
				show_name = '調撥入庫';
			}
			if(!confirm('是否要'+show_name+'?')) return;
			$.ajax({
				type: method,
				url: connect_url,
				data: {"_token":'{{csrf_token()}}'},
				success: function(data){
					if(data=='ok'){
						alert(show_name+'成功');
						form1.submit();
					}else{
						alert(data);
						if(types=='edit') form1.submit();
					}
				},
				error: function(xhr, status, error){
					console.log(xhr);
				}
			});
		}
		function clear_data(){
			$(".s_clear").val(null).trigger('change');
			$(".s_clear").attr('disabled', false);
			$("input[name='s_loc']").prop("checked", false);
			$(".s").prop("checked", false);
			$(".p").prop("checked", false);
			$(".r").prop("checked", false);
		}
	</script>
	<style type="text/css" rel="stylesheet">
		li {
			line-height:25px; /*li的行高，可以控制li的間距*/
			font-size:14px;  /*li内文字的大小*/
			padding-left:10px;  /*li内文字距離左邊的間距*/
			list-style: none; /*去掉li自带的圓點*/
		}
	</style>
	<body>
		<div class="flex-center position-ref full-height">
			<div class="links">
			@if(isset($is_show))
				@if($is_show == '1')
				<nav class="navbar navbar-default navbar-fixed-top">
					@if(isset($username))
						<p align="left">{{ $username }} 您好，<a href="../../../../../logout">登出</a></p>
					@endif
					@if(isset($modeldata))
					<center>
					@foreach($modeldata as $model_list)
						@if(isset(session('security_data')[$model_list['item_no']]['add_flag']))
							@if(session('security_data')[$model_list['item_no']]['add_flag'] == 1)
								@if($model_list->file_name=='pos')
									<a href="../../../../../Pos">{{$model_list->item_name}}</a>
								@else
									<a href="../../../../../{{$model_list->file_name}}">{{$model_list->item_name}}</a>
								@endif
							@endif
						@endif
					@endforeach
					</center>
					@endif
				</nav>
				<br><br><br>
				@endif
			@else
			<nav class="navbar navbar-default navbar-fixed-top">
				@if(isset($username))
					<p align="left">{{ $username }} 您好，<a href="../../../../../logout">登出</a></p>
				@endif
				@if(isset($modeldata))
				<center>
				@foreach($modeldata as $model_list)
					@if(isset(session('security_data')[$model_list['item_no']]['add_flag']))
						@if(session('security_data')[$model_list['item_no']]['add_flag'] == 1)
							@if($model_list->file_name=='pos')
								<a href="../../../../../Pos">{{$model_list->item_name}}</a>
							@else
								<a href="../../../../../{{$model_list->file_name}}">{{$model_list->item_name}}</a>
							@endif
						@endif
					@endif
				@endforeach
				</center>
				@endif
			</nav>
			<br><br><br>
			@endif
			</div>
			<div class="container">
				<center>{{$title}}</center>
				@yield('show')
				@php
                                try{
					if(!is_array($row_data)) echo $row_data->render();
                                }catch(\Exception $e){
                                }
                                @endphp
				@yield('content')
				@php
				try{
					if(!is_array($row_data)) echo $row_data->render();
				}catch(\Exception $e){
				}
				@endphp
			</div>
		</div>
	</body>
</html>
