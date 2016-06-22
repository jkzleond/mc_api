<!doctype html>
<html lang="en" manifest="app.manifest">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width initial-scale=1.0 maximum-scale=1.0 user-scalable=no" />
    <title>车友惠-本地惠</title>
    {% block header_css %}
<!--    <link rel="stylesheet" href="{{ url('/assets/css/bootstrap.min.css')}}"/>-->
    <!--    <link rel="stylesheet" href="{{ url('/assets/css/font-awesome.min.css')}}"/>-->
    <!--    <link rel="stylesheet" href="{{ url('/assets/css/main.css')}}"/>-->
<!--    <link rel="stylesheet" type="text/css" href="{{ url('/assets/css/j.css') }}"/>-->
    <link rel="stylesheet" href="{{ url('/assets/css/jquery.mobile.material.theme.css') }}"/>
    <link rel="stylesheet" href="{{ url('/assets/css/jquery.datetimepicker.css') }}"/>
    <link rel="stylesheet" href="{{ url('/assets/css/jquery.jqplot.min.css') }}"/>
    <link rel="stylesheet" href="{{ url('/assets/css/bdh.css') }}"/>
    <link rel="stylesheet" href="{{ url('/assets/css/bxjs.css?bust=5.2.19') }}"/>
    {% endblock %}
    {% block header_js %}
    <script type="text/javascript" src="{{ url('/assets/js/date.js') }}"></script>
    <script type="text/javascript" src="{{ url('/assets/js/require.js') }}" data-main="{{ url('/assets/js/app-built/app/main.js?bust=5.2.22') }}"></script>
<!--    <script type="text/javascript" src="{{ url('/assets/js/jquery-2.js') }}"></script>-->
<!--    <script type="text/javascript" src="{{ url('/assets/js/jquery.mobile-1.4.5.js') }}"></script>-->
<!--    <script type="text/javascript" src="{{ url('/assets/js/underscore.js') }}"></script>-->
<!--    <script type="text/javascript" src="{{ url('/assets/js/backbone.js') }}"></script>-->
<!--   <script type="text/javascript" src="{{ url('/assets/js/app/main.js?bust=1.041') }}"></script> -->
   <!-- <script type="text/javascript">
        window.addEventListener('load', function(){
            window.applicationCache.addEventListener('downloading', function(){
                console.log('downloading...');
            });
            window.applicationCache.addEventListener('progress', function(event){
                console.log(event);
            });
            window.applicationCache.addEventListener('oncached', function(event){
                console.log('cached');
            });
        });
    </script>-->
    {% endblock %}
</head>
<body style="background-color:#EEE; margin: 0px; border: 0px; padding: 0px;">
{% block content %}
{% endblock %}
</body>
{% block footer_css %}
{% endblock %}
{% block footer_js %}
{% endblock %}
</html>
