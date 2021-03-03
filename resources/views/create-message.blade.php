<!doctype html>
<html>
  <head>
    <title>Gmail Thread APi demo</title>
    <meta charset="UTF-8">

    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.4.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="{{ url('js/froala_editor/css/froala_editor.css') }}">
  <link rel="stylesheet" href="{{ url('js/froala_editor/css/froala_style.css') }}">
  <link rel="stylesheet" href="{{ url('js/froala_editor/css/plugins/code_view.css') }}">
  <link rel="stylesheet" href="{{ url('js/froala_editor/css/plugins/colors.css') }}">
  <link rel="stylesheet" href="{{ url('js/froala_editor/css/plugins/emoticons.css') }}">
  <link rel="stylesheet" href="{{ url('js/froala_editor/css/plugins/line_breaker.css') }}">
  <link rel="stylesheet" href="{{ url('js/froala_editor/css/plugins/table.css') }}">
  <link rel="stylesheet" href="{{ url('js/froala_editor/css/plugins/char_counter.css') }}">
  <link rel="stylesheet" href="{{ url('js/froala_editor/css/plugins/fullscreen.css') }}">
  <link rel="stylesheet" href="{{ url('js/froala_editor/css/plugins/file.css') }}">
  <link rel="stylesheet" href="{{ url('js/froala_editor/css/plugins/quick_insert.cs') }}s">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.3.0/codemirror.min.css">
    <style>
      iframe {
        width: 100%;
        border: 0;
        min-height: 80%;
        height: 600px;
        display: flex;
      }
      .d-none { display: none; }
    </style>
  </head>
  <body>
    <div class="container">
      <h1>COMPOSER MAIL</h1>
      <div class="row">
        <div class="col-12">
          <a class="btn btn-primary pull-right" href="{{ route('threadsjs') }}">Back to INBOX</a>
        </div>
      </div>
      
        @if (session()->has('success'))
        <div class="alert alert-success">
                {{ session('success') }}
        </div>
        @endif
        <form method="POST" enctype='multipart/form-data' action="{{ route('senddMessage') }}">
          {{ csrf_field() }}
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
                    <textarea class="form-control" name="body" id="body_message" placeholder="Reply"></textarea>
                </div>
    
                <div class="form-group">
                    <label for="">Attachment</label>
                    <input type="file" name="attachment[]" multiple id="attachment" class="form-control" value="" >
                </div>

                <button type="submit" id="send_message" class="btn btn-primary">Submit</button>
            </form>

    </div>

    <script src="//code.jquery.com/jquery-1.11.3.min.js"></script>
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
    <script type="text/javascript"
    src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.3.0/codemirror.min.js"></script>
  <script type="text/javascript"
    src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.3.0/mode/xml/xml.min.js"></script>

  <script type="text/javascript" src="{{ url('js/froala_editor/js/froala_editor.min.js') }}"></script>
  <script type="text/javascript" src="{{ url('js/froala_editor/js/plugins/align.min.js') }}"></script>
  <script type="text/javascript" src="{{ url('js/froala_editor/js/plugins/char_counter.min.js') }}"></script>
  <script type="text/javascript" src="{{ url('js/froala_editor/js/plugins/code_beautifier.min.js') }}"></script>
  <script type="text/javascript" src="{{ url('js/froala_editor/js/plugins/code_view.min.js') }}"></script>
  <script type="text/javascript" src="{{ url('js/froala_editor/js/plugins/colors.min.js') }}"></script>
  <script type="text/javascript" src="{{ url('js/froala_editor/js/plugins/draggable.min.js') }}"></script>
  <script type="text/javascript" src="{{ url('js/froala_editor/js/plugins/emoticons.min.js') }}"></script>
  <script type="text/javascript" src="{{ url('js/froala_editor/js/plugins/entities.min.js') }}"></script>
  <script type="text/javascript" src="{{ url('js/froala_editor/js/plugins/file.min.js') }}"></script>
  <script type="text/javascript" src="{{ url('js/froala_editor/js/plugins/font_size.min.js') }}"></script>
  <script type="text/javascript" src="{{ url('js/froala_editor/js/plugins/font_family.min.js') }}"></script>
  <script type="text/javascript" src="{{ url('js/froala_editor/js/plugins/fullscreen.min.js') }}"></script>
  <script type="text/javascript" src="{{ url('js/froala_editor/js/plugins/line_breaker.min.js') }}"></script>
  <script type="text/javascript" src="{{ url('js/froala_editor/js/plugins/inline_style.min.js') }}"></script>
  <script type="text/javascript" src="{{ url('js/froala_editor/js/plugins/link.min.js') }}"></script>
  <script type="text/javascript" src="{{ url('js/froala_editor/js/plugins/lists.min.js') }}"></script>
  <script type="text/javascript" src="{{ url('js/froala_editor/js/plugins/paragraph_format.min.js') }}"></script>
  <script type="text/javascript" src="{{ url('js/froala_editor/js/plugins/paragraph_style.min.js') }}"></script>
  <script type="text/javascript" src="{{ url('js/froala_editor/js/plugins/quick_insert.min.js') }}"></script>
  <script type="text/javascript" src="{{ url('js/froala_editor/js/plugins/quote.min.js') }}"></script>
  <script type="text/javascript" src="{{ url('js/froala_editor/js/plugins/table.min.js') }}"></script>
  <script type="text/javascript" src="{{ url('js/froala_editor/js/plugins/save.min.js') }}"></script>
  <script type="text/javascript" src="{{ url('js/froala_editor/js/plugins/url.min.js') }}"></script>
    
  <script>
    (function () {
      new FroalaEditor("#body_message", {
      })
    })()
  </script>

  </body>
</html>