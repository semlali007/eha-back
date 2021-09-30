<?php

namespace App\Controllers;
use Database\DBConnection;

class ConfigController extends Controller
{
  
    /**
     * AreaController constructor.
     * @param DBConnection $db
     */
    public function __construct(DBConnection $db, $roles = [])
    {
        parent::__construct($db, $roles);

    }

    public function getNews()
    {
        $response = [
            'viewed' => false,
            'infos' => [
                [
                  "title" => "Lorem ipsum dolor1",
                  "date" => "12/07/2021",
                  "info"=>"Donec placerat libero eget vulputate mattis. Fusce quis sollicitudin nulla. Nullam congue, justo et mollis lacinia, erat ipsum vestibulum nulla, ut molestie felis justo sed eros. Vivamus a condimentum dolor, et ultrices sapien. Donec elit arcu, mattis vel nibh ut, porttitor egestas justo."
                ],
                [
                    "title" => "Lorem ipsum dolor2",
                    "date" => "12/07/2021",
                    "info"=>"Donec placerat libero eget vulputate mattis. Fusce quis sollicitudin nulla. Nullam congue, justo et mollis lacinia, erat ipsum vestibulum nulla, ut molestie felis justo sed eros. Vivamus a condimentum dolor, et ultrices sapien. Donec elit arcu, mattis vel nibh ut, porttitor egestas justo."
                ],
                [
                    "title" => "Lorem ipsum dolor3",
                    "date" => "12/07/2021",
                    "info"=>"Donec placerat libero eget vulputate mattis. Fusce quis sollicitudin nulla. Nullam congue, justo et mollis lacinia, erat ipsum vestibulum nulla, ut molestie felis justo sed eros. Vivamus a condimentum dolor, et ultrices sapien. Donec elit arcu, mattis vel nibh ut, porttitor egestas justo."
                ]
              ]

        ];
        return  $this->response($response);
    }

    public function postCheck()
    {
        $response =[
            'viewed'=> true,
        ];
        return  $this->response($response);
    }

   
}
