<!doctype html>
<html>
  <head>
    <title>Gmail Thread APi demo</title>
    <meta charset="UTF-8">
    <meta name="csrf-token" id="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">
    <style>
      iframe {
        width: 100%;
        border: 0;
        min-height: 80%;
        height: 600px;
        display: flex;
      }
      .d-none { display: none; }
      .left-label.active {
        background: #337ab7; color: #fff; padding: 3px 10px;
      }
    </style>
  </head>
  <body>
    <div class="container">
      <h1>Gmail Thread API demo</h1>
      <input type="hidden" name="accessToken" id="accessToken" value="{{ $accessToken }}">
      <button id="authorize-button" class="btn btn-primary hidden">Authorize</button>
      <div class="row">
	      <div class="col-12">
          <a class="btn btn-primary pull-right" href="{{ route('createmessage') }}">Compose</a>
		      <div class="row">
            <div class="col-sm-3">
                <h5>
                  Labels
                  @if(!empty(app('request')->input('label')))
                    <a href="{{ route('threadsjs') }}" style="color: red">- Reset</a>
                  @endif
                </h5>
                <table>
                  @foreach ($labels->getLabels() as $label)
                  @php
                    $labelId = $label->getId();
                  @endphp
                  <tr>
                    <td>
                      <a href="{{ route('threadsjs', [ 'label' => $labelId]) }}" class="left-label {{ app('request')->input('label') == $labelId ? 'active' : '' }}">{{$label->getName()}}</a>
                    </td>
                  </tr>
                  @endforeach
                </table>
            </div>
			      <div class="col-sm-9">
            <a href="javascript:;" class="d-none" id="next_page" style="font-size: 20px; font-weight: bold; margin-bottom: 30px; display: inline-block; float: right; ">Next</a>
              <table class="table table-striped table-inbox hidden">
                <thead>
                  <tr>
                    <th>From</th>
                    <th>Subject</th>
                    <th>Date/Time</th>
                  </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                
                </tfoot>
              </table>
              <a href="javascript:;" class="d-none" id="next_page" style="font-size: 20px; font-weight: bold; margin-bottom: 30px; display: inline-block; float: right; ">Next</a>
            </div>
          </div>
        </div>
      </div>
      
    </div>
    <input type="hidden" name="hidden_label" id="hidden_label" value="{{ !empty(app('request')->input('label')) ? app('request')->input('label') : 'INBOX' }}"> 
    <script src="//code.jquery.com/jquery-1.11.3.min.js"></script>
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>

    <script type="text/javascript">
      var clientId = '829350265191-q61efgj7djcmbnukqo244281die8027d.apps.googleusercontent.com';
      var apiKey = 'AIzaSyCXcJMMObbpueWGqnhmp_Bckhqj-WYMwvI';
      var scopes = 'https://www.googleapis.com/auth/gmail.readonly';
      var nextPageToken = "";
      var previousToken = "";
      var headersArray = [];

      function handleClientLoad() {
        gapi.client.setApiKey(apiKey);
        window.setTimeout(checkAuth, 1);
      }

      function checkAuth() {
        gapi.auth.authorize({
          client_id: clientId,
          scope: scopes,
          immediate: true
        }, handleAuthResult);
      }
 
      function handleAuthClick() {
        gapi.auth.authorize({
          client_id: clientId,
          scope: scopes,
          immediate: false
        }, handleAuthResult);
        return false; 
      }

      function handleAuthResult(authResult) {
        if(authResult && !authResult.error) {
          console.log(authResult);
          //updateUserToken(authResult);
          loadGmailApi();
          $('#authorize-button').remove();
          $('.table-inbox').removeClass("hidden");
        } else {
          $('#authorize-button').removeClass("hidden");
          $('#authorize-button').on('click', function(){
            handleAuthClick();
          });
        }
      }

      function updateUserToken(authResult) {

        $.ajax({
          url: "{{ route('updateusertoken') }}",
          method: "POST",
          contentType: "application/json",
          headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
          data: JSON.stringify(authResult),
          success: function(res) {
            console.log(res);
          },
          error : function(error) {
            console.log(error);
           
          }
        });
      }
      function loadGmailApi() {
        gapi.client.load('gmail', 'v1', displayInbox);
      }

      function displayInbox() {

          // Each Single messages
          // var request = gapi.client.gmail.users.messages.list({
          //   'userId': 'me',
          //   'labelIds': 'INBOX',
          //   'maxResults': 20
          // });

          // Each single thread (included multile message)
          var optParams = {
            'userId': 'me',
            'labelIds': $("#hidden_label").val(),
            'maxResults': 20
          };
          if(nextPageToken != null && nextPageToken != "null" && nextPageToken != undefined) {
            optParams.pageToken = nextPageToken;
          }
          console.log(optParams);
          var request = gapi.client.gmail.users.threads.list(optParams);

        // console.log(request);

          request.execute(function(response) 
          {
            console.log(response);
            $('.table-inbox tbody').html("");
            $.each(response.threads, function() {
                var threadRequest = gapi.client.gmail.users.threads.get({
                  'userId': 'me',
                  'id':this.id
                });

                threadRequest.execute(appendThreadRow);

            });
 
          
            if(response.nextPageToken) {
              nextPageToken = response.nextPageToken; 
              $("#next_page").removeClass("d-none").attr("onclick", "loadNextPage()");
            }
              // $.each(response.messages, function() {
              //   var messageRequest = gapi.client.gmail.users.messages.get({
              //     'userId': 'me',
              //     'id': this.id
              //   }); 

              //   messageRequest.execute(appendMessageRow);
              // });
        });
      }

      function loadNextPage() {
        displayInbox();
      }

      
      function replyThread(threadId) {
        console.log(threadId);
      }


      function appendThreadRow(thread)
      {
            console.log(thread);
            var firstMessage = thread.messages[0];
            var lastMessage = thread.messages[ thread.messages.length - 1 ];
            var subject = getHeader(firstMessage.payload.headers, 'Subject');

            $('.table-inbox tbody').append(
              '<tr>\
                <td>'+getHeader(firstMessage.payload.headers, 'From')+'</td>\
                <td>\
                <a href="/readonly-inbox/' + thread.id +'?subject='+subject+'&label='+$("#hidden_label").val()+'">' +
                    subject+'  (#'+thread.messages.length+')'+'</a>\
                </td>\
                <td>'+getHeader(lastMessage.payload.headers, 'Date')+'</td>\
              </tr>'
            );

           
            $('body').append(
              '<div class="modal fade" id="message-modal-' + thread.id +
                  '" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">\
                <div class="modal-dialog modal-lg">\
                  <div class="modal-content">\
                    <div class="modal-header">\
                      <button type="button"\
                              class="close"\
                              data-dismiss="modal"\
                              aria-label="Close">\
                        <span aria-hidden="true">&times;</span></button>\
                      <h4 class="modal-title" id="myModalLabel">' +
                        getHeader(firstMessage.payload.headers, 'Subject') +
                      '</h4>\
                    </div>\
                    <div class="modal-body">\
                      <iframe id="message-iframe-'+thread.id+'" srcdoc="<p>Loading...</p>">\
                      </iframe>\
                    </div>\
                  </div>\
                </div>\
              </div>'
            );

            $('#message-link-'+thread.id).on('click', function(){
              console.log(thread);
              var ifrm = $('#message-iframe-'+thread.id)[0].contentWindow.document;

              var html = "";
              var count = 1;
              $.each(thread.messages,function(i,v){
                console.log(v);
                html+='<h5>Mail #'+count+"</h5><br/>";
                html+=getBody(v.payload);
                html+="<hr/>";
                count++;
              });
              var threadId = '"'+thread.id+'"';
              var form = "<form>";
              form += "<div class='col-12'>";
              form += "<textarea name='reply' class='form-control' id='reply_"+thread.id+"'></textarea>";
              form += "</div>";
              form += "<div class='col-12'>";
              form += "<button type='button' name='submit' onclick='replyThread("+threadId+")'>Submit</submit>";
              form += "</div>";
              form += "</form>";
              html += form;
              console.log(html);

              $('body', ifrm).html(html);
            });
      }

      function appendMessageRow(message) {
        $('.table-inbox tbody').append(
          '<tr>\
            <td>'+getHeader(message.payload.headers, 'From')+'</td>\
            <td>\
              <a href="#message-modal-' + message.id +
                '" data-toggle="modal" id="message-link-' + message.id+'">' +
                getHeader(message.payload.headers, 'Subject') +
              '</a>\
            </td>\
            <td>'+getHeader(message.payload.headers, 'Date')+'</td>\
          </tr>'
        );

        
        

        $('body').append(
          '<div class="modal fade" id="message-modal-' + message.id +
              '" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">\
            <div class="modal-dialog modal-lg">\
              <div class="modal-content">\
                <div class="modal-header">\
                  <button type="button"\
                          class="close"\
                          data-dismiss="modal"\
                          aria-label="Close">\
                    <span aria-hidden="true">&times;</span></button>\
                  <h4 class="modal-title" id="myModalLabel">' +
                    getHeader(message.payload.headers, 'Subject') +
                  '</h4>\
                </div>\
                <div class="modal-body">\
                  <iframe id="message-iframe-'+message.id+'" srcdoc="<p>Loading...</p>">\
                  </iframe>\
                </div>\
              </div>\
            </div>\
          </div>'
        );

        $('#message-link-'+message.id).on('click', function(){
          var ifrm = $('#message-iframe-'+message.id)[0].contentWindow.document;
          var body = getBody(message.payload);
          $('body', ifrm).html(body);
        });
      }

      function getHeader(headers, index) {
        var header = '';

        $.each(headers, function(){
          if(this.name === index){
            header = this.value;
          }
        });
        return header;
      }

      function getBody(message) {
        var encodedBody = '';
        if(typeof message.parts === 'undefined')
        {
          encodedBody = message.body.data;
        }
        else
        {
          encodedBody = getHTMLPart(message.parts);
        }
        encodedBody = encodedBody.replace(/-/g, '+').replace(/_/g, '/').replace(/\s/g, '');
        return decodeURIComponent(escape(window.atob(encodedBody)));
      }

      function getHTMLPart(arr) {
        for(var x = 0; x <= arr.length; x++)
        {
          if(typeof arr[x].parts === 'undefined')
          {
            if(arr[x].mimeType === 'text/html')
            {
              return arr[x].body.data;
            }
          }
          else
          {
            return getHTMLPart(arr[x].parts);
          }
        }
        return '';
      }
    </script>
    <script src="https://apis.google.com/js/client.js?onload=handleClientLoad"></script>
  </body>
</html>