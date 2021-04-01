@extends('layouts.custom')

@section('content')
<div class="container">
    <h3>All Sent Mails</h3>
    <div class="row">
        <table class="table">
            <thead>
                <tr>
                    <th>From</th>
                    <th>Subject</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
            @if($threads)
                @foreach($threads as $thread)
                <tr>
                    <td><a href="{{url('custom-mail/'.$thread->thread_id)}}">{{$thread->messages()->first()->from}} @if($thread->messages()->count()>1) ({{$thread->messages()->count()}}) @endif</a></td>
                    <td><a href="{{url('custom-mail/'.$thread->thread_id)}}">{{$thread->subject}}</a></td>
                    <td>{{$thread->record_time}}</td>
                </tr>
                @endforeach
            @else 
            <tr>
                <td colspan="4">No mail , we'll check after 1 min again</td>
            </tr>
            @endif
            </tbody>
        </table>
    </div>
    {{ $threads->links() }}
</div>
@endsection