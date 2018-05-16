<?php

namespace App\Api\Controllers;

use App\Models\Category;
use App\Models\Banner;
use App\Models\News;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Transformers\CategoryTransformer;
use App\Transformers\BannerTransformer;
use App\Transformers\ProductTransformer;

class IndexController extends Controller
{
    /*
    * 首页轮播
    */
    public function index()
    {
        $list = Banner::where('status', 1)->orderBy('sort', 'desc', 'created_at', 'desc')->get();
        return $this->response->collection($list, new BannerTransformer());
    }

    /**
    * 分类导航
    */
    public function categoryList()
    {
        $list = Category::where('status', 1)->orderBy('sort', 'desc', 'created_at', 'desc')->take(10)->get();
        return $this->response->collection($list, new CategoryTransformer());
    }
    /**
    * 公告列表--滚屏
    */
    public function newsList()
    {
      $list = News::where('status', 1)->orderBy('created_at', 'desc')->take(6)->get();
      return $this->response->array($list);
    }
    /**
    * 公告详情
    */
    public function newsDetail(News $news)
    {
      return $this->response->array($news);
    }

    public function productListByType($typeText = 'general', $sort = 0, $limit = null)
    {
        switch($typeText) {
            case 'general': $type = 0;
              break;
            case 'today': $type = 1;
              break;
            case 'customize': $type = 2;
              break;
            case 'limitTime': $type = 3;
              break;
            case 'mCoin': $type = 4;
              break;
            default: $type = 0;
              break;
        }

        $datas = Product::where(['status' => 1, 'type' => $type]);
        if ($limit) {
          $datas = $datas->take($limit);
        }
        switch ($sort) {
            case 0: $datas = $datas->orderBy('created_at', 'desc');
              break;
            case 1: $datas = $datas->orderBy('sale_num', 'desc');
              break;
            case 2: $datas = $datas->orderBy('pre_price', 'desc');
              break;
            case 3: $datas = $datas->orderBy('pre_price', 'asc');
              break;
            default: $datas = $datas->orderBy('created_at', 'desc');
              break;
        }
        $list = $datas->get();
        return $this->response->collection($list, new ProductTransformer());
    }

    public function productListByCate($category = 1, $sort = 0, $limit = null)
    {
        $datas = Product::where(['status' => 1, 'category_id' => $category]);
        if ($limit) {
          $datas = $datas->take($limit);
        }
        switch ($sort) {
            case 0: $datas = $datas->orderBy('created_at', 'desc');
              break;
            case 1: $datas = $datas->orderBy('sale_num', 'desc');
              break;
            case 2: $datas = $datas->orderBy('pre_price', 'desc');
              break;
            case 3: $datas = $datas->orderBy('pre_price', 'asc');
              break;
            default: $datas = $datas->orderBy('created_at', 'desc');
              break;
        }
        $list = $datas->get();
        return $this->response->collection($list, new ProductTransformer());
    }

    public function productListByName($title, $sort = 0, $limit = null)
    {
        $datas = Product::where('status', 1)->where('title', 'like', "%".trim($title)."%");
        if ($limit) {
          $datas = $datas->take($limit);
        }
        switch ($sort) {
            case 0: $datas = $datas->orderBy('created_at', 'desc');
              break;
            case 1: $datas = $datas->orderBy('sale_num', 'desc');
              break;
            case 2: $datas = $datas->orderBy('pre_price', 'desc');
              break;
            case 3: $datas = $datas->orderBy('pre_price', 'asc');
              break;
            default: $datas = $datas->orderBy('created_at', 'desc');
              break;
        }
        $list = $datas->get();
        return $this->response->collection($list, new ProductTransformer());
    }
}
