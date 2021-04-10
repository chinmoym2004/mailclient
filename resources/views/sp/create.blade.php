<div class="modal-dialog modal-lg">
    <div class="modal-content">
        <form method="POST" enctype='multipart/form-data' action="{{ url('sps') }}" class="ajaxFormSubmit">
            <div class="modal-header">
                <h5 class="modal-title">Create SP</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="body">
                    {{ csrf_field() }}
                    <div class="form-group">
                        <label for="">SP Name</label>
                        <input type="text" name="sp_name" required placeholder="SP name" class="form-control">
                    </div>
                    
                    
                    <div class="form-group">
                        <label for="">MySQL Query</label>
                        <textarea class="form-control" name="raw_query" id="raw_query" placeholder="Query details" rows="5"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="">Input Fields and Datatype</label>
                        <textarea class="form-control" name="in_fields" id="in_fields" placeholder="return fields JSON format" rows="4"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="">Output Fields and Datatype</label>
                        <textarea class="form-control" name="out_fields" id="out_fields" placeholder="return fields JSON format" rows="4"></textarea>
                    </div>
                    
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Create SP</button>
            </div>
        </form>
    </div>
</div>