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
                    <th>Query</th>
                    <th>In Fields</th>
                    <th>Out Fields</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            @if($sps)
                @foreach($sps as $sp)
                <tr>
                    <td><a href="{{url('sps/'.$sp->id)}}" class="commonmodal">{{$sp->sp_name}}</a></td>
                    <td>{{$sp->raw_query}}</td>
                    <td>{{$sp->in_fields}}</td>
                    <td>{{$sp->out_fields}}</td>
                    <td>{{$sp->migrated?'Deployed':'Not Deployed'}}</td>
                    <td>
                        <div class="btn-group">
                            <a href="{{url('sps/'.encrypt($sp->id).'/edit')}}" class="ajax btn btn-primary ml-1" style="margin-right: 2px">Edit</a>
                            <a target="_blank" href="{{url('sps/'.encrypt($sp->id))}}" class="btn btn-success">Report</a>
                        </div>
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