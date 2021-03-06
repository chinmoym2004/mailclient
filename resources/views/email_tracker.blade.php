@extends('layouts.custom')

@section('content')
<div class="container">
<h3>Email Tracker</h3>
<div class="row">
    <form method="POST" action="{{url('/add-to-tracking')}}">
        @csrf
        <div class="row mb-3">
          <div class="col-6">
            <label>Platform</label>
            <select name="platform" class="form-control" required>
                <option value>-- Select a platform --</option>
                <option value="msmail">Microsoft Mail</option>
                <option value="gmail">Gmail</option>
            </select>
          </div>
          <div class="col-6">
              <label>Enter Email</label>
              <input type="email" name="email" class="form-control" placeholder="Email address" required>
          </div>
        </div>
        <button type="submit" class="btn btn-primary">Add Email</button>
    </form>
</div>

<table class="table">
  <thead>
    <tr>
      <th>Email</th>
      <th>Provider</th>
      <th>Tracking Stat</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
    @if($emails)
        @foreach($emails as $entry)
        <tr>
            <td>{{$entry->email}}</td>
            <td>{{$entry->platform}}</td>
            <td>{{$entry->enable_tracking?'Enabled':'Disabled'}}</td>
            <td>
                @if($entry->provider_token)
                <a href="{{url('/disconnect?email='.$entry->email)}}" class="btn btn-danger">Disconnect</a>
                (Expired at : {{$entry->expires_at}})
                @else 
                <a  href="{{url('/'.($entry->platform=='gmail'?'authenticate':'ms-authenticate').'?email='.$entry->email)}}" class="btn btn-danger">Authenticate Gmail</a>
                @endif
            </td>
        </tr>
        @endforeach
    @endif
  </tbody>
</table>
</div>
@endsection