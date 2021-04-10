@extends('layouts.custom2')

@section('content')
<div class="container">
    <h3>All SPS</h3>
    <div class="alert alert-success text-center">
        For sample data visit <a href="https://github.com/chinmoym2004/test_db" target="_blank">https://github.com/chinmoym2004/test_db</a> and import in your DB.  
    </div>
    <div class="row">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>SP</th>
                    <th>Fields</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            @if($sps)
                @foreach($sps as $sp)
                <tr>
                    <td><a href="{{url('sps/'.$sp->id)}}" class="commonmodal">{{$sp->sp_name}}</a></td>
                    <td>{{$sp->sp_details}}</td>
                    <td>{{$sp->return_fields}}</td>
                    <td>{{$sp->migrated?'Deployed':'Not Deployed'}}</td>
                    <td>
                        <a href="#">Delete</a>
                        <a href="#">Deploy</a>
                        <a href="#">Edit</a>
                    </td>
                </tr>
                @endforeach
            @else 
            <tr>
                <td colspan="4">No SPs</td>
            </tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
@endsection