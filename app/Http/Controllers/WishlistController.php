<?php

namespace App\Http\Controllers;

use App\Facades\Cart;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function add_to_wishlist(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer',
            'name' => 'required|string',
            'quantity' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
        ]);
        
       Cart::instance('wishlist')->add($request->product_id, $request->name, $request->quantity, $request->price)->associate('App\Models\Product');
        
        return back()->with('success', 'Product added to wishlist successfully!');
    }
    
    public function index()
    {        
        return view('wishlist.index');
    }

    public function remove_from_wishlist(string $rowId)
    {
        Cart::instance('wishlist')->remove($rowId);
        
        return back()->with('success', 'Product removed from wishlist successfully!');
    }

    public function clear_wishlist()
    {
        Cart::instance('wishlist')->destroy();
        
        return back()->with('success', 'Wishlist cleared successfully!');
    }

    public function move_to_cart(string $rowId)
    {
        $item = Cart::instance('wishlist')->get($rowId);
        
        Cart::instance('wishlist')->remove($rowId);
        Cart::instance('cart')->add($item->id, $item->name, $item->qty, $item->price)->associate('App\Models\Product');
        
        return back()->with('success', 'Product moved to cart successfully!');
    }
}