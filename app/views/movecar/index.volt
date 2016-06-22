{% extends 'movecar/base.volt' %}

{% block title %}车友惠-挪车{% endblock %}

{% block header_css %}
{{ super() }}
{% endblock%}

{% block header_js %}
{{ super() }}
{% endblock%}

{% block content %}
<div class="app">
    <div class="navbar navbar-app navbar-absolute-top">
        <!-- ... -->
    </div>

    <div class="navbar navbar-app navbar-absolute-bottom">
        <div class="navbar-brand navbar-brand-center">
            Navbar Brand
        </div>

        <div class="btn-group pull-left">
            <div class="btn btn-navbar">
                Left Action
            </div>
        </div>

        <div class="btn-group pull-right">
            <div class="btn btn-navbar">
                Right Action
            </div>
        </div>
    </div>

    <div class="app-body">
        <ng-view></ng-view>
    </div>
</div>
{% endblock %}