<div class="modal-dialog modal-lg">
    <div class="modal-content">
        <form method="POST" enctype='multipart/form-data' action="{{ route('send') }}" class="ajaxFormSubmit">
            <div class="modal-header">
                <h5 class="modal-title">Composer Mail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="body">
                    {{ csrf_field() }}
                    <div class="form-group">
                        <label for="">From</label>
                        <select class="form-control" name="from">
                            @foreach($emails as $user)
                            <option value="{{$user->id}}">{{$user->email}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="">To (To send bulk email use comma separated. e.g. xxx@xx.com,yyy@yy.com - max: 20)</label>
                        <input type="text" name="to" id="to" class="form-control" value="" >
                    </div>
                    <div class="form-group">
                        <label for="">Subject</label>
                        <input type="text" name="subject" id="subject" class="form-control" value="" >
                    </div>
                    <div class="form-group">
                        <label for="">Body</label>
                        <textarea class="form-control" name="body" id="body_message" placeholder="Reply" rows="10"></textarea>
                    </div>
        
                    <div class="form-group">
                        <label for="">Attachment</label>
                        <input type="file" name="attachment[]" multiple id="attachment" class="form-control" value="" >
                    </div>

                    <!-- <button type="submit" id="send_message" class="btn btn-primary">Submit</button> -->
            
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Send Message</button>
            </div>
        </form>
    </div>
</div> 
<script>
(function () {
    new FroalaEditor("#body_message", {
    })
})()
</script>