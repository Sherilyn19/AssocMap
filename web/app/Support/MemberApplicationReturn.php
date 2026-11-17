<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

// Keep the list view when returning from an application action.
class MemberApplicationReturn
{
    public static function url(Request $request, string $route): string
    {
        // Only carry filters that the application list understands.
        $filters = $request->query();
        $allowed = ['q', 'status', 'sort', 'page'];
        if ($route === 'field-officer.member-applications.index') $allowed[] = 'association';
        $filters = array_intersect_key($filters, array_flip($allowed));
        $validator = Validator::make($filters, [
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
            'sort' => ['nullable', Rule::in(['az', 'za', 'newest', 'oldest'])],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'association' => ['nullable', 'uuid'],
        ]);

        return route($route, $validator->fails() ? [] : $filters);
    }
}
