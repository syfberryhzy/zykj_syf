<?php

namespace App\Repositories;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\OrderRepository;
use App\Models\Order;
use App\Models\OrderItem;
use App\Validators\OrderValidator;

/**
 * Class OrderRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class OrderRepositoryEloquent extends BaseRepository implements OrderRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return Order::class;
    }



    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }
    /**
    * 检测库存是否足够下单
    */
    public function isEnoughArr($orders)
    {
        foreach($orders as $val) {
          $item = ProductItem::find($val->id);
          if (!$item || $item->quantity < $val->qty) {
            return false;
          }
        }
        return true;
    }

    public function isEnough($order, $item)
    {
        $item = ProductItem::find($val->id);
        if (!$item || $item->quantity < $val->qty) {
          return false;
        }
    }

    public function decrementQty($item)
    {
        $proItem  = ProductItem::find($val->id);
        if (!$proItem || $proItem->status) {
          return false;
        }
        $proItem->quantity -= $item->qty;
        if ($proItem->quantity < 0) {
          return fasle;
        } else {
          $proItem->save();
        }
    }
}
