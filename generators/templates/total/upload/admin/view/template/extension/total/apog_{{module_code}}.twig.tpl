{{ header }}
{{ column_left }}

<div id="content" class="apog_total-content">
  <style>
    .apog_total-content .control-label.alert-danger span:after {
      color: currentColor;
    }
  </style>

  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-total" data-toggle="tooltip" title="{{ button_save }}" class="btn btn-primary">
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
        <form action="{{ action }}"
              method="post"
              enctype="multipart/form-data"
              id="form-total"
              class="form-horizontal">

          {# ================= GENERAL ================= #}
          <fieldset>
            <legend>{{ text_general }}</legend>

            {# Status #}
            <div class="form-group">
              <label class="col-sm-2 control-label" for="input-status">
                {{ entry_status }}
              </label>
              <div class="col-sm-10">
                <select name="total_{{ module_code }}_status"
                        id="input-status"
                        class="form-control">
                  <option value="1" {% if _context['total_' ~ module_code ~ '_status'] %}selected{% endif %}>{{ text_enabled }}</option>
                  <option value="0" {% if not _context['total_' ~ module_code ~ '_status'] %}selected{% endif %}>{{ text_disabled }}</option>
                </select>
              </div>
            </div>

            {# Tax class #}
            <div class="form-group">
              <label class="col-sm-2 control-label" for="input-tax-class">{{ entry_tax_class }}</label>
              <div class="col-sm-10">
                <select name="total_{{ module_code }}_tax_class_id" id="input-tax-class" class="form-control">
                  <option value="0">{{ text_none }}</option>
                  {% for tax_class in tax_classes %}
                  <option value="{{ tax_class.tax_class_id }}" 
                    {% if tax_class.tax_class_id == _context['total_' ~ module_code ~ '_tax_class_id'] %}
                      selected="selected"
                    {% endif %}>
                    {{ tax_class.title }}
                  </option>
                  {% endfor %}
                </select>
              </div>
            </div>

            {# Sort Order #}
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ entry_sort_order }}</label>
              <div class="col-sm-10">
                <input type="text"
                       name="total_{{ module_code }}_sort_order"
                       value="{{ _context['total_' ~ module_code ~ '_sort_order'] }}"
                       class="form-control" />
              </div>
            </div>

            {# Logging #}
            <div class="form-group">
              <label class="col-sm-2 control-label">
                <span data-toggle="tooltip" title="{{ help_enable_logging }}">
                  {{ entry_enable_logging }}
                </span>
              </label>
              <div class="col-sm-10">
                <select name="total_{{ module_code }}_enable_logging"
                        class="form-control">
                  <option value="1" {% if _context['total_' ~ module_code ~ '_enable_logging'] %}selected{% endif %}>{{ text_enabled }}</option>
                  <option value="0" {% if not _context['total_' ~ module_code ~ '_enable_logging'] %}selected{% endif %}>{{ text_disabled }}</option>
                </select>
              </div>
            </div>

          </fieldset>

          {# ================= CONDITIONS ================= #}
          <fieldset>
            <legend>{{ text_conditions }}</legend>

            {# Geo Zone Settings #}
            <div class="row">

              {# LEFT: Tabs navigation #}
              <div class="col-sm-2">
                <ul class="nav nav-pills nav-stacked" id="geo-zone-tabs">
                  {% for geo_zone in geo_zones %}
                    <li class="{% if loop.first %}active{% endif %}">
                      <a href="#tab-geo-zone{{ geo_zone.geo_zone_id }}" data-toggle="tab">
                        {{ geo_zone.name }}
                      </a>
                    </li>
                  {% endfor %}
                </ul>
              </div>

              {# RIGHT: Tab content #}
              <div class="col-sm-10">
                <div class="tab-content">
              
                  {% for geo_zone in geo_zones %}
                  <div class="tab-pane {% if loop.first %}active{% endif %}" id="tab-geo-zone{{ geo_zone.geo_zone_id }}">


                    {# Geo Zone - Status #}
                    <div class="form-group">
                      <label class="col-sm-2 control-label">{{ entry_status }}</label>
                      <div class="col-sm-10">
                        <select name="total_{{ module_code }}_{{ geo_zone.geo_zone_id }}_status" class="form-control">
                          <option value="1" {% if geo_zone_settings[geo_zone.geo_zone_id].status %}selected="selected"{% endif %}>{{ text_enabled }}</option>
                          <option value="0" {% if not geo_zone_settings[geo_zone.geo_zone_id].status %}selected="selected"{% endif %}>{{ text_disabled }}</option>
                        </select>
                      </div>
                    </div>

                    {# Geo Zone - Fee #}
                    <div class="form-group">
                      <label class="col-sm-2 control-label">{{ entry_fee }}</label>
                      <div class="col-sm-10">
                        <input type="text" 
                          name="total_{{ module_code }}_{{ geo_zone.geo_zone_id }}_fee" 
                          value="{{ geo_zone_settings[geo_zone.geo_zone_id].fee|default(0.00) }}" 
                          class="form-control" />
                      </div>
                    </div>

                    {# Geo Zone - Total free #}
                    <div class="form-group">
                      <label class="col-sm-2 control-label">
                        <span data-toggle="tooltip" title="{{ help_total_free }}">{{ entry_total_free }}</span>
                      </label>
                      <div class="col-sm-10">
                        <input type="text" 
                          name="total_{{ module_code }}_{{ geo_zone.geo_zone_id }}_total_free" 
                          value="{{ geo_zone_settings[geo_zone.geo_zone_id].total_free|default(0.00) }}" 
                          class="form-control" />
                      </div>
                    </div>
                  </div>
                  {% endfor %}
                </div><!-- /.tab-content -->
              </div><!-- /.col-sm-10 -->

            </div><!-- /.row -->

          </fieldset>

          {# ================= RESTRICTIONS ================= #}
          <fieldset>
            <legend>{{ text_restrictions }}</legend>

            {# Stores excluded #}
            <div class="form-group">
              <label class="col-sm-2 control-label alert alert-danger">
                <span data-toggle="tooltip" title="{{ help_excluded_stores }}">
                  {{ entry_excluded_stores }}
                </span>
              </label>

              <div class="col-sm-10">
                <div class="well well-sm" style="height:150px; overflow:auto;">
                  {% for store in stores %}
                    <div class="checkbox">
                      <label>
                        <input type="checkbox"
                               name="total_{{ module_code }}_excluded_stores[]"
                               value="{{ store.store_id }}"
                               {% if store.store_id in _context['total_' ~ module_code ~ '_excluded_stores'] %}
                                 checked
                               {% endif %}>
                        {{ store.name }}
                      </label>
                    </div>
                  {% endfor %}
                </div>
              </div>
            </div>

            {# Customer Groups excluded #}
            <div class="form-group">
              <label class="col-sm-2 control-label alert alert-danger">
                <span data-toggle="tooltip" title="{{ help_excluded_customer_groups }}">
                  {{ entry_excluded_customer_groups }}
                </span>
              </label>

              <div class="col-sm-10">
                <div class="well well-sm" style="height:150px; overflow:auto;">
                  {% for customer_group in customer_groups %}
                    <div class="checkbox">
                      <label>
                        <input type="checkbox"
                               name="total_{{ module_code }}_excluded_customer_groups[]"
                               value="{{ customer_group.customer_group_id }}"
                               {% if customer_group.customer_group_id in _context['total_' ~ module_code ~ '_excluded_customer_groups'] %}
                                 checked
                               {% endif %}>
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
                          name="total_{{ module_code }}_excluded_shipping_methods[]"
                          value="{{ method.code }}"
                          {% if method.code in _context['total_' ~ module_code ~ '_excluded_shipping_methods'] %}
                            checked="checked"
                          {% endif %} />
                        {{ method.name }} ({{ method.code }})
                      </label>
                    </div>
                  {% endfor %}

                </div>
              </div>
            </div>

            {# Payment methods excluded OR Binding Payment Method #}
            {% if has_binding_payment %}
              {# Binding Payment Method #}
              <div class="form-group">
                <label class="col-sm-2 control-label alert alert-danger">
                  <span data-toggle="tooltip" title="{{ help_binding_payment_code }}">{{ entry_binding_payment_code }}</span>
                </label>
                <div class="col-sm-10">
                  <div class="alert alert-warning" style="margin-bottom:0;">
                    <i class="fa fa-link"></i>
                    {{ text_binding_payment }}
                    <strong>{{ binding_payment_code }}</strong>
                  </div>
                </div>
              </div>
            {% else %}
              {# Payment methods excluded #}
              <div class="form-group">
                <label class="col-sm-2 control-label alert alert-danger">
                  <span data-toggle="tooltip" title="{{ help_excluded_payment_methods }}">{{ entry_excluded_payment_methods }}</span>
                </label>
                <div class="col-sm-10">
                  <div class="well well-sm" style="height: 150px; overflow: auto;">

                    {% for payment in payment_methods %}
                    <div class="checkbox">
                      <label>
                        <input type="checkbox" 
                          name="total_{{ module_code }}_excluded_payment_methods[]" 
                          value="{{ payment.code }}" 
                          {% if payment.code in _context['total_' ~ module_code ~ '_excluded_payment_methods'] %}
                            checked="checked"
                          {% endif %} />
                        {{ payment.name }}
                      </label>
                    </div>
                    {% endfor %}

                  </div>
                </div>
              </div>
            {% endif %}

          </fieldset>

        </form>
      </div>
    </div>
  </div>
</div>

{{ footer }}