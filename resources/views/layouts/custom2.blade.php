<!DOCTYPE html>
<html>
<head>
	<title>SP</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">

	

	<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.1/dist/umd/popper.min.js" integrity="sha384-SR1sx49pcuLnqZUnnPwx6FCym0wLsk5JZuNx2bPPENzswTNFaQU1RDvt3wT4gWFG" crossorigin="anonymous"></script>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.min.js" integrity="sha384-j0CNLUeiqtyaRmlzUHCPZ+Gy5fQu0dQ6eZ/xAww941Ai1SxSY+0EQqNXNE6DZiVc" crossorigin="anonymous"></script>

	<script src="//code.jquery.com/jquery-1.11.3.min.js"></script>
	<script src="http://malsup.github.io/jquery.form.js"></script>

	<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
</head>
<body>
	<style type="text/css">
		.active { background: #0d6efd; color: #fff; padding: 2px 10px;  }
	</style>

	<header class="d-flex flex-column flex-md-row align-items-center p-3 px-md-4 mb-3 bg-body border-bottom shadow-sm">	
		<a class="h5 my-0 me-md-auto fw-normal" href="{{url('/sps')}}">SPs</a>
		<nav class="my-2 my-md-0 me-md-3">
			<a class="p-2 text-dark" href="{{url('sps')}}">All SPs</a>
			<a class="p-2 text-dark ajax" href="{{url('sps/create')}}">Create SP</a>
		</nav>
	</header>
	
    @yield('content')

	<div class="modal fade" id="commonmodal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
	</div>	
	<script>
		$(document).on("click",".ajax",function(event){
			event.preventDefault();
			$.get($(this).attr('href'),function(){

			}).done(function(res){
				$("#commonmodal").html(res.html);
				$("#commonmodal").modal("show");


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