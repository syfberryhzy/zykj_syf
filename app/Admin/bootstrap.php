<?php

/**
 * Laravel-admin - admin builder based on Laravel.
 * @author z-song <https://github.com/z-song>
 *
 * Bootstraper for Admin.
 *
 * Here you can remove builtin form field:
 * Encore\Admin\Form::forget(['map', 'editor']);
 *
 * Or extend custom form field:
 * Encore\Admin\Form::extend('php', PHPEditor::class);
 *
 * Or require js and css assets:
 * Admin::css('/packages/prettydocs/css/styles.css');
 * Admin::js('/packages/prettydocs/js/main.js');
 *
 */

Encore\Admin\Form::forget(['map', 'editor']);

use App\Admin\Extensions\WangEditor;
use Encore\Admin\Form;
use App\Admin\Extensions\Column\ExpandRow;
// use App\Admin\Extensions\Column\OpenMap;
// use App\Admin\Extensions\Column\FloatBar;
// use App\Admin\Extensions\Column\UrlWrapper;
use Encore\Admin\Grid\Column;
 // Admin::js('/packages/clipboard/dist/clipboard.min.js');
 Form::extend('editor', WangEditor::class);

 Column::extend('expand', ExpandRow::class);
 // Column::extend('openMap', OpenMap::class);
 // Column::extend('floatBar', FloatBar::class);
 // Column::extend('urlwrapper', UrlWrapper::class);
 // Column::extend('prependIcon', function ($value, $icon) {
 //     return "<span style='color: #999;'><i class='fa fa-$icon'></i>  $value</span>";
 // });
 // app('view')->prependNamespace('admin', resource_path('views/admin'));
