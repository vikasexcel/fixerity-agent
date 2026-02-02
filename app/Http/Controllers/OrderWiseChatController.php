<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\UserPackageBooking;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Intervention\Image\Laravel\Facades\Image;
//use function App\Http\Controllers\Admin\Super\redirect;
//use function App\Http\Controllers\Admin\Super\response;
//use function App\Http\Controllers\Admin\Super\url;
//use function App\Http\Controllers\Admin\Super\view;

class OrderWiseChatController extends Controller
{
    public function __construct()
    {

    }

    //order wise chat
    public function getOrderWiseChat($slug, $order_id)
    {
        //fetching service category
        $service_category = ServiceCategory::select('id', 'category_type', 'name')->where('slug', $slug)->first();
        if ($service_category != null) {
            //fetching category type by service category
            $category_type = $service_category->category_type;
            return view('admin.pages.super_admin.chat.order_wise_chat.chat', compact('category_type', 'order_id', 'slug', 'service_category'));
        } else {
            Session::flash('error', 'Service Category Not Found!');
            return redirect()->back();
        }
    }

    //order wise chat ajax call
    public function getOrderWiseChatAjax(Request $request)
    {
        //fetching service category
        $service_category = ServiceCategory::select('id', 'category_type', 'name')->where('slug', $request->get('slug'))->first();
        if ($service_category != null) {
            //category type 1 - transport,2 - store, 3 & 4 for provider, 5 - courier , 6 - video
            //chat type 1 for customer-driver chat , 2 for customer-store chat , 3 for store-driver chat(it is only consider when order is for delivery service)
            //fetching order details according to service wise
            //fetching provider service/video order
            $order_detail = UserPackageBooking::query()->select('user_service_package_booking.id', 'user_service_package_booking.order_no', 'user_service_package_booking.user_id', 'user_service_package_booking.provider_id', 'users.first_name as user_name', 'providers.first_name as provider_name', 'providers.avatar as provider_image', 'users.avatar as user_image')
                ->join('users', 'users.id', 'user_service_package_booking.user_id')
                ->join('providers', 'providers.id', 'user_service_package_booking.provider_id')
                ->where('user_service_package_booking.id', '=', $request->get('order_id'))
                ->first();
            $provider_type = 'Provider';

            if ($order_detail != null) {
                $firebase_service = new FirebaseService();
                //fetching chat history
                $chat_history = ($firebase_service->fetchChatHistory($order_detail->order_no, $order_detail->id, 'order_chat'));
                $chat_order_id = $firebase_service->CreateOrderNumberForChat($order_detail->order_no, $order_detail->id);
                //sender and receiver name to display & their id to compare to show record
                $sender_user_name = 'User - ' . $order_detail->user_name;//for showing sender user will be customer
                $sender_user_id = 'u_' . $order_detail->user_id;//for showing sender user will be customer
                $receiver_user_name = $receiver_user_id = '';
                $receiver_image = "";
                $append_chat_data = '';
                //chat type 1 for customer-driver chat , 2 for customer-store chat , 3 for store-driver chat(it is only consider when order is for delivery service)

                //other services excluding delivery service
                $chat_history = isset($chat_history['p_' . $order_detail->provider_id . '_u_' . $order_detail->user_id]) ? $chat_history['p_' . $order_detail->provider_id . '_u_' . $order_detail->user_id] : null;

                $receiver_user_name = $provider_type . ' - ' . $order_detail->provider_name;//for showing receiver user will be provider/driver
                $receiver_user_id = 'p_' . $order_detail->provider_id;//for showing receiver user will be provider/driver

                $receiver_image = $sender_image = url('/assets/front/img/clients/default.png?v=0.3');
                if ($order_detail->provider_image != null) {
                    $receiver_image = url('/assets/images/profile-images/provider/' . $order_detail->provider_image);
                }
                if ($order_detail->user_image != null) {
                    $sender_image = url('/assets/images/profile-images/customer/' . $order_detail->user_image);
                }

                //loop of the chat messages to prepare the html body to display
                if ($chat_history != null) {
                    foreach ($chat_history as $chat_message) {
                        //receiver chat user will be shown left side so need to put condition
                        $class = ($receiver_user_id == $chat_message['sender_id']) ? 'received-chat' : 'send-chat';
                        $name = ($receiver_user_id == $chat_message['sender_id']) ? $receiver_user_name : $sender_user_name;
                        $image = ($receiver_user_id == $chat_message['sender_id']) ? $receiver_image : $sender_image;
                        $append_chat_data = $append_chat_data . '<div class="row m-b-20 ' . $class . '">
                                                    <div class="col-auto">
                                                        <img src="' . $image . '" alt="user image" class="img-radius img-40">
                                                    </div>
                                                    <div class="col">
                                                        <div class="msg">
                                                            <p class="m-b-0">' . $chat_message['message'] . '</p>
                                                        </div>
                                                        <p class="text-muted m-b-0 text-time-size">' . $name . '</p>
                                                        <p class="text-muted m-b-0 text-time-size"><i
                                                                class="fa fa-clock-o m-r-10"></i> ' . date("d/m/Y H:i:s", substr((int)$chat_message['date'], 0, 10)) . ' </p>
                                                    </div>
                                                </div>';
                    }
                } else {
                    $append_chat_data = '<div class="text-center">A chat between them has not been initiated.</div>';
                }


                return response()->json([
                    'success' => true,
                    'chat_history' => $append_chat_data ?? null,
                    'sender_user_name' => $sender_user_name ?? null,
                    'sender_user_id' => $sender_user_id ?? null,
                    'receiver_user_name' => $receiver_user_name ?? null,
                    'receiver_user_id' => $receiver_user_id ?? null,
                    'chat_order_id' => $chat_order_id ?? null,
                ]);
            } else {
                Session::flash('error', 'Order Details Not Found!');
                return response()->json([
                    'success' => false,
                    'message' => 'Order Details Not Found!'
                ]);
            }
        } else {
            Session::flash('error', 'Service Category Not Found!');
            return response()->json([
                'success' => false,
                'message' => 'Service Category Not Found!'
            ]);
        }
    }
}
