<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Coupon;

class CouponController extends Controller
{
    public function coupons()
    {
        $coupons = Coupon::orderBy('expiry_date', 'DESC')->paginate(12);
        return view('admin.coupons',compact('coupons'));
    }

    public function coupon_add()
    {
        return view('admin.coupon-add');
    }

    public function coupon_store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:coupons,code',
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0',
            'cart_value' => 'required|numeric|min:0',
            'expiry_date' => 'required|date',
        ]);

        $coupon = new Coupon();
        $coupon->code = $request->code;
        $coupon->type = $request->type;
        $coupon->value = $request->value;
        $coupon->cart_value = $request->cart_value;
        $coupon->expiry_date = $request->expiry_date;

        $coupon->save();

        return redirect()->route('admin.coupons')->with('success', 'Coupon created successfully.');
    }

    public function coupon_edit(int $coupon_id)
    {
        $coupon = Coupon::findOrFail($coupon_id);

        return view('admin.coupon-edit', compact('coupon'));
    }

    public function coupon_update(Request $request, int $coupon_id)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:coupons,code,' . $coupon_id,
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0',
            'cart_value' => 'required|numeric|min:0',
            'expiry_date' => 'required|date',
        ]);

        $coupon = Coupon::findOrFail($coupon_id);
        $coupon->code = $request->code;
        $coupon->type = $request->type;
        $coupon->value = $request->value;
        $coupon->cart_value = $request->cart_value;
        $coupon->expiry_date = $request->expiry_date;

        $coupon->save();

        return redirect()->route('admin.coupons')->with('success', 'Coupon updated successfully.');
    }

    public function coupon_delete(int $coupon_id)
    {
        $coupon = Coupon::findOrFail($coupon_id);
        $coupon->delete();

        return redirect()->route('admin.coupons')->with('success', 'Coupon deleted successfully.');
    }
}