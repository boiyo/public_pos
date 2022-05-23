@extends('layouts.master')

@section('content')
	@if(isset($workdata))
		@foreach($workdata as $k =>$v)
			<div width=30% style="width:30%;float:left;">
				<ul>
				@foreach($v as $k2 =>$v2)
						@if(isset($id) && isset(session('security_data')[$v2['item_no']]['add_flag']))
							@if($v2['main_name'] == $id && session('security_data')[$v2['item_no']]['add_flag'] == 1)
								<li><a href="../{{$v2['file_name']}}">{{$v2['item_name']}}</a></li>
							@endif
						@endif
				@endforeach
				</ul>
			</div>
		@endforeach
	@endif
@endsection
