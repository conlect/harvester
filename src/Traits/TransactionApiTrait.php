<?php

namespace Imamuseum\Harvester\Traits;
use Carbon\Carbon;
use Imamuseum\Harvester\Models\Object;

trait TransactionApiTrait
{
    public function apiObjectQuery($table, $request) {
        $params = config('harvester.api.params');

        // check that all params are valid.
        foreach ($request->all() as $key => $value) {
            if (! in_array($key, $params)) {
                return ['error' => $key . ' is not a valid request parameter.'];
            }
        }

        $take = $request->has('take') ? $request->input('take') : config('harvester.api.defualts.take');

        $query = Object::select();

        if ($request->has('action')) {
            $action = $request->input('action');
            $actions = config('harvester.api.actions');
            if (! in_array($action, $actions)) {
                return ['error' => 'action=' . $action . ' is not a valid request parameter.'];
            }
            $query->whereHas('transactions', function ($q) use ($table, $request, $action, $actions) {
                        $hours = $request->has('since') ? $request->input('since') : config('harvester.api.defualts.since');
                        $since = Carbon::now()->subhours($hours);
                        $q->where('created_at', '>=', $since);
                        if ($request->has('action')) {
                            // ?action=modified will find both created and update objects
                            if ($action == 'modified') {
                                $q->where('action', '=', 'created');
                                $q->orWhere('action', '=', 'updated');
                            }
                            // ?action=[created,updated,deleted]
                            if ($action != 'modified') {
                                $q->where('action', '=', $request->input('action'));
                            }
                        }
                        $q->where('table', '=', $table);
                    })->with(['transactions' => function ($q) use ($table) {
                $q->where('table', '=', $table);
            }]);
        }
        $query->with(['actors', 'assets', 'assets.type', 'terms', 'terms.type', 'texts', 'texts.type', 'locations', 'locations.type', 'dates', 'dates.type']);

        $request = $query->paginate($take);

        return $request;
    }
}