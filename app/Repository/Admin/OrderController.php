<?php

namespace App\Http\Controllers;

use App\Events\NewOrder;
use App\Http\Requests\AddRateRequest;
use App\Http\Requests\Order\AddOrderRequest;
use App\Http\Requests\Order\EditOrderRequest;
use App\Http\Resources\BillResource;
use App\Http\Resources\OrderResource;
use App\Models\Bill;
use App\Models\Branch;
use App\Models\ExtraIngredient;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderProductExtraIngredient;
use App\Models\Product;
use App\Models\ProductExtraIngredient;
use App\Models\Table;
use App\Types\OrderStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ApiResponseTrait;

    public function show(Order $order)
    {
        return $this->apiResponse(OrderResource::make($order), 'success', 200);
    }

    public function getByBranch(Branch $branch)
    {
        $orders = $branch->order()->get();

        return $this->apiResponse(OrderResource::collection($orders), 'success', 200);

    }

    public function getByTable(Table $table)
    {
        $orders = $table->order()->get();

        return $this->apiResponse(OrderResource::collection($orders), 'success', 200);

    }

    public function createBill()
    {
        $bill = Bill::create([
            'price' => 0,
            'is_paid' => 0,
        ]);

        return $bill;
    }

    public function createOrder($request, $bill)
    {
        $order = Order::create([
            'takeaway' => false,
            'status' => OrderStatus::BEFOR_PREPARING,
            'is_paid' => 0,
            'is_update' => 0,
            'time' => Carbon::now()->format('H:i:s'),
            'table_id' => $request['table_id'],
            'branch_id' => $request['branch_id'],
            'bill_id' => $bill->id,

        ]);

        return $order;
    }

    public function createOrderTakeaway($request, $bill)
    {
        $order = Order::create([
            'takeaway' => true,
            'status' => OrderStatus::BEFOR_PREPARING,
            'is_paid' => 0,
            'is_update' => 0,
            'time' => Carbon::now()->format('H:i:s'),
            'table_id' => $request['table_id'],
            'branch_id' => $request['branch_id'],
            'bill_id' => $bill->id,

        ]);

        return $order;
    }

    public function createOrderProduct($request, $order)
    {
        $totalPrice = 0;
        foreach ($request->products as $productData) {
            $product = Product::where('branch_id', $request->branch_id)->find($productData['product_id']);
            $orderProduct = OrderProduct::create([
                'order_id' => $order->id,
                'product_id' => $product['id'],
                'qty' => $productData['qty'],
                'note' => $productData['note'],
                'subTotal' => $product['price'] * $productData['qty'],
            ]);
            $totalPrice += $orderProduct['subTotal'];
            if (isset($productData['removedIngredients'])) {
                foreach ($productData['removedIngredients'] as $removedIngredientData) {
                    $ing = Ingredient::find($removedIngredientData['remove_id']);
                    $orderProduct->ingredients()->attach($ing->id);

                }
            }
            if (isset($productData['extraIngredients'])) {
                foreach ($productData['extraIngredients'] as $ingredientData) {
                    $extraingredient = ExtraIngredient::find($ingredientData['ingredient_id']);
                    $qtyExtra = ProductExtraIngredient::where('product_id', $product->id)->where('extra_ingredient_id', $extraingredient->id)->first();
                    $sub = $qtyExtra['price_per_piece'] * $productData['qty'];
                    OrderProductExtraIngredient::create([
                        'order_product_id' => $orderProduct->id,
                        'extra_ingredient_id' => $extraingredient['id'],

                    ]);
                    $totalPrice += $sub;
                }
            }

        }

        return $totalPrice;
    }

    public function MaxEstimatedTime($request, $order)
    {
        foreach ($request->products as $productData) {
            $product = Product::where('branch_id', $request->branch_id)->find($productData['product_id']);
            $estimatedTimesInSeconds = [];
            $estimated = \Carbon\Carbon::parse($product['estimated_time']);
            $estimatedTimesInSeconds[] = $estimated;
        }
        $maxEstimatedTimeInSeconds = max($estimatedTimesInSeconds);
        $maxEstimatedTimeFormatted = \Carbon\Carbon::parse($maxEstimatedTimeInSeconds)->format('H:i:s');
        $order->estimatedForOrder = $maxEstimatedTimeFormatted;
        $order->save();

    }

    public function tax($order, $totalPrice)
    {
        $orderTax = (intval($order->branch->taxRate) / 100);
        $order->total_price = $totalPrice + ($totalPrice * $orderTax);
        $order->save();
    }

    public function updateBill($bill, $order)
    {
        $bill->update([
            'price' => $order->total_price,
            'is_paid' => $order->is_paid,
        ]);
    }

    public function store(AddOrderRequest $request)
    {
        $request->validated();
        $Table = Table::where('branch_id', $request->branch_id)->where('table_num', 1111)->first();
        $TableID = $Table->id;
        $order = Order::where('table_id', $request->table_id)->where('table_id', '!=', $TableID)->where('branch_id', $request->branch_id)->where('is_paid', 0)->latest()->first();
        if ((! $TableID || $request->table_id !== $TableID) && ! $order) {
            $bill = $this->createBill();
            $order = $this->createOrder($request, $bill);
            $totalPrice = $this->createOrderProduct($request, $order);
            $this->MaxEstimatedTime($request, $order);
            $this->tax($order, $totalPrice);
            $this->updateBill($bill, $order);
            event(new NewOrder($order));

            return $this->apiResponse(($order), 'Data Saved successfully', 201);

        } elseif (! $order && $request->table_id === $TableID) {
            $bill = $this->createBill();
            $order = $this->createOrderTakeaway($request, $bill);
            $totalPrice = $this->createOrderProduct($request, $order);
            $this->MaxEstimatedTime($request, $order);
            $this->tax($order, $totalPrice);
            $this->updateBill($bill, $order);

            event(new NewOrder($order));

            return $this->apiResponse(($order), 'Data Saved successfully', 201);
        } else {
            $bill = $order->bill_id;
            $order = Order::create([
                'takeaway' => false,
                'status' => OrderStatus::BEFOR_PREPARING,
                'is_paid' => 0,
                'is_update' => 0,
                'time' => Carbon::now()->format('H:i:s'),
                'table_id' => $request['table_id'],
                'branch_id' => $request['branch_id'],
                'bill_id' => $bill,
            ]);
            $totalPrice = $this->createOrderProduct($request, $order);
            $this->MaxEstimatedTime($request, $order);
            $this->tax($order, $totalPrice);
            $this->BillOrder($order);
            event(new NewOrder($order));

            return $this->apiResponse(($order), 'Data Saved successfully', 201);
        }
    }

    public function BillOrder($order)
    {
        $billOrder = Bill::where('id', $order->bill_id)->where('is_paid', 0)->first();
        $billOrder->update([
            'price' => $billOrder->price + $order->total_price,
            'is_paid' => $order->is_paid,
        ]);
    }

    public function changeBill($order)
    {
        $bill = $order->bill_id;
        $billOrder = Bill::where('id', $bill)->where('is_paid', 0)->first();
        $billOrder->update([
            'price' => $billOrder->price - $order->total_price,
        ]);
        $order->delete();
    }

    public function update(EditOrderRequest $request, Order $order)
    {
        $request->validated();
        if ($order->status == 1 && $order->is_paid == 0) {

            $this->changeBill($order);

            $order = Order::create([
                'status' => OrderStatus::BEFOR_PREPARING,
                'is_paid' => 0,
                'is_update' => 1,
                'time' => Carbon::now()->format('H:i:s'),
                'table_id' => $request['table_id'],
                'branch_id' => $request['branch_id'],
                'bill_id' => $order->bill_id,
            ]);
            $totalPrice = $this->createOrderProduct($request, $order);
            $this->MaxEstimatedTime($request, $order);
            $this->tax($order, $totalPrice);
            $this->BillOrder($order);
            event(new NewOrder($order));

            return $this->apiResponse(($order), 'Data Saved successfully', 201);

        }
    }

    public function delete(Order $order)
    {
        $order->delete();

        return $this->apiResponse(null, 'Data Deleted', 200);
    }

    public function getOrderForEdit(Request $request)
    {
        $Table = Table::where('branch_id', $request->branch_id)->where('table_num', 1111)->first();
        $TableID = $Table->id;
        $order = Order::where('table_id', $request->table_id)->where('table_id', '!=', $TableID)->where('branch_id', $request->branch_id)->where('is_paid', 0)->latest()->first();
        if ($order && $order->status == 1) {
            return $this->apiResponse(OrderResource::make($order), 'success', 200);
        } elseif ($order) {
            return $this->apiResponse(($order), 'This order is under preparation', 200);
        } else {
            return $this->apiResponse(null, 'Not Found', 404);

        }
    }

    public function getOrderforRate(Branch $branch, Table $table)
    {
        $Table = Table::where('branch_id', $branch->id)->where('table_num', 1111)->first();
        $TableID = $Table->id;
        $bill = Bill::where('is_paid', 0)->whereHas('order', fn ($query) => $query->where('table_id', $table->id)->where('table_id', '!=', $TableID)->where('branch_id', $branch->id)->where('is_paid', 0)
            ->where('serviceRate', null)->where('feedback', null)
        )
            ->latest()->first();
        if ($bill) {
            $orderProduct = $bill->order()->with('product')->get()->pluck('product')->unique();

            $collection = $orderProduct->map(function ($array) {
                return collect($array)->unique('id');
            });
            $uniqueProducts = $collection->flatten(1)->unique('id')->values();
            $response = [
                'bill_id' => $bill->id,
                'products' => $uniqueProducts->toArray(),
            ];

            return $this->apiresponse($response, 'done', 200);
        } else {
            return $this->apiresponse(null, 'not found', 404);
        }

    }

    public function AddRate(AddRateRequest $request, Bill $bill)
    {
        $request->validated();
        if ($bill->is_paid == 0) {
            $orders = $bill->order()->where('bill_id', $bill->id)->where('is_paid', 0)->get();
            foreach ($orders as $order) {
                $order->update([
                    'serviceRate' => $request->serviceRate,
                    'feedback' => $request->feedback,
                ]);
            }

            return $this->apiResponse(BillResource::make($bill), 'Saved Successfully', 201);
        }

    }
}
