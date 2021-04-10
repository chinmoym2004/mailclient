<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SystemSp;

class SpController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $sps = SystemSp::all();
        return view('sp.index',compact('sps'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $view = view('sp.create')->render();
        return response()->json(['html'=>$view]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        $this->validate($request,[
            'sp_name'=>'required',
            'raw_query'=>'required',
            'out_fields'=>'required'
        ]);
        try
        {
            \DB::beginTransaction();

            $sp_details = <<<"FILE_CONTENTS"
DROP procedure IF EXISTS `$request->sp_name`;

DELIMITER $$
CREATE PROCEDURE `$request->sp_name`()
BEGIN
$request->raw_query;
END$$

DELIMITER
FILE_CONTENTS;
            $newsp = new SystemSp;
            $newsp->sp_name = $request->sp_name;
            $newsp->raw_query = $request->raw_query;
            $newsp->sp_details = $sp_details;
            $newsp->migrated = 0;
            $newsp->in_fields = $request->in_fields;
            $newsp->out_fields = $request->out_fields;
            $newsp->save();

            \DB::statement($newsp->sp_details);

            \DB::commit();
            return response()->json(['redirect_to'=>url('/sps')],200);
            
        } catch (Exception $e) {
            \DB::rollback();
            report($e);
            return response()->json(['message'=>'Someting went wrong.\nError: '.$e->getMessage()],400);
        }

        // CREATE PROCEDURE name_of_SP [(nameOfParameter1 datatypeOfParameter1 [,nameOfParameteri datatypeOfParameteri])] BEGIN
        // // Declaration part of stored procedure
        // // Execution part of stored procedure
        // END;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
