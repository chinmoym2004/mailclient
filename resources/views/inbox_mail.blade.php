<!DOCTYPE html>
<html>
<head>
	<title>Inbox</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/js/bootstrap.bundle.min.js" integrity="sha384-b5kHyXgcpbZJO/tY9Ul7kGkf1S0CWuKcCD38l8YkeH8z8QjE0GmW1gYU5S9FOnJ0" crossorigin="anonymous"></script>
</head>
<body>
	<style type="text/css">
		.active { background: #0d6efd; color: #fff; padding: 2px 10px;  }
	</style>
	<h4 style="text-align: center">Gmail Inbox Test</h4>
	<table class="table">
		<tr>
			<td class="col-sm-3">
				<h5>
					Labels
					@if(!empty(app('request')->input('label')))
						<a href="{{ route('inbox') }}" style="color: red">- Reset</a>
					@endif
				</h5>
				<table>
					@foreach ($labels->getLabels() as $label)
					@php
						$labelId = $label->getId();
					@endphp
					<tr>
						<td>
							<a href="{{ route('inbox', [ 'label' => $labelId]) }}" class="{{ app('request')->input('label') == $labelId ? 'active' : '' }}">{{$label->getName()}}</a>
						</td>
					</tr>
					@endforeach
				</table>
			</td>
			<td class="col-sm-12">
				<div>
					<ul>
					@foreach($mails->getMessages() as $message)
					<li>
						Message ID :{{$message->getId()}}, Thread ID : {{$message->getThreadId()}}
						<p>
							@php 
							$full = $service->users_messages->get('me',$message->getId(),['format'=>['FULL']]);
							$headers = $full->payload->headers;
							$parts = $full->getPayload()->getParts();
							$lookingfor = ['Delivered-To','To','Subject','Reply-To','Received'];
							foreach($headers as $eachel)
							{
								if(in_array($eachel['name'],$lookingfor))
								{
									$printH[$eachel['name']]=$eachel['value'];
								}
							}
							@endphp

							Label IDs: {{json_encode($full->labelIds)}}<br/>

							Delivered-To: {{$printH['Delivered-To'] ?? '--'}}<br/>
							To: {{$printH['To'] ?? '--'}}<br/>
							<b>Subject : {{$printH['Subject'] ?? '--'}}</b><br/>
							<b>Body : {{$printH['Body'] ?? '--'}}</b><br/>
							Reply-To: {{$printH['Reply-To'] ?? '--'}}<br/>
							Received: {{$printH['Received'] ?? '--'}}<br/>
							<br/>
						</p>
					</li>
					@endforeach
					</ul>
				</div>
				<div>
					{{ $mails->nextPageToken }}
					<a href="{{ route('inbox', [ 'pageToken' => $mails->nextPageToken]) }}">Load Next Page</a>
				</div>
			</td>
		</tr>
	</table>
</body>
</html>