<!DOCTYPE html>
<html>
<head>
	<title>Inbox</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/js/bootstrap.bundle.min.js" integrity="sha384-b5kHyXgcpbZJO/tY9Ul7kGkf1S0CWuKcCD38l8YkeH8z8QjE0GmW1gYU5S9FOnJ0" crossorigin="anonymous"></script>

</head>
<body>
	<h4 style="text-align: center">Gmail Inbox Test</h4>
	<table class="table">
		<tr>
			<td class="col-3">
				<h5>Labels</h5>
				<table>
					@foreach ($labels->getLabels() as $label)
					<tr>
						<td>{{$label->getName()}}</td>
					</tr>
					@endforeach
				</table>
			</td>
			<td class="col-9">
				<div>
					<div class="accordion" id="accordionExample">

					@foreach($threads->getThreads() as $thread)
						@php 
						$fullthread = $service->users_threads->get('me',$thread->getId(),['format'=>['FULL']]);
						$messages = $fullthread->messages;
						@endphp 
						
						<div class="accordion-item">
						    <h2 class="accordion-header" id="headingOne">
						      <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{$thread->getId()}}" aria-expanded="true" aria-controls="collapse{{$thread->getId()}}">
						        Thread ID : {{$thread->getId()}} #{{count($messages)}}
						      </button>
						    </h2>
						    <div id="collapse{{$thread->getId()}}" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
						      <div class="accordion-body">
						        	<div class="card card-body">
								      	<ul>
											@foreach($messages as $message)
												
												@php 

												$headers = $message->payload->headers;
												$lookingfor = ['Delivered-To','To','Subject','Reply-To','Received'];

												
												foreach($headers as $eachel)
												{
													if(in_array($eachel['name'],$lookingfor))
													{
														$printH[$eachel['name']]=$eachel['value'];
													}
												}
												

												@endphp
									
												<ol>
													<b>Delivered-To</b>: {{$printH['Delivered-To'] ?? '--'}}<br/>
													<b>To:</b> {{$printH['To'] ?? '--'}}<br/>
													<b>Subject : {{$printH['Subject'] ?? '--'}}</b><br/>
													<b>Reply-To:</b> {{$printH['Reply-To'] ?? '--'}}<br/>
													<b>Received:</b> {{$printH['Received'] ?? '--'}}<br/>
													<b>Body:</b> {{$printH['Received'] ?? '--'}}<br/>
													<br/>
												</ol>

											@endforeach
										</ul>
								      </div>
						      </div>
						    </div>
						</div>
					@endforeach
					</div>
				</div>
				<div>
					<a href="{{url('inbox/page='.$threads->nextPageToken)}}">Load Next Page</a>
				</div>
			</td>
		</tr>
	</table>
</body>
</html>