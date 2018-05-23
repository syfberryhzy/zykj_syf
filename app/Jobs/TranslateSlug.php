<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Product;
use App\Models\ProductItem;

class TranslateSlug implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $operate;
    protected $orders;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($orders, $operate = true)
    {
        $this->orders = $orders;
        $this->operate = $operate;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $error = 0;
          \DB::beginTransaction();
          foreach ($this->orders as $val) {
              $item = ProductItem::find($val->id);
              if (!$item || $item->status != 1 || $item->product->status != 1) {
                  $error++;
              } else {
                $item->quantity -= $val->qty;
                if ($item->quantity >= 0) {
                  $item->save();
                } else {
                  $error++;
              }
              }
          }
          if ($error > 0) {
            //接收异常处理并回滚
            \DB::rollBack();
            return false;
          }
          return true;
          \Log::info('库存操作: '.$error);
    }
}
