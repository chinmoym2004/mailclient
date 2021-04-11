<div class="modal-dialog modal-lg">
    <div class="modal-content">
        <form method="POST" enctype='multipart/form-data' action="{{ url('sps/'.encrypt($sp->id)) }}" class="ajaxFormSubmit">
            @method('put')
            <div class="modal-header">
                <h5 class="modal-title">Update SP</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="body">
                    {{ csrf_field() }}
                    <div class="form-group">
                        <label for="">SP Name</label>
                        <input type="text" name="sp_name" required placeholder="SP name" class="form-control" value="{{$sp->sp_name}}">
                    </div>
                    
                    
                    <div class="form-group">
                        <label for="">MySQL Query</label>
                        <textarea class="form-control" name="raw_query" id="raw_query" placeholder="Query details" rows="5">{{$sp->raw_query}}</textarea>
                    </div>

                    <div class="form-group">
                        <label for="">Input Fields and Datatype</label>
                        <textarea class="form-control" name="in_fields" id="in_fields" placeholder="return fields JSON format" rows="4">{{$sp->in_fields}}</textarea>
                    </div>

                    <div class="form-group">
                        <label for="">Output Fields and Datatype</label>
                        <textarea class="form-control" name="out_fields" id="out_fields" placeholder="return fields JSON format" rows="4">{{$sp->out_fields}}</textarea>
                    </div>
                    
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Update SP</button>
            </div>
        </form>
    </div>
</div>