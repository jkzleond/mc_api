<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width initial-scale=1.0 maximum-scale=1.0 user-scalable=no" />

    <title>{% block title %}挪车{% endblock %}</title>

    {% block header_css %}
    <link rel="stylesheet" href="{{ url('/assets/css/mobile-angular-ui/mobile-angular-ui-hover.min.css') }}" />
    <link rel="stylesheet" href="{{ url('/assets/css/mobile-angular-ui/mobile-angular-ui-base.min.css') }}" />
    {% endblock %}

    {% block header_js %}
    <script src="{{ url('/assets/js/angular/angular-1.3.min.js') }}"></script>
    <script src="{{ url('/assets/js/angular/angular-route-1.3.min.js') }}"></script>
    {% endblock %}
</head>
<body>
{% block content %}

{% endblock %}
{% block footer_css %}
{% endblock %}
{% block footer_js %}
{% endblock %}
</body>
</html>
