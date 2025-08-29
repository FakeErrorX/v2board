<?php

namespace App\Http\Controllers\V1\Admin\Server;

use App\Http\Controllers\Controller;
use App\Models\ServerRoute;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    public function fetch(Request $request)
    {
        $routes = ServerRoute::get();
        // TODO: remove on 1.8.0
        foreach ($routes as $k => $route) {
            $array = json_decode($route->match, true);
            if (is_array($array)) $routes[$k]['match'] = $array;
        }
        // TODO: remove on 1.8.0
        return [
            'data' => $routes
        ];
    }

    public function save(Request $request)
    {
        $params = $request->validate([
            'remarks' => 'required',
            'match' => 'required|array',
            'action' => 'required|in:block,dns',
            'action_value' => 'nullable'
        ], [
            'remarks.required' => 'Remarks cannot be empty',
            'match.required' => 'Match value cannot be empty',
            'action.required' => 'Action type cannot be empty',
            'action.in' => 'Action type parameter is incorrect'
        ]);
        $params['match'] = array_filter($params['match']);
        // TODO: remove on 1.8.0
        $params['match'] = json_encode($params['match']);
        // TODO: remove on 1.8.0
        if ($request->input('id')) {
            try {
                $route = ServerRoute::find($request->input('id'));
                $route->update($params);
                return [
                    'data' => true
                ];
            } catch (\Exception $e) {
                abort(500, 'Save failed');
            }
        }
        if (!ServerRoute::create($params)) abort(500, 'Create failed');
        return [
            'data' => true
        ];
    }

    public function drop(Request $request)
    {
        $route = ServerRoute::find($request->input('id'));
        if (!$route) abort(500, 'Route does not exist');
        if (!$route->delete()) abort(500, 'Delete failed');
        return [
            'data' => true
        ];
    }
}
