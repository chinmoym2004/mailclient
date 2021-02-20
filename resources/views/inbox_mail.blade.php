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
	<div class="row">
	<div class="col-12">
		<div class="row">
			<div class="col-sm-3">
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
			</div>
			<div class="col-sm-9">
				<div class="col-12">
					<div class="col-12">
					@php 
					function decodeBody($body) {
					$rawData = $body;
					$sanitizedData = strtr($rawData,'-_', '+/');
					$decodedMessage = base64_decode($sanitizedData);
					if(!$decodedMessage){
						$decodedMessage = FALSE;
					}
					return $decodedMessage;
}
					@endphp
					@foreach($mails->getMessages() as $message)
					<div class="col-12 mb-2">
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

							$payload = $full->getPayload();
							$parts = $payload->getParts();

							// With no attachment, the payload might be directly in the body, encoded.
							$body = $payload->getBody();
							$BODY = FALSE;
							// If we didn't find a body, let's look for the parts
							if(!$BODY) {
								
								foreach ($parts  as $part) {
									if($part['parts'] && !$BODY) {
										foreach ($part['parts'] as $p) {
											if($p['parts'] && count($p['parts']) > 0){
												foreach ($p['parts'] as $y) {
													if(($y['mimeType'] === 'text/html') && $y['body']) {
														$BODY = decodeBody($y['body']->data);
														break;
													}
												}
											} else if(($p['mimeType'] === 'text/html') && $p['body']) {
												$BODY = decodeBody($p['body']->data);
												break;
											}
										}
									}
									if($BODY) {
										break;
									}
								}
							
							}
							// let's save all the images linked to the mail's body:
							if($BODY && count($parts) > 1){
								$images_linked = array();
								foreach ($parts  as $part) {
									if($part['filename']){
										array_push($images_linked, $part);
									} else{
										if($part['parts']) {
											foreach ($part['parts'] as $p) {
												if($p['parts'] && count($p['parts']) > 0){
													foreach ($p['parts'] as $y) {
														if(($y['mimeType'] === 'text/html') && $y['body']) {
															array_push($images_linked, $y);
														}
													}
												} else if(($p['mimeType'] !== 'text/html') && $p['body']) {
													array_push($images_linked, $p);
												}
											}
										}
									}
								}
								// special case for the wdcid...
								preg_match_all('/wdcid(.*)"/Uims', $BODY, $wdmatches);
								if(count($wdmatches)) {
									$z = 0;
									foreach($wdmatches[0] as $match) {
										$z++;
										if($z > 9){
											$BODY = str_replace($match, 'image0' . $z . '@', $BODY);
										} else {
											$BODY = str_replace($match, 'image00' . $z . '@', $BODY);
										}
									}
								}
								preg_match_all('/src="cid:(.*)"/Uims', $BODY, $matches);
								if(count($matches)) {
									$search = array();
									$replace = array();
									// let's trasnform the CIDs as base64 attachements 
									foreach($matches[1] as $match) {
										foreach($images_linked as $img_linked) {
											foreach($img_linked['headers'] as $img_lnk) {
												if( $img_lnk['name'] === 'Content-ID' || $img_lnk['name'] === 'Content-Id' || $img_lnk['name'] === 'X-Attachment-Id'){
													if ($match === str_replace('>', '', str_replace('<', '', $img_lnk->value)) 
															|| explode("@", $match)[0] === explode(".", $img_linked->filename)[0]
															|| explode("@", $match)[0] === $img_linked->filename){
														$search = "src=\"cid:$match\"";
														$mimetype = $img_linked->mimeType;
														$attachment = $service->users_messages_attachments->get('me', $message->getId(), $img_linked['body']->attachmentId);
														$data64 = strtr($attachment->getData(), array('-' => '+', '_' => '/'));
														$replace = "src=\"data:" . $mimetype . ";base64," . $data64 . "\"";
														$BODY = str_replace($search, $replace, $BODY);
													}
												}
											}
										}
									}
								}
							}
							// If we didn't find the body in the last parts, 
							// let's loop for the first parts (text-html only)
							if(!$BODY) {
								foreach ($parts  as $part) {
									if($part['body'] && $part['mimeType'] === 'text/html') {
										$BODY = decodeBody($part['body']->data);
										break;
									}
								}
							}
							// With no attachment, the payload might be directly in the body, encoded.
							if(!$BODY) {
								$BODY = decodeBody($body['data']);
							}
							// Last try: if we didn't find the body in the last parts, 
							// let's loop for the first parts (text-plain only)
							if(!$BODY) {
								foreach ($parts  as $part) {
									if($part['body']) {
										$BODY = decodeBody($part['body']->data);
										break;
									}
								}
							}
							if(!$BODY) {
								$BODY = '(No message)';
							}
											
							


							@endphp

							Label IDs: {{json_encode($full->labelIds)}}<br/>

							Delivered-To: {{$printH['Delivered-To'] ?? '--'}}<br/>
							To: {{$printH['To'] ?? '--'}}<br/>
							<b>Subject : {{$printH['Subject'] ?? '--'}}</b><br/>
							<b>Body : {!! $BODY ?? '--'!!}</b><br/>
							Reply-To: {{$printH['Reply-To'] ?? '--'}}<br/>
							Received: {{$printH['Received'] ?? '--'}}<br/>
							<br/>
						</p>
					</div>
					@endforeach
					</div>
				</div>
				<div>
					<a href="{{ route('inbox', [ 'pageToken' => $mails->nextPageToken]) }}">Load Next Page</a>
				</div>
			</div>
		</div>
	</div>
	</div>
</body>
</html>