<!DOCTYPE html>
<html>
<head>
	<title>Inbox</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">

	

	<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.1/dist/umd/popper.min.js" integrity="sha384-SR1sx49pcuLnqZUnnPwx6FCym0wLsk5JZuNx2bPPENzswTNFaQU1RDvt3wT4gWFG" crossorigin="anonymous"></script>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.min.js" integrity="sha384-j0CNLUeiqtyaRmlzUHCPZ+Gy5fQu0dQ6eZ/xAww941Ai1SxSY+0EQqNXNE6DZiVc" crossorigin="anonymous"></script>

	<script src="//code.jquery.com/jquery-1.11.3.min.js"></script>
	<script src="http://malsup.github.io/jquery.form.js"></script>

	<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>


    <!-- include summernote css/js -->
	<!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.4.0/css/font-awesome.min.css">
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
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.3.0/codemirror.min.css"> -->	
</head>
<body>
	<style type="text/css">
		.active { background: #0d6efd; color: #fff; padding: 2px 10px;  }
	</style>

	<header class="d-flex flex-column flex-md-row align-items-center p-3 px-md-4 mb-3 bg-body border-bottom shadow-sm">	
		<a class="h5 my-0 me-md-auto fw-normal" href="{{url('/')}}">Mail Centre</a>
		<nav class="my-2 my-md-0 me-md-3">
			<a class="p-2 text-dark" href="{{url('custom-mail')}}">INBOX</a>
			<a class="p-2 text-dark" href="{{url('sent-mail')}}">SENT</a>
			<a class="p-2 text-dark ajax" href="{{url('compose-mail')}}">Compose Mail</a>
			<a class="p-2 text-dark" href="{{url('/')}}">Add Tracking</a>
		</nav>
	</header>
	
    @yield('content')

	<div class="modal fade" id="commonmodal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
	</div>

	
    <!-- <script type="text/javascript"
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
	<script type="text/javascript" src="{{ url('js/froala_editor/js/plugins/url.min.js') }}"></script>	 -->

	

	<script type="text/javascript">
		function sendFile(files,el)
      { 

          data = new FormData();
          data.append("editorfile",files);
          $.ajax({
              url : "{{url('/upload-files')}}",
              data:data,
              cache:false,
              contentType:false,
              processData:false,
              type : 'POST',
              success : function(res){
                var img = $('<img>').attr({ src: res });
                el.summernote('insertNode',img[0]);
                
                //return res.content.url;
              },
              error : function(){
                alert("Image Upload Failed");
                return false;
              }
          });

      }

      function callSummernote(){
      		$(".summernote").summernote({
              imageTitle: {
                specificAltField: true,
              },
              popover: {
                  image: [
                      ['imagesize', ['resizeFull', 'resizeHalf', 'resizeQuarter', 'resizeNone']],
                      ['float', ['floatLeft', 'floatRight', 'floatNone']],
                      ['remove', ['removeMedia']],
                      ['custom', ['imageTitle']],
                  ],
              },
              fontNames: ['montserrat-b','montserrat-r','montserrat-sb','Coco','lato-b','lato-r','Arial', 'Arial Black', 'Comic Sans MS', 'Courier New', 'Helvetica', 'Impact', 'Tahoma', 'Times New Roman', 'Verdana', 'Roboto'],
              fontNamesIgnoreCheck: ['montserrat-b','montserrat-r','montserrat-sb','Coco','lato-b','lato-r'],
              
              fontSizeUnits: ['px', 'pt','em','rem'],
              height:400,
              callbacks: {
                onImageUpload: function(files,we) {
                  if (!files.length) return;            
                  url = sendFile(files[0],$(this));
                  console.log(url);
                  
                }
              },
          });
          // summernote.image.link.insert
          // $('#summernote').on('summernote.image.link.insert', function(we, url) {
          //   // url is the image url from the dialog
          //   $img = $('<img>').attr({ src: url })
          //   $summernote.summernote('insertNode', $img[0]);
          // });
      }

      $(document).ready(function () {
          callSummernote();
      });
	</script>
	<script>
		$(document).on("click",".ajax",function(event){
			event.preventDefault();
			$.get($(this).attr('href'),function(){

			}).done(function(res){
				$("#commonmodal").html(res.html);
				$("#commonmodal").modal("show");

				callSummernote();

			}).fail(function(){

			});
		});

		$(document).on("submit",".ajaxFormSubmit",function(event){
			event.preventDefault();
			var form = $(this);
			$(this).ajaxSubmit({
				success: function(res) {
					form.trigger("reset");
					$("#commonmodal").modal("hide");

					if (res.reload != undefined)
						window.location.reload();

					if (res.redirect_to != undefined)
						window.location.href = res.redirect_to;

				},
				error: function(res) {
					alert("ERROR : check consle"+ res.responseJSON.errors);
				}
			});
		});
	</script>
	@yield('scripts')

	
	<script>
	
	
	</script>
</body>
</html>