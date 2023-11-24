<?php

namespace App\Http\Controllers;

use App\Http\Requests\MerchantUpdateRequest;
use App\Http\Resources\ItemResource;
use App\Http\Resources\MerchantResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\VoucherResource;
use App\Models\Item;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\Review;
use App\Models\Transaction;
use App\Models\Voucher;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MerchantController extends Controller
{
    use HttpResponses;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Get all merchant's products.
     * 
     * @group Product
     * 
     * @authenticated
     * 
     * @queryParam status_id int Product status ID Example: 1
     * @queryParam category_id int Category ID Example: 1
     * @queryParam sub_category_id int Sub Category ID Example: 1
     * @queryParam name string Product name (fuzzy search) Example: Prod
     * @queryParam duration int Product duration Example: 1
     * @queryParam start_date string Product minimum start date (Y-m-d) Example: 2023-10-16
     * @queryParam end_date string Product maximum end date (Y-m-d) Example: 2023-10-20
     * @queryParam price_min int Product minimum price Example: 50000
     * @queryParam price_max int Product maximum price Example: 1000000
     * @queryParam person_min int Product minimum person Example: 1
     * @queryParam person_max int Product maximum person Example: 20
     * @queryParam sort_by string Sort by (default: updated_at) Example: updated_at
     * @queryParam sort_direction string Sort direction (ASC or DESC) (default: DESC) Example: DESC
     * @queryParam page_size int Page size (default: 15) Example: 15
     */
    public function indexProduct(Request $request)
    {
        $query = $request->query();

        $products = Product::where('merchant_id', Auth::user()->merchant->id)
                    ->when(isset($query['category_id']), function ($q) use ($query) {
                        $q->whereRelation('productSubCategory', 'product_category_id', $query['category_id']);
                    })
                    ->when(isset($query['sub_category_id']), function ($q) use ($query) {
                        $q->where('product_sub_category_id', $query['sub_category_id']);
                    })
                    ->when(isset($query['name']), function ($q) use ($query) {
                        $q->where('name', 'like', '%' . $query['name'] . '%');
                    })
                    ->when(isset($query['duration']), function ($q) use ($query) {
                        $q->where('duration', $query['duration']);
                    })
                    ->when(isset($query['start_date']), function ($q) use ($query) {
                        $q->where('start_date', '>=', $query['start_date']);
                    })
                    ->when(isset($query['end_date']), function ($q) use ($query) {
                        $q->where('end_date', '<=', $query['end_date']);
                    })
                    ->when(isset($query['price_min']), function ($q) use ($query) {
                        $q->where('price', '>=', $query['price_min']);
                    })
                    ->when(isset($query['price_max']), function ($q) use ($query) {
                        $q->where('price', '<=', $query['price_max']);
                    })
                    ->when(isset($query['person_min']), function ($q) use ($query) {
                        $q->where('min_person', '>=', $query['person_min']);
                    })
                    ->when(isset($query['person_max']), function ($q) use ($query) {
                        $q->where('max_person', '<=', $query['person_max']);
                    })
                    ->when(isset($query['status_id']), function ($q) use ($query) {
                        $q->where('product_status_id', $query['status_id']);
                    })
                    ->orderBy($query['sort_by'] ?? 'updated_at', $query['sort_direction'] ?? 'DESC')
                    ->paginate($query['page_size'] ?? 15);

        return ProductResource::collection($products);
    }

    /**
     * Get all merchant's transactions.
     * 
     * @group Transaction
     * 
     * @authenticated
     * 
     * @queryParam status_id int Transaction status ID Example: 50
     * @queryParam order_by string Order by (default: updated_at) Example: updated_at
     * @queryParam order_direction string Order direction (ASC or DESC) (default: DESC) Example: DESC
     * @queryParam page_size int Page size (default: 15) Example: 15
     */
    public function indexTransaction(Request $request)
    {
        $query = $request->query();

        $transactions = Transaction::whereHas('items', function ($query) {
                        $query->whereHas('product', function ($query) {
                            $query->where('merchant_id', auth()->user()->merchant->id);
                        });})
                        ->when(isset($query['status_id']), function ($q) use ($query) {
                            $q->where('transaction_status_id', $query['status_id']);
                        })
                        ->orderBy($query['order_by'] ?? 'updated_at', $query['order_direction'] ?? 'DESC')
                        ->paginate($query['page_size'] ?? 15);

        return TransactionResource::collection($transactions);
    }

    /**
     * Get all merchant's order's items.
     * 
     * @group Order Item
     * 
     * @authenticated
     * 
     * @queryParam quantity_min int Item minimum quantity Example: 2
     * @queryParam quantity_max int Item maximum quantity Example: 10
     * @queryParam start_date string Item minimum start date (Y-m-d) Example: 2023-10-16
     * @queryParam end_date string Item maximum end date (Y-m-d) Example: 2023-10-20
     * @queryParam order_by string Order by (default: updated_at) Example: updated_at
     * @queryParam order_direction string Order direction (ASC or DESC) (default: DESC) Example: DESC
     * @queryParam page_size int Page size (default: 15) Example: 15
     */
    public function indexItem(Request $request)
    {
        $query = $request->query();

        $items = Item::whereRelation('product', 'merchant_id', Auth::user()->merchant->id)
                ->when(isset($query['status_id']), function ($q) use ($query) {
                    $q->where('status_id', $query['status_id']);
                })
                ->when(isset($query['quantity_min']), function ($q) use ($query) {
                    $q->where('quantity', '>=', $query['quantity_min']);
                })
                ->when(isset($query['quantity_max']), function ($q) use ($query) {
                    $q->where('quantity', '<=', $query['quantity_max']);
                })
                ->when(isset($query['start_date']), function ($q) use ($query) {
                    $q->where('start_date', '>=', $query['start_date']);
                })
                ->when(isset($query['end_date']), function ($q) use ($query) {
                    $q->where('end_date', '<=', $query['end_date']);
                })
                ->orderBy($query['order_by'] ?? 'updated_at', $query['order_direction'] ?? 'DESC')
                ->paginate($query['page_size'] ?? 15);

        return ItemResource::collection($items);
    }

    /**
     * Get all merchant's reviews.
     * 
     * @group Review
     * 
     * @authenticated
     * 
     * @queryParam rating_min int Review minimum rating Example: 2
     * @queryParam rating_max int Review maximum rating Example: 5
     * @queryParam is_replied bool Review is replied Example: true
     * @queryParam order_by string Order by (default: updated_at) Example: updated_at
     * @queryParam order_direction string Order direction (ASC or DESC) (default: DESC) Example: DESC
     * @queryParam page_size int Page size (default: 15) Example: 15
     */
    public function indexReview(Request $request)
    {
        $query = $request->query();

        $reviews = Review::whereRelation('product', 'merchant_id', Auth::user()->merchant->id)
                ->when(isset($query['rating_min']), function ($q) use ($query) {
                    $q->where('rating', '>=', $query['rating_min']);
                })
                ->when(isset($query['rating_max']), function ($q) use ($query) {
                    $q->where('rating', '<=', $query['rating_max']);
                })
                ->when(isset($query['is_replied']), function ($q) {
                    $q->whereNotNull('reply');
                })
                ->orderBy($query['order_by'] ?? 'updated_at', $query['order_direction'] ?? 'DESC')
                ->paginate($query['page_size'] ?? 15);
        $reviews->load('user', 'product');

        return ReviewResource::collection($reviews);
    }

    /**
     * Get all merchant's vouchers.
     * 
     * @group Voucher
     * 
     * @authenticated
     * 
     * @queryParam voucher_type_id int Voucher type ID Example: 1
     * @queryParam name string Voucher name (fuzzy search) Example: Vouc
     * @queryParam code string Voucher code (fuzzy search) Example: Vouc
     * @queryParam nominal int Voucher nominal Example: 10000
     * @queryParam start_date string Voucher minimum start date (Y-m-d) Example: 2023-10-16
     * @queryParam end_date string Voucher maximum end date (Y-m-d) Example: 2023-10-20
     * @queryParam min_transaction int Voucher minimum transaction Example: 100000
     * @queryParam max_discount int Voucher maximum discount Example: 10000
     * @queryParam quota int Voucher quota Example: 100
     * @queryParam order_by string Order by (default: updated_at) Example: updated_at
     * @queryParam order_direction string Order direction (ASC or DESC) (default: DESC) Example: DESC
     * @queryParam page_size int Page size (default: 15) Example: 15
     */
    public function indexVoucher(Request $request)
    {
        $query = $request->query();

        $vouchers = Voucher::whereRelation('merchant', 'id', Auth::user()->merchant->id)
                ->when(isset($query['voucher_type_id']), function ($q) use ($query) {
                    $q->where('voucher_type_id', $query['voucher_type_id']);
                })
                ->when(isset($query['name']), function ($q) use ($query) {
                    $q->where('name', 'like', '%' . $query['name'] . '%');
                })
                ->when(isset($query['code']), function ($q) use ($query) {
                    $q->where('code', 'like', '%' . $query['code'] . '%');
                })
                ->when(isset($query['nominal']), function ($q) use ($query) {
                    $q->where('nominal', $query['nominal']);
                })
                ->when(isset($query['start_date']), function ($q) use ($query) {
                    $q->where('start_date', '>=', $query['start_date']);
                })
                ->when(isset($query['end_date']), function ($q) use ($query) {
                    $q->where('end_date', '<=', $query['end_date']);
                })
                ->when(isset($query['min_transaction']), function ($q) use ($query) {
                    $q->where('min_transaction', $query['min_transaction']);
                })
                ->when(isset($query['max_discount']), function ($q) use ($query) {
                    $q->where('max_discount', $query['max_discount']);
                })
                ->when(isset($query['quota']), function ($q) use ($query) {
                    $q->where('quota', $query['quota']);
                })
                ->orderBy($query['order_by'] ?? 'updated_at', $query['order_direction'] ?? 'DESC')
                ->paginate($query['page_size'] ?? 15);
        $vouchers->load('voucherType', 'merchant');

        return VoucherResource::collection($vouchers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Get merchant profile
     * 
     * @group Merchant
     * 
     * @authenticated
     */
    public function show()
    {
        $merchant = Merchant::with(
            'merchantProfile',
            'merchantLevel',
            'merchantStatus',
        )->find(Auth::user()->merchant->id);

        return $this->success(new MerchantResource($merchant), 'Merchant profile retrieved successfully');
    }

    /**
     * Update merchant profile
     * 
     * @group Merchant
     * 
     * @authenticated
     */
    public function update(MerchantUpdateRequest $request)
    {
        
        $validated = $request->validated();

        $merchant = $request->user()->merchant;

        if (isset($validated['logo'])) {
            $logo = $validated['logo'];
            $directory = 'merchants/logo/';
            $logo->storeAs('public/' . $directory, $logo->hashName(), 'local');
            $logo_url = url('/storage/' . $directory . $logo->hashName());

            Storage::disk('local')->delete('public/' . $directory . basename($merchant->logo));

            $merchant->update([
                'logo' => $logo_url,
            ]);
        }

        if (isset($validated['banner'])) {
            $banner = $validated['banner'];
            $directory = 'merchants/banners/';
            $banner->storeAs('public/' . $directory, $banner->hashName(), 'local');
            $banner_url = url('/storage/' . $directory . $banner->hashName());

            Storage::disk('local')->delete('public/' . $directory . basename($merchant->merchantProfile->banner));

            $merchant->merchantProfile->update([
                'banner' => $banner_url,
            ]);
        }


        DB::transaction(function () use ($merchant, $validated) {
            $merchant->update([
                'name' => $validated['name'] ?? $merchant->name,
                'is_highlight' => $validated['is_highlight'] ?? $merchant->is_highlight,
                'notes' => $validated['notes'] ?? $merchant->notes,
            ]);

            $merchant->merchantProfile()->update([
                'address' => $validated['address'] ?? $merchant->merchantProfile->address,
                'description' => $validated['description'] ?? $merchant->merchantProfile->description,
                'ktp_number' => $validated['ktp_number'] ?? $merchant->merchantProfile->ktp_number,
                'npwp_number' => $validated['npwp_number'] ?? $merchant->merchantProfile->npwp_number,
                'siup_number' => $validated['siup_number'] ?? $merchant->merchantProfile->siup_number,
            ]);
        });

        $merchant = Merchant::with(
            'merchantProfile',
            'merchantLevel',
            'merchantStatus',
        )->find($merchant->id);

        return $this->success(new MerchantResource($merchant) ,'Merchant profile updated successfully', 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Merchant $merchant)
    {
        //
    }
}
