<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HelloWorldController extends Controller
{
    /**
     * Display a simple "Hello, World!" message.
     *
     * @return \Illuminate\Http\Response
     */
    public function hello()
    {
        $message = 'Hello, World!';
        return view('hello', ['message' => $message]);
    }
}
