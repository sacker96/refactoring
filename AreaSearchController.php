<?php

namespace App\Http\Controllers\Backend;

use App\Http\Requests\AreaDisplayRequest;
use App\Http\Requests\AreaSearchRequest;
use App\Models\Area2Search;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Exports\AgentExport;
use App\Http\Requests\AgentsRequest;
use App\Models\Agent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\City;
use App\Models\Area1Display;


class AreaDisplayController extends ParentController
{
    public function index(Request $request)
    {
        if($request->session()->exists(SESSION_AREA_DISPLAY_SELECTED)) {
            $cityID = $request->session()->get(SESSION_AREA_DISPLAY_SELECTED);
            $areas = Area1Display::getAllByCityIdPluck($cityID);
        }else {
            $areas = [];
        }
        $cities = City::all();
        return view('area_display.index', compact('cities', 'areas'));
    }

    public function display(Request $request)
    {
        $cityID = $request->get('city_id');
        $request->session()->put(SESSION_AREA_DISPLAY_SELECTED, $cityID);
    }

    public function delete($areaID)
    {
        $area = Area1Display::class;
        $user = Auth::guard('admin')->user();
        if ($user->cannot('deleteArea', $area)) {
            return redirect()->route(ADMIN_AREA_DISPLAY);
        }
        if (!Area1Display::Area1DisplayHavePost($areaID)) {
            $areaDisplay = Area1Display::find($areaID);
            if($areaDisplay){
                $areaDisplay->delete();
                Session::flash('alert-success', __('messages.area.delete_success'));
            }
        } else {
            Session::flash('alert-error', __('messages.area.delete_error'));
        }
        return redirect(route(ADMIN_AREA_DISPLAY));
    }

    public function edit(Request $request, $areaID)
    {
        $area = Area1Display::find($areaID);
        $area->fill($request->all());
        if ($area->save()) {
            Session::flash("alert-success", __("messages.area.edit_success"));
        } else {
            Session::flash("alert-error", __("messages.area.edit_error"));
        }
        return redirect(route(ADMIN_AREA_DISPLAY));
    }

    public function checkEdit(Request $request)
    {
        $areaName = $request->get('area_name');
        $areaId = $request->get('area_id');
        $areaName = preg_replace('/\s+/', ' ', $areaName);
        if ($this->checkDuplicateArea($areaName, $areaId)) {
            return response('duplicate', 200);
        }
        if ($areaName == '') {
            return response('error', 200);
        }
        if (strlen($areaName) > 100) {
            return response('fail', 200);
        }
        return response('success', 200);
    }

    public function checkDuplicateArea($name, $id)
    {
        $cityID = session()->get(SESSION_AREA_DISPLAY_SELECTED);
        $areas = Area1Display::getAllByCityIdPluck($cityID);
        if (array_search($name,$areas)) {
            if (array_search($name,$areas) != $id) {
                return true;
            }
        }
        return false;
    }

    public function addUp(Request $request)
    {
        $cityID = $request->session()->get(SESSION_AREA_DISPLAY_SELECTED);
        $areas = Area1Display::getAllByCityIdPluck($cityID);
        $area = new Area1Display();
        $area->fill($request->all());
        $area->name = preg_replace('/\s+/', ' ', $area->name);
        $area->order = 0;
        if($area->name == '') {
            Session::flash("alert-error", __("messages.area.add_required_name"));
        }
        elseif(strlen($area->name) > 100) {
            Session::flash("alert-error", __("messages.area.add_max_100_name"));
        }
        elseif(array_search($area->name, $areas) != false) {
            Session::flash("alert-error", __("messages.area.add_unique_name"));
        }
        elseif ($area->save()) {
            Area1Display::updateOrderByAreaAddUp($area->id, $cityID);
            Session::flash("alert-success", __("messages.area.addup_success"));
        }
        return redirect(route(ADMIN_AREA_DISPLAY));
    }

    public function addDown(Request $request)
    {
        $cityID = $request->session()->get(SESSION_AREA_DISPLAY_SELECTED);
        $areas = Area1Display::getAllByCityIdPluck($cityID);
        $area = new Area1Display();
        $area->fill($request->all());
        $area->name = preg_replace('/\s+/', ' ', $area->name);
        if($area->name == '') {
            Session::flash("alert-error", __("messages.area.add_required_name"));
        }
        elseif(strlen($area->name) > 100) {
            Session::flash("alert-error", __("messages.area.add_max_100_name"));
        }
        elseif(array_search($area->name, $areas) != false) {
            Session::flash("alert-error", __("messages.area.add_unique_name"));
        }
        elseif ($area->save()) {
            Area1Display::updateOrderByAreaAddDown($cityID, $area->id);
            Session::flash("alert-success", __("messages.area.addup_success"));
        }
        return redirect(route(ADMIN_AREA_DISPLAY));
    }



    public function order(Request $request)
    {
        $params = $request->all();
        if (Area1Display::orderArea1Display($params['ids'])) {
            return response()->json(true);
        }
        return response()->json(false);
    }

}
