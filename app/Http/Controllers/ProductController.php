<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Brand;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use App\Exports\ProductExport;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    public function products(Request $request)
    {
        $sort_by = $request->input('sort_by', 'created_at');
        $sort_order = $request->input('sort_order', 'DESC');

        $query = Product::with('category', 'brand');

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('SKU', 'LIKE', "%{$search}%");
            });
        }

        
        if ($request->filled('category')) {
            $query->where('category_id', $request->input('category'));
            }
            
        if ($request->filled('brand')) {
            $query->where('brand_id', $request->input('brand'));
        }

        if ($request->has('status') && $request->input('status') !== null) {
            $query->where('status', $request->input('status'));
        }
            
        $products = $query->orderBy($sort_by, $sort_order)->paginate(10)->withQueryString();
        
        $categories = Category::select('id', 'name')->orderBy('name')->get();
        $brands = Brand::select('id', 'name')->orderBy('name')->get();
        
        return view('admin.products', compact('products', 'categories', 'brands'));
    }

    public function productAdd()
    {
        $categories = Category::select('id', 'name')->orderBy('name')->get();
        $brands = Brand::select('id', 'name')->orderBy('name')->get();
        
        return view('admin.product-add', compact('categories', 'brands'));
    }

    public function productStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products,slug',
            'short_description' => 'nullable|string|max:500',
            'information' => 'nullable|string',
            'description' => 'required|string',
            'regular_price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:regular_price',
            'SKU' => 'required|string|max:100|unique:products,SKU',
            'stock_status' => 'required|in:instock,outofstock',
            'quantity' => 'required|integer|min:0',
            'featured' => 'boolean',
            'status' => 'boolean',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $product = new Product();
        $product->name = $request->name;
        $product->slug = $request->slug;
        $product->short_description = $request->short_description;
        $product->information = $request->information;
        $product->description = $request->description;
        $product->regular_price = $request->regular_price;
        $product->sale_price = $request->sale_price;
        $product->SKU = $request->SKU;
        $product->stock_status = $request->stock_status;
        $product->featured = $request->boolean('featured');
        $product->status = $request->boolean('status');
        $product->quantity = $request->quantity;
        $product->category_id = $request->category_id;
        $product->brand_id = $request->brand_id;

        $current_timestamp = Carbon::now()->timestamp;

        if($request->hasFile('image')) 
        {
            $image = $request->file('image');
            $imageName = $current_timestamp . '.' . $image->extension();
            $this->resizeAndSaveImage($image, $imageName, '/uploads/products', 570, 604);
            $this->resizeAndSaveImage($image, $imageName, '/uploads/products/thumbnails', 270, 303);
            $product->image = $imageName;
        }
        else
        {
            return redirect()->back()->with('error', 'Product image is required');
        }

        $gallery_arr = array();
        $gallery_images = "";
        $counter = 1;

        if($request->hasFile('images'))
        {
            $allowedfileExtion = ['jpg', 'png', 'jpeg', 'webp'];
            $files = $request->file('images');

            foreach ($files as $file) 
            {
                $gextension = $file->getClientOriginalExtension();
                $gcheck = in_array($gextension,$allowedfileExtion);

                if($gcheck)
                {
                    $gimageName = $current_timestamp . '_' . $counter . '.' . $gextension;
                    $this->resizeAndSaveImage($file, $gimageName, '/uploads/products', 570, 604);
                    $this->resizeAndSaveImage($file, $gimageName, '/uploads/products/thumbnails', 270, 303);
                    array_push($gallery_arr,$gimageName);
                    $counter = $counter + 1;
                }
            }

            $gallery_images = implode(',',$gallery_arr);
        }

        $product->images = $gallery_images;

        $product->save();

        return redirect()->route('admin.products')->with('success', 'Product added successfully');
    }

    public function resizeAndSaveImage($image, $imageName, $folder, $width = 270, $height = 303)
    {
        $imagePath = public_path($folder);
        
        if (!file_exists($imagePath)) {
            mkdir($imagePath, 0755, true);
        }
        
        Image::decode($image)->resize($width, $height)->save($imagePath . '/' . $imageName);
        
    }

    public function productEdit($id)
    {
        $product = Product::findOrFail($id);
        $categories = Category::select('id', 'name')->orderBy('name')->get();
        $brands = Brand::select('id', 'name')->orderBy('name')->get();
        
        return view('admin.product-edit', compact('product', 'categories', 'brands'));
    }

    public function productUpdate(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products,slug,' . $product->id,
            'short_description' => 'nullable|string|max:500',
            'description' => 'required|string',
            'regular_price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:regular_price',
            'SKU' => 'required|string|max:100|unique:products,SKU,' . $product->id,
            'stock_status' => 'required|in:instock,outofstock',
            'quantity' => 'required|integer|min:0',
            'featured' => 'boolean',
            'status' => 'boolean',
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $product->name = $request->name;
        $product->slug = $request->slug ? Str::slug($request->slug) : Str::slug($request->name);
        $product->short_description = $request->short_description;
        $product->information = $request->information;
        $product->description = $request->description;
        $product->regular_price = $request->regular_price;
        $product->sale_price = $request->sale_price;
        $product->SKU = $request->SKU;
        $product->stock_status = $request->stock_status;
        $product->featured = $request->boolean('featured');
        $product->status = $request->boolean('status');
        $product->quantity = $request->quantity;
        $product->category_id = $request->category_id;
        $product->brand_id = $request->brand_id;

        $current_timestamp = Carbon::now()->timestamp;

        if($request->hasFile('image')) 
        {
            if($product->image && File::exists(public_path('uploads/products/' . $product->image))) 
            {
                @unlink(public_path('uploads/products/' . $product->image));
            }
            if($product->image && File::exists(public_path('uploads/products/thumbnails/' . $product->image))) 
            {
                @unlink(public_path('uploads/products/thumbnails/' . $product->image));
            }

            $image = $request->file('image');
            $imageName = $current_timestamp . '.' . $image->extension();
            $this->resizeAndSaveImage($image, $imageName, '/uploads/products', 570, 604);
            $this->resizeAndSaveImage($image, $imageName, '/uploads/products/thumbnails', 270, 303);

            $product->image = $imageName;
        }

        $gallery_arr = $product->images ? explode(',', $product->images) : [];
        if ($request->has('deleted_gallery_images') && is_array($request->deleted_gallery_images)) 
        {
            foreach ($request->deleted_gallery_images as $deletedImage)
            {
                if($deletedImage && File::exists(public_path('uploads/products/' . $deletedImage))) 
                {
                    @unlink(public_path('uploads/products/' . $deletedImage));
                }

                if($deletedImage && File::exists(public_path('uploads/products/thumbnails/' . $deletedImage))) 
                {
                    @unlink(public_path('uploads/products/thumbnails/' . $deletedImage));
                }

                if (($key = array_search($deletedImage, $gallery_arr)) !== false) 
                {
                    unset($gallery_arr[$key]);
                }
            }

            $gallery_arr = array_values($gallery_arr);
        }


        if($request->hasFile('images'))
        {
           
            $files = $request->file('images');
            $counter = 1;

            foreach ($files as $file) 
            {
                $gextension = $file->getClientOriginalExtension();                
                $gimageName = $current_timestamp . '_' . $counter . '.' . $gextension;
                $this->resizeAndSaveImage($file, $gimageName, '/uploads/products', 570, 604);
                $this->resizeAndSaveImage($file, $gimageName, '/uploads/products/thumbnails', 270, 303);
                array_push($gallery_arr,$gimageName);
                $counter++;                
            }

            $product->images = !empty($gallery_arr) ? implode(',',$gallery_arr) : null;
        }

        $product->save();

        return redirect()->route('admin.products')->with('success', 'Product updated successfully');
    }

    public function productDelete($id)
    {
        $product = Product::findOrFail($id);

         if($product->image && File::exists(public_path('uploads/products/' . $product->image))) 
        {
            @unlink(public_path('uploads/products/' . $product->image));
        }
        if($product->image && File::exists(public_path('uploads/products/thumbnails/' . $product->image))) 
        {
            @unlink(public_path('uploads/products/thumbnails/' . $product->image));
        }

        if ($product->images)
        {
            $gallery_images = explode(',', $product->images);
            foreach ($gallery_images as $gallery_image)
            {
                if($gallery_image && File::exists(public_path('uploads/products/' . $gallery_image))) 
                {
                    @unlink(public_path('uploads/products/' . $gallery_image));
                }

                if($gallery_image && File::exists(public_path('uploads/products/thumbnails/' . $gallery_image))) 
                {
                    @unlink(public_path('uploads/products/thumbnails/' . $gallery_image));
                }
            }
        }
        
        $product->delete();
        
        return redirect()->route('admin.products')->with('success', 'Product deleted successfully');
    }

    public function productsBulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:products,id'
        ]);

        $ids = $request->ids;
        $products = Product::whereIn('id', $ids)->get();

        foreach($products as $product)
        {
            if($product->image && File::exists(public_path('uploads/products/' . $product->image))) 
            {
                @unlink(public_path('uploads/products/' . $product->image));
            }
            
            if($product->image && File::exists(public_path('uploads/products/thumbnails/' . $product->image))) 
            {
                @unlink(public_path('uploads/products/thumbnails/' . $product->image));
            }
            
            if ($product->images)
            {
        
                $gallery_images = explode(',', $product->images);

                foreach ($gallery_images as $gallery_image)
                {
                    if($gallery_image && File::exists(public_path('uploads/products/' . $gallery_image))) 
                    {
                        @unlink(public_path('uploads/products/' . $gallery_image));
                    }

                    if($gallery_image && File::exists(public_path('uploads/products/thumbnails/' . $gallery_image))) 
                    {
                        @unlink(public_path('uploads/products/thumbnails/' . $gallery_image));
                    }
                }
            }

            $product->delete();
        }
        
        return back()->with('success', count($ids) . ' products deleted successfully');
    }

    public function productExport()
    {
        return Excel::download(new ProductExport, 'products.xlsx');
    }

}