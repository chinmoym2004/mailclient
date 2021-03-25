@extends('layouts.custom')

@section('content')
<div class="container">
    <div class="">
        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link active" href="{{url('custom-mail')}}">BACK</a>
            </li>
        </ul>
    </div>
    <div class="my-3 p-3 bg-body rounded shadow-sm">
        <h4 class="border-bottom pb-2 mb-0">{{$thread->subject}}</h4>
        @php $lastmessage = null; @endphp
        @foreach($thread->messages as $message)
        @php $lastmessage = $message; @endphp
        <div class="card mb-1">
            <div class="card-header">
                @if($message->attachments()->count())
                <span style="float: right;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-paperclip" viewBox="0 0 16 16">
                    <path d="M4.5 3a2.5 2.5 0 0 1 5 0v9a1.5 1.5 0 0 1-3 0V5a.5.5 0 0 1 1 0v7a.5.5 0 0 0 1 0V3a1.5 1.5 0 1 0-3 0v9a2.5 2.5 0 0 0 5 0V5a.5.5 0 0 1 1 0v7a3.5 3.5 0 1 1-7 0V3z"/>
                    </svg>
                </span>
                @endif
                <b>FROM</b>: {{$message->from}}<br/>
                <b>To</b>: {{$message->to}}<br/>
                <b>Time</b> : {{$message->record_time}}
            </div>
            <div class="card-body">
                <iframe onload="loaded('message-iframe-{{$message->id}}')" id="message-iframe-{{$message->id}}" srcdoc="{{$message->body}}"></iframe>
                @if($message->attachments()->count())
                <h6>Attachments <span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-paperclip" viewBox="0 0 16 16">
                    <path d="M4.5 3a2.5 2.5 0 0 1 5 0v9a1.5 1.5 0 0 1-3 0V5a.5.5 0 0 1 1 0v7a.5.5 0 0 0 1 0V3a1.5 1.5 0 1 0-3 0v9a2.5 2.5 0 0 0 5 0V5a.5.5 0 0 1 1 0v7a3.5 3.5 0 1 1-7 0V3z"/>
                    </svg>
                </span></h6>
                <div class="row">
                @foreach($message->attachments as $attachment)
                <div class="col-3">
                    <div class="row g-0 border rounded overflow-hidden flex-md-row mb-4 shadow-sm h-md-250 position-relative">
                        <div class="col-auto d-none d-lg-block">
                        <i class="bi bi-file-earmark"></i>
                        </div>
                        <div class="col p-4 d-flex flex-column position-static" title="{{$attachment->filename}}">
                            <p class="card-text mb-auto">{{substr($attachment->filename,0,50)}}</p>
                            <a href="{{Storage::url($attachment->file_path)}}" target="_blank" class="stretched-link">Download</a>
                        </div>
                    </div>
                </div>
                @endforeach
                </div>
                @endif
            </div>
            <div class="card-footer">
                
                <!-- <a href="#" class="card-link">Reply</a>
                <a href="#" class="card-link">Reply All</a> -->
            </div>
        </div>
        @endforeach 
        <div class="card-footer">
            <a href="#" class="card-link">Reply</a>
            <div class="card">
                <form method="POST" enctype='multipart/form-data' action="{{ route('reply') }}" class="ajaxFormSubmit">
                    <input type="hidden" name="message_id" value="{{$lastmessage->id}}"/>
                    {{ csrf_field() }}
                   
                    <div class="form-group">
                        <label for="">Reply To</label>
                        <input type="text" name="to" id="to" class="form-control" value="{{$lastmessage->from}}" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label for="">Body</label>
                        <textarea class="form-control" name="body" id="body_message" placeholder="Reply" rows="10"></textarea>
                    </div>
        
                    <div class="form-group">
                        <label for="">Attachment</label>
                        <input type="file" name="attachment[]" multiple id="attachment" class="form-control" value="" >
                    </div>

                    <button type="submit" class="btn btn-primary">Reply</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<style>
    iframe{
        width:100%
    }
</style>
<script type="text/javascript">
    function loaded(id){
        console.log("ok");
        var iFrameID = $("#"+id);
        //iFrameID.css("height",iFrameID.contentWindow.document.body.scrollHeight + "px"); 
        iFrameID.css("height",iFrameID.contents().find('html').height()+ 'px');
    }
</script>
@endsection