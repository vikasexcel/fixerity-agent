<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Model;

class UserPackageBooking extends Model
{
    protected $table = 'user_service_package_booking';

    public function generateBookingNo()
    {
        $this->order_no = date('siHYdm');
        $this->save();
        return $this->order_no;
    }

    public function BookingCost($booking_price, $get_tax, $admin_commission, $promo_code_discount,$get_refer_discount_price=0,$extra_amount=0)
    {
//        $this->total_item_cost = round($booking_price, 2);
//        $this->save();
//        $this->tax = round((($this->total_item_cost * $get_tax) / 100), 2);
//        $this->save();
//        $this->total_pay = round(($this->total_item_cost + $this->tax), 2);
//        $this->save();
//        $this->provider_amount = round(($this->total_pay) - (($this->total_pay * $admin_commission)) / 100);
//        $this->save();
//        return "success";
        $this->total_item_cost = round($booking_price, 2);
        $this->save();

        $total_item_cost = ($this->total_item_cost - $promo_code_discount) - $get_refer_discount_price + $extra_amount;
        $this->tax = round((($total_item_cost * $get_tax) / 100), 2);
        $this->save();
        $this->total_pay = round(($total_item_cost + $this->tax), 2);
        $this->save();
        //$this->provider_amount = round(($this->total_pay) - (($this->total_pay * $admin_commission)) / 100);

        $this->provider_amount = round($this->total_item_cost + $extra_amount, 2);
        $commission = (($this->provider_amount * $admin_commission) / 100);
        $admin_commission = round($commission ,2);

        $this->admin_commission = round(($admin_commission - $promo_code_discount) - $get_refer_discount_price, 2);
        $this->provider_amount -= $commission;
        $this->save();
        return "success";
    }
}
