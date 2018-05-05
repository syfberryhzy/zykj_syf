<?php

namespace App\Models;

use Encore\Admin\Traits\AdminBuilder;
use Encore\Admin\Traits\ModelTree;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class Category extends Model
{
    use ModelTree, AdminBuilder;
    protected $table = 'categories';

    public function __construct(array $attributes = [])
   {
       parent::__construct($attributes);

       $this->setParentColumn('parent_id');
       $this->setOrderColumn('sort');
       // $this->setTitleColumn('name');
   }

   public function product()
   {
     return $this->hasMany(Product::class);
   }
}
