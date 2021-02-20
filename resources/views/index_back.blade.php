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
					@foreach ($labels as $label)
					@php
						$labelId = $label->getId();
					@endphp
					<tr>
						<td>
							<a href="{{ route('threads', [ 'label' => $labelId]) }}" class="{{ app('request')->input('label') == $labelId ? 'active' : '' }}">{{$label->getName()}}</a>
						</td>
					</tr>
					@endforeach
				</table>
			</td>
			<td class="col-9">
				<div>
					<div class="accordion" id="accordionExample">
					@php 
					date_default_timezone_set("Asia/Kolkata");
					function getAttachments($service , $message_id, $parts) {
						$attachments = [];
						foreach ($parts as $part) {
							if (!empty($part->body->attachmentId)) {
								$attachment = $service->users_messages_attachments->get('me', $message_id, $part->body->attachmentId);
								$attachments[] = [
									'filename' => $part->filename,
									'mimeType' => $part->mimeType,
									'data'     => strtr($attachment->data, '-_', '+/')
								];
							} else if (!empty($part->parts)) {
								//$attachments = array_merge($attachments, $this->getAttachments($service, $message_id, $part->parts));
							}
						}
						return $attachments;
					}
					
					@endphp
					@foreach($threads->getThreads() as $thread)
						@php 
						$fullthread = $service->users_threads->get('me',$thread->getId(),['format'=>['METADATA']]);
						$messages = $fullthread->messages;
						$messagesCount = count($messages);
						$firstMessage = $messages[0];
						$lastMessage = $messages[ $messagesCount - 1 ];
						$lastDate = date("M d, h:i a", substr($lastMessage->internalDate, 0, 10));
						
						$headers = $firstMessage->payload->headers;
						$lookingfor = ['Subject', 'From', 'Date'];
						$parts = $firstMessage->getPayload()->getParts();
						$seconds = $firstMessage->internalDat / 1000;
						
						foreach($headers as $eachel)
						{
						
							if(in_array($eachel['name'],$lookingfor))
							{
								$printH[$eachel['name']]=$eachel['value'];
							}
						}

						date("Y-m-d H:i:s", strtotime($printH["Date"]));
						
						
						@endphp 
						
						<div class="accordion-item">
						    <h2 class="accordion-header" id="headingOne">
						      <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{$thread->getId()}}" aria-expanded="true" aria-controls="collapse{{$thread->getId()}}">
								 @php echo str_replace('"', '', $printH['From']); @endphp({{$messagesCount}}) - {{$printH['Subject']}} - {{$lastDate}}
						      </button>
						    </h2>
						    <div id="collapse{{$thread->getId()}}" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
						      <div class="accordion-body">
						        	<div class="card card-body">
                                    <?php /*
								      	<ul>
											@foreach($messages as $message)
												
												@php 

												$headers = $message->payload->headers;
												$labelIds = $message->labelIds;
											
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
													<b>Labels</b>: {{is_array($labelIds) ? implode(', ', $labelIds) : '--'}}<br/>
													<b>To:</b> {{$printH['To'] ?? '--'}}<br/>
													<b>Subject : {{$printH['Subject'] ?? '--'}}</b><br/>
													<b>Reply-To:</b> {{$printH['Reply-To'] ?? '--'}}<br/>
													<b>Received:</b> {{$printH['Received'] ?? '--'}}<br/>
													<b>Body:</b> {{$printH['Received'] ?? '--'}}<br/>
													<br/>
												</ol>

											@endforeach
                                        </ul>
                                      */?>
								    </div>
						      </div>
						    </div>
						</div>
					@endforeach
					</div>
				</div>
				<div>
				<a href="{{ route('threads', [ 'pageToken' => $threads->nextPageToken]) }}">Load Next Page</a>
				</div>
			</td>
		</tr>
	</table>
</body>
</html>