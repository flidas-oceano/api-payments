<?php

namespace App\Http\Controllers;

use App\Models\MethodContact;
use App\Http\Requests\StoreMethodContactRequest;
use App\Http\Requests\UpdateMethodContactRequest;

class MethodContactController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $methods = MethodContact::all();
        return response()->json($methods);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreMethodContactRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreMethodContactRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MethodContact  $methodContact
     * @return \Illuminate\Http\Response
     */
    public function show(MethodContact $methodContact)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\MethodContact  $methodContact
     * @return \Illuminate\Http\Response
     */
    public function edit(MethodContact $methodContact)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateMethodContactRequest  $request
     * @param  \App\Models\MethodContact  $methodContact
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateMethodContactRequest $request, MethodContact $methodContact)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MethodContact  $methodContact
     * @return \Illuminate\Http\Response
     */
    public function destroy(MethodContact $methodContact)
    {
        //
    }
}
