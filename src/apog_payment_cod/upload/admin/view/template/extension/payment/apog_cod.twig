{{ header }}
{{ column_left }}
<div id="content" class="apog_payment-content">
  <style>
  .apog_payment-content .control-label.alert-danger span:after {
    color: currentColor;
  }
  </style>

  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-payment" data-toggle="tooltip" title="{{ button_save }}" class="btn btn-primary">
          <i class="fa fa-save"></i>
        </button>
        <a href="{{ cancel }}" data-toggle="tooltip" title="{{ button_cancel }}" class="btn btn-default">
          <i class="fa fa-reply"></i>
        </a>
      </div>

      <h1>{{ heading_title }}</h1>

      <ul class="breadcrumb">
        {% for breadcrumb in breadcrumbs %}
          <li><a href="{{ breadcrumb.href }}">{{ breadcrumb.text }}</a></li>
        {% endfor %}
      </ul>
    </div>
  </div>

  <div class="container-fluid">

    {% if error_warning %}
      <div class="alert alert-danger">
        <i class="fa fa-exclamation-circle"></i> {{ error_warning }}
      </div>
    {% endif %}

    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-pencil"></i> {{ text_edit }}</h3>
      </div>

      <div class="panel-body">
        <form action="{{ action }}" method="post" enctype="multipart/form-data" id="form-payment" class="form-horizontal">

          {# ================= GENERAL ================= #}
          <fieldset>
            <legend>{{ text_general }}</legend>

            {# Status #}
            <div class="form-group">
              <label class="col-sm-2 control-label" for="input-status">{{ entry_status }}</label>
              <div class="col-sm-10">
                <select name="payment_{{ module_code }}_status" id="input-status" class="form-control">
                  <option value="1" {% if _context['payment_' ~ module_code ~ '_status'] %}selected="selected"{% endif %}>{{ text_enabled }}</option>
                  <option value="0" {% if not _context['payment_' ~ module_code ~ '_status'] %}selected="selected"{% endif %}>{{ text_disabled }}</option>
                </select>
              </div>
            </div>

            {# Sort Order #}
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ entry_sort_order }}</label>
              <div class="col-sm-10">
                <input type="text"
                       name="payment_{{ module_code }}_sort_order"
                       value="{{ _context['payment_' ~ module_code ~ '_sort_order'] }}"
                       class="form-control" />
              </div>
            </div>

            {# Order Status #}
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ entry_order_status }}</label>
              <div class="col-sm-10">
                <select name="payment_{{ module_code }}_order_status_id" class="form-control">
                  {% for status in order_statuses %}
                    <option value="{{ status.order_status_id }}"
                      {% if status.order_status_id == _context['payment_' ~ module_code ~ '_order_status_id'] %}selected{% endif %}>
                      {{ status.name }}
                    </option>
                  {% endfor %}
                </select>
              </div>
            </div>

            {# Toggle Logging #}
            <div class="form-group">
              <label class="col-sm-2 control-label" for="input-enable-logging">
                <span data-toggle="tooltip" title="{{ help_enable_logging }}">{{ entry_enable_logging }}</span>
              </label>
              <div class="col-sm-10">
                <select name="payment_{{ module_code }}_enable_logging" id="input-enable-logging" class="form-control">
                  <option value="1" {% if _context['payment_' ~ module_code ~ '_enable_logging'] %}selected="selected"{% endif %}>{{ text_enabled }}</option>
                  <option value="0" {% if not _context['payment_' ~ module_code ~ '_enable_logging'] %}selected="selected"{% endif %}>{{ text_disabled }}</option>
                </select>
              </div>
            </div>
          </fieldset>

          {# ================= CONDITIONS ================= #}
          <fieldset>
            <legend>{{ text_conditions }}</legend>

            {# Geo Zone #}
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ entry_geo_zone }}</label>
              <div class="col-sm-10">
                <select name="payment_{{ module_code }}_geo_zone_id" class="form-control">
                  <option value="0">All Zones</option>
                  {% for zone in geo_zones %}
                    <option value="{{ zone.geo_zone_id }}"
                      {% if zone.geo_zone_id == _context['payment_' ~ module_code ~ '_geo_zone_id'] %}selected{% endif %}>
                      {{ zone.name }}
                    </option>
                  {% endfor %}
                </select>
              </div>
            </div>

            {# Min Total #}
            <div class="form-group">
              <label class="col-sm-2 control-label">
                <span data-toggle="tooltip" title="{{ help_min_total }}">{{ entry_min_total }}</span>
              </label>
              <div class="col-sm-10">
                <input type="text"
                       name="payment_{{ module_code }}_min_total"
                       value="{{ _context['payment_' ~ module_code ~ '_min_total'] }}"
                       class="form-control" />
              </div>
            </div>
          </fieldset>

          {# ================= RESTRICTIONS ================= #}
          <fieldset>
            <legend>{{ text_restrictions }}</legend>

            {# Stores excluded #}
            <div class="form-group">
              <label class="col-sm-2 control-label alert alert-danger">
                <span data-toggle="tooltip" title="{{ help_excluded_stores }}">{{ entry_excluded_stores }}</span>
              </label>

              <div class="col-sm-10">
                <div class="well well-sm" style="height: 150px; overflow: auto;">

                  {% for store in stores %}
                    <div class="checkbox">
                      <label>
                        <input type="checkbox"
                          name="payment_{{ module_code }}_excluded_stores[]"
                          value="{{ store.store_id }}"
                          {% if store.store_id in _context['payment_' ~ module_code ~ '_excluded_stores'] %}
                            checked="checked"
                          {% endif %} />
                        {{ store.name }} {% if store.domain %} ({{ store.domain }}){% endif %}
                      </label>
                    </div>
                  {% endfor %}

                </div>
              </div>
            </div>

            {# Customer Groups excluded #}
            <div class="form-group">
              <label class="col-sm-2 control-label alert alert-danger">
                <span data-toggle="tooltip" title="{{ help_excluded_customer_groups }}">{{ entry_excluded_customer_groups }}</span>
              </label>

              <div class="col-sm-10">
                <div class="well well-sm" style="height: 150px; overflow: auto;">

                  {% for customer_group in customer_groups %}
                    <div class="checkbox">
                      <label>
                        <input type="checkbox"
                          name="payment_{{ module_code }}_excluded_customer_groups[]"
                          value="{{ customer_group.customer_group_id }}"
                          {% if customer_group.customer_group_id in _context['payment_' ~ module_code ~ '_excluded_customer_groups'] %}
                            checked="checked"
                          {% endif %} />
                        {{ customer_group.name }}
                      </label>
                    </div>
                  {% endfor %}

                </div>
              </div>
            </div>

            {# Shipping Methods excluded #}
            <div class="form-group">
              <label class="col-sm-2 control-label alert alert-danger">
                <span data-toggle="tooltip" title="{{ help_excluded_shipping_methods }}">{{ entry_excluded_shipping_methods }}</span>
              </label>

              <div class="col-sm-10">
                <div class="well well-sm" style="height: 150px; overflow: auto;">

                  {% for method in shipping_methods %}
                    <div class="checkbox">
                      <label>
                        <input type="checkbox"
                          name="payment_{{ module_code }}_excluded_shipping_methods[]"
                          value="{{ method.code }}"
                          {% if method.code in _context['payment_' ~ module_code ~ '_excluded_shipping_methods'] %}
                            checked="checked"
                          {% endif %} />
                        {{ method.name }} ({{ method.code }})
                      </label>
                    </div>
                  {% endfor %}

                </div>
              </div>
            </div>

          </fieldset>

          {# ================= INSTRUCTIONS ================= #}
          <fieldset>
            <legend>
              <span data-toggle="tooltip" title="{{ help_instructions }}">
                {{ entry_instructions }}
              </span>
            </legend>

            {% for language in languages %}
              <div class="form-group">
                <label class="col-sm-2 control-label">
                  <img src="language/{{ language.code }}/{{ language.code }}.png" title="{{ language.name }}" />
                  {{ language.name }}
                </label>
                <div class="col-sm-10">
                  <textarea name="payment_{{ module_code }}_instructions_{{ language.language_id }}"
                            rows="4"
                            class="form-control">{{ _context['payment_' ~ module_code ~ '_instructions_' ~ language.language_id] }}</textarea>
                </div>
              </div>
            {% endfor %}
          </fieldset>

        </form>
      </div>
    </div>
  </div>
</div>

{{ footer }}