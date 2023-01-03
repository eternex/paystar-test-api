<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>PAY-STAR</title>
    </head>
    <body>
        <h3>PAY-STAR EMPLOYMENT TEST</h3>
        <h5>Here you can see payment result</h5>
        
        Tracking Code: {{$tracking_code}}<br><br>
        Amount Based IRR: {{$payment_amount}}<br><br>
        
        Payment Result:
        @if ($status)
            <span style="color:green;">Success</span><br>
        @else
            <span style="color:red;">Failed</span><br>
        @endif
        
        <br>
        <span style="font-size: 16px; color:#ff4411;">{{$message}}</span>
        
        <br><br>
        <a href="{{env('CALLBACK_VUE_APP_PAY_STAR_IPG')}}/callback?invoice_id={{$invoice_id}}">
            <button>Show result in VueJs App</button>
        </a>
    </body>
</html>