<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $order = Order::all();
            return response()->json($order);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'status' => 'required|in:preparing',
                'requested_date' => 'required|string',
            ]);

            $order = Order::create([
                'customer_id' => $request->customer_id,
                'status' => $request->status,
                'requested_date' => now(),
            ]);
            return response()->json([
                'message' => 'El pedido se ha creado correctamente',
                'order' => $order,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $order = Order::find($id);

            if (!$order) {
                return response()->json(['error' => 'No se ha encontrado el pedido'], 404);
            }

            return response()->json(['data' => $order], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Se produjo un error al procesar la solicitud: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateShipped(Request $request, string $id)
    {
        try {
            $order = Order::findOrFail($id);
    
            // Verificar si el estado se está actualizando a 'shipped' y no estaba en 'shipped' antes.
            if ($order->status !== 'shipped' && $request->input('status') === 'shipped') {
                foreach ($order->productOrders as $productOrder) {
                    if ($productOrder->product->stock < $productOrder->unit_quantity) {
                        return response()->json(['error' => 'No hay suficiente stock para completar este pedido'], 400);
                    }
    
                    // Restar la cantidad del stock del producto
                    $productOrder->product->decrement('stock', $productOrder->unit_quantity);
                }
                // Actualizar el estado de la orden
                $order->status = $request->input('status');
                $order->save();
        
                return response()->json(['success' => 'El estado del pedido se ha actualizado correctamente'], 200);
            } else {
                return response()->json(['failure' => 'El estado del pedido no ha podido ser actualizado'], 405);
            }
    
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ha ocurrido un error al actualizar el estado del pedido. Detalles: ' . $e->getMessage()], 500);
        }
    }

    public function updateCancelled(Request $request, string $id)
    {
        try {
            $order = Order::findOrFail($id);

            if ($order->status === 'preparing' && $request->input('status') === 'cancelled') {

                // Actualizar el estado de la orden
                $order->update([
                    'status' => $request->input('status'),
                ]);

                return response()->json(['success' => 'El estado del pedido ha pasado a cancelado'], 200);
            } else {
                return response()->json(['failure' => 'El estado del pedido no ha podido ser actualizado'], 405);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ha ocurrido un error al actualizar el estado del pedido. Detalles: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(string $id)
    // {
    //     //
    // }
}
