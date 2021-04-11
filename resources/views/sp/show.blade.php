@extends('layouts.custom2')

@section('content')
<div class="container">
    <h3>Report - #{{$sp->sp_name}}</h3>
    
    <div class="row">
        <table class="table">
            <thead>
                <tr>
                    @foreach($outfields as $out)
                    <th>{{$out}}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                
                @if($datas)
                    @foreach($datas as $data)
                        <tr>
                        @foreach($outfields as $key=>$dt)
                            <td>{{$data->$key}}</td>
                        @endforeach
                        </tr>
                    @endforeach
                @else 

                @endif
           
            </tbody>
        </table>
    </div>
</div>
@endsection