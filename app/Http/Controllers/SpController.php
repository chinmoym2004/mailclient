<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SystemSp;
use Illuminate\Support\Facades\DB;

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
            DB::beginTransaction();

            $sp_details = "CREATE PROCEDURE `".$request->sp_name."`() BEGIN ".$request->raw_query."; END";

            $newsp = new SystemSp;
            $newsp->sp_name = $request->sp_name;
            $newsp->raw_query = $request->raw_query;
            $newsp->sp_details = $sp_details;
            $newsp->migrated = 1;
            $newsp->in_fields = $request->in_fields;
            $newsp->out_fields = $request->out_fields;
            $newsp->save();

            DB::connection()->getPdo()->exec("DROP procedure IF EXISTS `".$request->sp_name."`");
            DB::connection()->getPdo()->exec($newsp->sp_details);

            DB::commit();
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
        $sp = SystemSp::find(decrypt($id));
        $outfields = $sp->out_fields?json_decode($sp->out_fields):null;
        
        $datas = DB::select("call ".$sp->sp_name."(null,'1991-05-01','1991-05-01')");  /// Supply null,'1991-05-01','1991-05-01' as user input to manupulate the report
        
        return view('sp.show',compact('sp','outfields','datas'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $sp = SystemSp::find(decrypt($id));
        $view = view('sp.edit',compact('sp'))->render();
        return response()->json(['html'=>$view]);
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
        //print_r($request->all());
        $this->validate($request,[
            'sp_name'=>'required',
            'raw_query'=>'required',
            'out_fields'=>'required'
        ]);

        $infields = $request->in_fields?json_decode($request->in_fields,true):null;

        $inf = '';
        foreach ($infields as $key => $value) {
            if($inf=='')
                $inf.='IN '.$key.' '.$value;
            else
                $inf.=',IN '.$key.' '.$value;
        }
        //echo $outf;
        try
        {
            DB::beginTransaction();

            $sp_details = "CREATE PROCEDURE `".$request->sp_name."`(".$inf.") BEGIN ".$request->raw_query."; END";

            $newsp = SystemSp::find(decrypt($id));

            $newsp->sp_name = $request->sp_name;
            $newsp->raw_query = $request->raw_query;
            $newsp->sp_details = $sp_details;
            $newsp->migrated = 1;
            $newsp->in_fields = $request->in_fields;
            $newsp->out_fields = $request->out_fields;
            $newsp->save();

            DB::connection()->getPdo()->exec("DROP procedure IF EXISTS `".$request->sp_name."`");
            DB::connection()->getPdo()->exec($newsp->sp_details);

            DB::commit();
            return response()->json(['redirect_to'=>url('/sps')],200);
            
        } catch (Exception $e) {
            \DB::rollback();
            report($e);
            return response()->json(['message'=>'Someting went wrong.\nError: '.$e->getMessage()],400);
        }
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
