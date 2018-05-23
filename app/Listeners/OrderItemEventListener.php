<?php

namespace App\Listeners;

use App\Events\OrderItemEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\OrderItem;
use Auth;
class OrderItemEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  OrderItemEvent  $event
     * @return void
     */
    public function handle(OrderItemEvent $event)
    {
      $order = $event->order;

      $data = collect($event->carts)->map(function ($item, $key) use ($order) {
          return [
            'user_id' => auth()->user()->id,
            'order_id' => $order->id,
            'product_id' => $item['product_id'],
            'title' => $item['name'],
            'norm' => $item['size'],
            'num' => $item['qty'],
            'pre_price' => $item['price'],
            'total_price' => $item['total']
          ];
      });
      $datas = collect($data)->values()->toArray();
      // dd($datas);
      OrderItem::insert($datas);
    }
}
