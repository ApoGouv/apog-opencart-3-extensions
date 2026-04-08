{{ header }}
{{ column_left }}
<div id="content" class="apog_shipping-content">
  <style>
  .apog_shipping-content .control-label.alert-danger span:after {
    color: currentColor;
  }
  </style>

  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right action-buttons">
        <button type="submit" form="form-shipping" data-toggle="tooltip" title="{{ button_save }}" class="btn btn-primary">
          <i class="fa fa-save"></i>
        </button>
        <a href="{{ cancel }}" data-toggle="tooltip" title="{{ button_cancel }}" class="btn btn-default">
          <i class="fa fa-reply"></i>
        </a>
      </div>
      <h1 class="main-title">{{ heading_title }}</h1>
      <ul class="breadcrumb-list breadcrumb">
        {% for breadcrumb in breadcrumbs %}
        <li class="breadcrumb-item">
          <a href="{{ breadcrumb.href }}">{{ breadcrumb.text }}</a>
        </li>
        {% endfor %}
      </ul>
    </div>
  </div><!-- /.page-header -->

  <div class="container-fluid module-container">
    {% if error_warning %}
    <div class="alert alert-danger alert-dismissible"><i class="fa fa-exclamation-circle"></i> {{ error_warning }}
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    {% endif %}

    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-pencil"></i> {{ text_edit }}</h3>
      </div>
      <div class="panel-body">
        <form action="{{ action }}" method="post" enctype="multipart/form-data" id="form-shipping" class="form-horizontal">
          <div class="row">
            <div class="col-sm-2 tab-nav-container">
              <ul class="nav nav-pills nav-stacked" id="vtabs">
                <li class="active">
                  <a href="#tab-general" data-toggle="tab">{{ tab_general }}</a>
                </li>
                {% for geo_zone in geo_zones %}
                <li>
                  <a href="#tab-geo-zone{{ geo_zone.geo_zone_id }}" data-toggle="tab">{{ geo_zone.name }}</a>
                </li>
                {% endfor %}
              </ul>
            </div>
            <div class="col-sm-10 tab-content-container">
              <div class="tab-content">
                {# General Settings #}
                <div class="tab-pane active" id="tab-general">

                  {# Status #}  
                  <div class="form-group">
                    <label class="col-sm-2 control-label" for="input-status">{{ entry_status }}</label>
                    <div class="col-sm-10">
                      <select name="shipping_{{ module_code }}_status" id="input-status" class="form-control">
                        <option value="1" {% if _context['shipping_' ~ module_code ~ '_status'] %}selected="selected"{% endif %}>{{ text_enabled }}</option>
                        <option value="0" {% if not _context['shipping_' ~ module_code ~ '_status'] %}selected="selected"{% endif %}>{{ text_disabled }}</option>
                      </select>
                    </div>
                  </div>

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
                              name="shipping_{{ module_code }}_excluded_stores[]"
                              value="{{ store.store_id }}"
                              {% if store.store_id in _context['shipping_' ~ module_code ~ '_excluded_stores'] %}
                                checked="checked"
                              {% endif %} />
                            {{ store.name }} {% if store.domain %} ({{ store.domain }}){% endif %}
                          </label>
                        </div>
                      {% endfor %}

                      </div>
                    </div>
                  </div>
                  
                  {# Customer group excluded #}
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
                              name="shipping_{{ module_code }}_excluded_customer_groups[]" 
                              value="{{ customer_group.customer_group_id }}" 
                              {% if customer_group.customer_group_id in _context['shipping_' ~ module_code ~ '_excluded_customer_groups'] %}
                                checked="checked"
                              {% endif %} />
                            {{ customer_group.name }}
                          </label>
                        </div>
                        {% endfor %}

                      </div>
                    </div>
                  </div>

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
                              name="shipping_{{ module_code }}_excluded_payment_methods[]" 
                              value="{{ payment.code }}" 
                              {% if payment.code in _context['shipping_' ~ module_code ~ '_excluded_payment_methods'] %}
                                checked="checked"
                              {% endif %} />
                            {{ payment.name }}
                          </label>
                        </div>
                        {% endfor %}

                      </div>
                    </div>
                  </div>

                  {# Tax class #}
                  <div class="form-group">
                    <label class="col-sm-2 control-label" for="input-tax-class">{{ entry_tax_class }}</label>
                    <div class="col-sm-10">
                      <select name="shipping_{{ module_code }}_tax_class_id" id="input-tax-class" class="form-control">
                        <option value="0">{{ text_none }}</option>
                        {% for tax_class in tax_classes %}
                        <option value="{{ tax_class.tax_class_id }}" 
                          {% if tax_class.tax_class_id == _context['shipping_' ~ module_code ~ '_tax_class_id'] %}
                            selected="selected"
                          {% endif %}>
                          {{ tax_class.title }}
                        </option>
                        {% endfor %}
                      </select>
                    </div>
                  </div>

                  {# Sort order #}
                  <div class="form-group">
                    <label class="col-sm-2 control-label" for="input-sort-order">{{ entry_sort_order }}</label>
                    <div class="col-sm-10">
                      <input type="text" 
                        name="shipping_{{ module_code }}_sort_order" 
                        value="{{ _context['shipping_' ~ module_code ~ '_sort_order'] }}" 
                        placeholder="{{ entry_sort_order }}" 
                        id="input-sort-order" 
                        class="form-control" />
                    </div>
                  </div>

                  {# Toggle Logging #}
                  <div class="form-group">
                    <label class="col-sm-2 control-label" for="input-enable-logging">
                      <span data-toggle="tooltip" title="{{ help_enable_logging }}">{{ entry_enable_logging }}</span>
                    </label>
                    <div class="col-sm-10">
                      <select name="shipping_{{ module_code }}_enable_logging" id="input-enable-logging" class="form-control">
                        <option value="1" {% if _context['shipping_' ~ module_code ~ '_enable_logging'] %}selected="selected"{% endif %}>{{ text_enabled }}</option>
                        <option value="0" {% if not _context['shipping_' ~ module_code ~ '_enable_logging'] %}selected="selected"{% endif %}>{{ text_disabled }}</option>
                      </select>
                    </div>
                  </div>
                </div>

                {# Geo Zone Settings #}
                {% for geo_zone in geo_zones %}
                <div class="tab-pane" id="tab-geo-zone{{ geo_zone.geo_zone_id }}">

                  {# Geo Zone - Status #}
                  <div class="form-group">
                    <label class="col-sm-2 control-label">{{ entry_status }}</label>
                    <div class="col-sm-10">
                      <select name="shipping_{{ module_code }}_{{ geo_zone.geo_zone_id }}_status" class="form-control">
                        <option value="1" {% if geo_zone_settings[geo_zone.geo_zone_id].status %}selected="selected"{% endif %}>{{ text_enabled }}</option>
                        <option value="0" {% if not geo_zone_settings[geo_zone.geo_zone_id].status %}selected="selected"{% endif %}>{{ text_disabled }}</option>
                      </select>
                    </div>
                  </div>

                  {# Geo Zone - Cost #}
                  <div class="form-group">
                    <label class="col-sm-2 control-label">{{ entry_cost }}</label>
                    <div class="col-sm-10">
                      <input type="text" 
                        name="shipping_{{ module_code }}_{{ geo_zone.geo_zone_id }}_cost" 
                        value="{{ geo_zone_settings[geo_zone.geo_zone_id].cost }}" 
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
                        name="shipping_{{ module_code }}_{{ geo_zone.geo_zone_id }}_total_free" 
                        value="{{ geo_zone_settings[geo_zone.geo_zone_id].total_free }}" 
                        class="form-control" />
                    </div>
                  </div>
                </div>
                {% endfor %}
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
{{ footer }}
