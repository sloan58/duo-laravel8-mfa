@extends('layouts.app')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 text-center">
            <iframe
                id="duo_iframe"
                width="590"
                height="500"
                frameborder="0"
                allowtransparency="true"
                style="background:transparent;">
            </iframe>
        </div>
    </div>
    @push('js')
        <script>
            Duo.init({
                'host': '{{ $duoInfo['host'] }}',
                'sig_request': '{{ $duoInfo['sig'] }}',
                'post_action': '{{ $duoInfo['callback'] }}'
            });
        </script>
    @endpush
@endsection
