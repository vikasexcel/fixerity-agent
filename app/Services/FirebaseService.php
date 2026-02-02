<?php

namespace App\Services;

use Kreait\Firebase\Factory;

class FirebaseService
{
    private $firebase;
    private $chat_domain;

    /* To connect with the firebase real time database */
    public function __construct(){
        $get_host = request()->getHost();
        $this->chat_domain = preg_replace("/[\s_\-\.]/", "-",$get_host);
        $this->firebase = (new Factory)
            ->withServiceAccount(config("firebase-cloud-messaging.configurations"))
            ->withDatabaseUri("https://fixerity-app-default-rtdb.firebaseio.com")->createDatabase();
    }

    /* Deleting the chat when order is completed*/
    public function deleteOrderChat($order_no,$order_id,$chat_type='order_chat'){
//        info("deleteOrderChat");
        $chat_order_id = $this->CreateOrderNumberForChat($order_no,$order_id);
//        info($chat_order_id);
//        info($this->chat_domain."/".$chat_type."/".$chat_order_id);
        $this->firebase->getReference($this->chat_domain."/".$chat_type."/".$chat_order_id)->remove();
        return 1;
    }

    //fetching the chat history
    public function fetchChatHistory($order_no,$order_id,$chat_type){
        //order_chat for chat wise order & ticket_chat for ticket wise chat
        $chat_order_id = $this->CreateOrderNumberForChat($order_no,$order_id);
        $this->chat_domain = "fixerity-com";
        $chat_history = $this->firebase->getReference($this->chat_domain."/".$chat_type."/".$chat_order_id);

//        return response()->json($chat_history->getValue());
        return $chat_history->getValue();
    }

    /* return Fire Base Instance for reading or any other firebase stuffs */
    public function getDatabase(){
        return $this->firebase;
    }
    //creating order number for the chat
    public function CreateOrderNumberForChat($value1,$value2){
        return $value1.'-'.$value2;
    }
}
