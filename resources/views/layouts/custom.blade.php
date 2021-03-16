<!DOCTYPE html>
<html>
<head>
	<title>Inbox</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">
	<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/js/bootstrap.bundle.min.js" integrity="sha384-b5kHyXgcpbZJO/tY9Ul7kGkf1S0CWuKcCD38l8YkeH8z8QjE0GmW1gYU5S9FOnJ0" crossorigin="anonymous"></script>
</head>
<body>
	<style type="text/css">
		.active { background: #0d6efd; color: #fff; padding: 2px 10px;  }
	</style>

	<header class="d-flex flex-column flex-md-row align-items-center p-3 px-md-4 mb-3 bg-body border-bottom shadow-sm">	
		<p class="h5 my-0 me-md-auto fw-normal">Mail Centre</p>
		<nav class="my-2 my-md-0 me-md-3">
			<a class="p-2 text-dark" href="{{url('custom-mail')}}">All Mail</a>
			<a class="p-2 text-dark" href="{{url('/')}}">Add New Email</a>
		</nav>
	</header>
	
    @yield('content')

	@yield('scripts')
</body>
</html>