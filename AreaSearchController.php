<?php

namespace App\Http\Controllers\Backend;

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


/**
 * Class BarsController
 *
 * @category  Controller
 * @package   App\Http\Controllers
 * @author    Tran Quang Vu
 * @copyright 2018 kyujin
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 * @link      https://laravel.com Laravel(tm) Project
 */

class AreaSearchController extends ParentController
{
    public function index(Request $request)
    {
        if ($request->session()->exists(SESSION_AREA_SEARCH_SELECTED)) {
            $cityID = $request->session()->get(SESSION_AREA_SEARCH_SELECTED);
            $areas = Area2Search::getAllByCityIdPluck($cityID);
        } else {
            $areas = [];
        }
        $cities = City::all();
        return view('area_search.index', compact('cities', 'areas'));
    }

    public function search(Request $request)
    {
        $cityID = $request->get('city_id');
        $request->session()->put(SESSION_AREA_SEARCH_SELECTED, $cityID);
    }

    /**
     * @param  $area_id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function delete($areaID)
    {
        $area = Area2Search::class;
        $user = Auth::guard('admin')->user();
        if ($user->cannot('deleteArea', $area)) {
            return redirect()->route(ADMIN_AREA_SEARCH);
        }
        if (!Area2Search::Area2SearchHavePost($areaID)) {
            $areaSearch = Area2Search::find($areaID);
            if($areaSearch) {
                $areaSearch->delete();
                Session::flash('alert-success', __('messages.area.delete_success'));
            }
        } else {
            Session::flash('alert-error', __('messages.area.delete_error'));
        }
        return redirect(route(ADMIN_AREA_SEARCH));
    }

    /**
     * @param  Request $request
     * @param  $area_id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function edit(Request $request, $areaID)
    {

        $area = Area2Search::find($areaID);
        $area->fill($request->all());
        if ($area->save()) {
            Session::flash("alert-success", __("messages.area.edit_success"));
        } else {
            Session::flash("alert-error", __("messages.area.edit_error"));
        }
        return redirect(route(ADMIN_AREA_SEARCH));
    }

    /**
     * Check Edit
     *
     * @param Request $request request
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
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
        $cityID = session()->get(SESSION_AREA_SEARCH_SELECTED);
        $areas = Area2Search::getAllByCityIdPluck($cityID);
        if (array_search($name,$areas)) {
            if (array_search($name,$areas) != $id) {
                return true;
            }
        }
        return false;
    }

    public function addUp(Request $request)
    {
        $cityID = $request->session()->get(SESSION_AREA_SEARCH_SELECTED);
        $areas = Area2Search::getAllByCityIdPluck($cityID);
        $area = new Area2Search();
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
            Area2Search::updateOrderByAreaAddUp($area->id, $cityID);
            Session::flash("alert-success", __("messages.area.addup_success"));
        }
        return redirect(route(ADMIN_AREA_SEARCH));
    }

    public function addDown(Request $request)
    {
        $cityID = $request->session()->get(SESSION_AREA_SEARCH_SELECTED);
        $areas = Area2Search::getAllByCityIdPluck($cityID);
        $area = new Area2Search();
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
            Area2Search::updateOrderByAreaAddDown($cityID, $area->id);
            Session::flash("alert-success", __("messages.area.addup_success"));
        }
        return redirect(route(ADMIN_AREA_SEARCH));
    }

    public function order(Request $request)
    {
        $params = $request->all();
        if (Area2Search::orderArea2Search($params['ids'])) {
            return response()->json(true);
        } else {
            return response()->json(false);a
        }
    }

}
