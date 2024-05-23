<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\PaymentTypeResource ;
use App\Models\payment_type;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PaymentTypeController extends Controller
{
    public function index()
    {
            $pt = payment_type::all();
    
            $arr = [
                'status' => true,
                'message' => 'Danh sách ma giam gia',
                'data' => PaymentTypeResource::collection($pt)
            ];
    
            return response()->json($arr, 200);
         
    }
    
    public function store(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'payment_type_name' => 'required',
        ]);
        if ($validator->fails()) {
            $arr = [
                'success' => false,
                'message' => 'Lỗi kiểm tra dữ liệu',
                'data' => $validator->errors()
            ];
            return response()->json($arr, 200);
        }
        $pt = payment_type::create($input);
        $arr = [
            'status' => true,
            'message' => "đã lưu thành công",
            'data' => new PaymentTypeResource($pt)
        ];
        return response()->json($arr, 201);
    }
    public function destroy(string $id)
    {
        //chỉ admin được chỉnh sửa
        try {
            $pt = payment_type::findOrFail($id);
            $pt->delete();

            $arr = [
                'status' => true,
                'message' => 'đã được xóa thành công',
                'data' => null
            ];

            return response()->json($arr, 200);
        } catch (ModelNotFoundException $e) {
            $arr = [
                'success' => false,
                'message' => ' không tồn tại',
                'data' => null
            ];

            return response()->json($arr, 404);
        }
    }
}
