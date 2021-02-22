<!doctype html>
<html>
  <head>
    <title>Gmail Thread APi demo</title>
    <meta charset="UTF-8">

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
    </style>
  </head>
  <body>
    <div class="container">
      <h1>{{ $subject }}</h1>
      <input type="hidden" name="accessToken" id="accessToken" value="{{ $accessToken }}">
      <input type="hidden" name="threadId" id="threadId" value="{{ $threadId }}">
      <input type="hidden" name="emailId" id="emailId" value="">

      <button id="authorize-button" class="btn btn-primary hidden">Authorize</button>

      <table class="table table-striped table-inbox hidden">
        <tbody>
        <div id="body_content">Loading...</div>
        <form>
                <div class="form-group">
                    <label for="exampleInputEmail1">Reply</label>
                    <textarea class="form-control" id="body_message" placeholder="Reply"></textarea>
                    
                </div>
    

                <button type="button" onclick="sendMessage()" id="send_message" class="btn btn-primary">Submit</button>
            </form>

        </tbody>
        <tfoot>
            
        </tfoot>
      </table>
      
    </div>

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

      function loadGmailApi() {
        gapi.client.load('gmail', 'v1', displayThreads);
      }

      function displayThreads() {

          // Each Single messages
          // var request = gapi.client.gmail.users.messages.list({
          //   'userId': 'me',
          //   'labelIds': 'INBOX',
          //   'maxResults': 20
          // });

          // Each single thread (included multile message)
          $('#body_content').html("Loading...");
          var threadRequest = gapi.client.gmail.users.threads.get({
                  'userId': 'me',
                  'id': $("#threadId").val()
                });
          threadRequest.execute(appendThreadRow);
      }


      function sendMessage() {
        var body =  $("#body_message").val();
        if($.trim(body) == "") {
            alert("Message is missing");
            return false;
        }
        var encodedResponse = btoa(
          "Content-Type: text/plain; charset=\"UTF-8\"\n" +
          "MIME-Version: 1.0\n" +
          "Content-Transfer-Encoding: 7bit\n" +
          "Subject: Subject of the original mail\n" +
          "From: reachus.ootb@gmail.com\n" +
          "To: "+$("#emailId").val()+"\n\n" +
          body
        ).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
        
        $("#send_message").html("Sending...");
        $.ajax({
          url: "https://www.googleapis.com/gmail/v1/users/me/messages/send?access_token="+$("#accessToken").val(),
          method: "POST",
          contentType: "application/json",
          data: JSON.stringify({           
            raw: encodedResponse,
            threadId: $("#threadId").val()
          }),
          success: function(res) {
            console.log(res);
            $("#body_message").val('')
            $("#send_message").html("Send");
            displayThreads();
            alert("Message sent!");
          },
          error : function(error) {
            console.log(error);
            $("#send_message").html("Error please refresh and try again!");
          }
        });
      }

   


      function appendThreadRow(thread)
      {
            console.log(thread);
            var firstMessage = thread.messages[0];
            var lastMessage = thread.messages[ thread.messages.length - 1 ];
            console.log(firstMessage.payload.headers);
            var from = getHeader(firstMessage.payload.headers, 'From');
            var fromEmail = "";
            if(from) {
                fromEmail = from.match(/(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))/g);
                fromEmail = fromEmail.join(",");
                $("#emailId").val(fromEmail);
            }
            console.log(fromEmail);
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
          
            console.log(html);

            $('#body_content').html(html);
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